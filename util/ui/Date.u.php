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
class DateUi {

	/**
	 * Date type
	 *
	 * @var int
	 */
	const DATE = 1;

	/**
	 * Date/hour type
	 *
	 * @var int
	 */
	const DATE_TIME = 2;

	/**
	 * Date/hour/minute type
	 *
	 * @var int
	 */
	const DATE_HOUR_MINUTE = 4;

	/**
	 * Hour type
	 *
	 * @var int
	 */
	const TIME = 8;

	/**
	 * Hour/minute type
	 *
	 * @var int
	 */
	const TIME_HOUR_MINUTE = 128;

	/**
	 * Exclude time if it is midnight
	 *
	 * @var int
	 */
	const NO_TIME_IF_MIDNIGHT = 32;

	/**
	 * Month YEAR type
	 *
	 * @var int
	 */
	const MONTH_YEAR = 64;

	/**
	 * Day month type
	 *
	 * @var int
	 */
	const DAY_MONTH = 16;

	/**
	 * Duration type
	 *
	 * @var int
	 */
	const LONG = 0;

	/**
	 * Duration type
	 *
	 * @var int
	 */
	const SHORT = 1;

	/**
	 * Duration type
	 *
	 * @var int
	 */
	const AGO = 2;

	/**
	 * Default timeZone
	 *
	 * @var string
	 */
	protected static ?string $timeZone = NULL;

	/**
	 * Change default timeZone
	 *
	 * @param string $timeZone
	 */
	public static function setTimeZone($timeZone) {
		self::$timeZone = $timeZone;
	}

	/**
	 * Get default timeZone
	 *
	 * @return mixed A string or NULL
	 */
	public static function getTimeZone() {
		return self::$timeZone;
	}

	/**
	 * Get date order
	 *
	 * @return string
	 */
	public static function order(): array {

		switch(\L::getLang()) {

			case 'en' :
			case 'ar_AE' :
			case 'zh_CN' :
			case 'zh_TW' :
			case 'ja_JP' :
			case 'lt_LT' :
				return ['Y', 'M', 'D'];

			case 'en_US' :
				return ['M', 'D', 'Y'];

			default :
				return ['D', 'M', 'Y'];

		}

	}

	/**
	 * Get a numeric date
	 *
	 * @param string $date The YYYY-MM-DD [HH:MM:SS] date
	 * @param string $format Date format
	 */
	public static function numeric(string $date, int $format = self::DATE_TIME, string $timeZone = NULL): string {

		if(strlen($date) === 19) {
			$timeZone = is_null($timeZone) ? self::$timeZone : $timeZone;
			if($timeZone !== NULL) {
				$date = getDateTime($date, $timeZone)->format('Y-m-d H:i:s');
			}
		}

		$day = (int)substr($date, 8, 2);
		$month = (int)substr($date, 5, 2);
		$year = (int)substr($date, 0, 4);

		$return = '';

		if(!($format & self::TIME or $format & self::TIME_HOUR_MINUTE)) {

			switch(\L::getLang()) {

				case 'fr_FR' :
				case 'it_IT' :
				case 'nl_NL' :
				case 'pt_PT' :
				case 'pt_BR' :
				case 'sv_SE' :
				case 'el_GR' :
				case 'no_NO' :
				case 'en_GB' :
				case 'en_CA' :
				case 'he_IL' :
				case 'ro_RO' :
				case 'sl_SI' :

					if($format & self::MONTH_YEAR) {
						return sprintf("%02d/%04d", $month, $year);
					}

					$return .= sprintf("%02d/%02d/%04d", $day, $month, $year);
					if($format & self::DAY_MONTH) {
						$return = substr($return, 0, -5);
					}
					break;

				case 'ar_AE' :
				case 'hu_HU' :
				case 'en' :
				case 'ko_KR' :
				case 'lt_LT' :

					if($format & self::MONTH_YEAR) {
						return sprintf("%02d-%04d", $month, $year);
					}

					$return .= sprintf("%04d-%02d-%02d", $year, $month, $day);
					if($format & self::DAY_MONTH) {
						$return = substr($return, 5);
					}
					break;

				case 'en_US' :

					if($format & self::MONTH_YEAR) {
						return sprintf("%02d/%04d", $month, $year);
					}

					$return .= sprintf("%02d/%02d/%04d", $month, $day, $year);
					if($format & self::DAY_MONTH) {
						$return = substr($return, 0, -5);
					}
					break;

				case 'de_DE' :
				case 'tr_TR' :
				case 'pl_PL' :
				case 'fi_FI' :
				case 'da_DK' :
				case 'cs_CZ' :
				case 'bg_BG' :
				case 'lv_LV' :
				case 'ru_RU' :
				case 'ru_UA' :
				case 'sk_SK' :

					if($format & self::MONTH_YEAR) {
						return sprintf("%02d.%04d", $month, $year);
					}

					$return .= sprintf("%02d.%02d.%04d", $day, $month, $year);
					if($format & self::DAY_MONTH) {
						$return = substr($return, 0, -5);
					}
					break;

				case 'es_ES' :

					if($format & self::MONTH_YEAR) {
						return sprintf("%02d-%04d", $month, $year);
					}

					$return .= sprintf("%02d-%02d-%04d", $day, $month, $year);
					if($format & self::DAY_MONTH) {
						$return = substr($return, 0, -5);
					}
					break;

				case 'zh_CN' :
				case 'zh_TW' :
				case 'ja_JP' :

					if($format & self::MONTH_YEAR) {
						return sprintf("%02d.04d", $month, $year);
					}

					$return .= sprintf("%04d.%02d.%02d", $year, $month, $day);
					if($format & self::DAY_MONTH) {
						$return = substr($return, 5);
					}
					break;

			}

		}

		self::addTime($return, $format, $date);

		return $return;

	}

	/**
	 * Get a textual date
	 *
	 * @param string $date The YYYY-MM-DD [HH:MM:SS] date
	 * @param string $format Date format
	 */
	public static function textual(string $date, string $format = self::DATE_TIME, string $timeZone = NULL): string {

		if(strlen($date) === 19) {
			$timeZone = is_null($timeZone) ? self::$timeZone : $timeZone;
			if($timeZone !== NULL) {
				$date = getDateTime($date, $timeZone)->format('Y-m-d H:i:s');
			}
		}

		$day = (int)substr($date, 8, 2);
		$month = (int)substr($date, 5, 2);

		if($month < 1 or $month > 12) {
			return '?';
		}

		$return = '';

		if($format & self::MONTH_YEAR) {
			return self::getMonthName($month).' '.substr($date, 0, 4);
		}


		if(!($format & self::TIME or $format & self::TIME_HOUR_MINUTE)) {

			switch(\L::getLang()) {

				case 'fr_FR' :

					$return = $day;

					if($day === 1) {
						$return .= "er";
					}

					$return .= ' '.self::getMonthName($month);

					if(!($format & self::DAY_MONTH)) {
						$return .= ' '.substr($date, 0, 4);
					}

					break;

				case 'el_GR' :

					$return = $day;

					if($day === 1) {
						$return .= "??";
					}

					$return .= ' '.self::getMonthName($month);

					if(!($format & self::DAY_MONTH)) {
						$return .= ' '.substr($date, 0, 4);
					}

					break;

				case 'ar_AE' :
				case 'he_IL' :
				case 'ru_RU' :
				case 'es_ES' :
				case 'it_IT' :
				case 'tr_TR' :
				case 'sv_SE' :
				case 'ro_RO' :
				case 'pl_PL' :

					$return = $day.' '.self::getMonthName($month);

					if(!($format & self::DAY_MONTH)) {
						$return .= ' '.substr($date, 0, 4);
					}

					break;

				case 'en_GB' :
				case 'en_CA' :
				case 'en' :

					$return = $day;

					if($day%10 === 1 and $day !== 11) {
						$return .= "st";
					} elseif($day%10 === 2 and $day !== 12) {
						$return .= "nd";
					} elseif($day%10 === 3 and $day !== 13) {
						$return .= "rd";
					} else {
						$return .= "th";
					}

					$return .= ' '.ucfirst(self::getMonthName($month));

					if($format & self::DAY_MONTH === FALSE) {
						$return .= ' '.substr($date, 0, 4);
					}
					break;

				case 'en_US' :

					$return = self::getMonthName($month).' '.$day;

					if($format & self::DAY_MONTH === FALSE) {
						$return .= ', '.substr($date, 0, 4);
					}

					break;

				case 'pt_PT' :
				case 'pt_BR' :

					$return = $day.' de '.self::getMonthName($month);

					if($format & self::DAY_MONTH === FALSE) {
						$return .= ' de '.substr($date, 0, 4);
					}

					break;

				case 'de_DE' :
				case 'nl_NL' :
				case 'fi_FI' :
				case 'da_DK' :
				case 'cs_CZ' :
				case 'no_NO' :
				case 'sl_SI' :
				case 'sk_SK' :

					$return = $day.'. '.self::getMonthName($month);

					if($format & self::DAY_MONTH === FALSE) {
						$return .= ' '.substr($date, 0, 4);
					}

					break;

				case 'zh_CN' :
				case 'zh_TW' :

					$return = '';
					if($format & self::DAY_MONTH === FALSE) {
						$return .= substr($date, 0, 4).' ??? ';
					}
					$return = ''.self::getMonthName($month).' '.$day.' ???';

					break;

				case 'ja_JP' :

					$return = '';
					if($format & self::DAY_MONTH === FALSE) {
						$return .= substr($date, 0, 4).'???';
					}
					$return = ''.self::getMonthName($month).' '.$day.'???';

					break;

				case 'ko_KR' :

					$return = '';
					if($format & self::DAY_MONTH === FALSE) {
						$return .= substr($date, 0, 4).'??? ';
					}
					$return = ''.self::getMonthName($month).' '.$day.'???';

					break;

				case 'hu_HU' :

					if($format & self::DAY_MONTH === FALSE) {
						$return = '';
					} else {
						$return = substr($date, 0, 4);
					}
					$return .= ''.self::getMonthName($month).' '.$day.'.';
					break;

				case 'bg_BG' :
				case 'ru_UA' :

					$return = $day.' '.self::getMonthName($month);

					if($format & self::DAY_MONTH === FALSE) {
						$return .= ' '.substr($date, 0, 4).' ??.';
					}

					break;

				case 'lt_LT' :

					$return = 'm. '.self::getMonthName($month).' '.$day.' d.';

					if($format & self::DAY_MONTH === FALSE) {
						$return = substr($date, 0, 4).' '.$return;
					}

					break;

				case 'lv_LV' :

					if($format & self::DAY_MONTH === FALSE) {
						$return = 'gada '.$day.'. '.self::getMonthName($month);
					} else {
						$return = $day.'. '.self::getMonthName($month);
					}

					if($format & self::DAY_MONTH === FALSE) {
						$return = substr($date, 0, 4).'. '.$return;
					}

					break;


			}

		}

		self::addTime($return, $format, $date);

		return $return;

	}

