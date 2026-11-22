<?php
// Copyright (c) 2026 PlanZ contributors. All rights reserved. See copyright document for more details.
//
// OpenID Connect sign-in support.
//
// The first section holds pure functions (no database, no session, no output) so they can be unit
// tested in isolation: configuration, PKCE, building the authorization URL, verifying the ID token,
// and turning the provider's member claims into PlanZ badge IDs and roles.
//
// The second section holds the functions that talk to the PlanZ database and session, used by
// oidc_callback.php and SelectParticipant.php.
//
// The member claims follow ConReg's layout (see "Contact details" and "Example: ConReg" in
// Documentation/User_Documentation/oidc_sign_in.md). Their claim names are configurable,
// so another registration system can be supported by sending the same shape under different names.

// The ID token library, firebase/php-jwt, comes from Composer: run "composer install --no-dev" in
// webpages/. If it's missing, OIDC sign-in reports a configuration problem instead of failing with
// a fatal error. It needs PHP 8.0, and Composer's autoloader stops PHP with an error on older
// versions, so it isn't loaded there.
if (PHP_VERSION_ID >= 80000 && file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
}
require_once(__DIR__ . '/login_functions.php');

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

const OIDC_PENDING_LIFETIME = 600; // seconds a sign-in attempt may take between redirect and callback

final class OidcException extends Exception
{
    // A message that is safe to show the user. The exception message itself goes to the error log.
    public string $userMessage;

    public function __construct(string $logMessage, string $userMessage = '')
    {
        parent::__construct($logMessage);
        $this->userMessage = $userMessage !== '' ? $userMessage
            : 'Sign-in failed. Please try again, or contact ' . (defined('CON_SUPPORT_EMAIL') && CON_SUPPORT_EMAIL ? CON_SUPPORT_EMAIL : 'convention staff') . '.';
    }
}

// ---------------------------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------------------------

function oidc_const(string $name, $default) {
    return defined($name) ? constant($name) : $default;
}

// Collects the OIDC settings from db_name.php / db_env.php into one array. Everything else in this
// file takes the array as a parameter, so tests can point it at a mock provider.
function oidc_config(): array {
    $entitlementRoles = oidc_const('OIDC_ENTITLEMENT_ROLES', []);
    return [
        'issuer' => (string)oidc_const('OIDC_ISSUER', ''),
        'client_id' => (string)oidc_const('OIDC_CLIENT_ID', ''),
        'client_secret' => (string)oidc_const('OIDC_CLIENT_SECRET', ''),
        'redirect_uri' => (string)oidc_const('OIDC_REDIRECT_URI', ''),
        'scopes' => (string)oidc_const('OIDC_SCOPES', 'openid profile email phone address conreg_member conreg_members conreg_entitlements'),
        'provider_name' => (string)oidc_const('OIDC_PROVIDER_NAME', 'the registration system'),
        'logout_url' => (string)oidc_const('OIDC_LOGOUT_URL', ''),
        'event_id' => oidc_const('OIDC_EVENT_ID', null),
        'badgeid_prefix' => (string)oidc_const('OIDC_BADGEID_PREFIX', ''),
        'badgeid_digits' => (int)oidc_const('OIDC_BADGEID_DIGITS', 0),
        'badgeid_source' => (string)oidc_const('OIDC_BADGEID_SOURCE', 'mid'),
        'badgeid_claim' => (string)oidc_const('OIDC_BADGEID_CLAIM', 'planz_badge_id'),
        'auto_provision' => (bool)oidc_const('OIDC_AUTO_PROVISION', false),
        'participant_role' => (string)oidc_const('OIDC_PARTICIPANT_ROLE', 'Program Participant'),
        'entitlement_roles' => is_array($entitlementRoles) ? $entitlementRoles : [],
        'local_roles' => oidc_const('OIDC_LOCAL_ROLES', []),
        'silent_signin' => (bool)oidc_const('OIDC_SILENT_SIGNIN', false),
        'self_claim' => (string)oidc_const('OIDC_SELF_CLAIM', 'conreg_member'),
        'members_claim' => (string)oidc_const('OIDC_MEMBERS_CLAIM', 'conreg_members'),
        'entitlements_claim' => (string)oidc_const('OIDC_ENTITLEMENTS_CLAIM', 'conreg_entitlements'),
        'http_timeout' => (int)oidc_const('OIDC_HTTP_TIMEOUT', 10),
        'member_fields' => oidc_member_fields(oidc_const('OIDC_MEMBER_FIELDS', [])),
        'country_names' => (bool)oidc_const('OIDC_COUNTRY_NAMES', true),
        'country_name_overrides' => (array)oidc_const('OIDC_COUNTRY_NAME_OVERRIDES', []),
    ];
}

