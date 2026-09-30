<?php
// Direct DB access for Functional tests: sets up and tears down the dedicated
// PLANZ_TEST_BADGEID fixture, and lets tests assert on persisted state independently
// of whatever the app's HTTP response claims happened.

final class TestDb
{
    private static ?mysqli $connection = null;

    public static function connection(): mysqli
    {
        if (self::$connection === null) {
            self::$connection = mysqli_connect(DBHOSTNAME, DBUSERID, DBPASSWORD, DBDB);
            if (!self::$connection) {
                throw new RuntimeException('Could not connect to the test database: ' . mysqli_connect_error());
            }
            mysqli_set_charset(self::$connection, 'utf8');
        }
        return self::$connection;
    }

    // Removes any trace of the fixture participant, then recreates it with a known
    // password and the 'my_availability' permission. Safe to call before every test.
    public static function resetFixture(): void
    {
        $db = self::connection();
        $badgeid = PLANZ_TEST_BADGEID;

        foreach ([
            'ParticipantAvailabilityTimes',
            'ParticipantAvailabilityDays',
            'ParticipantAvailability',
            'UserHasPermissionRole',
            'CongoDump',
            'Participants',
        ] as $table) {
            mysqli_query($db, "DELETE FROM `$table` WHERE badgeid = '$badgeid'");
        }

        mysqli_query($db, "
            INSERT INTO Participants (badgeid, password, pubsname, data_retention)
            VALUES ('$badgeid', '" . PLANZ_TEST_PASSWORD_HASH . "', 'PlanZ Test User', 1)
        ");
        mysqli_query($db, "
            INSERT INTO CongoDump (badgeid, firstname, lastname, badgename, email)
            VALUES ('$badgeid', 'PlanZ', 'Test User', 'PlanZ Test User', '" . PLANZ_TEST_EMAIL . "')
        ");
        // Role 3 = 'Program Participant', which grants the 'my_availability' permission
        // atom for phase 3 ('Availability') — see Install/EmptyDbase.dump.
        mysqli_query($db, "INSERT INTO UserHasPermissionRole (badgeid, permroleid) VALUES ('$badgeid', 3)");
        // The 'Availability' phase isn't current by default in a fresh install; turn
        // it on so the fixture user actually has access to My Availability.
        mysqli_query($db, "UPDATE Phases SET current = 1 WHERE phaseid = 3");
    }

    public static function dropFixture(): void
    {
        $db = self::connection();
        $badgeid = PLANZ_TEST_BADGEID;
        foreach ([
            'ParticipantAvailabilityTimes',
            'ParticipantAvailabilityDays',
            'ParticipantAvailability',
            'UserHasPermissionRole',
            'CongoDump',
            'Participants',
        ] as $table) {
            mysqli_query($db, "DELETE FROM `$table` WHERE badgeid = '$badgeid'");
        }
    }

    public static function fetchAvailability(): ?array
    {
        $db = self::connection();
        $badgeid = PLANZ_TEST_BADGEID;
        $result = mysqli_query($db, "SELECT * FROM ParticipantAvailability WHERE badgeid = '$badgeid'");
        $row = mysqli_fetch_assoc($result);
        return $row ?: null;
    }

    // Renames a table out of the way so the next write against it fails — used to
    // prove SubmitMySchedConstr.php's transaction actually rolls back a failure that
    // happens partway through the save, not just on the very first statement.
    public static function disableTable(string $table): void
    {
        $db = self::connection();
        mysqli_query($db, "RENAME TABLE `$table` TO `{$table}_test_disabled`");
    }

    // Idempotent: safe to call even if the table was never disabled, or was already
    // restored, so it's safe to use as an unconditional tearDown() safety net.
    public static function restoreTable(string $table): void
    {
        $db = self::connection();
        $result = mysqli_query($db, "SHOW TABLES LIKE '{$table}_test_disabled'");
        if (mysqli_num_rows($result) > 0) {
            mysqli_query($db, "RENAME TABLE `{$table}_test_disabled` TO `$table`");
        }
    }

    public static function fetchAvailabilityTimes(): array
    {
        $db = self::connection();
        $badgeid = PLANZ_TEST_BADGEID;
        $result = mysqli_query($db, "
            SELECT availabilitynum, TIME_FORMAT(starttime, '%T') AS starttime, TIME_FORMAT(endtime, '%T') AS endtime
            FROM ParticipantAvailabilityTimes
            WHERE badgeid = '$badgeid'
            ORDER BY availabilitynum
        ");
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }
}
