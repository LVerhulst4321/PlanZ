<?php
// Minimal curl-based HTTP client for Functional tests. Deliberately dependency-free
// (no Guzzle) so the root composer.json only needs PHPUnit.

final class HttpClient
{
    private string $baseUrl;
    private string $cookieJar;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? getenv('PLANZ_TEST_BASE_URL') ?: 'https://planz.ddev.site', '/');
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'planz-test-cookies-');
    }

    public function __destruct()
    {
        if (is_file($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }

    /** @param array<string,string> $fields */
    public function post(string $path, array $fields): array
    {
        return $this->request('POST', $path, $fields);
    }

    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    // Sends a request under an explicit session id instead of this client's own cookie
    // jar — for exercising a session forged by SessionForge (e.g. one with a stale
    // password hash) rather than one this client actually logged in with.
    /** @param array<string,string> $fields */
    public function postWithSessionId(string $sessionId, string $path, array $fields): array
    {
        return $this->request('POST', $path, $fields, "PHPSESSID=$sessionId");
    }

    // The PHPSESSID this client is currently using, if it has made any request yet.
    public function sessionId(): ?string
    {
        if (!is_file($this->cookieJar)) {
            return null;
        }
        foreach (file($this->cookieJar) as $line) {
            if (preg_match('/\bPHPSESSID\t(\S+)/', $line, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /** @param array<string,string> $fields */
    private function request(string $method, string $path, array $fields = [], ?string $rawCookie = null): array
    {
        $ch = curl_init($this->baseUrl . $path);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            // The test target is either a local DDEV site (self-signed cert) or a
            // plain http:// PHP built-in server in CI — never a public endpoint.
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => 15,
        ];
        if ($rawCookie !== null) {
            $options[CURLOPT_COOKIE] = $rawCookie;
        } else {
            $options[CURLOPT_COOKIEJAR] = $this->cookieJar;
            $options[CURLOPT_COOKIEFILE] = $this->cookieJar;
        }
        curl_setopt_array($ch, $options);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        }
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("HTTP $method $path failed: $error");
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $status, 'body' => $body];
    }

    public function login(string $badgeidOrEmail, string $password): array
    {
        return $this->post('/doLogin.php', [
            'badgeid' => $badgeidOrEmail,
            'passwd' => $password,
        ]);
    }
}