// Where each CongoDump contact column comes from in a member claim (ConReg's names; see "Contact
// details" in Documentation/User_Documentation/oidc_sign_in.md). A dotted name such as
// "address.locality" reads a nested value. ":first_line" takes the first line of a multi-line
// value, and ":other_lines" the rest, joined with ", ".
// OIDC_MEMBER_FIELDS changes entries; an empty name stops PlanZ taking that column from the
// provider.
const OIDC_DEFAULT_MEMBER_FIELDS = [
    'firstname' => 'first_name',
    'lastname' => 'last_name',
    'badgename' => 'badge_name',
    'email' => 'email',
    'phone' => 'phone',
    'postaddress1' => 'address.street_address:first_line',
    'postaddress2' => 'address.street_address:other_lines',
    'postcity' => 'address.locality',
    'poststate' => 'address.region',
    'postzip' => 'address.postal_code',
    'postcountry' => 'address.country',
    'regtype' => 'member_type',
];

// CongoDump column sizes.
const OIDC_CONGODUMP_LENGTHS = [
    'firstname' => 30, 'lastname' => 40, 'badgename' => 51, 'phone' => 100, 'email' => 100,
    'postaddress1' => 100, 'postaddress2' => 100, 'postcity' => 50, 'poststate' => 25,
    'postzip' => 10, 'postcountry' => 25, 'regtype' => 40,
];

function oidc_member_fields($overrides): array {
    $fields = OIDC_DEFAULT_MEMBER_FIELDS;
    foreach (is_array($overrides) ? $overrides : [] as $column => $claim) {
        if (isset(OIDC_CONGODUMP_LENGTHS[$column])) {
            $fields[$column] = (string)$claim;
        }
    }
    return array_filter($fields, fn($claim) => $claim !== '');
}

// Returns a list of problems with the configuration; empty if it is usable.
// Why the libraries OIDC sign-in needs can't be used, or null if they can.
function oidc_library_problem(): ?string {
    if (PHP_VERSION_ID < 80000) {
        return 'OIDC sign-in needs PHP 8.0 or later; this server has PHP ' . PHP_VERSION;
    }
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        return 'the PHP libraries OIDC sign-in needs are not installed: run "composer install --no-dev" in ' . __DIR__;
    }
    if (!class_exists(JWT::class) || !class_exists(JWK::class)) {
        return 'firebase/php-jwt is not installed: run "composer install --no-dev" in ' . __DIR__;
    }
    return null;
}

function oidc_config_problems(array $config): array {
    $problems = [];
    $library = oidc_library_problem();
    if ($library !== null) {
        $problems[] = $library;
    }
    foreach (['issuer', 'client_id', 'client_secret', 'redirect_uri'] as $key) {
        if ($config[$key] === '') {
            $problems[] = 'OIDC_' . strtoupper($key) . ' is not set';
        }
    }
    if ($config['event_id'] === null || $config['event_id'] === '') {
        $problems[] = 'OIDC_EVENT_ID is not set';
    }
    if (!in_array($config['badgeid_source'], ['mid', 'member_no'], true)) {
        $problems[] = 'OIDC_BADGEID_SOURCE must be "mid" or "member_no"';
    }
    if ($config['participant_role'] === '') {
        $problems[] = 'OIDC_PARTICIPANT_ROLE is not set';
    }
    return $problems;
}

// ---------------------------------------------------------------------------------------------
// Protocol helpers (pure)
// ---------------------------------------------------------------------------------------------

