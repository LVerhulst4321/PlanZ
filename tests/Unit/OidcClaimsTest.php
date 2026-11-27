<?php

use PHPUnit\Framework\TestCase;

// The rules that turn the provider's member claims into PlanZ participants and roles. These are
// the security-relevant parts of OIDC sign-in, so they are tested on their own, without a provider
// or a database.
final class OidcClaimsTest extends TestCase
{
    private function config(array $overrides = []): array
    {
        return array_merge([
            'event_id' => 1,
            'badgeid_prefix' => 'OC',
            'badgeid_digits' => 4,
            'badgeid_source' => 'mid',
            'participant_role' => 'Program Participant',
            'entitlement_roles' => ['staff' => ['Staff'], 'admin' => ['Administrator', 'Staff']],
            'self_claim' => 'conreg_member',
            'members_claim' => 'conreg_members',
            'entitlements_claim' => 'conreg_entitlements',
        ], $overrides);
    }

    private function member(int $mid, int $eid = 1, array $extra = []): array
    {
        return array_merge([
            'mid' => $mid, 'eid' => $eid, 'event_name' => "Event $eid", 'member_no' => $mid + 100,
            'first_name' => "First$mid", 'last_name' => "Last$mid", 'badge_name' => "Badge$mid",
            'email' => 'lead@example.com', 'member_type' => 'A', 'badge_type' => 'A', 'days' => '',
            'is_checked_in' => false, 'lead_mid' => 4, 'is_self' => false,
        ], $extra);
    }

    // ConReg's layout: a group lead (mid 4) who registered one other member (mid 5).
    private function groupLeadUserinfo(): array
    {
        return [
            'sub' => '2',
            'email' => 'lead@example.com',
            'conreg_member' => $this->member(4),
            'conreg_members' => [$this->member(4, 1, ['is_self' => true]), $this->member(5)],
            'conreg_entitlements' => ['staff'],
        ];
    }

    public function testBadgeIdFromMidIsPrefixedAndZeroPadded(): void
    {
        $this->assertSame('OC0004', oidc_badgeid_for_member(['mid' => 4], $this->config()));
    }

    public function testBadgeIdFromMemberNumber(): void
    {
        $config = $this->config(['badgeid_source' => 'member_no', 'badgeid_prefix' => '', 'badgeid_digits' => 5]);
        $this->assertSame('00104', oidc_badgeid_for_member(['mid' => 4, 'member_no' => 104], $config));
    }

    public function testNoBadgeIdWithoutAMemberNumberYet(): void
    {
        $config = $this->config(['badgeid_source' => 'member_no']);
        $this->assertNull(oidc_badgeid_for_member(['mid' => 4, 'member_no' => null], $config));
    }

    public function testNoBadgeIdLongerThanTheColumn(): void
    {
        $config = $this->config(['badgeid_prefix' => 'AVERYLONGPREFIX']);
        $this->assertNull(oidc_badgeid_for_member(['mid' => 4], $config));
    }

    public function testNumbersAreNotPaddedWhenDigitsIsZero(): void
    {
        $this->assertSame('OC4', oidc_badgeid_for_member(['mid' => 4], $this->config(['badgeid_digits' => 0])));
    }

    public function testMembersAreFilteredToTheConfiguredEvent(): void
    {
        $userinfo = $this->groupLeadUserinfo();
        $userinfo['conreg_members'][] = $this->member(9, 2);
        $members = oidc_event_members($userinfo, $this->config());
        $this->assertSame([4, 5], array_column($members, 'mid'));
        $this->assertSame(['OC0004', 'OC0005'], array_column($members, 'badgeid'));
    }

    public function testOnlyTheOwnMemberIsMarkedSelf(): void
    {
        $members = oidc_event_members($this->groupLeadUserinfo(), $this->config());
        $this->assertSame([true, false], array_column($members, 'is_self'));
    }

    public function testAnEntryClaimingIsSelfIsNotTrustedWhenTheOwnMemberSaysOtherwise(): void
    {
        // conreg_member is the user's own member. A conreg_members entry with is_self true for a
        // different member must not be treated as the user's own.
        $userinfo = $this->groupLeadUserinfo();
        $userinfo['conreg_members'][1]['is_self'] = true;
        $members = oidc_event_members($userinfo, $this->config());
        $this->assertSame([true, false], array_column($members, 'is_self'));
    }

