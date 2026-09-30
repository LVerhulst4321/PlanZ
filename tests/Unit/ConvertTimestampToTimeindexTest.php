<?php

use PHPUnit\Framework\TestCase;

// Exercises webpages/my_sched_constr_func.php's convert_timestamp_to_timeindex(),
// which turns a stored "hh:mm:ss" / "hhh:mm:ss" availability timestamp back into a
// (day, Times-table-index) pair. It's pure given a DOMXPath, so we build a small
// synthetic Times XML document rather than hitting the real DB.
final class ConvertTimestampToTimeindexTest extends TestCase
{
    private function buildTimesXPath(): DOMXPath
    {
        $xml = new DOMDocument();
        $doc = $xml->appendChild($xml->createElement('doc'));
        $query = $doc->appendChild($xml->createElement('query'));

        $rows = [
            ['timeid' => 1, 'timevalue' => '10:00:00', 'next_day' => 0, 'avail_start' => 1, 'avail_end' => 0],
            ['timeid' => 2, 'timevalue' => '12:00:00', 'next_day' => 0, 'avail_start' => 1, 'avail_end' => 1],
            ['timeid' => 3, 'timevalue' => '02:00:00', 'next_day' => 1, 'avail_start' => 0, 'avail_end' => 1],
        ];
        foreach ($rows as $attributes) {
            $row = $query->appendChild($xml->createElement('row'));
            foreach ($attributes as $name => $value) {
                $row->setAttribute($name, (string) $value);
            }
        }

        return new DOMXPath($xml);
    }

    public function testSameDayStartTime(): void
    {
        $xpath = $this->buildTimesXPath();

        $result = convert_timestamp_to_timeindex($xpath, '10:00:00', true);

        // day is computed via floor(), so it comes back as a float — compare by value.
        $this->assertEquals(1, $result['day']);
        $this->assertSame('1', $result['hour']);
    }

    public function testHourPastMidnightRollsOverToNextDay(): void
    {
        $xpath = $this->buildTimesXPath();

        // 26:00:00 = 2am, one day after the con's day-1 start.
        $result = convert_timestamp_to_timeindex($xpath, '26:00:00', false);

        $this->assertEquals(1, $result['day']);
        $this->assertSame('3', $result['hour']);
    }

    public function testTimeNotInTimesTableReturnsUnsetIndex(): void
    {
        $xpath = $this->buildTimesXPath();

        $result = convert_timestamp_to_timeindex($xpath, '23:15:00', true);

        $this->assertSame('0', $result['hour']);
    }
}