function oidc_base64url(string $bytes): string {
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

function oidc_random_token(int $bytes = 32): string {
    return oidc_base64url(random_bytes($bytes));
}

function oidc_pkce_challenge(string $verifier): string {
    return oidc_base64url(hash('sha256', $verifier, true));
}

// Creates the values for one sign-in attempt. Stored in the session until the callback.
function oidc_new_pending(bool $silent, ?int $now = null): array {
    return [
        'state' => oidc_random_token(),
        'nonce' => oidc_random_token(),
        'code_verifier' => oidc_random_token(48),
        'silent' => $silent,
        'created' => $now ?? time(),
    ];
}

function oidc_authorize_url(array $config, array $discovery, array $pending): string {
    $params = [
        'response_type' => 'code',
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'scope' => $config['scopes'],
        'state' => $pending['state'],
        'nonce' => $pending['nonce'],
        'code_challenge' => oidc_pkce_challenge($pending['code_verifier']),
        'code_challenge_method' => 'S256',
    ];
    if (!empty($pending['silent'])) {
        $params['prompt'] = 'none';
    }
    $endpoint = $discovery['authorization_endpoint'];
    return $endpoint . (strpos($endpoint, '?') === false ? '?' : '&') . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

// Checks the state returned to the callback against the pending sign-in. Returns the pending
// sign-in if it matches.
function oidc_check_state(?array $pending, ?string $state, ?int $now = null): array {
    if (!$pending || !isset($pending['state'])) {
        throw new OidcException('No pending OIDC sign-in in session', 'Your sign-in attempt has expired or was started in another browser. Please try again.');
    }
    if (!is_string($state) || !hash_equals($pending['state'], $state)) {
        throw new OidcException('OIDC state mismatch', 'Your sign-in attempt has expired or was started in another browser. Please try again.');
    }
    if (($now ?? time()) - $pending['created'] > OIDC_PENDING_LIFETIME) {
        throw new OidcException('OIDC sign-in attempt expired', 'Your sign-in attempt took too long. Please try again.');
    }
    return $pending;
}

function oidc_check_discovery(array $config, array $discovery): void {
    foreach (['issuer', 'authorization_endpoint', 'token_endpoint', 'jwks_uri', 'userinfo_endpoint'] as $key) {
        if (empty($discovery[$key]) || !is_string($discovery[$key])) {
            throw new OidcException("OIDC discovery document has no $key");
        }
    }
    if ($discovery['issuer'] !== $config['issuer']) {
        throw new OidcException("OIDC discovery issuer '{$discovery['issuer']}' does not match configured issuer '{$config['issuer']}'");
    }
}

// The key or keys to check an ID token's signature with. A key set is matched on the token's "kid".
// Some providers (ConReg included) publish a single key with no "kid", and sign tokens without one;
// then that one key is used.
function oidc_verification_keys(array $jwks, string $idToken) {
    $keys = JWK::parseKeySet($jwks, 'RS256');
    $headerPart = explode('.', $idToken)[0];
    $header = json_decode((string)base64_decode(strtr($headerPart, '-_', '+/'), true), true);
    $kid = is_array($header) && isset($header['kid']) && is_string($header['kid']) ? $header['kid'] : null;
    $publishedKids = array_filter(array_map(fn($k) => is_array($k) ? ($k['kid'] ?? null) : null, $jwks['keys'] ?? []));
    if (count($keys) === 1 && count($publishedKids) === 0 && ($kid === null || !isset($keys[$kid]))) {
        return reset($keys);
    }
    return $keys;
}

// Verifies the ID token's signature against the provider's JWKS, and its iss, aud, exp and nonce.
// Returns the token's claims as an array.
function oidc_verify_id_token(array $config, array $jwks, string $idToken, string $expectedNonce): array {
    try {
        // Keys without an "alg" are taken as RS256, the only algorithm ConReg signs with. JWT::decode
        // rejects any token whose header alg doesn't match the key's, so "none" and HS256 are refused.
        $keys = oidc_verification_keys($jwks, $idToken);
        $previousLeeway = JWT::$leeway;
        JWT::$leeway = 60;
        try {
            $claims = (array)JWT::decode($idToken, $keys);
        } finally {
            JWT::$leeway = $previousLeeway;
        }
    } catch (Throwable $e) {
        throw new OidcException('ID token failed verification: ' . $e->getMessage());
    }
    if (($claims['iss'] ?? null) !== $config['issuer']) {
        throw new OidcException('ID token issuer mismatch: ' . json_encode($claims['iss'] ?? null));
    }
    $aud = $claims['aud'] ?? null;
    $audiences = is_array($aud) ? $aud : [$aud];
    if (!in_array($config['client_id'], $audiences, true)) {
        throw new OidcException('ID token audience does not include client ID');
    }
    if (count($audiences) > 1 && ($claims['azp'] ?? null) !== $config['client_id']) {
        throw new OidcException('ID token has several audiences and azp is not this client');
    }
    if (!isset($claims['exp'])) {
        throw new OidcException('ID token has no exp');
    }
    if (!isset($claims['nonce']) || !is_string($claims['nonce']) || !hash_equals($expectedNonce, $claims['nonce'])) {
        throw new OidcException('ID token nonce mismatch');
    }
    if (!isset($claims['sub']) || !is_string($claims['sub']) || $claims['sub'] === '') {
        throw new OidcException('ID token has no sub');
    }
    return $claims;
}

// ---------------------------------------------------------------------------------------------
// Member claims (pure)
// ---------------------------------------------------------------------------------------------

// The mid of the user's own member, or null. Only this member may ever be given staff roles.
function oidc_self_mid(array $userinfo, array $config): ?int {
    $self = $userinfo[$config['self_claim']] ?? null;
    if (is_array($self) && isset($self['mid'])) {
        return (int)$self['mid'];
    }
    foreach ((array)($userinfo[$config['members_claim']] ?? []) as $member) {
        if (is_array($member) && ($member['is_self'] ?? false) === true && isset($member['mid'])) {
            return (int)$member['mid'];
        }
    }
    return null;
}

// The members this user may act as in this PlanZ installation's event. Each entry has the provider's
// fields plus "is_self" (recomputed here, not taken on trust from the entry alone) and "badgeid".
// Members without a usable badge ID (for example no member number yet, when badge IDs are built
// from member numbers) are left out.
function oidc_event_members(array $userinfo, array $config): array {
    $selfMid = oidc_self_mid($userinfo, $config);
    $members = [];
    $seen = [];
    foreach ((array)($userinfo[$config['members_claim']] ?? []) as $member) {
        if (!is_array($member) || !isset($member['mid'], $member['eid'])) {
            continue;
        }
        if ((string)$member['eid'] !== (string)$config['event_id']) {
            continue;
        }
        $mid = (int)$member['mid'];
        if (isset($seen[$mid])) {
            continue;
        }
        $badgeid = oidc_badgeid_for_member($member, $config);
        if ($badgeid === null) {
            continue;
        }
        $seen[$mid] = true;
        $member['mid'] = $mid;
        $member['is_self'] = ($selfMid !== null && $mid === $selfMid);
        $member['badgeid'] = $badgeid;
        $members[] = $member;
    }
    return $members;
}

// The PlanZ badge ID for a member, or null if there isn't one yet (that member can't sign in).
//
// When the member claim includes OIDC_BADGEID_CLAIM (ConReg's planz_badge_id, sent when
// conreg_planz is installed), that's the badge ID, including for users conreg_planz linked to an
// existing PlanZ user by email. If the claim is there but null, the member has no badge ID yet.
// Without the claim, the badge ID is built the same way conreg_planz does:
// prefix + str_pad(mid or member_no, digits, "0", STR_PAD_LEFT)
function oidc_badgeid_for_member(array $member, array $config): ?string {
    $claim = $config['badgeid_claim'] ?? '';
    if ($claim !== '' && array_key_exists($claim, $member)) {
        $value = $member[$claim];
        // Some older PlanZ code still puts badge IDs into SQL directly, so only plain IDs are taken.
        if ((is_string($value) || is_int($value)) && preg_match('/^[A-Za-z0-9._-]{1,15}$/', (string)$value)) {
            return (string)$value;
        }
        if ($value !== null && $value !== '') {
            error_log('OIDC: ignoring member ' . ($member['mid'] ?? '?') . ": unusable $claim");
        }
        return null;
    }
    $number = $config['badgeid_source'] === 'member_no' ? ($member['member_no'] ?? null) : ($member['mid'] ?? null);
    if ($number === null || $number === '' || !preg_match('/^\d+$/', (string)$number)) {
        return null;
    }
    $badgeid = $config['badgeid_prefix'] . str_pad((string)$number, max(0, $config['badgeid_digits']), '0', STR_PAD_LEFT);
    return strlen($badgeid) <= 15 ? $badgeid : null; // Participants.badgeid is varchar(15)
}

function oidc_member_label(array $member): string {
    $name = trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
    $badge = trim((string)($member['badge_name'] ?? ''));
    if ($badge !== '' && $name !== '' && $badge !== $name) {
        return "$badge ($name)";
    }
    return $badge !== '' ? $badge : ($name !== '' ? $name : 'Member ' . ($member['mid'] ?? '?'));
}

// The entitlement values to honour. Entitlements only ever apply to the user's own member, so
// acting as anyone else gives none.
function oidc_entitlements(array $userinfo, array $config): array {
    $values = $userinfo[$config['entitlements_claim']] ?? [];
    if (!is_array($values)) {
        return [];
    }
    return array_values(array_unique(array_filter($values, 'is_string')));
}

// The names of the PlanZ roles a member gets: the participant role, plus, for the user's own member
// only, the roles mapped from their entitlements. Unknown entitlements are ignored.
function oidc_role_names_for_member(array $member, array $entitlements, array $config): array {
    $roles = [$config['participant_role']];
    if (!empty($member['is_self'])) {
        foreach ($entitlements as $entitlement) {
            $mapped = $config['entitlement_roles'][$entitlement] ?? [];
            foreach ((array)$mapped as $roleName) {
                $roles[] = (string)$roleName;
            }
        }
    }
    return array_values(array_unique($roles));
}

// Short English names for the countries whose usual names don't fit CongoDump.postcountry.
const OIDC_SHORT_COUNTRY_NAMES = [
    'GS' => 'S. Georgia & S. Sandwich',
    'IO' => 'British Ind. Ocean Terr.',
    'TF' => 'French Southern Terr.',
];

// The English name of an ISO 3166-1 alpha-2 country code ("IE" -> "Ireland"). Anything that isn't
// a known two-letter code is returned unchanged, so names already sent as names are kept.
function oidc_country_name(string $value, array $config): string {
    if (!preg_match('/^[A-Za-z]{2}$/', $value)) {
        return $value;
    }
    $code = strtoupper($value);
    $overrides = array_change_key_case($config['country_name_overrides'] ?? [], CASE_UPPER);
    if (isset($overrides[$code])) {
        return (string)$overrides[$code];
    }
    if (isset(OIDC_SHORT_COUNTRY_NAMES[$code])) {
        return OIDC_SHORT_COUNTRY_NAMES[$code];
    }
    if (class_exists('Locale')) {
        $name = Locale::getDisplayRegion('-' . $code, 'en');
        if (is_string($name) && $name !== '' && $name !== $code && $name !== 'Unknown Region') {
            return $name;
        }
    }
    return $value;
}

// Reads a value from a member claim; "a.b" reads $member['a']['b'], and a ":first_line" or
// ":other_lines" suffix splits a multi-line value. Returns null if the claim doesn't include the
// value at all (so PlanZ keeps what it has), and a string, possibly empty, if it does. A null
// value counts as empty.
function oidc_member_value(array $member, string $path): ?string {
    $part = null;
    if (preg_match('/^(.*):(first_line|other_lines)$/', $path, $m)) {
        [, $path, $part] = $m;
    }
    $value = $member;
    foreach (explode('.', $path) as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return null;
        }
        $value = $value[$key];
    }
    if ($value === null) {
        $value = '';
    }
    if (!is_scalar($value)) {
        return null;
    }
    $value = (string)$value;
    if ($part !== null) {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value)), fn($l) => $l !== ''));
        $value = $part === 'first_line' ? ($lines[0] ?? '') : implode(', ', array_slice($lines, 1));
    } else {
        // Single-line columns: fold any line breaks into spaces.
        $value = preg_replace('/\s*[\r\n]+\s*/', ' ', $value);
    }
    return $value;
}

