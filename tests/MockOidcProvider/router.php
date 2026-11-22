<?php
// A minimal OpenID Connect provider for automated tests, run with PHP's built-in server:
//
//     MOCK_OIDC_DIR=/some/dir php -S 127.0.0.1:8099 tests/MockOidcProvider/router.php
//
// It behaves like ConReg's conreg_oidc module as far as PlanZ is concerned: discovery, the
// authorization code flow with PKCE (S256), a confidential client, RS256-signed ID tokens, a JWKS
// endpoint and a userinfo endpoint returning ConReg-style member claims. There is no login page:
// the authorization endpoint signs in whoever the test says, straight away.
//
// Everything it needs is in MOCK_OIDC_DIR, written by MockOidcProvider.php:
//     settings.json   issuer, client_id, client_secret, redirect_uri, and test switches (see below)
//     private.pem     RSA key ID tokens are signed with
//     userinfo.json   the claims returned by userinfo; "sub" is also the ID token's sub
//     codes/, tokens/ issued authorization codes and access tokens
//
// Test switches in settings.json:
//     "authorize_error": "access_denied" | "login_required" | ...   send this error instead of a code
//     "login_required_if_silent": true   answer prompt=none with error=login_required
//     "id_token_overrides": {...}        claims to replace in the ID token (e.g. a wrong "aud")
//     "sign_with_key": "other.pem"       sign ID tokens with a key not in the JWKS
//     "id_token_alg": "HS256"            sign ID tokens with another algorithm (using the client secret)
//     "no_kid": true                     like ConReg: no "kid" (or "alg") in the JWKS or ID token header

require_once __DIR__ . '/../../webpages/vendor/autoload.php';

use Firebase\JWT\JWT;