	/**
	 * Returns months names
	 *
	 * @param bool $short if the short version is required
	 *
	 * @return array
	 */
	public static function months(bool $short = FALSE): array {

		switch(\L::getLang()) {

			case 'fr_FR' :
				if($short) {
					return [
						1 => 'janv',
						2 => 'f??v',
						3 => 'mars',
						4 => 'avr',
						5 => 'mai',
						6 => 'juin',
						7 => 'juil',
						8 => 'ao??t',
						9 => 'sept',
						10 => 'oct',
						11 => 'nov',
						12 => 'd??c'
					];
				}
				return [
					1 => 'janvier',
					2 => 'f??vrier',
					3 => 'mars',
					4 => 'avril',
					5 => 'mai',
					6 => 'juin',
					7 => 'juillet',
					8 => 'ao??t',
					9 => 'septembre',
					10 => 'octobre',
					11 => 'novembre',
					12 => 'd??cembre'
				];

			case 'ar_AE' :
				return [
					1 => '??????????',
					2 => '????????????',
					3 => '????????',
					4 => '??????????',
					5 => '????????',
					6 => '??????????',
					7 => '??????????',
					8 => '??????????',
					9 => '????????????',
					10 => '????????????',
					11 => '????????????',
					12 => '????????????'
				];

			case 'it_IT' :
				return [
					1 => 'gennaio',
					2 => 'febbraio',
					3 => 'marzo',
					4 => 'aprile',
					5 => 'maggio',
					6 => 'giugno',
					7 => 'luglio',
					8 => 'agosto',
					9 => 'settembre',
					10 => 'ottobre',
					11 => 'novembre',
					12 => 'dicembre'
				];

			case 'nl_NL' :
				return [
					1 => 'Januari',
					2 => 'Februari',
					3 => 'Maart',
					4 => 'April',
					5 => 'Mei',
					6 => 'Juni',
					7 => 'Juli',
					8 => 'Augustus',
					9 => 'September',
					10 => 'Oktober',
					11 => 'November',
					12 => 'December',
				];

			case 'pl_PL' :
				return [
					1 => 'stycznia',
					2 => 'lutego',
					3 => 'marca',
					4 => 'kwietnia',
					5 => 'maja',
					6 => 'czerwca',
					7 => 'lipca',
					8 => 'sierpnia',
					9 => 'wrze??nia',
					10 => 'pa??dziernika',
					11 => 'listopada',
					12 => 'grudnia',
				];

			case 'pt_PT' :
			case 'pt_BR' :
				return [
					1 => 'janeiro',
					2 => 'fevereiro',
					3 => 'mar??o',
					4 => 'abril',
					5 => 'maio',
					6 => 'junho',
					7 => 'julho',
					8 => 'agosto',
					9 => 'setembro',
					10 => 'outubro',
					11 => 'novembro',
					12 => 'dezembro',
				];

			case 'sv_SE' :
				return [
					1 => 'januari',
					2 => 'februari',
					3 => 'mars',
					4 => 'april',
					5 => 'maj',
					6 => 'juni',
					7 => 'juli',
					8 => 'augusti',
					9 => 'september',
					10 => 'oktober',
					11 => 'november',
					12 => 'december',
				];

			case 'tr_TR' :
				return [
					1 => 'Ocak',
					2 => '??ubat',
					3 => 'Mart',
					4 => 'Nisan',
					5 => 'May??s',
					6 => 'Haziran',
					7 => 'Temmuz',
					8 => 'A??ustos',
					9 => 'Eyl??l',
					10 => 'Ekim',
					11 => 'Kas??m',
					12 => 'Aral??k',
				];

			case 'he_IL' :
				return [
					1 => '??????????',
					2 => '????????????',
					3 => '??????',
					4 => '??????????',
					5 => '??????',
					6 => '????????',
					7 => '????????',
					8 => '????????????',
					9 => '????????????',
					10 => '??????????????',
					11 => '????????????',
					12 => '??????????',
				];

			case 'en_GB' :
			case 'en_US' :
			case 'en_CA' :
			case 'en' :
				return [
					1 => 'January',
					2 => 'February',
					3 => 'March',
					4 => 'April',
					5 => 'May',
					6 => 'June',
					7 => 'July',
					8 => 'August',
					9 => 'September',
					10 => 'October',
					11 => 'November',
					12 => 'December'
				];

			case 'de_DE' :
				return [
					1 => "Januar",
					2 => "Februar",
					3 => "M??rz",
					4 => "April",
					5 => "Mai",
					6 => "Juni",
					7 => "Juli",
					8 => "August",
					9 => "September",
					10 => "Oktober",
					11 => "November",
					12 => "Dezember"
				];

			case 'es_ES' :
				return [
					1 => "Enero",
					2 => "Febrero",
					3 => "Marzo",
					4 => "Abril",
					5 => "Mayo",
					6 => "Junio",
					7 => "Julio",
					8 => "Agosto",
					9 => "Septiembre",
					10 => "Octubre",
					11 => "Noviembre",
					12 => "Diciembre"
				];

			case 'ru_RU' :
				return [
					1 => "????????????",
					2 => "??????????????",
					3 => "??????????",
					4 => "????????????",
					5 => "??????",
					6 => "????????",
					7 => "????????",
					8 => "??????????????",
					9 => "????????????????",
					10 => "??????????????",
					11 => "????????????",
					12 => "??????????????"
				];

			case 'zh_CN' :
			case 'zh_TW' :
				return [
					1 => '1???',
					2 => '2???',
					3 => '3???',
					4 => '4???',
					5 => '5???',
					6 => '6???',
					7 => '7???',
					8 => '8???',
					9 => '9???',
					10 => '10???',
					11 => '11???',
					12 => '12???'
				];

			case 'el_GR' :
				return [
					1 => '????????????????????',
					2 => '??????????????????????',
					3 => '??????????????',
					4 => '????????????????',
					5 => '??????????',
					6 => '??????????????',
					7 => '??????????????',
					8 => '??????????????????',
					9 => '??????????????????????',
					10 => '??????????????????',
					11 => '??????????????????',
					12 => '????????????????????'
				];

			case 'fi_FI' :
				return [
					1 => 'tammikuu',
					2 => 'helmikuu',
					3 => 'maaliskuu',
					4 => 'huhtikuu',
					5 => 'toukokuu',
					6 => 'kes??kuu',
					7 => 'hein??kuu',
					8 => 'elokuu',
					9 => 'syyskuu',
					10 => 'lokakuu',
					11 => 'marraskuu',
					12 => 'joulukuu'
				];

			case 'da_DK' :
				return [
					1 => 'januar',
					2 => 'februar',
					3 => 'marts',
					4 => 'april',
					5 => 'maj',
					6 => 'juni',
					7 => 'juli',
					8 => 'august',
					9 => 'september',
					10 => 'oktober',
					11 => 'november',
					12 => 'december'
				];

			case 'cs_CZ' :
				return [
					1 => 'leden',
					2 => '??nor',
					3 => 'b??ezen',
					4 => 'duben',
					5 => 'kv??ten',
					6 => '??erven',
					7 => '??ervenec',
					8 => 'srpen',
					9 => 'z??????',
					10 => '????jen',
					11 => 'listopad',
					12 => 'prosinec'
				];

			case 'no_NO' :
				return [
					1 => 'januar',
					2 => 'februar',
					3 => 'mars',
					4 => 'april',
					5 => 'mai',
					6 => 'juni',
					7 => 'juli',
					8 => 'august',
					9 => 'september',
					10 => 'oktober',
					11 => 'november',
					12 => 'desember'
				];

			case 'hu_HU' :
				return [
					1 => 'janu??r',
					2 => 'febru??r',
					3 => 'm??rcius',
					4 => '??prilis',
					5 => 'm??jus',
					6 => 'j??nius',
					7 => 'j??lius',
					8 => 'augusztus',
					9 => 'szeptember',
					10 => 'okt??ber',
					11 => 'november',
					12 => 'december'
				];

			case 'ja_JP' :
				return [
					1 => '1???',
					2 => '2???',
					3 => '3???',
					4 => '4???',
					5 => '5???',
					6 => '6???',
					7 => '7???',
					8 => '8???',
					9 => '9???',
					10 => '10???',
					11 => '11???',
					12 => '12???'
				];

			case 'ko_KR' :
				return [
					1 => '1???',
					2 => '2???',
					3 => '3???',
					4 => '4???',
					5 => '5???',
					6 => '6???',
					7 => '7???',
					8 => '8???',
					9 => '9???',
					10 => '10???',
					11 => '11???',
					12 => '12???'
				];

			case 'ro_RO' :
				return [
					1 => 'ianuarie',
					2 => 'februarie',
					3 => 'martie',
					4 => 'aprilie',
					5 => 'mai',
					6 => 'iunie',
					7 => 'iulie',
					8 => 'august',
					9 => 'septembrie',
					10 => 'octombrie',
					11 => 'noiembrie',
					12 => 'decembrie'
				];

			case 'bg_BG' :
				return [
					1 => '????????????',
					2 => '????????????????',
					3 => '????????',
					4 => '??????????',
					5 => '??????',
					6 => '??????',
					7 => '??????',
					8 => '????????????',
					9 => '??????????????????',
					10 => '????????????????',
					11 => '??????????????',
					12 => '????????????????'
				];

			case 'lt_LT' : // Versions longues. Versions courtes : sausis, vasaris, kovas, balandis, gegu????, bir??elis, liepa, rugpj??tis, rugs??jis, spalis, lapkritis, gruodis
				return [
					1 => 'sausio',
					2 => 'vasario',
					3 => 'kovo',
					4 => 'baland??io',
					5 => 'gegu????s',
					6 => 'bir??elio',
					7 => 'liepos',
					8 => 'rugpj????io',
					9 => 'rugs??jo',
					10 => 'spalio',
					11 => 'lapkri??io',
					12 => 'gruod??io'
				];

			case 'lv_LV' :
				return [
					1 => 'janv??ris',
					2 => 'febru??ris',
					3 => 'marts',
					4 => 'apr??lis',
					5 => 'maijs',
					6 => 'j??nijs',
					7 => 'j??lijs',
					8 => 'augusts',
					9 => 'septembris',
					10 => 'oktobris',
					11 => 'novembris',
					12 => 'decembris'
				];

			case 'ru_UA' :
				return [
					1 => '????????????',
					2 => '??????????????',
					3 => '??????????',
					4 => '????????????',
					5 => '??????',
					6 => '????????',
					7 => '????????',
					8 => '??????????????',
					9 => '????????????????',
					10 => '??????????????',
					11 => '????????????',
					12 => '??????????????'
				];

			case 'sl_SI' :
				return [
					1 => 'januar',
					2 => 'februar',
					3 => 'marec',
					4 => 'april',
					5 => 'maj',
					6 => 'junij',
					7 => 'julij',
					8 => 'avgust',
					9 => 'september',
					10 => 'oktober',
					11 => 'november',
					12 => 'december'
				];

			case 'sk_SK' :
				return [
					1 => 'janu??ra',
					2 => 'febru??ra',
					3 => 'marca',
					4 => 'apr??la',
					5 => 'm??ja',
					6 => 'j??na',
					7 => 'j??la',
					8 => 'augusta',
					9 => 'septembra',
					10 => 'okt??bra',
					11 => 'novembra',
					12 => 'decembra'
				];

			default :
				return [];

		}

	}