// Contact fields written to CongoDump from a member claim, trimmed and cut to the column sizes.
// The provider is the source of these details, so a field the claim includes replaces what PlanZ
// has, even when it's empty. A field the claim leaves out (for example because the provider
// didn't grant that scope) is null, and PlanZ keeps its value.
function oidc_congodump_fields(array $member, array $config = []): array {
    $sources = $config['member_fields'] ?? OIDC_DEFAULT_MEMBER_FIELDS;
    $fields = [];
    foreach (OIDC_CONGODUMP_LENGTHS as $column => $length) {
        $value = isset($sources[$column]) ? oidc_member_value($member, $sources[$column]) : null;
        if ($value === null) {
            $fields[$column] = null;
            continue;
        }
        $value = trim($value);
        if ($value !== '' && $column === 'postcountry' && ($config['country_names'] ?? true)) {
            $value = oidc_country_name($value, $config);
        }
        $fields[$column] = mb_substr($value, 0, $length);
    }
    // PlanZ needs a badge name, so an empty one falls back to the member's name.
    if (($fields['badgename'] ?? '') === '') {
        $name = trim(($fields['firstname'] ?? '') . ' ' . ($fields['lastname'] ?? ''));
        if ($name !== '') {
            $fields['badgename'] = mb_substr($name, 0, OIDC_CONGODUMP_LENGTHS['badgename']);
        }
    }
    return $fields;
}

