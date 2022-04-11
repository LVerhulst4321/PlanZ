<?php

class ConData {
    public $name;
    public $startDate;
    public $endDate;
    public $numberOfDays;

    public static function fromEnvironmentDefinition() {
        $result = new ConData();
        $result->name = CON_NAME;

        $timeZone = PHP_DEFAULT_TIMEZONE;
        $dateSrc = CON_START_DATIM;
    
        $result->startDate = new DateTime($dateSrc, new DateTimeZone($timeZone));
        $endTime = new DateTime($dateSrc, new DateTimeZone($timeZone));
        $endTime->add(new DateInterval('P'.CON_NUM_DAYS.'D'));
        $endTime->sub(new DateInterval('PT1H'));
        $result->endDate = $endTime;

        $result->numberOfDays = CON_NUM_DAYS;

        return $result;
    }
}

?>