	/**
	 * Gets the date of a day of a week
	 *
	 */
	public static function weekToDays(string $week, bool $short = FALSE, bool $withYear = TRUE): string {

		if($short) {

			$callback = function($from, $to) {

				if(substr($from, 5, 2)=== substr($to, 5, 2)) {
					return (int)substr($from, 8, 2).' - '.(int)substr($to, 8, 2).'<br/>'.\util\DateUi::getMonthName(substr($to, 5, 2), TRUE);
				} else {
					return (int)substr($from, 8, 2).' '.\util\DateUi::getMonthName(substr($from, 5, 2), TRUE).' -<br/>'.(int)substr($to, 8, 2).' '.\util\DateUi::getMonthName(substr($to, 5, 2), TRUE);
				}

			};

		} else {

			$callback = function($from, $to): string {

				if(substr($from, 5, 2)=== substr($to, 5, 2)) {
					return (int)substr($from, 8, 2).' - '.DateUi::textual($to, DateUi::DAY_MONTH);
				} else {
					return DateUi::textual($from, DateUi::DAY_MONTH).' - '.DateUi::textual($to, DateUi::DAY_MONTH);
				}

			};

		}


		$from = date('Y-m-d', strtotime($week));
		$to = date('Y-m-d', strtotime($week.' + 6 days'));
		$days = $callback($from, $to);

		if($withYear) {
			$days .= ' '.substr($to, 0, 4);
		}

		return $days;

	}

	/**
	 * Returns the textual difference between now and the given date
	 * See >secondsToDuration()
	 *
	 * @param string $date
	 * @param string $format
	 * @param string $mode D for day, H for hours, M for minutes, S for seconds
	 * @param string $maxNumber Max number of period entries
	 *
	 */
	public static function ago(string $date, int $format = self::AGO, string $mode = NULL, int $maxNumber = 1): string {

		$seconds = time() - DateLib::timestamp($date);

		return self::secondToDuration($seconds, $format, $mode, $maxNumber);

	}

