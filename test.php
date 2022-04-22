<?php

date_default_timezone_set('Asia/Manila'); // set default timezone to manila
$curr_date = date("Y/m/d"); // date
$curr_time = date("h:i:sa"); // time

$finalDate = $curr_date." ".date('h:i:sa', strtotime('+3 hours'));
echo $finalDate;
?>