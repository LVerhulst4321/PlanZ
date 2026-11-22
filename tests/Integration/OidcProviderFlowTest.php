<?php

use PHPUnit\Framework\TestCase;

// The whole provider conversation, against the mock provider in tests/MockOidcProvider: the
// authorization redirect, the code exchange with PKCE and the client secret, ID token
// verification against the JWKS, and userinfo. No PlanZ database is used.
final class OidcProviderFlowTest extends TestCase
{
    private static MockOidcProvider $provider;

    public static function setUpBeforeClass(): void
    {
        self::$provider = new MockOidcProvider();
    }

    public static function tearDownAfterClass(): void
    {
        self::$provider->stop();
    }

    protected function setUp(): void
    {
        self::$provider->reset();
        self::$provider->setUserinfo([
            'sub' => '2',
            'email' => 'lead@example.com',
            'email_verified' => true,
            'name' => 'First4 Last4',
            'conreg_member' => ['mid' => 4, 'eid' => 1, 'member_no' => 104, 'first_name' => 'First4', 'last_name' => 'Last4',
                'badge_name' => 'Badge4', 'email' => 'lead@example.com'],
            'conreg_members' => [
                ['mid' => 4, 'eid' => 1, 'member_no' => 104, 'first_name' => 'First4', 'last_name' => 'Last4',
                    'badge_name' => 'Badge4', 'email' => 'lead@example.com', 'is_self' => true, 'lead_mid' => 4],
                ['mid' => 5, 'eid' => 1, 'member_no' => 105, 'first_name' => 'First5', 'last_name' => 'Last5',
                    'badge_name' => 'Badge5', 'email' => 'lead@example.com', 'is_self' => false, 'lead_mid' => 4],
            ],
            'conreg_entitlements' => ['staff'],
        ]);
    }

    // Runs the browser half of the flow and returns [config, pending, callback query].
    private function signIn(array $configOverrides = [], bool $silent = false): array
    {
        $config = self::$provider->planzConfig($configOverrides);
        $discovery = oidc_fetch_discovery($config);
        $pending = oidc_new_pending($silent);
        $query = self::$provider->authorize(oidc_authorize_url($config, $discovery, $pending));
        return [$config, $pending, $query];
    }

    public function testSignInReturnsVerifiedClaimsAndMembers(): void
    {
        [$config, $pending, $query] = $this->signIn();
        $result = oidc_complete_authorization($config, $pending, $query);

        $this->assertSame('2', $result['id_token']['sub']);
        $members = oidc_event_members($result['userinfo'], $config);
        $this->assertSame(['T00004', 'T00005'], array_column($members, 'badgeid'));
        $this->assertSame([true, false], array_column($members, 'is_self'));
        $this->assertSame(['staff'], oidc_entitlements($result['userinfo'], $config));
    }

    public function testSignInWorksWithConRegStyleKeysWithoutKid(): void
    {
        self::$provider->set(['no_kid' => true]);
        [$config, $pending, $query] = $this->signIn();
        $this->assertSame('2', oidc_complete_authorization($config, $pending, $query)['id_token']['sub']);
    }

    public function testKeyWithoutKidStillRefusesTokensSignedWithAnotherKey(): void
    {
        self::$provider->set(['no_kid' => true, 'sign_with_key' => 'other.pem']);
        [$config, $pending, $query] = $this->signIn();
        $this->expectException(OidcException::class);
        oidc_complete_authorization($config, $pending, $query);
    }

    public function testCodeCanOnlyBeUsedOnce(): void
    {
        [$config, $pending, $query] = $this->signIn();
        oidc_complete_authorization($config, $pending, $query);
        $this->expectException(OidcException::class);
        oidc_complete_authorization($config, $pending, $query);
    }

    public function testWrongStateIsRefusedBeforeTheCodeIsUsed(): void
    {
        [$config, $pending, $query] = $this->signIn();
        $query['state'] = 'forged';
        try {
            oidc_complete_authorization($config, $pending, $query);
            $this->fail('Forged state was accepted');
        } catch (OidcException $e) {
            $this->assertStringContainsString('state', $e->getMessage());
        }
        // The code wasn't spent, so the real callback still works.
        $query['state'] = $pending['state'];
        $this->assertSame('2', oidc_complete_authorization($config, $pending, $query)['id_token']['sub']);
    }

    public function testCodeFromAnotherSignInAttemptIsRefused(): void
    {
        // An attacker's code, injected into the victim's callback with the victim's state: the
        // PKCE verifier (and nonce) from the victim's session don't match it.
        [$config, , $attackerQuery] = $this->signIn();
        [, $victimPending] = $this->signIn();
        $attackerQuery['state'] = $victimPending['state'];
        $this->expectException(OidcException::class);
        oidc_complete_authorization($config, $victimPending, $attackerQuery);
    }

    public function testWrongClientSecretIsRefused(): void
    {
        [$config, $pending, $query] = $this->signIn();
        $config['client_secret'] = 'wrong';
        $this->expectException(OidcException::class);
        oidc_complete_authorization($config, $pending, $query);
    }

    public function testIdTokenForAnotherClientIsRefused(): void
    {
        self::$provider->set(['id_token_overrides' => ['aud' => 'some-other-app']]);
        [$config, $pending, $query] = $this->signIn();
        $this->expectException(OidcException::class);
        oidc_complete_authorization($config, $pending, $query);
    }

    public function testIdTokenSignedWithAKeyNotInTheJwksIsRefused(): void
    {
        self::$provider->set(['sign_with_key' => 'other.pem']);
        [$config, $pending, $query] = $this->signIn();
        $this->expectException(OidcException::class);
        oidc_complete_authorization($config, $pending, $query);
    }

    public function testHs256IdTokenIsRefused(): void
    {
        self::$provider->set(['id_token_alg' => 'HS256']);
        [$config, $pending, $query] = $this->signIn();
        $this->expectException(OidcException::class);
        oidc_complete_authorization($config, $pending, $query);
    }

    public function testUserinfoForAnotherSubjectIsRefused(): void
    {
        self::$provider->set(['id_token_overrides' => ['sub' => '99']]);
        [$config, $pending, $query] = $this->signIn();
        $this->expectException(OidcException::class);
        oidc_complete_authorization($config, $pending, $query);
    }

    public function testIssuerMismatchIsRefusedAtDiscovery(): void
    {
        $config = self::$provider->planzConfig(['issuer' => rtrim(self::$provider->issuer(), '/')]);
        $this->expectException(OidcException::class);
        oidc_fetch_discovery($config);
    }

    public function testAccessDeniedComesBackAsAnError(): void
    {
        self::$provider->set(['authorize_error' => 'access_denied']);
        [, $pending, $query] = $this->signIn();
        $this->assertSame('access_denied', $query['error']);
        $this->assertSame($pending['state'], $query['state']);
    }

    public function testSilentSignInWithoutAProviderSessionComesBackLoginRequired(): void
    {
        self::$provider->set(['login_required_if_silent' => true]);
        [, $pending, $query] = $this->signIn([], true);
        $this->assertSame('login_required', $query['error']);
        $this->assertSame($pending['state'], $query['state']);
    }

    public function testUnreachableProviderGivesAFriendlyMessage(): void
    {
        $config = self::$provider->planzConfig(['issuer' => 'http://127.0.0.1:1/', 'http_timeout' => 2]);
        try {
            oidc_fetch_discovery($config);
            $this->fail('Expected an exception');
        } catch (OidcException $e) {
            $this->assertStringContainsString('Could not contact Mock ConReg', $e->userMessage);
        }
    }
}
