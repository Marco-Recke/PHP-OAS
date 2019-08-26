<?php

require_once("./config.php");

/**
 * Parameter- generator for graphprovider
 */

// we use the login data as used on the website
$login = $_SERVER['PHP_AUTH_USER'].":".$_SERVER['PHP_AUTH_PW']."@";

$granularity = $_REQUEST['granularity'];

if(isset($_REQUEST['until'])){
    $until = new DateTime($_REQUEST['until']);
}
$until = $until->format('Y-m-d');

// the from date is set either from the the given from date or the given interval date
if(isset($_REQUEST['from'])){
    $from = new DateTime($_REQUEST['from']);
}else if(isset($_REQUEST['interval'])) {
    //FROM = UNTIL minus $config['interval']
    $from = new DateTime($until);
    //The first letter of the interval parameter (day, week or  month) is equal to the DateInterval- parameter (D/W/M)
    $dateintervalparameter = strtoupper(substr($granularity, 0, 1));

    $from->sub(new DateInterval("P".$_REQUEST['interval'].$dateintervalparameter));
}
$from = $from->format('Y-m-d');

//content
$content = $_REQUEST['content'];

$id = $_REQUEST['identifier'];

if (isset($_REQUEST['rep'])) {
    $rep = $_REQUEST['rep'];
}

if (isset($_SESSION['currentrep'])) {
    $rep = $_SESSION['currentrep'];
}