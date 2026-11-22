<?php

use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

// PKCE, the authorization URL, state checks, discovery checks, and ID token verification.
final class OidcProtocolTest extends TestCase
{
    private static string $privateKey;
    private static array $jwks;

    public static function setUpBeforeClass(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        self::$privateKey = $pem;
        $details = openssl_pkey_get_details($key);
        self::$jwks = ['keys' => [[
            'kty' => 'RSA', 'use' => 'sig', 'kid' => 'k1',
            'n' => oidc_base64url($details['rsa']['n']), 'e' => oidc_base64url($details['rsa']['e']),
        ]]];
    }

    private function config(): array
    {
        return ['issuer' => 'https://reg.example.org/', 'client_id' => 'planz', 'client_secret' => 's3cret',
            'redirect_uri' => 'https://planz.example.org/oidc_callback.php', 'scopes' => 'openid profile'];
    }

    private function discovery(): array
    {
        return ['issuer' => 'https://reg.example.org/', 'authorization_endpoint' => 'https://reg.example.org/oauth/authorize',
            'token_endpoint' => 'https://reg.example.org/oauth/token', 'jwks_uri' => 'https://reg.example.org/oauth/jwks',
            'userinfo_endpoint' => 'https://reg.example.org/oauth/userinfo'];
    }

    private function idToken(array $overrides = [], ?string $key = null, string $alg = 'RS256'): string
    {
        $claims = array_merge(['iss' => 'https://reg.example.org/', 'aud' => 'planz', 'sub' => '2',
            'iat' => time(), 'exp' => time() + 300, 'nonce' => 'the-nonce'], $overrides);
        $claims = array_filter($claims, fn($v) => $v !== null);
        return JWT::encode($claims, $key ?? self::$privateKey, $alg, 'k1');
    }

    public function testPkceChallengeMatchesRfc7636Example(): void
    {
        // RFC 7636, appendix B.
        $this->assertSame('E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            oidc_pkce_challenge('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk'));
    }

    public function testPendingSignInValuesAreRandomAndLongEnough(): void
    {
        $a = oidc_new_pending(false);
        $b = oidc_new_pending(false);
        $this->assertNotSame($a['state'], $b['state']);
        $this->assertNotSame($a['nonce'], $b['nonce']);
        $this->assertGreaterThanOrEqual(43, strlen($a['code_verifier']), 'RFC 7636 minimum verifier length');
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $a['code_verifier']);
    }

