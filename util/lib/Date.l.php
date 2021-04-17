<?php
namespace util;

/*

Copyright (C) 2006 Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/**
 * Translated dates handling
 *
 * @author Vincent Guth
 */
class DateLib {

	const DATE = 'date';

	/**
	 * Compare two dates
	 *
	 * @param mixed $date1
	 * @param mixed $date2
	 * @return int 0 if the dates are equal, < 0 if $date1 is the smallest or > 0 if $date1 is the biggest
	 */
	public static function compare($date1, $date2): int {

		if(is_int($date1)) {
			$compare1 = date('Y-m-d H:i:s', $date1);
		} else {
			$date = new \DateTime($date1);
			$compare1 = $date->format('Y-m-d H:i:s');
		}

		if(is_int($date2)) {
			$compare2 = date('Y-m-d H:i:s', $date2);
		} else {
			$date = new \DateTime($date2);
			$compare2 = $date->format('Y-m-d H:i:s');
		}

		return strcmp($compare1, $compare2);

	}

	/**
	 * Get a timestamp from a YYYY-MM-DD HH:MM:SS
	 *
	 * @param string $date The date
	 */
	public static function timestamp(string $date): string {

		if(ctype_digit($date)) {
			return (int)$date;
		}

		if(strlen($date) === 10) {
			$date .= ' 00:00:00';
		}

		return mktime(
			(int)substr($date, 11, 2),
			(int)substr($date, 14, 2),
			(int)substr($date, 17, 2),
			(int)substr($date, 5, 2),
			(int)substr($date, 8, 2),
			(int)substr($date, 0, 4)
		);

	}

	/**
	 * Get YYYY-MM-DD HH:MM:SS date from a timestamp
	 *
	 * @param string $date The date
	 * @param string $format Date format
	 */
	public static function date(string $date, string $format): string {

		switch($format) {
			case self::DATE :
				return date('Y-m-d', $date);
			case self::DATE_TIME :
				return date('Y-m-d H:i:s', $date);
			case self::TIME :
				return date('H:i:s', $date);
		}

	}

