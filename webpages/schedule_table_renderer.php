<?php

require_once(__DIR__ . '/con_data.php');

function determine_con_start_date() {
    $conData = ConData::fromEnvironmentDefinition();
    return $conData->startDate;
}

interface ScheduleCellData {
    public function getStartIndex();
    public function getRowHeight();
    public function getColumnWidth();
    public function getAdditionalClasses();
    public function getData();
}

interface ScheduleCellDataProvider {
    public function findFirstStartIndexForDay($day);
    public function findLastEndIndexForDay($day);
    public function isCellDataAvailableForDay($day);
    public function getCellDataFor($row, $column, $day);
}

class ScheduleTableRenderer {

    private $rooms;
    private $dataProvider;
    public $showRoomArea = false;

    public function __construct($rooms, $dataProvider) {
        $this->rooms = $rooms;
        $this->dataProvider = $dataProvider;
    }

    private function buildTableHeaderRows(&$headerRows, $rooms, $rowNumber) {
        $header = $headerRows[$rowNumber];
        if ($rowNumber == 0) {
            $header .= "<th rowSpan=\"" . count($headerRows) . "\">Time</th>";
        }
        foreach ($rooms as $value) {
            $width = $value->getColumnWidth() > 1 ? "colspan=\"{$value->getColumnWidth()}\"" : "";
            $height = count($headerRows) - $rowNumber - $value->getRowHeight() + 1;
            $rowHeight = $height == 1 ? "" : "rowspan=\"$height\"";
            if ($this->showRoomArea) {
                $header .= "<th $rowHeight $width>" . $value->roomName
                    . ($value->isOnline ? " <span class=\"small\"><br />(Online)</span>" : "")
                    . ($value->area ? ("<span class=\"small\"><br />" . number_format($value->area) . " sq ft</span>") : "")
                    . "</th>";
            } else {
                $header .= "<th $rowHeight $width>" . $value->roomName . "</th>";
            }

            if ($value->children && count($value->children) > 0) {
                $this->buildTableHeaderRows($headerRows, $value->children, $rowNumber + 1);
            }
        }
        $headerRows[$rowNumber] = $header;
    }

    function renderTableHeader() {
        $maxRows = 1;
        foreach ($this->rooms as $r) {
            $maxRows = max($r->getRowHeight(), $maxRows);
        }
        $headerRows = array();
        for ($i = 0; $i < $maxRows; $i++) {
            $headerRows[] = "";
        }
        $this->buildTableHeaderRows($headerRows, $this->rooms, 0);
        echo "<thead>";
        foreach ($headerRows as $header) {
            echo "<tr>" . $header . "</tr>";
        }
        echo "</thead>";
    }

    function renderTable() {

        echo <<<EOD
        <ul class="nav nav-tabs" id="day_tabs" role="tablist">
EOD;
        $startDate = determine_con_start_date();
        for ($day = 0; $day < CON_NUM_DAYS; $day++) {
            echo "<li class=\"nav-item\">";
            echo "<a class=\"nav-link ";
            echo ($day === 0 ? "active" : "");
            echo "\" data-toggle=\"tab\" href=\"#";
            echo mb_strtolower($startDate->format('D_d_M'));
            echo "\" role=\"tab\" aria-selected=\"";
            echo ($day === 0 ? "true" : "false");
            echo "\">";
            echo $startDate->format('D');
            echo "</a></li>";

            $startDate->add(new DateInterval('P1D'));
        }

        echo <<<EOD
        </ul>
        <div class="tab-content">
EOD;

        $lastRoom = $this->rooms[count($this->rooms)-1];
        $maxColumns = $lastRoom->columnNumber + $lastRoom->getColumnWidth() - 1;

        $startDate = determine_con_start_date();
        for ($day = 0; $day < CON_NUM_DAYS; $day++) {
            $classes = ($day === 0) ? " show active" : "";
            $id = mb_strtolower($startDate->format('D_d_M'));
            echo <<<EOD
            <div role="tabpanel" class="tab-pane fade $classes" id="$id">
            <table class="table table-sm table-bordered">
EOD;

            $this->renderTableHeader();


            echo "<tbody>";
            echo "<tr><th colspan=\"" . ($maxColumns + 2) . "\">" . $startDate->format('D, d M') . "</th></tr>";

            if ($this->dataProvider->isCellDataAvailableForDay($day)) {
                $fromIndex = $this->dataProvider->findFirstStartIndexForDay($day);
                $toIndex = $this->dataProvider->findLastEndIndexForDay($day);

                for ($row = $fromIndex; $row <= $toIndex; $row++) {
                    echo "<tr>";
                    echo "<th class=\"bg-light small\">";
                    if ($row % 4 == 0 || $row == $fromIndex) {
                        $hours = floor($row / 4);
                        if ($hours > 23) {
                            $hours -= 24;
                        }
                        $minutes = str_pad(($row % 4) * 15, 2, "0", STR_PAD_LEFT);

                        echo "$hours:$minutes";
                    } else {
                        echo "&nbsp;";
                    }
                    echo "</th>";

                    for ($column = 0; $column <= $maxColumns; $column++) {
                        $slot = $this->dataProvider->getCellDataFor($row, $column, $day);
                        if ($slot) {
                            if ($slot->getStartIndex() == $row && $slot->room->columnNumber == $column) {
                                $classes = $slot->getAdditionalClasses() ? $slot->getAdditionalClasses() : "";
                                echo "<td class=\"small $classes\" rowspan=\"" . $slot->getRowHeight() . "\" colspan=\"" . $slot->getColumnWidth() . "\">" . $slot->getData() . "</td>";
                            }
                        } else {
                            echo "<td class=\"bg-light small\">&nbsp;</td>";
                        }
                    }
                    echo "</tr>";
                }
            } else {
                echo "<tr><td class=\"bg-light\" colspan=\"" . ($maxColumns + 2) . "\">None</td></tr>";
            }
            $startDate->add(new DateInterval('P1D'));
            echo "</tbody>";
            echo <<<EOD
            </table>
            </div>
EOD;
        }
        echo <<<EOD
        </div>
EOD;
    }
}


?>