<?php
// Produces a real, valid PlanZ session (via an actual login) and then lets a test
// mutate it before saving it under a fresh session id — for exercising session states
// that are impractical to reach through the normal login flow, like "the participant's
// password changed after they logged in" or "their permission set changed mid-session".
//
// This reads and writes real PHP session files via the session_* functions (not by
// guessing paths), so it works the same way locally and in CI regardless of
// session.save_path.
final class SessionForge
{
    /**
     * @param callable(array<string,mixed> &$session): void $mutate Receives the real,
     *     logged-in session data by reference; mutate it however the test needs.
     */
    public static function create(callable $mutate): string
    {
        $client = new HttpClient();
        $client->login(PLANZ_TEST_EMAIL, PLANZ_TEST_PASSWORD);
        $realSessionId = $client->sessionId();
        if ($realSessionId === null) {
            throw new RuntimeException('Login did not produce a session cookie — cannot forge a session from it.');
        }

        session_id($realSessionId);
        session_start();
        $sessionData = $_SESSION;
        session_abort(); // discard without touching the real session file

        $forgedSessionId = bin2hex(random_bytes(16));
        session_id($forgedSessionId);
        session_start();
        $_SESSION = $sessionData;
        $mutate($_SESSION);
        session_write_close();

        return $forgedSessionId;
    }
}