    public function testIsSelfEntryIsUsedWhenThereIsNoOwnMemberClaim(): void
    {
        $userinfo = $this->groupLeadUserinfo();
        unset($userinfo['conreg_member']);
        $this->assertSame(4, oidc_self_mid($userinfo, $this->config()));
    }

    public function testParentWhoOnlyPaidForChildrenHasNoOwnMember(): void
    {
        $userinfo = [
            'sub' => '3',
            'conreg_member' => null,
            'conreg_members' => [$this->member(7), $this->member(8)],
            'conreg_entitlements' => ['admin'],
        ];
        $members = oidc_event_members($userinfo, $this->config());
        $this->assertSame([false, false], array_column($members, 'is_self'));
        foreach ($members as $member) {
            $this->assertSame(['Program Participant'],
                oidc_role_names_for_member($member, oidc_entitlements($userinfo, $this->config()), $this->config()));
        }
    }

    public function testDuplicateMembersAreListedOnce(): void
    {
        $userinfo = $this->groupLeadUserinfo();
        $userinfo['conreg_members'][] = $this->member(5);
        $this->assertCount(2, oidc_event_members($userinfo, $this->config()));
    }

    public function testMembersWithoutABadgeIdAreLeftOut(): void
    {
        $userinfo = $this->groupLeadUserinfo();
        $userinfo['conreg_members'][1]['member_no'] = null;
        $members = oidc_event_members($userinfo, $this->config(['badgeid_source' => 'member_no']));
        $this->assertSame([4], array_column($members, 'mid'));
    }

    public function testMissingOrMalformedMemberClaimsGiveNoMembers(): void
    {
        $this->assertSame([], oidc_event_members(['sub' => '1'], $this->config()));
        $this->assertSame([], oidc_event_members(['sub' => '1', 'conreg_members' => 'nonsense'], $this->config()));
        $this->assertSame([], oidc_event_members(['sub' => '1', 'conreg_members' => [['mid' => 4]]], $this->config()));
    }

    public function testOwnMemberGetsParticipantRolePlusMappedEntitlements(): void
    {
        $self = ['mid' => 4, 'is_self' => true];
        $roles = oidc_role_names_for_member($self, ['admin', 'staff'], $this->config());
        $this->assertSame(['Program Participant', 'Administrator', 'Staff'], $roles);
    }

    public function testAnotherMemberNeverGetsEntitlementRoles(): void
    {
        $other = ['mid' => 5, 'is_self' => false];
        $this->assertSame(['Program Participant'], oidc_role_names_for_member($other, ['admin'], $this->config()));
    }

    public function testUnknownEntitlementsAreIgnored(): void
    {
        $self = ['mid' => 4, 'is_self' => true];
        $this->assertSame(['Program Participant'], oidc_role_names_for_member($self, ['tea_maker'], $this->config()));
    }

    public function testNoEntitlementsMeansParticipantOnly(): void
    {
        $self = ['mid' => 4, 'is_self' => true];
        $this->assertSame(['Program Participant'], oidc_role_names_for_member($self, [], $this->config()));
        $this->assertSame([], oidc_entitlements(['sub' => '1'], $this->config()));
        $this->assertSame([], oidc_entitlements(['sub' => '1', 'conreg_entitlements' => 'staff'], $this->config()));
    }

    public function testClaimNamesAreConfigurable(): void
    {
        $config = $this->config(['self_claim' => 'me', 'members_claim' => 'people', 'entitlements_claim' => 'roles']);
        $userinfo = ['sub' => '2', 'me' => ['mid' => 4], 'people' => [$this->member(4), $this->member(5)], 'roles' => ['staff']];
        $members = oidc_event_members($userinfo, $config);
        $this->assertSame([true, false], array_column($members, 'is_self'));
        $this->assertSame(['staff'], oidc_entitlements($userinfo, $config));
    }

    public function testEventIdMayBeConfiguredAsAString(): void
    {
        $members = oidc_event_members($this->groupLeadUserinfo(), $this->config(['event_id' => '1']));
        $this->assertCount(2, $members);
    }

    public function testContactFieldsAreTrimmedAndCutToColumnSizes(): void
    {
        $fields = oidc_congodump_fields([
            'first_name' => '  ' . str_repeat('A', 40) . ' ',
            'last_name' => 'Ó Briain',
            'badge_name' => '',
            'email' => 'x@example.com',
        ]);
        $this->assertSame(str_repeat('A', 30), $fields['firstname']);
        $this->assertSame('Ó Briain', $fields['lastname']);
        $this->assertSame(str_repeat('A', 30) . ' Ó Briain', $fields['badgename'], 'falls back to the full name');
        $this->assertSame('x@example.com', $fields['email']);
    }