// ---------------------------------------------------------------------------------------------
// HTTP calls to the provider
// ---------------------------------------------------------------------------------------------

function oidc_http(array $config, string $method, string $url, ?array $form = null, array $headers = []): array {
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $config['http_timeout'],
        CURLOPT_CONNECTTIMEOUT => $config['http_timeout'],
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
    ];
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($form ?? [], '', '&');
    }
    curl_setopt_array($ch, $options);
    $body = curl_exec($ch);
    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new OidcException("OIDC request to $url failed: $error",
            'Could not contact ' . $config['provider_name'] . '. Please try again later.');
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode($body, true);
    if (!is_array($json)) {
        throw new OidcException("OIDC response from $url (HTTP $status) is not JSON");
    }
    if ($status < 200 || $status >= 300) {
        $error = $json['error'] ?? 'unknown';
        throw new OidcException("OIDC request to $url returned HTTP $status: $error");
    }
    return $json;
}

function oidc_fetch_discovery(array $config): array {
    $discovery = oidc_http($config, 'GET', rtrim($config['issuer'], '/') . '/.well-known/openid-configuration');
    oidc_check_discovery($config, $discovery);
    return $discovery;
}

function oidc_exchange_code(array $config, array $discovery, string $code, string $codeVerifier): array {
    $tokens = oidc_http($config, 'POST', $discovery['token_endpoint'], [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $config['redirect_uri'],
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'code_verifier' => $codeVerifier,
    ]);
    if (empty($tokens['access_token']) || empty($tokens['id_token'])) {
        throw new OidcException('OIDC token response has no access_token or id_token');
    }
    return $tokens;
}

