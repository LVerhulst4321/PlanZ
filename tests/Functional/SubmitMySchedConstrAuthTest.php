<?php

use PHPUnit\Framework\TestCase;

// Regression coverage for the session/permission ordering bug found alongside the
// utf8mb4 one: SubmitMySchedConstr.php used to perform its database writes before
// checking whether the session was still valid, and the only check that existed
// (participant_header()'s isLoggedIn() re-verification) ran at the very end, after
// renderMySchedConstr.php had already decided what to render. A session that went
// stale between page-load and submit (e.g. the participant's password changed, or
// their role lost the my_availability permission) could therefore have its write
// either succeed or fail with no confirmation ever reaching the user — see
// SubmitMySchedConstr.php's isLoggedIn()/may_I() check, added right after the
// includes and before any POST data is read.
final class SubmitMySchedConstrAuthTest extends TestCase
{
    protected function setUp(): void
    {
        TestDb::resetFixture();
    }

    protected function tearDown(): void
    {
        TestDb::dropFixture();
    }

    /** @param array<string,string> $overrides */
    private function payload(array $overrides = []): array
    {
        $fields = [
            'maxprog' => '3',
            'availstartday_1' => '1',
            'availstarttime_1' => '1',
            'availendday_1' => '1',
            'availendtime_1' => '3',
            'preventconflict' => 'SHOULD NOT BE SAVED',
            'otherconstraints' => 'SHOULD NOT BE SAVED',
        ];
        for ($day = 1; $day <= CON_NUM_DAYS; $day++) {
            $fields["maxprogday$day"] = '1';
        }
        for ($i = 2; $i <= AVAILABILITY_ROWS; $i++) {
            $fields["availstartday_$i"] = '0';
            $fields["availstarttime_$i"] = '0';
            $fields["availendday_$i"] = '0';
            $fields["availendtime_$i"] = '0';
        }
        return array_merge($fields, $overrides);
    }

    public function testStaleSessionBlocksTheWriteAndShowsAMessage(): void
    {
        $sessionId = SessionForge::create(function (array &$session): void {
            // Same effect as the participant's password changing after they logged in:
            // isLoggedIn()'s DB re-check will no longer match this cached hash.
            $session['hashedPassword'] = str_repeat('x', 60);
        });

        $client = new HttpClient();
        $response = $client->postWithSessionId($sessionId, '/SubmitMySchedConstr.php', $this->payload());

        $this->assertStringNotContainsString('Fatal error', $response['body']);
        $this->assertNotEmpty(trim(strip_tags($response['body'])), 'A blocked save must never return a blank page.');
        $this->assertStringContainsString(
            'alert-danger',
            $response['body'],
            'A stale session must show a clear error rather than silently doing nothing.'
        );
        $this->assertNull(
            TestDb::fetchAvailability(),
            'The write must not happen when the session is no longer valid.'
        );
    }

    public function testSessionMissingPermissionBlocksTheWriteAndShowsAMessage(): void
    {
        $sessionId = SessionForge::create(function (array &$session): void {
            // Same effect as a staff member revoking the participant's my_availability
            // permission after they logged in but before they submitted the form. The
            // 'Participant' tag is kept so the page still renders a normal (not
            // logged-out) header.
            $session['permission_set'] = array_values(array_filter(
                $session['permission_set'],
                fn($tag) => $tag !== 'my_availability'
            ));
        });

        $client = new HttpClient();
        $response = $client->postWithSessionId($sessionId, '/SubmitMySchedConstr.php', $this->payload());

        $this->assertStringContainsString(
            'You do not currently have permission',
            $response['body']
        );
        $this->assertNull(
            TestDb::fetchAvailability(),
            'The write must not happen once the my_availability permission is gone.'
        );
    }
}
