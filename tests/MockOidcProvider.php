<?php
// Starts tests/MockOidcProvider/router.php under PHP's built-in server for the length of a test
// class, and lets tests choose who signs in and how the provider misbehaves.

final class MockOidcProvider
{
    public const CLIENT_ID = 'planz-test';
    public const CLIENT_SECRET = 'mock-client-secret-for-tests-only';
    public const REDIRECT_URI = 'https://planz.test/oidc_callback.php';

    private string $dir;
    private $process;
    private int $port;
    private array $settings;

    public function __construct()
    {
        $this->dir = sys_get_temp_dir() . '/planz-mock-oidc-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0700, true);
        $this->port = self::freePort();
        $this->settings = [
            'issuer' => $this->issuer(),
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'redirect_uri' => self::REDIRECT_URI,
        ];
        file_put_contents("$this->dir/private.pem", self::newKey());
        file_put_contents("$this->dir/other.pem", self::newKey());
        $this->writeSettings();
        $this->setUserinfo(['sub' => '1']);

        $router = __DIR__ . '/MockOidcProvider/router.php';
        $command = [PHP_BINARY, '-S', "127.0.0.1:$this->port", $router];
        $env = array_merge(getenv(), ['MOCK_OIDC_DIR' => $this->dir]);
        $this->process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', "$this->dir/server.log", 'w']],
            $pipes, null, $env);
        if (!is_resource($this->process)) {
            throw new RuntimeException('Could not start the mock OIDC provider');
        }
        for ($i = 0; $i < 50; $i++) {
            $socket = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 0.1);
            if ($socket) {
                fclose($socket);
                return;
            }
            usleep(100000);
        }
        $this->stop();
        throw new RuntimeException('The mock OIDC provider did not start: ' . @file_get_contents("$this->dir/server.log"));
    }

    public function stop(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }
        $this->process = null;
        if (is_dir($this->dir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->dir);
        }
    }

    public function __destruct()
    {
        $this->stop();
    }

    public function issuer(): string
    {
        return "http://127.0.0.1:$this->port/";
    }

    // A PlanZ OIDC configuration (as oidc_config() returns) pointing at this provider.
    public function planzConfig(array $overrides = []): array
    {
        return array_merge([
            'issuer' => $this->issuer(),
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'redirect_uri' => self::REDIRECT_URI,
            'scopes' => 'openid profile email phone address conreg_member conreg_members conreg_entitlements',
            'provider_name' => 'Mock ConReg',
            'logout_url' => '',
            'event_id' => 1,
            'badgeid_prefix' => 'T',
            'badgeid_digits' => 5,
            'badgeid_source' => 'mid',
            'auto_provision' => false,
            'participant_role' => 'Program Participant',
            'entitlement_roles' => ['staff' => ['Staff'], 'admin' => ['Administrator', 'Staff']],
            'local_roles' => [],
            'silent_signin' => false,
            'self_claim' => 'conreg_member',
            'members_claim' => 'conreg_members',
            'entitlements_claim' => 'conreg_entitlements',
            'http_timeout' => 5,
        ], $overrides);
    }

    public function setUserinfo(array $userinfo): void
    {
        file_put_contents("$this->dir/userinfo.json", json_encode($userinfo));
    }

    // Changes test switches (see router.php); null removes one.
    public function set(array $switches): void
    {
        foreach ($switches as $key => $value) {
            if ($value === null) {
                unset($this->settings[$key]);
            } else {
                $this->settings[$key] = $value;
            }
        }
        $this->writeSettings();
    }

    public function reset(): void
    {
        foreach (['authorize_error', 'login_required_if_silent', 'id_token_overrides', 'sign_with_key', 'id_token_alg', 'no_kid'] as $key) {
            unset($this->settings[$key]);
        }
        $this->writeSettings();
    }

    // Plays the browser's part: follows the authorization URL one step, and returns the query
    // parameters the provider sent back to the redirect URI.
    public function authorize(string $authorizeUrl): array
    {
        $ch = curl_init($authorizeUrl);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 5]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $location = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        if ($status !== 302 || !$location) {
            throw new RuntimeException("Authorization endpoint did not redirect (HTTP $status): $response");
        }
        if (strpos($location, self::REDIRECT_URI) !== 0) {
            throw new RuntimeException("Authorization endpoint redirected somewhere unexpected: $location");
        }
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        return $query;
    }

    private function writeSettings(): void
    {
        file_put_contents("$this->dir/settings.json", json_encode($this->settings));
    }

    private static function newKey(): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        return $pem;
    }

    private static function freePort(): int
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $name = stream_socket_get_name($server, false);
        fclose($server);
        return (int)substr($name, strrpos($name, ':') + 1);
    }
}