	/**
	 * Returns a duration in the format
	 *
	 * fr_FR : tt j xx h yy min zz sec or tt jours xx heures yy minutes zz secondes
	 * en_US : tt d xx h yy min zz sec tt days xx hours yy minutes zz seconds
	 *
	 * @param int $duration
	 * @param int $format
	 * @param string $mode D for day, H for hours, M for minutes, S for seconds
	 * @param int $maxNumber Max number of period entries
	 *
	 */
	public static function secondToDuration(int $duration, int $format = self::SHORT, string $mode = NULL, int $maxNumber = NULL): ?string {

		if($mode === NULL) {
			$mode = 'DHMS';
		} else {
			$mode = strtoupper($mode);
		}

		if(strpos($mode, 'Y') !== FALSE) {
			$years = (int)floor($duration / (365 * 86400));
			$duration -= $years * 365 * 86400;
		} else {
			$years = 0;
		}

		if(strpos($mode, 'O') !== FALSE) {
			$months = (int)floor($duration / (30 * 86400));
			$duration -= $months * 30 * 86400;
		} else {
			$months = 0;
		}

		if(strpos($mode, 'D') !== FALSE) {
			$days = (int)floor($duration / 86400);
			$duration -= $days * 86400;
		} else {
			$days = 0;
		}

		if(strpos($mode, 'H') !== FALSE) {
			$hours = (int)floor($duration / 3600);
			$duration -= $hours * 3600;
		} else {
			$hours = 0;
		}

		if(strpos($mode, 'M') !== FALSE) {
			$minutes = (int)floor($duration / 60);
			$duration -= $minutes * 60;
		} else {
			$minutes = 0;
		}

		if(strpos($mode, 'S') !== FALSE) {
			$seconds = $duration;
		} else {
			$seconds = 0;
		}

		$result = '';
		$nResult = 0;

		if(
			($maxNumber === NULL or $nResult < $maxNumber) and
			$years > 0
		) {

			$nResult++;
			$result .= self::getYears($years, $format);

		}

		if(
			($maxNumber === NULL or $nResult < $maxNumber) and
			$months > 0
		) {

			$nResult++;

			if($nResult > 1) {
				$result .= ' ';
			}

			$result .= self::getMonths($months, $format);

		}

		if(
			($maxNumber === NULL or $nResult < $maxNumber) and
			$days > 0
		) {

			$nResult++;

			if($nResult > 1) {
				$result .= ' ';
			}

			if($format === self::LONG or $format === self::AGO) {
				$result .= self::getDays($days, $format);
			} else {

				switch(\L::getLang()) {

					case 'fr_FR' :
						$result .= sprintf("%d j", $days);
						break;

					case 'it_IT' :
						$result .= sprintf("%d g", $days);
						break;

					case 'tr_TR' :
						$result .= sprintf("%d gn.", $days);
						break;

					case 'pl_PL' :
						$result .= sprintf("%d dz.", $days);
						break;

					case 'ar_AE' :
						$result .= sprintf("%d ??????", $days);
						break;

					case 'el_GR' :
						$result .= sprintf("%d ??", $days);
						break;

					case 'fi_FI' :
						$result .= sprintf("%d pv", $days);
						break;
					case 'de_DE' :
						$result .= sprintf("%d T", $days);
						break;

					case 'en_GB' :
					case 'en_US' :
					case 'en_CA' :
					case 'en' :
					case 'es_ES' :
					case 'zh_CN' :
					case 'zh_TW' :
					case 'pt_PT' :
					case 'pt_BR' :
					case 'nl_NL' :
					case 'sv_SE' :
					case 'da_DK' :
					case 'cs_CZ' :
					case 'no_NO' :
					case 'ko_KR' :
						$result .= sprintf("%d d", $days);
						break;

					case 'he_IL' :
						$result .= sprintf("%d ????????", $days);
						break;

					case 'hu_HU' :
						$result .= sprintf("%d n", $days);
						break;

					case 'ja_JP' :
						$result .= sprintf("%d ???", $days);
						break;

					case 'ro_RO' :
						$result = sprintf("%d z", $days);
						break;

					case 'bg_BG' :
						$result = sprintf("%d ??.", $days);
						break;

					case 'lt_LT' :
						$result = sprintf("%d d.", $days);
						break;

					case 'lv_LV' :
					case 'sl_SI' :
						$result = sprintf("%dd", $days);
						break;

					case 'ru_UA' :
						$result = sprintf("%d ??.", $days);
						break;

					case 'sk_SK' :
					case 'ru_RU' :
						$result = self::getDays($days, $format);
						break;

				}
			}
		}

		if(
			($maxNumber === NULL or $nResult < $maxNumber) and
			$hours > 0
		) {

			$nResult++;

			if($nResult > 1) {
				$result .= ' ';
			}

			if($format === self::LONG) {
				$result .= self::getHours($hours);
			} else if($format === self::AGO) {
				if($days === 0) {
					$result .= self::getHours($hours);
				}
			}  else {

				switch(\L::getLang()) {
					case 'fr_FR' :
					case 'en_GB' :
					case 'en_US' :
					case 'en_CA' :
					case 'en' :
					case 'de_DE' :
					case 'es_ES' :
					case 'pt_PT' :
					case 'pt_BR' :
					case 'cs_CZ' :
					case 'ko_KR' :
					case 'ro_RO' :
					case 'sk_SK' :
						$result .= sprintf("%d h", $hours);
						break;
					case 'ar_AE' :
						$result .= sprintf("%d ????????", $hours);
						break;
					case 'pl_PL' :
						$result .= sprintf("%d godz.", $hours);
						break;
					case 'sv_SE' :
						$result .= sprintf("%d tim", $hours);
						break;
					case 'nl_NL' :
						$result .= sprintf("%d u", $hours);
						break;
					case 'tr_TR' :
						$result .= sprintf("%d s.", $hours);
						break;
					case 'it_IT' :
						if($hours === 1 or $hours === 0) {
							$result .= sprintf("%d ora", $hours);
						} else {
							$result .= sprintf("%d ore", $hours);
						}
						break;
					case 'ru_RU' :
					case 'ru_UA' :
						$result .= sprintf("%d ??", $hours);
						break;
					case 'zh_CN' :
					case 'zh_TW' :
						$result .= sprintf("%d ??????", $hours);
						break;
					case 'el_GR' :
						$result .= sprintf("%d ??", $hours);
						break;
					case 'fi_FI' :
					case 'da_DK' :
					case 'no_NO' :
						$result .= sprintf("%d t", $hours);
						break;
					case 'he_IL' :
						$result .= sprintf("%d ??'", $hours);
						break;
					case 'hu_HU' :
						$result .= sprintf("%d ??", $hours);
						break;
					case 'ja_JP' :
						$result .= sprintf("%d ??????", $hours);
						break;
					case 'bg_BG' :
						$result .= sprintf("%d ??.", $hours);
						break;
					case 'lt_LT' :
						$result .= sprintf("%d val.", $hours);
						break;
					case 'lv_LV' :
						$result .= sprintf("%dst", $hours);
						break;
					case 'sl_SI' :
						$result .= sprintf("%dh", $hours);
						break;
					default :
						return NULL;

				}

			}

		}

		if(
			($maxNumber === NULL or $nResult < $maxNumber) and
			$minutes > 0
		) {

			$nResult++;

			if($nResult > 1) {
				$result .= ' ';
			}

			if($format === self::LONG) {
				$result .= self::getMinutes($minutes);
			} else if($format === self::AGO) {
				if($days === 0) {
					$result .= self::getMinutes($minutes);
				}
			} else {
				switch(\L::getLang()) {
					case 'fr_FR' :
					case 'it_IT' :
					case 'en_GB' :
					case 'en_US' :
					case 'en_CA' :
					case 'en' :
					case 'de_DE' :
					case 'es_ES' :
					case 'pt_PT' :
					case 'pt_BR' :
					case 'nl_NL' :
					case 'sv_SE' :
					case 'fi_FI' :
					case 'da_DK' :
					case 'cs_CZ' :
					case 'no_NO' :
					case 'ko_KR' :
					case 'ro_RO' :
					case 'sk_SK' :
						$result .= sprintf("%d min", $minutes);
						break;
					case 'pl_PL' :
					case 'lt_LT' :
						$result .= sprintf("%d min.", $minutes);
						break;
					case 'ar_AE' :
						$result .= sprintf("%d ??????????", $minutes);
						break;
					case 'tr_TR' :
						$result .= sprintf("%d dk.", $minutes);
						break;
					case 'ru_RU' :
						$result .= sprintf("%d ??????", $minutes);
						break;
					case 'zh_CN' :
					case 'zh_TW' :
						$result .= sprintf("%d ??????", $minutes);
						break;
					case 'el_GR' :
						$result .= sprintf("%d'", $minutes);
						break;
					case 'he_IL' :
						$result .= sprintf("%d ????'", $minutes);
						break;
					case 'hu_HU' :
						$result .= sprintf("%d perc", $minutes);
						break;
					case 'ja_JP' :
						$result .= sprintf("%d ???", $minutes);
						break;
					case 'bg_BG' :
					case 'ru_UA' :
						$result .= sprintf("%d ??????.", $minutes);
						break;
					case 'lv_LV' :
					case 'sl_SI' :
						$result .= sprintf("%dmin", $minutes);
						break;
					default :
						return NULL;

				}
			}
		}

		if(
			($maxNumber === NULL or $nResult < $maxNumber) and
			($seconds > 0 and $format !== self::AGO)
		) {

			$nResult++;

			if($nResult > 1) {
				$result .= ' ';
			}

			if($format === self::LONG) {
				$result .= self::getSeconds($seconds);
			} else {

				switch(\L::getLang()) {
					case 'fr_FR' :
					case 'en_GB' :
					case 'en_US' :
					case 'en_CA' :
					case 'en' :
					case 'es_ES' :
					case 'pt_PT' :
					case 'pt_BR' :
					case 'it_IT' :
					case 'nl_NL' :
					case 'sv_SE' :
					case 'fi_FI' :
					case 'da_DK' :
					case 'cs_CZ' :
					case 'no_NO' :
					case 'ko_KR' :
					case 'ro_RO' :
					case 'sk_SK' :
						$result .= sprintf("%d s", $seconds);
						break;
					case 'pl_PL' :
						$result .= sprintf("%d s.", $seconds);
						break;
					case 'tr_TR' :
						$result .= sprintf("%d sn.", $seconds);
						break;
					case 'de_DE' :
						$result .= sprintf("%d sek", $seconds);
						break;
					case 'ru_RU' :
						$result .= sprintf("%d ??????", $seconds);
						break;
					case 'ar_AE' :
						$result .= sprintf("%d ??????????", $seconds);
						break;
					case 'zh_CN' :
					case 'zh_TW' :
						$result .= sprintf("%d ???", $seconds);
						break;
					case 'el_GR' :
						$result .= sprintf("%d\"", $seconds);
						break;
					case 'he_IL' :
						$result .= sprintf("%d ??'", $seconds);
						break;
					case 'hu_HU':
						$result .= sprintf("%d mp", $seconds);
						break;
					case 'ja_JP':
						$result .= sprintf("%d ???", $seconds);
						break;
					case 'bg_BG' :
					case 'ru_UA' :
						$result .= sprintf("%d ??????.", $seconds);
						break;
					case 'lt_LT' :
						$result .= sprintf("%d sek.", $seconds);
						break;
					case 'lv_LV' :
						$result .= sprintf("%dsek", $seconds);
						break;
					case 'sl_SI' :
						$result .= sprintf("%ds", $seconds);
						break;
					default :
						return NULL;

				}
			}
		}

		if(in_array($format, [self::AGO, self::LONG]) and $nResult === 0) {

			switch(\L::getLang()) {
				case 'fr_FR' :
					$result = "moins d'une minute";
					break;
				case 'ar_AE' :
					$result = "?????? ???? ??????????";
					break;
				case 'it_IT' :
					$result = "meno di un minuto";
					break;
				case 'nl_NL' :
					$result = "minder dan 1 minuut";
					break;
				case 'tr_TR' :
					$result = "bir dakikadan az";
					break;
				case 'sv_SE' :
					$result = "mindre ??n en minut";
					break;
				case 'en_GB' :
				case 'en_US' :
				case 'en_CA' :
				case 'en' :
					$result = "less than a minute";
					break;
				case 'de_DE' :
					$result = "weniger als einer Minute";
					break;
				case 'es_ES' :
					$result = "menos de un minuto";
					break;
				case 'pt_PT' :
				case 'pt_BR' :
					$result = "menos de um minuto";
					break;
				case 'ru_RU' :
					$result = "?????????? ?????????? ????????????";
					break;
				case 'pl_PL' :
					$result = "mniej ni?? minuta";
					break;
				case 'zh_CN' :
				case 'zh_TW' :
					$result = "???1?????????";
					break;
				case 'el_GR' :
					$result = "???????????????? ?????? ?????? ??????????";
					break;
				case 'fi_FI' :
					$result = "alle minuutti";
					break;
				case 'da_DK' :
					$result = "under et minut";
					break;
				case 'cs_CZ' :
					$result = "m??n?? ne?? jedna minuta";
					break;
				case 'no_NO' :
					$result = "under ett minutt";
					break;
				case 'he_IL' :
					$result = "???????? ????????";
					break;
				case 'hu_HU':
					$result = "kevesebb mint 1 perc";
					break;
				case 'ja_JP':
					$result = "1?????????";
					break;
				case 'ko_KR':
					$result = "1??? ??????";
					break;
				case 'ro_RO':
					$result = "mai pu??in de un minut";
					break;
				case 'bg_BG' :
					$result = "????-?????????? ???? ????????????";
					break;
				case 'lt_LT' :
					$result = "ma??iau nei minut??";
					break;
				case 'lv_LV' :
					$result = "maz??k par min??ti";
					break;
				case 'ru_UA' :
					$result = "?????????? ????????????";
					break;
				case 'sl_SI' :
					$result = "manj kot minuta";
					break;
				case 'sk_SK' :
					$result = "menej ako min??tu";
					break;
				default :
					return NULL;
			}

		}

		if($result !== "") {
			if($format === self::AGO) {
				switch(\L::getLang()) {
					case 'fr_FR':
						return "Il y a ".$result;
					case 'ar_AE':
						return "?????? ".$result;
					case 'it_IT':
						return "Da ".$result;
					case 'nl_NL' :
						return "U hebt ".$result;
					case 'tr_TR':
						return $result." ??nce";
					case 'sv_SE':
						return "f??r ".$result." sedan";
					case 'en_GB':
					case 'en_US':
					case 'en_CA':
					case 'en':
						return $result." ago";
					case 'de_DE':
						return "Vor ".$result;
					case 'es_ES':
						return "Hace ".$result;
					case 'pt_PT':
						return "Faz ".$result;
					case 'pt_BR':
						return "H?? ".$result;
					case 'ru_RU' :
						return $result." ??????????";
					case 'pl_PL' :
						return $result." temu";
					case 'zh_CN' :
					case 'zh_TW' :
						return $result."??????";
					case 'el_GR' :
						return "???????? ".$result;
					case 'fi_FI' :
						return $result." sitten";
					case 'da_DK' :
						return "for ".$result." siden";
					case 'cs_CZ' :
						return "p??ed ".$result;
					case 'no_NO' :
						return "for ".$result." siden";
					case 'he_IL' :
						return "???????? ".$result;
					case 'hu_HU':
						return $result." ezel??tt";
					case 'ja_JP':
						return $result." ???";
					case 'ko_KR':
						return $result." ???";
					case 'ro_RO':
						return "Acum ".$result;
					case 'bg_BG':
						return "?????????? ".$result;
					case 'lt_LT' :
						return "prie?? ".$result;
					case 'lv_LV' :
						return "pirms ".$result;
					case 'ru_UA' :
						return $result." ??????????";
					case 'sl_SI' :
					case 'sk_SK' :
						return "pred ".$result;
				}
			} else {
				return $result;
			}
		} else {
			switch(\L::getLang()) {
				case 'fr_FR':
					return "maintenant";
				case 'ar_AE':
					return "????????";
				case 'it_IT':
					return "ora";
				case 'nl_NL':
					return "nu";
				case 'tr_TR':
					return "??imdi";
				case 'sv_SE':
					return "nu";
				case 'en_GB':
				case 'en_US':
				case 'en_CA':
				case 'en':
					return "now";
				case 'de_DE':
					return "jetzt";
				case 'es_ES':
					return "ahora";
				case 'pt_PT':
				case 'pt_BR':
					return "agora";
				case 'ru_RU' :
				case 'ru_UA' :
					return "????????????";
				case 'pl_PL' :
					return "teraz";
				case 'zh_CN' :
				case 'zh_TW' :
					return "??????";
				case 'el_GR' :
					return "????????";
				case 'fi_FI' :
					return "nyt";
				case 'da_DK' :
					return "nu";
				case 'cs_CZ' :
					return "te??";
				case 'no_NO' :
					return "n??";
				case 'he_IL' :
					return "??????????";
				case 'hu_HU':
					return "most";
				case 'ja_JP':
					return "??????";
				case 'ko_KR':
					return "??????"; //??????; this is more colloquial, ??????; this is more formal.
				case 'ro_RO':
					return "acum";
				case 'bg_BG' :
					return "????????";
				case 'lt_LT' :
					return "dabar";
				case 'lv_LV' :
					return "tagad";
				case 'sl_SI' :
					return "zdaj";
				case 'sk_SK' :
					return "teraz";
			}
		}

	}

