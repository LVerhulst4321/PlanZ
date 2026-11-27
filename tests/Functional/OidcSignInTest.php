<?php

use PHPUnit\Framework\TestCase;

// What OIDC sign-in does to the PlanZ database and session: creating participants (or refusing
// to), refreshing their details from the claims, and setting roles only from the provider's
// entitlements, and only for the user's own member.
//
// Runs in-process against the test database. Uses its own badge IDs (OIDCT00004, OIDCT00005),
// which are deleted before and after every test.
final class OidcSignInTest extends TestCase
{
    private const SELF_BADGEID = 'OIDCT00004';
    private const OTHER_BADGEID = 'OIDCT00005';

    public static function setUpBeforeClass(): void
    {
        require_once PLANZ_ROOT . '/db_functions.php';
        require_once PLANZ_ROOT . '/api/admin/module_model.php';
    }

    protected function setUp(): void
    {
        global $linki;
        $linki = TestDb::connection();
        mysqli_report(MYSQLI_REPORT_OFF);
        // The 'Participant' atom is only granted in the 'Invitation' phase (1), which isn't
        // current in a fresh install (as in CI).
        mysqli_query($linki, "UPDATE Phases SET current = 1 WHERE phaseid = 1");
        $this->cleanUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        $_SESSION = [];
    }

    private function cleanUp(): void
    {
        $db = TestDb::connection();
        foreach (['UserHasPermissionRole', 'CongoDump', 'Participants'] as $table) {
            mysqli_query($db, "DELETE FROM `$table` WHERE badgeid IN ('" . self::SELF_BADGEID . "', '" . self::OTHER_BADGEID . "')");
        }
    }

    private function config(array $overrides = []): array
    {
        return array_merge([
            'event_id' => 1,
            'badgeid_prefix' => 'OIDCT',
            'badgeid_digits' => 5,
            'badgeid_source' => 'mid',
            'auto_provision' => false,
            'participant_role' => 'Program Participant',
            'entitlement_roles' => ['staff' => ['Staff'], 'admin' => ['Administrator', 'Staff']],
            'local_roles' => [],
            'self_claim' => 'conreg_member',
            'members_claim' => 'conreg_members',
            'entitlements_claim' => 'conreg_entitlements',
        ], $overrides);
    }

    private function members(array $config, array $entitlements = []): array
    {
        $userinfo = [
            'sub' => '2',
            'conreg_member' => ['mid' => 4, 'eid' => 1],
            'conreg_members' => [
                ['mid' => 4, 'eid' => 1, 'member_no' => 104, 'first_name' => 'Ada', 'last_name' => 'Lead',
                    'badge_name' => 'Ada the Lead', 'email' => 'lead@example.invalid', 'is_self' => true],
                ['mid' => 5, 'eid' => 1, 'member_no' => 105, 'first_name' => 'Kit', 'last_name' => 'Lead',
                    'badge_name' => 'Kit', 'email' => 'lead@example.invalid', 'is_self' => false],
            ],
            'conreg_entitlements' => $entitlements,
        ];
        $_SESSION['oidc_entitlements'] = oidc_entitlements($userinfo, $config);
        $members = oidc_event_members($userinfo, $config);
        $_SESSION['oidc_members'] = $members;
        return $members;
    }