function oidc_fetch_userinfo(array $config, array $discovery, string $accessToken): array {
    return oidc_http($config, 'GET', $discovery['userinfo_endpoint'], null, ['Authorization: Bearer ' . $accessToken]);
}

// Runs the whole callback exchange: checks state, swaps the code for tokens, verifies the ID token
// and fetches userinfo. Returns ['id_token' => claims, 'userinfo' => claims].
function oidc_complete_authorization(array $config, ?array $pending, array $query): array {
    $pending = oidc_check_state($pending, $query['state'] ?? null);
    if (empty($query['code']) || !is_string($query['code'])) {
        throw new OidcException('OIDC callback has no code');
    }
    $discovery = oidc_fetch_discovery($config);
    $tokens = oidc_exchange_code($config, $discovery, $query['code'], $pending['code_verifier']);
    $jwks = oidc_http($config, 'GET', $discovery['jwks_uri']);
    $idClaims = oidc_verify_id_token($config, $jwks, $tokens['id_token'], $pending['nonce']);
    $userinfo = oidc_fetch_userinfo($config, $discovery, $tokens['access_token']);
    if (($userinfo['sub'] ?? null) !== $idClaims['sub']) {
        throw new OidcException('Userinfo sub does not match ID token sub');
    }
    return ['id_token' => $idClaims, 'userinfo' => $userinfo];
}

// ---------------------------------------------------------------------------------------------
// PlanZ database and session
// ---------------------------------------------------------------------------------------------

// Small prepared-statement helpers that throw on failure. The app runs with mysqli exceptions
// switched off (see prepare_db_and_more()), and the shared helpers render an error page part way
// through, which would leave a sign-in half done.
function oidc_db_execute(string $query, string $types = '', array $params = []): mysqli_stmt {
    global $linki;
    $stmt = mysqli_prepare($linki, $query);
    if (!$stmt) {
        throw new OidcException('OIDC: could not prepare query: ' . mysqli_error($linki) . " -- $query");
    }
    if ($types !== '' && !mysqli_stmt_bind_param($stmt, $types, ...$params)) {
        throw new OidcException('OIDC: could not bind query parameters: ' . mysqli_stmt_error($stmt));
    }
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new OidcException("OIDC: query failed: $error -- $query");
    }
    return $stmt;
}

function oidc_db_rows(string $query, string $types = '', array $params = []): array {
    $stmt = oidc_db_execute($query, $types, $params);
    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $rows;
}

function oidc_db_command(string $query, string $types = '', array $params = []): int {
    $stmt = oidc_db_execute($query, $types, $params);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected;
}

// Looks up which of the members already have a PlanZ account. Returns badgeid => true.
function oidc_existing_badgeids(array $members): array {
    if (count($members) === 0) {
        return [];
    }
    $badgeids = array_values(array_map(fn($m) => $m['badgeid'], $members));
    $placeholders = implode(',', array_fill(0, count($badgeids), '?'));
    $found = [];
    foreach (oidc_db_rows("SELECT badgeid FROM Participants WHERE badgeid IN ($placeholders);",
            str_repeat('s', count($badgeids)), $badgeids) as $row) {
        $found[$row['badgeid']] = true;
    }
    return $found;
}

// Members the user may pick from. Without automatic provisioning, only members who have been set
// up in PlanZ are offered.
function oidc_selectable_members(array $members, array $config): array {
    if ($config['auto_provision']) {
        return $members;
    }
    $existing = oidc_existing_badgeids($members);
    return array_values(array_filter($members, fn($m) => isset($existing[$m['badgeid']])));
}