	protected static function getYears(int $number): string {

		$type = \L::getType(\L::getLang(), $number);

		switch(\L::getLang()) {

			case 'fr_FR' :
				if($type === 0) {
					return sprintf("%d an", $number);
				} else {
					return sprintf("%d ans", $number);
				}

			case 'en_GB' :
			case 'en_US' :
			case 'en_CA' :
			case 'en' :
				if($type === 0) {
					return sprintf("%d year", $number);
				} else {
					return sprintf("%d years", $number);
				}

			default :
				return '';

		}


	}

	protected static function getMonths(int $number): string {

		$type = \L::getType(\L::getLang(), $number);

		switch(\L::getLang()) {

			case 'fr_FR' :
				if($type === 0) {
					return sprintf("%d mois", $number);
				} else {
					return sprintf("%d mois", $number);
				}

			case 'en_GB' :
			case 'en_US' :
			case 'en_CA' :
			case 'en' :
				if($type === 0) {
					return sprintf("%d month", $number);
				} else {
					return sprintf("%d months", $number);
				}

			default :
				return '';

		}


	}

	protected static function getDays(int $number, int $format): string {

		$type = \L::getType(\L::getLang(), $number);

		switch(\L::getLang()) {

			case 'fr_FR' :
				if($type === 0) {
					return sprintf("%d jour", $number);
				} else {
					return sprintf("%d jours", $number);
				}

			case 'ar_AE' :
				return sprintf("%d ??????", $number);

			case 'pl_PL' :
				if($type === 0) {
					return sprintf("%d dzie??", $number);
				} else {
					return sprintf("%d dni", $number);
				}

			case 'it_IT' :
				if($type === 0) {
					return sprintf("%d giorno", $number);
				} else {
					return sprintf("%d giorni", $number);
				}

			case 'nl_NL' :
				if($type === 0) {
					return sprintf("%d dag", $number);
				} else {
					return sprintf("%d dagen", $number);
				}

			case 'pt_PT' :
			case 'pt_BR' :
				if($type === 0) {
					return sprintf("%d dia", $number);
				} else {
					return sprintf("%d dias", $number);
				}

			case 'sv_SE' :
				if($type === 0) {
					return sprintf("%d dag", $number);
				} else {
					return sprintf("%d dagar", $number);
				}

			case 'tr_TR' :
				return sprintf("%d g??n", $number);

			case 'en_GB' :
			case 'en_US' :
			case 'en_CA' :
			case 'en' :
				if($type === 0) {
					return sprintf("%d day", $number);
				} else {
					return sprintf("%d days", $number);
				}

			case 'de_DE' :
				if($type === 0) {
					return sprintf("%d Tag", $number);
				} else {
					if($format === self::AGO) {
						return sprintf("%d Tagen", $number);
					} else {
						return sprintf("%d Tage", $number);
					}
				}

			case 'es_ES' :
				if($type === 0) {
					return sprintf("%d d??a", $number);
				} else {
					return sprintf("%d d??as", $number);
				}

			case 'el_GR' :
				if($type === 0) {
					return sprintf("%d ????????", $number);
				} else {
					return sprintf("%d ??????????", $number);
				}

			case 'fi_FI' :
				if($type === 0) {
					return sprintf("%d p??iv??", $number);
				} else {
					return sprintf("%d p??iv????", $number);
				}

			case 'da_DK' :
				if($type === 0) {
					return sprintf("%d dag", $number);
				} else {
					return sprintf("%d dage", $number);
				}

			case 'no_NO' :
				if($type === 0) {
					return sprintf("%d dag", $number);
				} else {
					return sprintf("%d dager", $number);
				}

			case 'ru_RU' :
			case 'ru_UA' :
				if($type === 0) {
					return sprintf("%d ????????", $number);
				} else if ($type === 1) {
					return sprintf("%d ??????", $number);
				} else {
					return sprintf("%d ????????", $number);
				}

			case 'cs_CZ' :
				if($type === 0) {
					return sprintf("%d den", $number);
				} else if ($type === 1) {
					return sprintf("%d dny", $number);
				} else {
					return sprintf("%d dn??", $number);
				}

			case 'zh_CN' :
			case 'zh_TW' :
				return sprintf("%d ???", $number);

			case 'he_IL' :
				if($type === 0) {
					return sprintf("%d ??????", $number);
				} else {
					return sprintf("%d ????????", $number);
				}

			case 'hu_HU' :
				return sprintf("%d nap", $number);

			case 'ja_JP' :
				return sprintf("%d ???", $number);

			case 'ko_KR' :
				return sprintf("%d ???", $number);

			case 'ro_RO' :
				if($type === 0) {
					return sprintf("%d zi", $number);
				} else {
					return sprintf("%d zile", $number);
				}

			case 'bg_BG' :
				if($type === 0) {
					return sprintf("%d ??????", $number);
				} else {
					return sprintf("%d ??????", $number);
				}

			case 'lt_LT' :
				if($type === 0) {
					return sprintf("%d diena", $number);
				} elseif($type === 1) {
					return sprintf("%d dien??", $number);
				} elseif($type === 2) {
					return sprintf("%d dienos", $number);
				}

			case 'lv_LV' :
				if($type === 0) {
					return sprintf("%d diena", $number);
				} elseif($type === 1) {
					return sprintf("%d dienas", $number);
				}

			case 'sl_SI' :
				if($type === 0) {
					return sprintf("%d dan", $number);
				} else {
					return sprintf("%d dni", $number);
				}

			case 'sk_SK' :
				if($type === 0) {
					return sprintf("%d de??", $number);
				} elseif($type === 1) {
					return sprintf("%d dni", $number);
				} else {
					return sprintf("%d dn??", $number);
				}

			default :
				return '';

		}


	}

