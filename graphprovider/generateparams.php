<?php

require_once("./config.php");

/**
 * Parameter- generator for graphprovider
 *
 * @author Paul Borchert <paul.borchert@gbv.de>
 * @author Marc Giesmann <giesmann@sub.uni-goettingen.de> for VZG Göttingen
 * @package graphprovider
 * @version 0.1
 */

$login = $config['username'].":".$config['password']."@";

//The first letter of the interval parameter (day, month or year) is equal to the DateInterval- parameter (D/M/Y)
if(isset($_REQUEST['granularity'])){
    $granularity = $_REQUEST['granularity'];
}else{
    $granularity = $config['granularity'];
}
$dateintervalparameter = strtoupper(substr($granularity, 0, 1));

if(isset($_REQUEST['until'])){
    $until = new DateTime($_REQUEST['until']);
}else{
    if(isset($config['until'])){
        $until = new DateTime($config['until']);
    }else{
        //UNTIL = TODAY
        $until = new DateTime("today");
    }
}
$until = $until->format('Y-m-d');

if(isset($_REQUEST['from'])){
    $from = new DateTime($_REQUEST['from']);
}else{
    
    if(isset($config['from'])){
        $from = new DateTime($config['from']);
    }else{
        //FROM = UNTIL minus $config['interval']
        $from = new DateTime($until);
        $from->sub(new DateInterval("P".$config['interval'].$dateintervalparameter));
    }
}

$from = $from->format('Y-m-d');

//Identifier in $GET. % values are not allowed.
$id = trim($_REQUEST['identifier'],"%");

isset($_REQUEST['formatExtension'])? $formatExtension=$_REQUEST['formatExtension'] : $formatExtension='json';


//content
if(isset($_REQUEST['content'])){
    $content = $_REQUEST['content'];
}else{
    $content = $config['content'];
}