// Role name => permroleid, for the names that exist. Missing names are logged and left out.
function oidc_role_ids(array $roleNames): array {
    $roleNames = array_values(array_unique(array_map('strval', $roleNames)));
    if (count($roleNames) === 0) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($roleNames), '?'));
    $ids = [];
    foreach (oidc_db_rows("SELECT permroleid, permrolename FROM PermissionRoles WHERE permrolename IN ($placeholders);",
            str_repeat('s', count($roleNames)), $roleNames) as $row) {
        $ids[$row['permrolename']] = (int)$row['permroleid'];
    }
    foreach ($roleNames as $name) {
        if (!isset($ids[$name])) {
            error_log("OIDC: role '$name' from the OIDC configuration does not exist in PermissionRoles");
        }
    }
    return $ids;
}

// Creates the PlanZ participant for a member signing in for the first time.
function oidc_provision_participant(array $member, array $config): void {
    $fields = oidc_congodump_fields($member, $config);
    $pubsname = $fields['badgename'] ?? trim(($fields['firstname'] ?? '') . ' ' . ($fields['lastname'] ?? ''));
    $pubsname = mb_substr($pubsname, 0, 50);
    oidc_db_command("INSERT INTO Participants (badgeid, password, pubsname) VALUES (?, NULL, ?);",
        'ss', [$member['badgeid'], $pubsname]);
    oidc_insert_congodump($member, $fields);
}

function oidc_insert_congodump(array $member, array $fields): void {
    $badgenumber = isset($member['member_no']) && is_numeric($member['member_no']) ? (int)$member['member_no'] : null;
    $columns = array_keys($fields);
    oidc_db_command('INSERT INTO CongoDump (badgeid, ' . implode(', ', $columns) . ', badgenumber) VALUES ('
        . implode(', ', array_fill(0, count($columns) + 2, '?')) . ');',
        's' . str_repeat('s', count($columns)) . 'i', array_merge([$member['badgeid']], array_values($fields), [$badgenumber]));
}

// Refreshes the contact details PlanZ holds for a member from the latest claims. Fields the claim
// leaves out keep what PlanZ has.
function oidc_update_participant(array $member, array $config): void {
    $fields = oidc_congodump_fields($member, $config);
    if (count(oidc_db_rows("SELECT 1 FROM CongoDump WHERE badgeid = ?;", 's', [$member['badgeid']])) === 0) {
        oidc_insert_congodump($member, $fields);
        return;
    }
    // Only the fields the claim included. Column names come from OIDC_CONGODUMP_LENGTHS, never
    // from the claims.
    $fields = array_filter($fields, fn($v) => $v !== null);
    if (count($fields) === 0) {
        return;
    }
    $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($fields)));
    oidc_db_command("UPDATE CongoDump SET $set WHERE badgeid = ?;",
        str_repeat('s', count($fields) + 1), array_merge(array_values($fields), [$member['badgeid']]));
}

// Makes the user's own member's roles in PlanZ match ConReg exactly: the participant role plus the
// roles mapped from their entitlements. Roles listed in OIDC_LOCAL_ROLES are left alone (for roles
// PlanZ uses only for reports or email lists, such as GOH). Any other role, including legacy staff
// roles granted inside PlanZ, is removed.
function oidc_sync_own_roles(string $badgeid, array $roleIds, array $config): void {
    $wantedIds = array_values(array_unique($roleIds));
    $keep = array_values(array_unique(array_merge($wantedIds, array_values(oidc_role_ids((array)$config['local_roles'])))));
    if (count($keep) > 0) {
        $placeholders = implode(',', array_fill(0, count($keep), '?'));
        oidc_db_command("DELETE FROM UserHasPermissionRole WHERE badgeid = ? AND permroleid NOT IN ($placeholders);",
            's' . str_repeat('i', count($keep)), array_merge([$badgeid], $keep));
    } else {
        oidc_db_command("DELETE FROM UserHasPermissionRole WHERE badgeid = ?;", 's', [$badgeid]);
    }
    foreach ($wantedIds as $roleId) {
        oidc_db_command("INSERT IGNORE INTO UserHasPermissionRole (badgeid, permroleid) VALUES (?, ?);", 'si', [$badgeid, $roleId]);
    }
}

// For a member the user acts for (not their own), only makes sure they have the participant role.
// Their other roles are not touched, and are not used in this session.
function oidc_ensure_role(string $badgeid, int $roleId): void {
    oidc_db_command("INSERT IGNORE INTO UserHasPermissionRole (badgeid, permroleid) VALUES (?, ?);", 'si', [$badgeid, $roleId]);
}