	protected static function getHours(int $number): string {

		$type = \L::getType(\L::getLang(), $number);

		switch(\L::getLang()) {

			case 'fr_FR' :
				if($type === 0) {
					return sprintf("%d heure", $number);
				} else {
					return sprintf("%d heures", $number);
				}

			case 'ar_AE' :
				return sprintf("%d ????????", $number);

			case 'it_IT' :
				if($type === 0) {
					return sprintf("%d ora", $number);
				} else {
					return sprintf("%d ore", $number);
				}

			case 'pl_PL' :
				if($type === 0) {
					return sprintf("%d godzina", $number);
				} else if($type === 1) {
					return sprintf("%d godziny", $number);
				} else {
					return sprintf("%d godzin", $number);
				}

			case 'nl_NL' :
				if($type === 0) {
					return sprintf("%d uur", $number);
				} else {
					return sprintf("%d uren", $number);
				}

			case 'sv_SE' :
				if($type === 0) {
					return sprintf("%d timme", $number);
				} else {
					return sprintf("%d timmar", $number);
				}

			case 'tr_TR' :
				return sprintf("%d saat", $number);

			case 'en_GB' :
			case 'en_US' :
			case 'en_CA' :
			case 'en' :
				if($type === 0) {
					return sprintf("%d hour", $number);
				} else {
					return sprintf("%d hours", $number);
				}

			case 'de_DE' :
				if($type === 0) {
					return sprintf("%d Stunde", $number);
				} else {
					return sprintf("%d Stunden", $number);
				}


			case 'es_ES' :
			case 'pt_PT' :
			case 'pt_BR' :
				if($type === 0) {
					return sprintf("%d hora", $number);
				} else {
					return sprintf("%d horas", $number);
				}

			case 'el_GR' :
				if($type === 0) {
					return sprintf("%d ??????", $number);
				} else {
					return sprintf("%d ????????", $number);
				}

			case 'fi_FI' :
				if($type === 0) {
					return sprintf("%d tunti", $number);
				} else {
					return sprintf("%d tuntia", $number);
				}

			case 'da_DK' :
				if($type === 0) {
					return sprintf("%d time", $number);
				} else {
					return sprintf("%d timer", $number);
				}

			case 'no_NO' :
				if($type === 0) {
					return sprintf("%d time", $number);
				} else {
					return sprintf("%d timer", $number);
				}

			case 'ru_RU' :
			case 'ru_UA' :
				if($type === 0) {
					return sprintf("%d ??????", $number);
				} else if($type === 1) {
					return sprintf("%d ????????", $number);
				} else {
					return sprintf("%d ??????????", $number);
				}

			case 'cs_CZ' :
				if($type === 0) {
					return sprintf("%d hodina", $number);
				} else if($type === 1) {
					return sprintf("%d hodiny", $number);
				} else {
					return sprintf("%d hodin", $number);
				}

			case 'zh_CN' :
			case 'zh_TW' :
				return sprintf("%d ??????", $number);

			case 'he_IL' :
				if($type === 0) {
					return sprintf("%d ??????", $number);
				} else {
					return sprintf("%d ????????", $number);
				}

			case 'hu_HU' :
				return sprintf("%d ??ra", $number);

			case 'ja_JP' :
				return sprintf("%d ??????", $number);

			case 'ko_KR' :
				return sprintf("%d ??????", $number);

			case 'ro_RO' :
				if($type === 0) {
					return sprintf("%d or??", $number);
				} else {
					return sprintf("%d ore", $number);
				}

			case 'bg_BG' :
				if($type === 0) {
					return sprintf("%d ??????", $number);
				} else {
					return sprintf("%d ????????", $number);
				}

			case 'lt_LT' :
				if($type === 0) {
					return sprintf("%d valanda", $number);
				} elseif($type === 1) {
					return sprintf("%d valand??", $number);
				} elseif($type === 2) {
					return sprintf("%d valandos", $number);
				}

			case 'lv_LV' :
				if($type === 0) {
					return sprintf("%d stunda", $number);
				} elseif($type === 1) {
					return sprintf("%d stundas", $number);
				}

			case 'sl_SI' :
				if($type === 0) {
					return sprintf("%s ura", $number);
				} else if($type === 1) {
					return sprintf("%s uri", $number);
				} else if($type === 2) {
					return sprintf("%s ure", $number);
				} else {
					return sprintf("%s ur", $number);
				}

			case 'sk_SK' :
				if($type === 0) {
					return sprintf("%d hodina", $number);
				} elseif($type === 1) {
					return sprintf("%d hodiny", $number);
				} else {
					return sprintf("%d hod??n", $number);
				}

			default :
				return '';

		}

	}