    public function testMissingContactFieldsAreNull(): void
    {
        $fields = oidc_congodump_fields(['first_name' => 'Only']);
        $this->assertNull($fields['lastname']);
        $this->assertNull($fields['email']);
        $this->assertSame('Only', $fields['badgename']);
    }

    // A member object in ConReg's layout, with the phone and address scopes.
    private function conregMember(array $overrides = []): array
    {
        return array_replace_recursive([
            'mid' => 4, 'eid' => 1, 'member_no' => 4,
            'first_name' => 'Aoife', 'last_name' => 'Murphy', 'badge_name' => 'Aoife', 'email' => 'a@example.com',
            'phone' => '+353 1 234 5678',
            'address' => [
                'street_address' => "1 Main Street\nApartment 2",
                'locality' => 'Dublin', 'region' => 'Co. Dublin', 'postal_code' => 'D01 X2Y3', 'country' => 'IE',
            ],
            'member_type' => 'A', 'badge_type' => 'A', 'days' => '', 'is_checked_in' => false,
            'planz_badge_id' => 'A0004',
        ], $overrides);
    }

    public function testAllContactInformationFieldsAreMappedFromConRegClaims(): void
    {
        $fields = oidc_congodump_fields($this->conregMember(), ['member_fields' => oidc_member_fields([])]);
        $this->assertSame([
            'firstname' => 'Aoife', 'lastname' => 'Murphy', 'badgename' => 'Aoife', 'phone' => '+353 1 234 5678',
            'email' => 'a@example.com', 'postaddress1' => '1 Main Street', 'postaddress2' => 'Apartment 2',
            'postcity' => 'Dublin', 'poststate' => 'Co. Dublin', 'postzip' => 'D01 X2Y3', 'postcountry' => 'Ireland',
            'regtype' => 'A',
        ], $fields);
    }

    public function testStreetAddressIsSplitIntoTwoLines(): void
    {
        $split = fn(string $street) => array_intersect_key(
            oidc_congodump_fields($this->conregMember(['address' => ['street_address' => $street]])),
            ['postaddress1' => 1, 'postaddress2' => 1]);
        $this->assertSame(['postaddress1' => '1 Main St', 'postaddress2' => ''], $split('1 Main St'),
            'a one-line address clears line 2');
        $this->assertSame(['postaddress1' => '1 Main St', 'postaddress2' => 'Rathmines, Dublin 6'],
            $split("  1 Main St \r\n\nRathmines\nDublin 6\n"), 'extra lines go on line 2; blank lines are dropped');
        $this->assertSame(['postaddress1' => '', 'postaddress2' => ''], $split(''));
    }

    public function testClaimsReplacePlanZValuesEvenWhenEmpty(): void
    {
        $fields = oidc_congodump_fields($this->conregMember([
            'phone' => '   ', 'address' => ['region' => '', 'country' => '', 'postal_code' => null],
        ]));
        $this->assertSame('', $fields['phone']);
        $this->assertSame('', $fields['poststate']);
        $this->assertSame('', $fields['postcountry'], 'members can prefer not to give a country');
        $this->assertSame('', $fields['postzip'], 'null counts as empty');
    }

    public function testFieldsTheClaimLeavesOutAreNotTouched(): void
    {
        // As when the phone and address scopes weren't granted.
        $member = $this->conregMember();
        unset($member['phone'], $member['address']);
        $fields = oidc_congodump_fields($member);
        foreach (['phone', 'postaddress1', 'postaddress2', 'postcity', 'poststate', 'postzip', 'postcountry'] as $column) {
            $this->assertNull($fields[$column], $column);
        }
        $this->assertSame('Aoife', $fields['firstname']);
    }

    public function testNonTextValuesAreNotUsed(): void
    {
        $fields = oidc_congodump_fields($this->conregMember(['phone' => ['not', 'text'], 'address' => 'not an object']));
        $this->assertNull($fields['phone']);
        $this->assertNull($fields['postcity']);
    }

    public function testLineBreaksInSingleLineFieldsBecomeSpaces(): void
    {
        $fields = oidc_congodump_fields($this->conregMember(['badge_name' => "Two\nLines"]));
        $this->assertSame('Two Lines', $fields['badgename']);
    }

