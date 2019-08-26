<?php
/**
 * A simple wrapper for calling outcli multiple times to split the given date duration
 * into multiple files
 */

require_once(dirname(__FILE__).'/lib/data/datetimehelper.php');

function buildArgumentList($args)
{
	$passedArgs = ' ';
	foreach (array_slice($args,1) as $arg) {
		$passedArgs .= $arg . ' ';
	}
	return $passedArgs;
}

// gets the necessary values from the arguments
for($i=1;$i<sizeof($argv);$i++) {
	switch ($argv[$i]) {
		case '-f':
			$posFrom = $i+1;
			$from = $argv[$posFrom];
			break;
		case '-u':
			$posUntil = $i+1;
			$until = $argv[$posUntil];
			break;
		case '-g':
			$granularity = $argv[$i+1];
			break;
		default:
			break;
	}
}

$date	= new DateTime($from);
$until 	= new DateTime($until);

// sets default values if not set in argument list
if (!isset($from))
	$from = '-3 days';
if (!isset($granularity))
	$until = '-3 days';
if (!isset($granularity))
	$granularity = 'day';


do {
	$startDate 	= clone DateTimeHelper::getStartDateOfPeriod($date,$granularity);
    $endDate 	= clone DateTimeHelper::getEndDateOfPeriod($date,$granularity);

    // prepares the argument list for the next call
	$argv[$posFrom] 	= $startDate->format(DateTimeHelper::getPeriodFormat($granularity));
	$argv[$posUntil] 	= $endDate->format(DateTimeHelper::getPeriodFormat($granularity));

	// run outcli.php with date duration
	$passedArgs = buildArgumentList($argv);
	passthru('php outcli.php' . $passedArgs);

	$date->add(new DateInterval(DateTimeHelper::getPeriodInterval($granularity)));
} while ($date <= DateTimeHelper::getEndDateOfPeriod($until,$granularity));

?>