	protected static function getMinutes(int $number): string {

		$type = \L::getType(\L::getLang(), $number);

		switch(\L::getLang()) {

			case 'fr_FR' :
			case 'en_GB' :
			case 'en_US' :
			case 'en_CA' :
			case 'en' :
				if($type === 0) {
					return sprintf("%d minute", $number);
				} else {
					return sprintf("%d minutes", $number);
				}

			case 'ar_AE' :
				return sprintf("%d ??????????", $number);

			case 'it_IT' :
				if($type === 0) {
					return sprintf("%d minuto", $number);
				} else {
					return sprintf("%d minuti", $number);
				}

			case 'nl_NL' :
				if($type === 0) {
					return sprintf("%d minuut", $number);
				} else {
					return sprintf("%d minuten", $number);
				}

			case 'sv_SE' :
				if($type === 0) {
					return sprintf("%d minut", $number);
				} else {
					return sprintf("%d minuter", $number);
				}

			case 'tr_TR' :
				return sprintf("%d dakika", $number);

			case 'de_DE' :
				if($type === 0) {
					return sprintf("%d Minute", $number);
				} else {
					return sprintf("%d Minuten", $number);
				}

			case 'es_ES' :
			case 'pt_PT' :
			case 'pt_BR' :
				if($type === 0) {
					return sprintf("%d minuto", $number);
				} else {
					return sprintf("%d minutos", $number);
				}

			case 'el_GR' :
				if($type === 0) {
					return sprintf("%d ??????????", $number);
				} else {
					return sprintf("%d ??????????", $number);
				}

			case 'fi_FI' :
				if($type === 0) {
					return sprintf("%d minuutti", $number);
				} else {
					return sprintf("%d minuuttia", $number);
				}

			case 'da_DK' :
				if($type === 0) {
					return sprintf("%d minut", $number);
				} else {
					return sprintf("%d minutter", $number);
				}

			case 'no_NO' :
				if($type === 0) {
					return sprintf("%d minutt", $number);
				} else {
					return sprintf("%d minutter", $number);
				}

			case 'ru_RU' :
			case 'ru_UA' :
				if($type === 0) {
					return sprintf("%d ????????????", $number);
				} else if ($type === 1) {
					return sprintf("%d ????????????", $number);
				} else {
					return sprintf("%d ??????????", $number);
				}

			case 'pl_PL' :
				if($type === 0) {
					return sprintf("%d minuta", $number);
				} else if ($type === 1) {
					return sprintf("%d minuty", $number);
				} else {
					return sprintf("%d minut", $number);
				}

			case 'cs_CZ' :
				if($type === 0) {
					return sprintf("%d minuta", $number);
				} else if ($type === 1) {
					return sprintf("%d minuty", $number);
				} else {
					return sprintf("%d minut", $number);
				}

			case 'zh_CN' :
			case 'zh_TW' :
				return sprintf("%d ??????", $number);

			case 'he_IL' :
				if($type === 0) {
					return sprintf("%d ??????", $number);
				} else {
					return sprintf("%d ????????", $number);
				}

			case 'hu_HU' :
				return sprintf("%d perc", $number);

			case 'ja_JP' :
				return sprintf("%d ???", $number);

			case 'ko_KR' :
				return sprintf("%d ???", $number);

			case 'ro_RO' :
				if($type === 0) {
					return sprintf("%d minut", $number);
				} else {
					return sprintf("%d minute", $number);
				}

			case 'bg_BG' :
				if($type === 0) {
					return sprintf("%d ????????????", $number);
				} else {
					return sprintf("%d ????????????", $number);
				}

			case 'lt_LT' :
				if($type === 0) {
					return sprintf("%d minut??", $number);
				} elseif($type === 1) {
					return sprintf("%d minu??i??", $number);
				} elseif($type === 2) {
					return sprintf("%d minut??s", $number);
				}

			case 'lv_LV' :
				if($type === 0) {
					return sprintf("%d min??te", $number);
				} elseif($type === 1) {
					return sprintf("%d min??tes", $number);
				}

			case 'sl_SI' :
				if($type === 0) {
					return sprintf("%s minuta", $number);
				} else if($type === 1) {
					return sprintf("%s minuti", $number);
				} else if($type === 2) {
					return sprintf("%s minute", $number);
				} else {
					return sprintf("%s minut", $number);
				}

			case 'sk_SK' :
				if($type === 0) {
					return sprintf("%d min??ta", $number);
				} elseif($type === 1) {
					return sprintf("%d min??ty", $number);
				} else {
					return sprintf("%d min??t", $number);
				}

			default :
				return '';

		}

	}

	protected static function getSeconds(int $number): string {

		$type = \L::getType(\L::getLang(), $number);

		switch(\L::getLang()) {
			case 'fr_FR' :
				if($type === 0) {
					return sprintf("%d seconde", $number);
				} else {
					return sprintf("%d secondes", $number);
				}

			case 'ar_AE' :
				return sprintf("%d ??????????", $number);

			case 'it_IT' :
				if($type === 0) {
					return sprintf("%d secondo", $number);
				} else {
					return sprintf("%d secondi", $number);
				}

			case 'nl_NL' :
				if($type === 0) {
					return sprintf("%d seconde", $number);
				} else {
					return sprintf("%d seconden", $number);
				}

			case 'sv_SE' :
				if($type === 0) {
					return sprintf("%d sekund", $number);
				} else {
					return sprintf("%d sekunder", $number);
				}

			case 'tr_TR' :
				return sprintf("%d saniye", $number);

			case 'en_GB' :
			case 'en_US' :
			case 'en_CA' :
			case 'en' :
				if($type === 0) {
					return sprintf("%d second", $number);
				} else {
					return sprintf("%d seconds", $number);
				}

			case 'de_DE' :
				if($type === 0) {
					return sprintf("%d Sekunde", $number);
				} else {
					return sprintf("%d Sekunden", $number);
				}

			case 'es_ES' :
			case 'pt_PT' :
			case 'pt_BR' :
				if($type === 0) {
					return sprintf("%d segundo", $number);
				} else {
					return sprintf("%d segundos", $number);
				}

			case 'el_GR' :
				if($type === 0) {
					return sprintf("%d ????????????????????????", $number);
				} else {
					return sprintf("%d ????????????????????????", $number);
				}

			case 'fi_FI' :
				if($type === 0) {
					return sprintf("%d sekunti", $number);
				} else {
					return sprintf("%d sekuntia", $number);
				}

			case 'da_DK' :
				if($type === 0) {
					return sprintf("%d sekund", $number);
				} else {
					return sprintf("%d sekunder", $number);
				}

			case 'no_NO' :
				if($type === 0) {
					return sprintf("%d sekund", $number);
				} else {
					return sprintf("%d sekunder", $number);
				}

			case 'ru_RU' :
			case 'ru_UA' :
				if($type === 0) {
					return sprintf("%d ??????????????", $number);
				} else if ($type === 2) {
					return sprintf("%d ??????????????", $number);
				} else {
					return sprintf("%d ????????????", $number);
				}

			case 'pl_PL' :
				if($type === 0) {
					return sprintf("%d sekunda", $number);
				} else if ($type === 1) {
					return sprintf("%d sekundy", $number);
				} else {
					return sprintf("%d sekund", $number);
				}

			case 'cs_CZ' :
				if($type === 0) {
					return sprintf("%d sekunda", $number);
				} else if ($type === 1) {
					return sprintf("%d sekundy", $number);
				} else {
					return sprintf("%d sekund", $number);
				}

			case 'zh_CN' :
			case 'zh_TW' :
				return sprintf("%d ?????", $number);

			case 'he_IL' :
				if($type === 0) {
					return sprintf("%d ????????", $number);
				} else {
					return sprintf("%d ??????????", $number);
				}

			case 'hu_HU' :
				 return sprintf("%d m??sodperc", $number);

			case 'ja_JP' :
				return sprintf("%d ???", $number);

			case 'ko_KR' :
				return sprintf("%d ???", $number);

			case 'ro_RO' :
				if($type === 0) {
					return sprintf("%d secund??", $number);
				} else {
					return sprintf("%d secunde", $number);
				}

			case 'bg_BG' :
				if($type === 0) {
					return sprintf("%d ??????????????", $number);
				} else {
					return sprintf("%d ??????????????", $number);
				}

			case 'lt_LT' :
				if($type === 0) {
					return sprintf("%d sekund??", $number);
				} elseif($type === 1) {
					return sprintf("%d sekund??i??", $number);
				} elseif($type === 2) {
					return sprintf("%d sekund??s", $number);
				}

			case 'lv_LV' :
				if($type === 0) {
					return sprintf("%d sekunde", $number);
				} elseif($type === 1) {
					return sprintf("%d sekundes", $number);
				}

			case 'sl_SI' :
				if($type === 0) {
					return sprintf("%s sekunda", $number);
				} else if($type === 1) {
					return sprintf("%s sekundi", $number);
				} else if($type === 2) {
					return sprintf("%s sekunde", $number);
				} else {
					return sprintf("%s sekund", $number);
				}

			case 'sk_SK' :
				if($type === 0) {
					return sprintf("%d sekunda", $number);
				} elseif($type === 1) {
					return sprintf("%d sekundy", $number);
				} else {
					return sprintf("%d sek??nd", $number);
				}

			default :
				return '';

		}

	}

