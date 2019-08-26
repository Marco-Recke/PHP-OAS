<?php

/**
* Helper class for handling DateTime Objects
*/
class DateTimeHelper
{
	public static $periodInterval = array(
	    'day' 	=> 'P1D',
	    'week' 	=> 'P1W',
	    'month' => 'P1M',
	    'year' 	=> 'P1Y'
    );

    public static $periodFormat = array(
		'day' 	=> 'Y-m-d',
		'week' 	=> 'W Y',
		'month' => 'Y-m',
		'year' 	=> 'Y'
	);

	public static function getStartDateOfPeriod($date,$period)
	{
		if (!$date instanceof DateTime)
			$dateTime = new DateTime($date);
		else
			$dateTime = clone $date;
		switch ($period) {
			case 'week':
				$dateTime->modify(($dateTime->format('w') == '0' ? "monday last week" : "monday this week"));
				break;

			case 'month':
				$dateTime->modify("first day of this month");
				break;

			case 'year':
				$dateTime->modify("first day of january" . $dateTime->format('Y'));
				break;

			default:
		}
		return $dateTime;
	}

	public static function getEndDateOfPeriod($date,$period)
	{
		if (!$date instanceof DateTime)
			$dateTime = new DateTime($date);
		else
			$dateTime = clone $date;
		switch ($period) {
			case 'week':
				$dateTime->modify(($dateTime->format('w') == '0' ? "sunday last week" : "sunday this week"));
				break;

			case 'month':
				$dateTime->modify("last day of this month");
				break;

			case 'year':
				$dateTime->modify("last day of december" . $dateTime->format('Y'));
				break;

			default:
		}
		return $dateTime;
	}

	public static function getPeriodInterval($period)
	{
		if (array_key_exists($period, self::$periodInterval)) {
			return self::$periodInterval[$period];
		}
		return false;
	}

	public static function getPeriodFormat($period)
	{
		if (array_key_exists($period, self::$periodFormat)) {
			return self::$periodFormat[$period];
		}
		return false;
	}
}