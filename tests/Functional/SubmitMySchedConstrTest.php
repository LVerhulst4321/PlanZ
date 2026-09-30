<?php

use PHPUnit\Framework\TestCase;

// End-to-end regression coverage for webpages/SubmitMySchedConstr.php, run as an
// authenticated HTTP client against a live copy of the app (DDEV locally, PHP's
// built-in server in CI) plus direct DB assertions. No production code is required
// for these tests to run — that's what makes them usable to prove a fix works
// without first changing anything.
final class SubmitMySchedConstrTest extends TestCase
{
    private HttpClient $client;

    protected function setUp(): void
    {
        TestDb::resetFixture();
        $this->client = new HttpClient();
        $login = $this->client->login(PLANZ_TEST_EMAIL, PLANZ_TEST_PASSWORD);
        $this->assertStringContainsString(
            'My Availability',
            $this->client->get('/my_sched_constr.php')['body'],
            'Fixture login did not reach the My Availability page — check TestDb::resetFixture() permissions setup.'
        );
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
            'preventconflict' => '',
            'otherconstraints' => '',
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

    public function testValidSubmissionIsConfirmedAndPersisted(): void
    {
        $response = $this->client->post('/SubmitMySchedConstr.php', $this->payload([
            'preventconflict' => 'No panels before 10am please',
        ]));

        $this->assertStringContainsString('Database updated successfully', $response['body']);

        $row = TestDb::fetchAvailability();
        $this->assertNotNull($row, 'Save was confirmed but no ParticipantAvailability row exists.');
        $this->assertSame('3', $row['maxprog']);
        $this->assertSame('No panels before 10am please', $row['preventconflict']);

        $times = TestDb::fetchAvailabilityTimes();
        $this->assertCount(1, $times, 'Expected exactly the one submitted availability slot to be saved.');
    }

    public function testInvalidSubmissionShowsErrorAndSavesNothing(): void
    {
        $response = $this->client->post('/SubmitMySchedConstr.php', $this->payload([
            // End time before start time on the same day: validate_participant_availability()
            // should reject this.
            'availstartday_1' => '2',
            'availstarttime_1' => '3',
            'availendday_1' => '2',
            'availendtime_1' => '1',
        ]));

        $this->assertStringContainsString(
            'alert-danger',
            $response['body'],
            'Invalid input must show a visible error, not save silently.'
        );
        $this->assertNull(
            TestDb::fetchAvailability(),
            'Validation was supposed to fail — nothing should have been written to the database.'
        );
    }

    // Regression test for the bug reported against my_sched_constr.php: a participant
    // could save successfully with no visible error, but the data never actually
    // persisted. Root cause: PHP 8.1 changed mysqli's default error-reporting mode to
    // throw mysqli_sql_exception instead of returning false, but this app was written
    // assuming the old "returns false" behavior and never calls
    // mysqli_report(MYSQLI_REPORT_OFF). A 4-byte UTF-8 character (e.g. an emoji) in a
    // free-text field fails against the ParticipantAvailability table's utf8 (3-byte)
    // charset, which used to trigger the app's own friendly "Error updating database"
    // message and now instead throws an uncaught exception straight through — a blank
    // or broken page, and nothing saved, with no error shown to the user.
    public function testEmojiInFreeTextDoesNotCrashOrSilentlyDropTheSave(): void
    {
        $response = $this->client->post('/SubmitMySchedConstr.php', $this->payload([
            'preventconflict' => "No conflicts please \u{1F600}",
        ]));

        $this->assertStringNotContainsString(
            'Fatal error',
            $response['body'],
            'A save must never surface a raw PHP fatal error to the user.'
        );
        $this->assertNotEmpty(
            trim(strip_tags($response['body'])),
            'A save attempt must never return a blank page with no feedback at all.'
        );

        $row = TestDb::fetchAvailability();
        $claimsSuccess = str_contains($response['body'], 'Database updated successfully');

        if ($claimsSuccess) {
            $this->assertNotNull($row, 'Page claimed success, but no row was persisted.');
            $this->assertStringContainsString(
                'No conflicts please',
                $row['preventconflict'] ?? '',
                'Page claimed success, but the saved value does not match what was submitted.'
            );
        } else {
            $this->assertStringContainsString(
                'alert-danger',
                $response['body'],
                'Save was not confirmed, so the page must clearly show an error instead of staying silent.'
            );
        }
    }
}
