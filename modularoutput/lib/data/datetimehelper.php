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
		'week' 	=> 'Y-\WW',
		'month' => 'Y-m',
		'year' 	=> 'Y'
	);

	public static function getStartDateOfPeriod($date,$period)
	{
		// sets params run by getDate()
		$params = array(
			'week' => 'monday',
			'month' => 'first day of this month',
			'year' => 'first day of january '
		);
		return self::getDate($date,$period,$params);
	}

	public static function getEndDateOfPeriod($date,$period)
	{
		// sets params run by getDate()
		$params = array(
			'week' => 'sunday',
			'month' => 'last day of this month',
			'year' => 'last day of december '
		);
		return self::getDate($date,$period,$params);
	}

	private static function getDate($date,$period,$params) {
		if (!$date instanceof DateTime) {
			/*
				workaround for php not understanding a year only string in his strtotime function
				this regex checks for a year from 2000-2999
			*/
			if (preg_match('/^[2][0-9]{3}$/',$date)) {
				$dateTime = new DateTime($date."-01-01");
			} else {
				$dateTime = new DateTime($date);
			}
		} else {
			$dateTime = clone $date;
		}
		switch ($period) {
			case 'week':
				$dateTime->modify(($dateTime->format('w') == '0' ? $params['week'] . " last week" : $params['week'] . " this week"));
				break;

			case 'month':
				$dateTime->modify($params['month']);
				break;

			case 'year':
				$dateTime->modify($params['year'] . $dateTime->format('Y'));
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