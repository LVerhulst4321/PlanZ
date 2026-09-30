<?php

use PHPUnit\Framework\TestCase;

// Exercises webpages/validation_functions.php's validate_participant_availability()
// directly, with no DB or HTTP involved. The function reads/writes through
// `global $partAvail, $messages;`, so tests populate $GLOBALS['partAvail'] before
// calling it, matching how SubmitMySchedConstr.php uses it.
final class ValidateParticipantAvailabilityTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['partAvail'], $GLOBALS['messages']);
    }

    private function baseValidAvail(): array
    {
        $avail = [
            'maxprog' => 3,
            'numkidsfasttrack' => 0,
        ];
        for ($day = 1; $day <= CON_NUM_DAYS; $day++) {
            $avail["maxprogday$day"] = 1;
        }
        for ($i = 1; $i <= AVAILABILITY_ROWS; $i++) {
            $avail["availstartday_$i"] = 0;
            $avail["availstarttime_$i"] = 0;
            $avail["availendday_$i"] = 0;
            $avail["availendtime_$i"] = 0;
        }
        return $avail;
    }

    public function testFullyBlankScheduleIsValid(): void
    {
        $GLOBALS['partAvail'] = $this->baseValidAvail();
        $this->assertTrue(validate_participant_availability());
    }

    public function testOneCompleteAvailabilitySlotIsValid(): void
    {
        $avail = $this->baseValidAvail();
        $avail['availstartday_1'] = 1;
        $avail['availstarttime_1'] = 1;
        $avail['availendday_1'] = 1;
        $avail['availendtime_1'] = 3;
        $GLOBALS['partAvail'] = $avail;

        $this->assertTrue(validate_participant_availability());
    }

    public function testPartiallyFilledSlotIsRejected(): void
    {
        if (CON_NUM_DAYS <= 1) {
            $this->markTestSkipped('Day fields are only collected for a multi-day con.');
        }
        $avail = $this->baseValidAvail();
        // Start day/time set, but end day/time left blank.
        $avail['availstartday_1'] = 1;
        $avail['availstarttime_1'] = 1;
        $GLOBALS['partAvail'] = $avail;

        $this->assertFalse(validate_participant_availability());
    }

    public function testEndBeforeStartIsRejected(): void
    {
        $avail = $this->baseValidAvail();
        $avail['availstartday_1'] = 2;
        $avail['availstarttime_1'] = 3;
        $avail['availendday_1'] = 2;
        $avail['availendtime_1'] = 1; // ends before it starts, same day
        $GLOBALS['partAvail'] = $avail;

        $this->assertFalse(validate_participant_availability());
    }

    public function testMaxProgOverLimitIsRejected(): void
    {
        $avail = $this->baseValidAvail();
        $avail['maxprog'] = PREF_TTL_SESNS_LMT + 1;
        $GLOBALS['partAvail'] = $avail;

        $this->assertFalse(validate_participant_availability());
    }
}
