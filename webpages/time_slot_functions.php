<?php

function time_to_row_index($time, $rowSize = 15) {
    $index1 = strpos($time, ':');
    $hours = intval(substr($time, 0, $index1));
    $minutes = intval(substr($time, $index1+1, 2));
    return ($hours * 60 + $minutes) / $rowSize;
}

?>