	/**
	 * Checks if a string match YYYY-MM-DD date
	 *
	 * @param string $date The date
	 * @param string $format Date format
	 */
	public static function isValid(string $date): string {

		return checkdate(substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4));

	}


	/**
	 * Gets the date from a week number of the current year by default
	 * @param int $week
	 * @param int $year
	 * @param int $format
	 * @return The formated date
	 */
	public static function weekToDate(int $week, int $year = 0, int $format = DateUi::DATE_TIME): string {

		setType($week, 'int');

		if($week < 1 or $week > 53) {
			return NULL;
		} else {
			$year		= ($year) ? $year : date("Y");
			$mktime 	= mktime (0, 0, 0, 01, (01+(($week-1)*7)), $year);
			$lag    	= ((date("w",$mktime)-1)*60*60*24);
			$date   	= $mktime - $lag;
			$timeStamp	= self::timestamp(date("Y-m-d H:m:s", $date));
		}
		return self::date($timeStamp, $format);
	}

	/**
	 * Modify a date, add or substract (hour, day, month, year, . . .)
	 * @var string $date YYYY-MM-DD [HH:MM:SS]
	 * @var string $modification type of modification, add or sub
	 * @var string $format DATE_TIME, DATE, TIME
	 */
	private static function modifyDate(string $date, string $modification, int $format): string {
		switch($format) {
			case DateUi::DATE :
				$formatDate = "%Y-%m-%d";
				break;
			case DateUi::TIME :
				$formatDate = "%H:%M:%S";
				break;
			case DateUi::DATE_TIME :
			default:
				$formatDate = "%Y-%m-%d %H:%M:%S";
				break;
		}
		return strftime($formatDate, strtotime($modification, strtotime($date)));
	}

	/**
	 * Add to a date (hour, day, month, year, . . .)
	 * @var string $date YYYY-MM-DD [HH:MM:SS]
	 * @var string $modification type of data to add (year, month, day, hour, . . .)
	 * @var string $format DATE_TIME, DATE, TIME
	 */
	public static function addToDate(string $date, string $modification, int $format = DateUi::DATE_TIME): string {
		return self::modifyDate($date, '+'.$modification, $format);
	}

	/**
	 * Sub to a date (hour, day, month, year, . . .)
	 * @var string $date YYYY-MM-DD [HH:MM:SS]
	 * @var string $modification type of data to sub (year, month, day, hour, . . .)
	 * @var string $format DATE_TIME, DATE, TIME
	 */
	public static function subFromDate(string $date, string $modification, string $format = DateUi::DATE_TIME): string {
		return self::modifyDate($date, '-'.$modification, $format);
	}

	/**
	 * Return interval timestamp between two date
	 * @param string $maxDate date/datetime/time
	 * @param string $minDate date/datetime/time
	 */
	public static function interval(string $maxDate, string $minDate): string {

		$maxDate = self::timestamp($maxDate);
		$minDate = self::timestamp($minDate);

		return ($maxDate - $minDate);

	}

	/**
	 * Return the week number of the given date.
	 * Valid formats are either YYYY-MM-DD or YYYY-MM-DD HH:MM:SS.
	 *
	 * @param string $date
	 * @return int
	 */
	public static function getWeekNumber(string $date): int {
		return (int)date('W', self::timestamp($date));
	}

	/*
	 * ISO-8601 :
	 * - First week of a year contains 4th of January OR contains 1th Thursday of January
	 * - Many of years get 52 weeks, but year which begins by Thursday and leap year begins on Wednesday get 53 weeks
	 * - First day of a week is Monday, last day is sunday
	 */
	public static function weekBoundary(int $week, int $year): array {

		$dateRange = [];

		// January first day
		$januaryFirstDay = mktime(0,0,0,1,1, $year);

		// Thursday first week of $year
		$thursdayYearFirstWeek = (date('N', $januaryFirstDay) === 4) ? $januaryFirstDay : strtotime('thursday', $januaryFirstDay);

		// Thursday of $week that we want to find
		$thursdayWeek = strtotime("+".($week-1)." weeks", $thursdayYearFirstWeek);

		// Monday of $week that we want to find
		$mondayWeek = strtotime("last monday", $thursdayWeek);

		// Sunday of $week that we want to find
		$sundayWeek = strtotime("sunday", $thursdayWeek);

		$dateRange["year"] = $year;
		$dateRange["week"] = $week;
		$dateRange["monday"]= date("Y-m-d", $mondayWeek);
		$dateRange["sunday"] = date("Y-m-d",$sundayWeek);

		return $dateRange;

	}

	/**
	 * return age at today date
	 * if $dateRef != null compute age at $dateRef date
	 * @author julien delsescaux
	 * @return int
	 */
	public static function getAge(string $date, string $dateRef = NULL): int {

		$month = (int)substr($date, 5, 2);
		$day = (int)substr($date, 8, 2);
		$year = (int)substr($date, 0, 4);

		if($dateRef === NULL) {
			$d = (int)date('d') - $day;
			$m = (int)date('m') - $month;
			$y = (int)date('Y') - $year;
		} else {
			$d = (int)substr($dateRef, 8, 2) - $day;
			$m = (int)substr($dateRef, 5, 2) - $month;
			$y = (int)substr($dateRef, 0, 4) - $year;
		}
		return $y + max(-1, min(0, $m * 40 + $d));
	}

	/**
	 * Convert date to new timezone
	 *
	 * @param String $date Date string
	 * @param String $timezoneFrom Current timezone of the date
	 * @param String $timezoneTo Timezone wish
	 * @throws Exception : when timezone given is incorrect
	 * @see DateTime
	 * @see DateTimeZone
	 * @return String Date in the new timezone
	 */
	public static function convertTimezone(string $date, string $timezoneFrom, string $timezoneTo): string {

		if(
			isTimeZone($timezoneTo) and
			(isTimeZone($timezoneFrom) or $timezoneFrom === NULL)
		) {

			$datetime = new \DateTime($date, $timezoneFrom ? new \DateTimeZone($timezoneFrom) : NULL);
			$datetime->setTimezone(new \DateTimeZone($timezoneTo));

			return $datetime->format('Y-m-d H:i:s');
		} else {
			throw new \Exception('Timezone incorrect');
		}
	}

	/**
	 * Return offset between two time-zone in seconds
	 *
	 * @param string $fromTZ TimeZone from wich to convert
	 * @param string $toTZ TimeZone to convert
	 * @return int Offset in seconds between the two time-zone
	 * @example getTimeZoneOffset('Europe/Paris', 'America/Guatemala') ===> return 28800
	 */
	public static function getTimeZoneOffset(string $fromTZ, string $toTZ = NULL): int {

		if($toTZ === NULL) {
			if(!is_string($toTZ = date_default_timezone_get())) {
				return FALSE;
			}
		}

		$toDTZ = new DateTimeZone($toTZ);
		$fromDTZ = new DateTimeZone($fromTZ);

		$datetime = new DateTime("now", $fromDTZ);

		return $fromDTZ->getOffset($datetime) - $toDTZ->getOffset($datetime);

	}

}
?>