$dir = getenv('MOCK_OIDC_DIR');
if (!$dir || !is_dir($dir)) {
    http_response_code(500);
    exit("MOCK_OIDC_DIR is not set\n");
}
$settings = json_decode(file_get_contents("$dir/settings.json"), true);
$issuer = $settings['issuer'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function mock_json(int $status, array $body): void {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

function mock_redirect(string $uri, array $params): void {
    header('Location: ' . $uri . (strpos($uri, '?') === false ? '?' : '&') . http_build_query($params));
    http_response_code(302);
    exit;
}

function mock_b64url(string $bytes): string {
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

function mock_store(string $dir, string $kind, string $id, ?array $data = null): ?array {
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
        return null;
    }
    $file = "$dir/$kind/$id.json";
    if ($data !== null) {
        @mkdir("$dir/$kind", 0700, true);
        file_put_contents($file, json_encode($data));
        return $data;
    }
    return is_file($file) ? json_decode(file_get_contents($file), true) : null;
}

switch ($path) {
    case '/.well-known/openid-configuration':
        mock_json(200, [
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer . 'oauth/authorize',
            'token_endpoint' => $issuer . 'oauth/token',
            'userinfo_endpoint' => $issuer . 'oauth/userinfo',
            'jwks_uri' => $issuer . 'oauth/jwks',
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => ['openid', 'profile', 'email', 'phone', 'address', 'conreg_member', 'conreg_members', 'conreg_entitlements'],
        ]);

    case '/oauth/jwks':
        $details = openssl_pkey_get_details(openssl_pkey_get_private(file_get_contents("$dir/private.pem")));
        $jwk = [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => 'mock-key-1',
            'n' => mock_b64url($details['rsa']['n']),
            'e' => mock_b64url($details['rsa']['e']),
        ];
        if (!empty($settings['no_kid'])) {
            $jwk = ['kty' => 'RSA', 'n' => $jwk['n'], 'e' => $jwk['e']];
        }
        mock_json(200, ['keys' => [$jwk]]);

    case '/oauth/authorize':
        $q = $_GET;
        if (($q['client_id'] ?? '') !== $settings['client_id'] || ($q['redirect_uri'] ?? '') !== $settings['redirect_uri']) {
            mock_json(400, ['error' => 'invalid_client', 'error_description' => 'Unknown client or redirect URI']);
        }
        $redirect = $q['redirect_uri'];
        $state = $q['state'] ?? '';
        if (($q['response_type'] ?? '') !== 'code') {
            mock_redirect($redirect, ['error' => 'unsupported_response_type', 'state' => $state]);
        }
        if (($q['code_challenge_method'] ?? '') !== 'S256' || empty($q['code_challenge'])) {
            mock_redirect($redirect, ['error' => 'invalid_request', 'error_description' => 'PKCE S256 required', 'state' => $state]);
        }
        if (!empty($settings['authorize_error'])) {
            mock_redirect($redirect, ['error' => $settings['authorize_error'], 'state' => $state]);
        }
        if (($q['prompt'] ?? '') === 'none' && !empty($settings['login_required_if_silent'])) {
            mock_redirect($redirect, ['error' => 'login_required', 'state' => $state]);
        }
        $code = mock_b64url(random_bytes(24));
        mock_store($dir, 'codes', $code, [
            'challenge' => $q['code_challenge'],
            'nonce' => $q['nonce'] ?? null,
            'redirect_uri' => $redirect,
            'scope' => $q['scope'] ?? '',
            'expires' => time() + 60,
        ]);
        mock_redirect($redirect, ['code' => $code, 'state' => $state]);

    case '/oauth/token':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            mock_json(405, ['error' => 'invalid_request']);
        }
        $clientId = $_POST['client_id'] ?? null;
        $secret = $_POST['client_secret'] ?? null;
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $clientId = $_SERVER['PHP_AUTH_USER'];
            $secret = $_SERVER['PHP_AUTH_PW'] ?? '';
        }
        if ($clientId !== $settings['client_id'] || !is_string($secret) || !hash_equals($settings['client_secret'], $secret)) {
            mock_json(401, ['error' => 'invalid_client']);
        }
        if (($_POST['grant_type'] ?? '') !== 'authorization_code') {
            mock_json(400, ['error' => 'unsupported_grant_type']);
        }
        $codeId = (string)($_POST['code'] ?? '');
        $code = mock_store($dir, 'codes', $codeId);
        if ($code === null || $code['expires'] < time()) {
            mock_json(400, ['error' => 'invalid_grant']);
        }
        @unlink("$dir/codes/$codeId.json"); // single use
        if (($_POST['redirect_uri'] ?? '') !== $code['redirect_uri']) {
            mock_json(400, ['error' => 'invalid_grant', 'error_description' => 'redirect_uri mismatch']);
        }
        $verifier = (string)($_POST['code_verifier'] ?? '');
        if (!hash_equals($code['challenge'], mock_b64url(hash('sha256', $verifier, true)))) {
            mock_json(400, ['error' => 'invalid_grant', 'error_description' => 'PKCE verification failed']);
        }
        $userinfo = json_decode(file_get_contents("$dir/userinfo.json"), true);
        $accessToken = mock_b64url(random_bytes(24));
        mock_store($dir, 'tokens', $accessToken, ['sub' => $userinfo['sub'], 'expires' => time() + 300]);
        $now = time();
        $claims = array_merge([
            'iss' => $issuer,
            'sub' => $userinfo['sub'],
            'aud' => $settings['client_id'],
            'iat' => $now,
            'exp' => $now + 300,
            'auth_time' => $now,
            'nonce' => $code['nonce'],
        ], $settings['id_token_overrides'] ?? []);
        $claims = array_filter($claims, fn($v) => $v !== null);
        $alg = $settings['id_token_alg'] ?? 'RS256';
        $kid = empty($settings['no_kid']) ? 'mock-key-1' : null;
        if ($alg === 'HS256') {
            $idToken = JWT::encode($claims, $settings['client_secret'], 'HS256', $kid);
        } else {
            $keyFile = $settings['sign_with_key'] ?? 'private.pem';
            $idToken = JWT::encode($claims, file_get_contents("$dir/$keyFile"), 'RS256', $kid);
        }
        mock_json(200, [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 300,
            'id_token' => $idToken,
        ]);

    case '/oauth/userinfo':
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = preg_match('/^Bearer\s+(\S+)$/', $auth, $m) ? mock_store($dir, 'tokens', $m[1]) : null;
        if ($token === null || $token['expires'] < time()) {
            header('WWW-Authenticate: Bearer error="invalid_token"');
            mock_json(401, ['error' => 'invalid_token']);
        }
        mock_json(200, json_decode(file_get_contents("$dir/userinfo.json"), true));

    default:
        mock_json(404, ['error' => 'not_found']);
}
