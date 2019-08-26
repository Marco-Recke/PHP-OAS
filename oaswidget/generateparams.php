<?php

require_once("./config.php");

/**
 * Parameter- generator for graphprovider
 */

$login = $config['username'].":".$config['password']."@";

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

//Identifier in $GET. % values are not allowed.
$id = trim($_REQUEST['identifier'],"%");


//content
$content = $_REQUEST['content'];