// Like set_permission_set(), but from an explicit list of roles rather than the user's rows in
// UserHasPermissionRole, and without per-badge permissions: an OIDC session's access comes only
// from what ConReg says.
function oidc_set_permission_set(array $roleIds, $db): void {
    $permissions = [];
    $roleIds = array_map('intval', array_values($roleIds));
    if (count($roleIds) > 0) {
        $roleList = implode(',', $roleIds);
        $query = <<<EOD
SELECT DISTINCT
        PA.permatomtag
    FROM
                  PermissionAtoms PA
             JOIN Permissions P USING (permatomid)
        LEFT JOIN Phases PH ON P.phaseid = PH.phaseid AND PH.current = TRUE
    WHERE
            (PH.phaseid IS NOT NULL OR P.phaseid IS NULL)
        AND P.permroleid IN ($roleList)
        AND (PA.module_id IS NULL OR PA.module_id IN (SELECT id FROM module WHERE is_enabled = 1));
EOD;
        $result = mysqli_query($db, $query);
        if ($result) {
            while ($row = mysqli_fetch_object($result)) {
                $permissions[] = $row->permatomtag;
            }
            mysqli_free_result($result);
        }
    }
    $_SESSION['permission_set'] = $permissions;
}

// Signs the session in as one of the user's members. Used at sign-in and when switching
// participant. $member must come from $_SESSION['oidc_members'] (set at sign-in from verified
// claims), never from the request.
function oidc_activate_member(array $member, array $config): void {
    global $linki;
    $roleNames = oidc_role_names_for_member($member, $member['is_self'] ? ($_SESSION['oidc_entitlements'] ?? []) : [], $config);

    mysqli_begin_transaction($linki);
    try {
        if (isset(oidc_existing_badgeids([$member])[$member['badgeid']])) {
            oidc_update_participant($member, $config);
        } elseif ($config['auto_provision']) {
            oidc_provision_participant($member, $config);
        } else {
            throw new OidcException("OIDC: member {$member['mid']} ({$member['badgeid']}) is not set up in PlanZ",
                'You have not been set up as a participant in PlanZ yet. Please contact '
                . (defined('PROGRAM_EMAIL') && PROGRAM_EMAIL ? PROGRAM_EMAIL : 'the programme team') . '.');
        }
        $roleIds = oidc_role_ids($roleNames);
        if (!isset($roleIds[$config['participant_role']])) {
            throw new OidcException("OIDC: OIDC_PARTICIPANT_ROLE '{$config['participant_role']}' does not exist in PermissionRoles");
        }
        if ($member['is_self']) {
            oidc_sync_own_roles($member['badgeid'], $roleIds, $config);
        } else {
            oidc_ensure_role($member['badgeid'], $roleIds[$config['participant_role']]);
        }
        $rows = oidc_db_rows(<<<EOD
SELECT P.data_retention, P.pubsname, C.badgename
    FROM Participants P LEFT JOIN CongoDump C USING (badgeid)
    WHERE P.badgeid = ?;
EOD, 's', [$member['badgeid']]);
        oidc_db_command("UPDATE CongoDump SET last_login = NOW() WHERE badgeid = ?;", 's', [$member['badgeid']]);
        mysqli_commit($linki);
    } catch (Throwable $e) {
        mysqli_rollback($linki);
        throw $e;
    }
    $row = $rows[0];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true); // new session ID at every change of identity
    }
    unset($_SESSION['hashedPassword'], $_SESSION['survey_exists']);
    $_SESSION['auth_method'] = 'oidc';
    $_SESSION['badgeid'] = $member['badgeid'];
    $_SESSION['oidc_active_mid'] = $member['mid'];
    $_SESSION['data_consent'] = (int)$row['data_retention'];
    $_SESSION['badgename'] = !empty($row['pubsname']) ? $row['pubsname'] : ($row['badgename'] ?? '');
    oidc_set_permission_set(array_values($roleIds), $linki);
    set_modules($linki);
}

function oidc_find_session_member(int $mid): ?array {
    foreach ($_SESSION['oidc_members'] ?? [] as $member) {
        if ($member['mid'] === $mid) {
            return $member;
        }
    }
    return null;
}

function oidc_csrf_token(): string {
    if (empty($_SESSION['oidc_csrf'])) {
        $_SESSION['oidc_csrf'] = oidc_random_token();
    }
    return $_SESSION['oidc_csrf'];
}

function oidc_check_csrf(?string $token): bool {
    return is_string($token) && !empty($_SESSION['oidc_csrf']) && hash_equals($_SESSION['oidc_csrf'], $token);
}

// Where to go after signing in as a member.
function oidc_landing_page(): string {
    return may_I('Staff') ? 'StaffPage.php' : 'welcome.php';
}