    public function testMemberFieldsCanBeRemapped(): void
    {
        $config = ['member_fields' => oidc_member_fields([
            'postaddress1' => 'street',
            'phone' => '', // not taken from the provider
            'nonsense' => 'ignored',
        ])];
        $this->assertArrayNotHasKey('phone', $config['member_fields']);
        $this->assertArrayNotHasKey('nonsense', $config['member_fields']);
        $fields = oidc_congodump_fields($this->conregMember(['street' => '2 High St', 'address' => ['country' => 'gb']]), $config);
        $this->assertNull($fields['phone']);
        $this->assertSame('2 High St', $fields['postaddress1']);
        $this->assertSame('United Kingdom', $fields['postcountry']);
    }

    public function testCountryCodesBecomeNamesThatFitTheColumn(): void
    {
        $this->assertSame('Ireland', oidc_country_name('IE', []));
        $this->assertSame('United States', oidc_country_name('us', []));
        $this->assertSame('S. Georgia & S. Sandwich', oidc_country_name('GS', []));
        $this->assertSame('Ireland', oidc_country_name('Ireland', []), 'names are kept');
        $this->assertSame('ZZ', oidc_country_name('ZZ', []), 'unknown codes are kept');
        $this->assertSame('Republic of Ireland', oidc_country_name('IE', ['country_name_overrides' => ['ie' => 'Republic of Ireland']]));
        foreach (range('A', 'Z') as $a) {
            foreach (range('A', 'Z') as $b) {
                $this->assertLessThanOrEqual(25, mb_strlen(oidc_country_name("$a$b", [])), "$a$b");
            }
        }
    }

    public function testCountryConversionCanBeSwitchedOff(): void
    {
        $fields = oidc_congodump_fields($this->conregMember(), ['member_fields' => oidc_member_fields([]), 'country_names' => false]);
        $this->assertSame('IE', $fields['postcountry']);
    }

    public function testBadgeIdComesFromPlanzBadgeIdClaimWhenPresent(): void
    {
        $config = $this->config(['badgeid_claim' => 'planz_badge_id']);
        $this->assertSame('A0004', oidc_badgeid_for_member($this->conregMember(), $config));
        // Users conreg_planz linked by email keep their own badge IDs, whatever the formula says.
        $this->assertSame('17', oidc_badgeid_for_member($this->conregMember(['planz_badge_id' => '17']), $config));
        $this->assertSame('17', oidc_badgeid_for_member($this->conregMember(['planz_badge_id' => 17]), $config));
    }

    public function testNullPlanzBadgeIdMeansNoBadgeIdYet(): void
    {
        $config = $this->config(['badgeid_claim' => 'planz_badge_id']);
        $this->assertNull(oidc_badgeid_for_member($this->conregMember(['planz_badge_id' => null]), $config),
            'PlanZ must not make one up');
        $userinfo = ['conreg_members' => [$this->conregMember(['planz_badge_id' => null])]];
        $this->assertSame([], oidc_event_members($userinfo, $config));
    }

    public function testUnsafePlanzBadgeIdsAreRefused(): void
    {
        $config = $this->config(['badgeid_claim' => 'planz_badge_id']);
        foreach (["A0004' OR '1'='1", 'has space', str_repeat('9', 16), '', true, ['A0004']] as $bad) {
            $this->assertNull(oidc_badgeid_for_member($this->conregMember(['planz_badge_id' => $bad]), $config), var_export($bad, true));
        }
    }

    public function testWithoutTheClaimTheBadgeIdIsBuiltFromSettings(): void
    {
        $member = $this->conregMember();
        unset($member['planz_badge_id']);
        $this->assertSame('OC0004', oidc_badgeid_for_member($member, $this->config(['badgeid_claim' => 'planz_badge_id'])));
        $this->assertSame('OC0004', oidc_badgeid_for_member($this->conregMember(), $this->config(['badgeid_claim' => ''])),
            'an empty OIDC_BADGEID_CLAIM ignores the claim');
    }

    public function testMemberLabel(): void
    {
        $this->assertSame('Badge4 (First4 Last4)', oidc_member_label($this->member(4)));
        $this->assertSame('First4 Last4', oidc_member_label($this->member(4, 1, ['badge_name' => 'First4 Last4'])));
        $this->assertSame('First4 Last4', oidc_member_label($this->member(4, 1, ['badge_name' => ''])));
    }
}