    private function createExisting(string $badgeid, array $roleNames): void
    {
        $db = TestDb::connection();
        mysqli_query($db, "INSERT INTO Participants (badgeid, password, pubsname, data_retention) VALUES ('$badgeid', NULL, 'Old Pubs Name', 1)");
        mysqli_query($db, "INSERT INTO CongoDump (badgeid, firstname, lastname, badgename, email, phone)
            VALUES ('$badgeid', 'Old', 'Name', 'Old Badge', 'old@example.invalid', '555-0100')");
        foreach ($roleNames as $name) {
            mysqli_query($db, "INSERT INTO UserHasPermissionRole (badgeid, permroleid)
                SELECT '$badgeid', permroleid FROM PermissionRoles WHERE permrolename = '" . mysqli_real_escape_string($db, $name) . "'");
        }
    }

    private function roles(string $badgeid): array
    {
        $result = mysqli_query(TestDb::connection(), "SELECT PR.permrolename FROM UserHasPermissionRole U
            JOIN PermissionRoles PR USING (permroleid) WHERE U.badgeid = '$badgeid' ORDER BY PR.permrolename");
        return array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'permrolename');
    }

    private function row(string $sql): ?array
    {
        $row = mysqli_fetch_assoc(mysqli_query(TestDb::connection(), $sql));
        return $row ?: null;
    }

    public function testUnknownMemberIsRefusedWithoutAutoProvisioning(): void
    {
        $config = $this->config();
        $members = $this->members($config);
        try {
            oidc_activate_member($members[0], $config);
            $this->fail('Expected sign-in to be refused');
        } catch (OidcException $e) {
            $this->assertStringContainsString('not been set up', $e->userMessage);
        }
        $this->assertNull($this->row("SELECT * FROM Participants WHERE badgeid = '" . self::SELF_BADGEID . "'"));
        $this->assertArrayNotHasKey('badgeid', $_SESSION);
    }

    public function testOnlyMembersSetUpInPlanZAreOfferedWithoutAutoProvisioning(): void
    {
        $config = $this->config();
        $this->createExisting(self::OTHER_BADGEID, ['Program Participant']);
        $selectable = oidc_selectable_members($this->members($config), $config);
        $this->assertSame([self::OTHER_BADGEID], array_column($selectable, 'badgeid'));
    }

    public function testFirstSignInCreatesTheParticipantWithAutoProvisioning(): void
    {
        $config = $this->config(['auto_provision' => true]);
        oidc_activate_member($this->members($config)[0], $config);

        $participant = $this->row("SELECT * FROM Participants WHERE badgeid = '" . self::SELF_BADGEID . "'");
        $this->assertNotNull($participant);
        $this->assertNull($participant['password'], 'OIDC participants have no PlanZ password');
        $this->assertSame('Ada the Lead', $participant['pubsname']);
        $congo = $this->row("SELECT * FROM CongoDump WHERE badgeid = '" . self::SELF_BADGEID . "'");
        $this->assertSame(['Ada', 'Lead', 'Ada the Lead', 'lead@example.invalid', '104'],
            [$congo['firstname'], $congo['lastname'], $congo['badgename'], $congo['email'], (string)$congo['badgenumber']]);
        $this->assertSame(['Program Participant'], $this->roles(self::SELF_BADGEID));

        $this->assertSame('oidc', $_SESSION['auth_method']);
        $this->assertSame(self::SELF_BADGEID, $_SESSION['badgeid']);
        $this->assertSame(4, $_SESSION['oidc_active_mid']);
        $this->assertContains('Participant', $_SESSION['permission_set']);
        $this->assertNotContains('Staff', $_SESSION['permission_set']);
    }

    public function testLaterSignInRefreshesDetailsFromTheClaims(): void
    {
        $config = $this->config();
        $this->createExisting(self::SELF_BADGEID, ['Program Participant']);
        oidc_activate_member($this->members($config)[0], $config);

        $congo = $this->row("SELECT * FROM CongoDump WHERE badgeid = '" . self::SELF_BADGEID . "'");
        $this->assertSame(['Ada', 'Lead', 'Ada the Lead', 'lead@example.invalid'],
            [$congo['firstname'], $congo['lastname'], $congo['badgename'], $congo['email']]);
        $this->assertSame('555-0100', $congo['phone'], 'fields not in the claims are left alone');
        $this->assertSame('Old Pubs Name', $this->row("SELECT pubsname FROM Participants WHERE badgeid = '"
            . self::SELF_BADGEID . "'")['pubsname'], 'the name for publications is the participant\'s to edit');
        $this->assertSame('Old Pubs Name', $_SESSION['badgename']);
        $this->assertSame(1, $_SESSION['data_consent']);
    }

    public function testContactDetailsFromTheClaimsReplacePlanZValuesIncludingEmptyOnes(): void
    {
        $config = $this->config();
        $this->createExisting(self::SELF_BADGEID, ['Program Participant']);
        $member = $this->members($config)[0];
        $member['phone'] = ''; // removed in ConReg
        $member['address'] = ['street_address' => "1 Main Street\nApartment 2", 'locality' => 'Dublin',
            'region' => 'Co. Dublin', 'postal_code' => 'D01 X2Y3', 'country' => 'IE'];
        $member['member_type'] = 'A';
        oidc_activate_member($member, $config);

        $congo = $this->row("SELECT * FROM CongoDump WHERE badgeid = '" . self::SELF_BADGEID . "'");
        $this->assertSame(['', '1 Main Street', 'Apartment 2', 'Dublin', 'Co. Dublin', 'D01 X2Y3', 'Ireland', 'A'],
            [$congo['phone'], $congo['postaddress1'], $congo['postaddress2'], $congo['postcity'], $congo['poststate'],
                $congo['postzip'], $congo['postcountry'], $congo['regtype']]);
    }

    public function testOwnMemberGetsRolesFromEntitlements(): void
    {
        $config = $this->config();
        $this->createExisting(self::SELF_BADGEID, ['Program Participant']);
        oidc_activate_member($this->members($config, ['staff'])[0], $config);

        $this->assertSame(['Program Participant', 'Staff'], $this->roles(self::SELF_BADGEID));
        $this->assertContains('Staff', $_SESSION['permission_set']);
    }

    public function testLegacyRolesAreRemovedFromTheOwnMember(): void
    {
        $config = $this->config();
        $this->createExisting(self::SELF_BADGEID, ['Program Participant', 'Administrator', 'Senior Staff']);
        oidc_activate_member($this->members($config)[0], $config);

        $this->assertSame(['Program Participant'], $this->roles(self::SELF_BADGEID));
        $this->assertNotContains('Staff', $_SESSION['permission_set']);
        $this->assertNotContains('Administrator', $_SESSION['permission_set']);
    }

    public function testLocalRolesAreKept(): void
    {
        $config = $this->config(['local_roles' => ['GOH']]);
        $this->createExisting(self::SELF_BADGEID, ['Program Participant', 'GOH', 'Staff']);
        oidc_activate_member($this->members($config)[0], $config);

        $this->assertSame(['GOH', 'Program Participant'], $this->roles(self::SELF_BADGEID));
    }

    public function testActingForAnotherMemberNeverGivesStaffAccess(): void
    {
        // The lead has the "admin" entitlement, and the other member has a staff role in PlanZ.
        // Acting as the other member must give neither.
        $config = $this->config();
        $this->createExisting(self::OTHER_BADGEID, ['Staff']);
        oidc_activate_member($this->members($config, ['admin'])[1], $config);

        $this->assertSame(self::OTHER_BADGEID, $_SESSION['badgeid']);
        $this->assertContains('Participant', $_SESSION['permission_set']);
        $this->assertNotContains('Staff', $_SESSION['permission_set']);
        $this->assertNotContains('Administrator', $_SESSION['permission_set']);
        // Their own roles in PlanZ are theirs: only the participant role is added.
        $this->assertSame(['Program Participant', 'Staff'], $this->roles(self::OTHER_BADGEID));
    }

    public function testSwitchingBackToTheOwnMemberRestoresStaffAccess(): void
    {
        $config = $this->config(['auto_provision' => true]);
        $members = $this->members($config, ['staff']);
        oidc_activate_member($members[0], $config);
        $this->assertContains('Staff', $_SESSION['permission_set']);
        oidc_activate_member($members[1], $config);
        $this->assertNotContains('Staff', $_SESSION['permission_set']);
        oidc_activate_member($members[0], $config);
        $this->assertContains('Staff', $_SESSION['permission_set']);
        $this->assertSame(self::SELF_BADGEID, $_SESSION['badgeid']);
    }

    public function testAMissingParticipantRoleStopsSignInWithoutChanges(): void
    {
        $config = $this->config(['participant_role' => 'No Such Role']);
        $this->createExisting(self::SELF_BADGEID, ['Program Participant', 'GOH']);
        try {
            oidc_activate_member($this->members($config)[0], $config);
            $this->fail('Expected sign-in to fail');
        } catch (OidcException $e) {
            $this->assertStringContainsString('No Such Role', $e->getMessage());
        }
        $this->assertSame(['GOH', 'Program Participant'], $this->roles(self::SELF_BADGEID), 'roles unchanged');
        $this->assertSame('Old', $this->row("SELECT firstname FROM CongoDump WHERE badgeid = '" . self::SELF_BADGEID . "'")['firstname'],
            'details unchanged: the update was rolled back');
        $this->assertArrayNotHasKey('badgeid', $_SESSION);
    }

    public function testOidcSessionStaysValidWhileTheParticipantExists(): void
    {
        $this->createExisting(self::SELF_BADGEID, ['Program Participant']);
        $_SESSION = ['badgeid' => self::SELF_BADGEID, 'auth_method' => 'oidc'];
        if (!defined('LOGIN_MODE') || LOGIN_MODE === 'password') {
            $this->markTestSkipped('LOGIN_MODE in db_name.php is "password", so OIDC sessions are always invalid');
        }
        $this->assertTrue(isOidcSessionValid());
        $this->cleanUp();
        $this->assertFalse(isOidcSessionValid());
        $this->assertArrayNotHasKey('badgeid', $_SESSION);
    }
}