    public function testAuthorizeUrlCarriesEverythingTheProviderNeeds(): void
    {
        $pending = oidc_new_pending(false);
        $url = oidc_authorize_url($this->config(), $this->discovery(), $pending);
        $this->assertStringStartsWith('https://reg.example.org/oauth/authorize?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame('code', $q['response_type']);
        $this->assertSame('planz', $q['client_id']);
        $this->assertSame('https://planz.example.org/oidc_callback.php', $q['redirect_uri']);
        $this->assertSame('openid profile', $q['scope']);
        $this->assertSame($pending['state'], $q['state']);
        $this->assertSame($pending['nonce'], $q['nonce']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertSame(oidc_pkce_challenge($pending['code_verifier']), $q['code_challenge']);
        $this->assertArrayNotHasKey('prompt', $q);
        $this->assertStringNotContainsString('s3cret', $url, 'client secret must never go to the browser');
        $this->assertStringNotContainsString($pending['code_verifier'], $url, 'verifier must never go to the browser');
    }

    public function testSilentAuthorizeUrlAsksForNoPrompt(): void
    {
        parse_str(parse_url(oidc_authorize_url($this->config(), $this->discovery(), oidc_new_pending(true)), PHP_URL_QUERY), $q);
        $this->assertSame('none', $q['prompt']);
    }

    public function testStateMustMatch(): void
    {
        $pending = oidc_new_pending(false);
        $this->assertSame($pending, oidc_check_state($pending, $pending['state']));
        $this->expectException(OidcException::class);
        oidc_check_state($pending, 'someone-elses-state');
    }

    public function testStateIsRequired(): void
    {
        $this->expectException(OidcException::class);
        oidc_check_state(oidc_new_pending(false), null);
    }

    public function testCallbackWithoutAPendingSignInIsRefused(): void
    {
        $this->expectException(OidcException::class);
        oidc_check_state(null, 'anything');
    }

    public function testOldPendingSignInIsRefused(): void
    {
        $pending = oidc_new_pending(false, time() - OIDC_PENDING_LIFETIME - 1);
        $this->expectException(OidcException::class);
        oidc_check_state($pending, $pending['state']);
    }

    public function testDiscoveryIssuerMustMatchConfiguredIssuerExactly(): void
    {
        oidc_check_discovery($this->config(), $this->discovery());
        $discovery = $this->discovery();
        $discovery['issuer'] = 'https://reg.example.org'; // no trailing slash
        $this->expectException(OidcException::class);
        oidc_check_discovery($this->config(), $discovery);
    }

    public function testDiscoveryMustHaveTheEndpoints(): void
    {
        $discovery = $this->discovery();
        unset($discovery['jwks_uri']);
        $this->expectException(OidcException::class);
        oidc_check_discovery($this->config(), $discovery);
    }

    public function testValidIdTokenIsAccepted(): void
    {
        $claims = oidc_verify_id_token($this->config(), self::$jwks, $this->idToken(), 'the-nonce');
        $this->assertSame('2', $claims['sub']);
    }

    private function jwksWithoutKid(): array
    {
        $jwks = self::$jwks;
        unset($jwks['keys'][0]['kid']);
        return $jwks;
    }

    private function idTokenWithoutKid(array $overrides = [], ?string $key = null): string
    {
        $claims = array_merge(['iss' => 'https://reg.example.org/', 'aud' => 'planz', 'sub' => '2',
            'iat' => time(), 'exp' => time() + 300, 'nonce' => 'the-nonce'], $overrides);
        return JWT::encode($claims, $key ?? self::$privateKey, 'RS256');
    }

    public function testSingleKeyWithoutKidIsUsedForTokensWithoutKid(): void
    {
        // ConReg publishes one key with no "kid".
        $claims = oidc_verify_id_token($this->config(), $this->jwksWithoutKid(), $this->idTokenWithoutKid(), 'the-nonce');
        $this->assertSame('2', $claims['sub']);
    }

    public function testSingleKeyWithoutKidStillChecksTheSignature(): void
    {
        $other = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($other, $pem);
        $this->expectException(OidcException::class);
        oidc_verify_id_token($this->config(), $this->jwksWithoutKid(), $this->idTokenWithoutKid([], $pem), 'the-nonce');
    }

    public function testTokenWithAnUnknownKidIsRefusedWhenKeysHaveKids(): void
    {
        $token = JWT::encode(['iss' => 'https://reg.example.org/', 'aud' => 'planz', 'sub' => '2',
            'exp' => time() + 300, 'nonce' => 'the-nonce'], self::$privateKey, 'RS256', 'unknown-kid');
        $this->expectException(OidcException::class);
        oidc_verify_id_token($this->config(), self::$jwks, $token, 'the-nonce');
    }

    public function testIdTokenWithSeveralAudiencesNeedsAzp(): void
    {
        $token = $this->idToken(['aud' => ['planz', 'other'], 'azp' => 'planz']);
        $this->assertSame('2', oidc_verify_id_token($this->config(), self::$jwks, $token, 'the-nonce')['sub']);
        $this->expectException(OidcException::class);
        oidc_verify_id_token($this->config(), self::$jwks, $this->idToken(['aud' => ['planz', 'other']]), 'the-nonce');
    }

    public static function badTokens(): array
    {
        return [
            'wrong issuer' => [['iss' => 'https://evil.example.org/']],
            'issuer without trailing slash' => [['iss' => 'https://reg.example.org']],
            'wrong audience' => [['aud' => 'another-client']],
            'expired' => [['exp' => time() - 3600, 'iat' => time() - 7200]],
            'no expiry' => [['exp' => null]],
            'wrong nonce' => [['nonce' => 'replayed-nonce']],
            'no nonce' => [['nonce' => null]],
            'no subject' => [['sub' => null]],
            'issued in the future' => [['iat' => time() + 3600, 'nbf' => time() + 3600]],
        ];
    }

    /** @dataProvider badTokens */
    public function testBadIdTokensAreRefused(array $overrides): void
    {
        $this->expectException(OidcException::class);
        oidc_verify_id_token($this->config(), self::$jwks, $this->idToken($overrides), 'the-nonce');
    }

    public function testIdTokenSignedWithAnotherKeyIsRefused(): void
    {
        $other = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($other, $pem);
        $this->expectException(OidcException::class);
        oidc_verify_id_token($this->config(), self::$jwks, $this->idToken([], $pem), 'the-nonce');
    }

    public function testHs256TokenIsRefused(): void
    {
        // Algorithm confusion: an HS256 token "signed" with something public must not verify.
        $this->expectException(OidcException::class);
        oidc_verify_id_token($this->config(), self::$jwks, $this->idToken([], str_repeat('k', 64), 'HS256'), 'the-nonce');
    }

    public function testUnsignedTokenIsRefused(): void
    {
        $header = oidc_base64url(json_encode(['alg' => 'none', 'typ' => 'JWT', 'kid' => 'k1']));
        $body = oidc_base64url(json_encode(['iss' => 'https://reg.example.org/', 'aud' => 'planz', 'sub' => '2',
            'exp' => time() + 300, 'nonce' => 'the-nonce']));
        $this->expectException(OidcException::class);
        oidc_verify_id_token($this->config(), self::$jwks, "$header.$body.", 'the-nonce');
    }

    public function testTamperedTokenIsRefused(): void
    {
        [$header, , $signature] = explode('.', $this->idToken());
        $body = oidc_base64url(json_encode(['iss' => 'https://reg.example.org/', 'aud' => 'planz', 'sub' => '1',
            'exp' => time() + 300, 'nonce' => 'the-nonce']));
        $this->expectException(OidcException::class);
        oidc_verify_id_token($this->config(), self::$jwks, "$header.$body.$signature", 'the-nonce');
    }

    public function testConfigProblemsAreReported(): void
    {
        $config = ['issuer' => '', 'client_id' => 'planz', 'client_secret' => '', 'redirect_uri' => 'x',
            'event_id' => null, 'badgeid_source' => 'badge', 'participant_role' => 'Program Participant'];
        $problems = oidc_config_problems($config);
        $this->assertContains('OIDC_ISSUER is not set', $problems);
        $this->assertContains('OIDC_CLIENT_SECRET is not set', $problems);
        $this->assertContains('OIDC_EVENT_ID is not set', $problems);
        $this->assertContains('OIDC_BADGEID_SOURCE must be "mid" or "member_no"', $problems);
    }
}
