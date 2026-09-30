<?php

use PHPUnit\Framework\TestCase;

// Regression test for Fix #4: SubmitMySchedConstr.php wraps its four writes
// (ParticipantAvailability, ParticipantAvailabilityTimes per row,
// ParticipantAvailabilityDays, and the cleanup DELETE) in a single transaction, so a
// failure partway through can't leave partial data behind.
//
// There's no normal HTTP-level input that reaches a later write without also being
// valid for the first one — the only failure mode found so far (a 4-byte UTF-8
// character in a free-text field) fails on the very first statement, where there's
// nothing yet to roll back. So this test forces the failure directly: temporarily
// renaming ParticipantAvailabilityDays out of the way so its REPLACE fails *after* the
// ParticipantAvailability and ParticipantAvailabilityTimes writes have already
// succeeded, then checks that neither of those survived.
final class SubmitMySchedConstrTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        TestDb::resetFixture();
    }

    protected function tearDown(): void
    {
        // Safety net: restore the table before dropFixture() touches it, even if the
        // test body didn't get that far (e.g. it failed an assertion first).
        TestDb::restoreTable('ParticipantAvailabilityDays');
        TestDb::dropFixture();
    }

    public function testFailurePartwayThroughRollsBackEverything(): void
    {
        $client = new HttpClient();
        $client->login(PLANZ_TEST_EMAIL, PLANZ_TEST_PASSWORD);

        $fields = [
            'maxprog' => '3',
            'availstartday_1' => '1',
            'availstarttime_1' => '1',
            'availendday_1' => '1',
            'availendtime_1' => '3',
            'preventconflict' => 'SHOULD NOT SURVIVE A ROLLBACK',
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

        // ParticipantAvailability (write 1) and ParticipantAvailabilityTimes (write 2)
        // will succeed normally; ParticipantAvailabilityDays (write 3) will fail
        // because the table is gone.
        TestDb::disableTable('ParticipantAvailabilityDays');
        $response = $client->post('/SubmitMySchedConstr.php', $fields);
        TestDb::restoreTable('ParticipantAvailabilityDays');

        $this->assertStringContainsString(
            'Error updating database',
            $response['body'],
            'A write failure partway through must surface as an error, not a silent partial save.'
        );
        $this->assertNull(
            TestDb::fetchAvailability(),
            'ParticipantAvailability must not survive the rollback, even though its own write succeeded before the failure.'
        );
        $this->assertSame(
            [],
            TestDb::fetchAvailabilityTimes(),
            'ParticipantAvailabilityTimes must not survive the rollback, even though its own write succeeded before the failure.'
        );
    }
}