	/**
	 * Returns a month name
	 *
	 * @param int $month
	 * @param bool $short
	 * @return string
	 */
	public static function getMonthName(int $month, bool $short = FALSE): string {
		return self::months($short)[$month];
	}

	/**
	 * Returns a day name
	 *
	 * @param int $day 0-6
	 * @param bool $short
	 * @return string
	 */
	public static function getDayName(int $day): string {

		switch(\L::getLang()) {

			case 'fr_FR' :

				return [
					1 => 'Lundi',
					2 => 'Mardi',
					3 => 'Mercredi',
					4 => 'Jeudi',
					5 => 'Vendredi',
					6 => 'Samedi',
					7 => 'Dimanche',
				][$day];

			default :
				return '';

		}

	}


	private static function addTime(string &$text, int $format, string $date) {

		if($format & self::DATE) {
			return;
		}

		if(strlen($date) <= 10) {
			return;
		}

		if($format & self::NO_TIME_IF_MIDNIGHT and strpos($date, '00:00:00')) {
			return;
		}

		switch(\L::getLang()) {

			case 'fr_FR' :
				$textLink = ' ?? ';
				break;

			case 'it_IT' :
				$textLink = ' alle ore ';
				break;

			case 'nl_NL' :
				$textLink = ' om ';
				break;

			case 'tr_TR' :
				$textLink = ' saat ';
				break;

			case 'sv_SE' :
				$textLink = ' kl. ';
				break;

			case 'pl_PL' :
				$textLink = ', ';
				break;

			case 'pt_PT' :
			case 'pt_BR' :
				$hour = (int)substr($date, 11, 2);
				if($hour > 1) {
					$textLink = ' ??s ';
				} else {
					$textLink = ' ?? ';
				}
				break;

			case 'en_GB' :
			case 'en_US' :
			case 'en_CA' :
			case 'en' :
			case 'zh_CN' :
			case 'zh_TW' :
			case 'hu_HU' :
			case 'ja_JP':
			case 'ko_KR':
			case 'lt_LT' :
			case 'ar_AE' :
				$textLink = ' ';
				break;

			case 'de_DE' :
				$textLink = ' um ';
				break;

			case 'es_ES' :
				$textLink = ' a las ';
				break;

			case 'pl_PL' :
				$textLink = ' o ';
				break;

			case 'bg_BG' :
			case 'ru_UA' :
			case 'ru_RU' :
				$textLink = ' ?? ';
				break;

			case 'el_GR' :
				$textLink = ' ???????? ';
				break;

			case 'fi_FI' :
				$textLink = ' klo ';
				break;

			case 'da_DK' :
				$textLink = ' kl. ';
				break;

			case 'cs_CZ' :
				$textLink = ' ve ';
				break;

			case 'no_NO' :
				$textLink = ' klokken ';
				break;

			case 'he_IL' :
				$textLink = ' ??- ';
				break;

			case 'ro_RO' :
				$textLink = ' la ';
				break;

			case 'lv_LV' :
				$textLink = ', plkst. ';
				break;

			case 'sl_SI' :
				$textLink = ' ob ';
				break;

			case 'sk_SK' :
				$textLink = ' o ';
				break;

			default :
				$textLink = '';
				break;

		}

		try {

			if($format & self::DATE_TIME or $format & self::DATE_HOUR_MINUTE) {
				$text .= $textLink;
			}

			$text .= self::getCommonTime($format, $date);

		}
		catch(\Exception $e) {
		}

	}

	/**
	 * Get time from a datetime
	 *
	 * @param string $date
	 * @return string
	 */
	public static function getTime(string $date): string {
		return substr($date, 11);
	}

	private static function getCommonTime(int $format, string $date): string {

		if(!($format & self::DATE)) {

			if($format & self::DATE_TIME) {
				$position = 11;
				$length = 8;
			} else if($format & self::DATE_HOUR_MINUTE) {
				$position = 11;
				$length = 5;
			} else if($format & self::TIME_HOUR_MINUTE) {
				$position = 11;
				$length = 5;
			} else if($format & self::TIME) {
				$position = 11;
				$length = 8;
			} else {
				return '';
			}

			$text = substr($date, $position, $length);

			if($text === FALSE) {
				throw new \Exception('Date does not match the format');
			}
			return $text;

		} else {
			return '';
		}

	}

	public static function getDateFormat(bool $hasYear = TRUE): string {

		switch(\L::getLang()) {

			case 'ar_AE' :
			case 'hu_HU' :
			case 'en' :
			case 'ko_KR' :
				return ($hasYear ? 'Y-' : '').'m-d';

			case 'en_US' :
				return 'm/d/Y';

			case 'de_DE' :
			case 'tr_TR' :
			case 'pl_PL' :
			case 'fi_FI' :
			case 'da_DK' :
			case 'cs_CZ' :
				return 'd.m'.($hasYear ? '.Y' : '');

			case 'es_ES' :
				return 'd-m'.($hasYear ? '-Y' : '');

			case 'ru_RU' :
				return 'm.d'.($hasYear ? '-Y' : '');

			case 'zh_CN' :
			case 'zh_TW' :
			case 'ja_JP' :
				return ($hasYear ? 'Y.' : '').'m.d';

			case 'fr_FR' :
			case 'it_IT' :
			case 'nl_NL' :
			case 'pt_PT' :
			case 'pt_BR' :
			case 'sv_SE' :
			case 'el_GR' :
			case 'no_NO' :
			case 'en_GB' :
			case 'en_CA' :
			case 'he_IL' :
			case 'ro_RO' :
			default :
				return 'd/m'.($hasYear ? '/Y' : '');
		}

	}

	/**
	 * Return a regex to check date format (PCRE)
	 * without delimiters
	 *
	 * @see preg_match
	 * @return String Regex for the current lang (ex: mm/dd/YYYY)
	 */
	public static function getDatePattern(): string {
		$dayRegex = '(0[1-9]|[12][0-9]|3[01])';
		$monthRegex = '(0[1-9]|1[012])';
		$yearRegex = '(19|20)\d\d';

		switch(\L::getLang()) {

			case 'ar_AE' :
			case 'hu_HU' :
			case 'en' :
			case 'ko_KR' :
				return $yearRegex.'-'.$monthRegex.'-'.$dayRegex;

			case 'en_US' :
				return $monthRegex.'/'.$dayRegex.'/'.$yearRegex;

			case 'de_DE' :
			case 'tr_TR' :
			case 'pl_PL' :
			case 'fi_FI' :
			case 'da_DK' :
			case 'cs_CZ' :
				return $dayRegex.'.'.$monthRegex.'.'.$yearRegex;

			case 'es_ES' :
				return $dayRegex.'-'.$monthRegex.'-'.$yearRegex;

			case 'ru_RU' :
				return $monthRegex.'.'.$dayRegex.'.'.$yearRegex;

			case 'zh_CN' :
			case 'zh_TW' :
			case 'ja_JP' :
				return $yearRegex.'.'.$monthRegex.'.'.$dayRegex;

			case 'fr_FR' :
			case 'it_IT' :
			case 'nl_NL' :
			case 'pt_PT' :
			case 'pt_BR' :
			case 'sv_SE' :
			case 'el_GR' :
			case 'no_NO' :
			case 'en_GB' :
			case 'en_CA' :
			case 'he_IL' :
			case 'ro_RO' :
			default :
				return $dayRegex.'/'.$monthRegex.'/'.$yearRegex;
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
	public static function getTimeZoneOffset(string $fromTZ, string $toTZ = NULL): string {

		if($toTZ === NULL) {
			$toTZ = date_default_timezone_get();
		}

		$toDTZ = new DateTimeZone($toTZ);
		$fromDTZ = new DateTimeZone($fromTZ);

		$toDT = new DateTime("now", $toDTZ);
		$fromDT = new DateTime("now", $fromDTZ);

		return $fromDTZ->getOffset($fromDT) - $toDTZ->getOffset($fromDT);

	}

}
?>
