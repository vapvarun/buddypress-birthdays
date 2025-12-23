<?php
/**
 * Helper Functions for BuddyPress Birthdays
 *
 * @package BP_Birthdays
 * @since 2.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BP_Birthdays_Helpers
 *
 * Utility functions for the plugin.
 */
class BP_Birthdays_Helpers {

	/**
	 * Zodiac signs data.
	 *
	 * @var array
	 */
	private static $zodiac_signs = array(
		array(
			'name'   => 'Capricorn',
			'symbol' => "\u{2651}",
			'start'  => '12-22',
			'end'    => '01-19',
		),
		array(
			'name'   => 'Aquarius',
			'symbol' => "\u{2652}",
			'start'  => '01-20',
			'end'    => '02-18',
		),
		array(
			'name'   => 'Pisces',
			'symbol' => "\u{2653}",
			'start'  => '02-19',
			'end'    => '03-20',
		),
		array(
			'name'   => 'Aries',
			'symbol' => "\u{2648}",
			'start'  => '03-21',
			'end'    => '04-19',
		),
		array(
			'name'   => 'Taurus',
			'symbol' => "\u{2649}",
			'start'  => '04-20',
			'end'    => '05-20',
		),
		array(
			'name'   => 'Gemini',
			'symbol' => "\u{264A}",
			'start'  => '05-21',
			'end'    => '06-20',
		),
		array(
			'name'   => 'Cancer',
			'symbol' => "\u{264B}",
			'start'  => '06-21',
			'end'    => '07-22',
		),
		array(
			'name'   => 'Leo',
			'symbol' => "\u{264C}",
			'start'  => '07-23',
			'end'    => '08-22',
		),
		array(
			'name'   => 'Virgo',
			'symbol' => "\u{264D}",
			'start'  => '08-23',
			'end'    => '09-22',
		),
		array(
			'name'   => 'Libra',
			'symbol' => "\u{264E}",
			'start'  => '09-23',
			'end'    => '10-22',
		),
		array(
			'name'   => 'Scorpio',
			'symbol' => "\u{264F}",
			'start'  => '10-23',
			'end'    => '11-21',
		),
		array(
			'name'   => 'Sagittarius',
			'symbol' => "\u{2650}",
			'start'  => '11-22',
			'end'    => '12-21',
		),
	);

	/**
	 * Get zodiac sign for a given date.
	 *
	 * @param string $date Date string (Y-m-d format or DateTime).
	 * @return array|false Array with 'name' and 'symbol' or false on error.
	 */
	public static function get_zodiac_sign( $date ) {
		if ( $date instanceof DateTime ) {
			$month_day = $date->format( 'm-d' );
		} else {
			$parsed = date_parse( $date );
			if ( ! $parsed || empty( $parsed['month'] ) || empty( $parsed['day'] ) ) {
				return false;
			}
			$month_day = sprintf( '%02d-%02d', $parsed['month'], $parsed['day'] );
		}

		foreach ( self::$zodiac_signs as $sign ) {
			// Handle Capricorn which spans year boundary.
			if ( 'Capricorn' === $sign['name'] ) {
				if ( $month_day >= '12-22' || $month_day <= '01-19' ) {
					return array(
						'name'   => $sign['name'],
						'symbol' => $sign['symbol'],
					);
				}
				continue;
			}

			if ( $month_day >= $sign['start'] && $month_day <= $sign['end'] ) {
				return array(
					'name'   => $sign['name'],
					'symbol' => $sign['symbol'],
				);
			}
		}

		return false;
	}

	/**
	 * Get zodiac symbol HTML.
	 *
	 * @param string $date Date string.
	 * @param bool   $include_name Whether to include the sign name.
	 * @return string HTML string or empty.
	 */
	public static function get_zodiac_html( $date, $include_name = false ) {
		$sign = self::get_zodiac_sign( $date );

		if ( ! $sign ) {
			return '';
		}

		$html = '<span class="bp-birthday-zodiac" title="' . esc_attr( $sign['name'] ) . '">';
		$html .= '<span class="zodiac-symbol">' . esc_html( $sign['symbol'] ) . '</span>';

		if ( $include_name ) {
			$html .= ' <span class="zodiac-name">' . esc_html( $sign['name'] ) . '</span>';
		}

		$html .= '</span>';

		return $html;
	}

	/**
	 * Calculate age from birth date.
	 *
	 * @param string $birth_date Birth date string.
	 * @return int|false Age in years or false on error.
	 */
	public static function calculate_age( $birth_date ) {
		try {
			$birth = new DateTime( $birth_date );
			$today = new DateTime();
			$age   = $today->diff( $birth )->y;
			return $age;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Format birthday for display.
	 *
	 * @param string $date Date string.
	 * @param string $format PHP date format.
	 * @return string Formatted date.
	 */
	public static function format_birthday( $date, $format = 'F j' ) {
		try {
			$datetime = new DateTime( $date );
			return wp_date( $format, $datetime->getTimestamp() );
		} catch ( Exception $e ) {
			return '';
		}
	}

	/**
	 * Check if a date is today.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	public static function is_birthday_today( $date ) {
		try {
			$birth    = new DateTime( $date );
			$today    = new DateTime();
			return $birth->format( 'm-d' ) === $today->format( 'm-d' );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get days until next birthday.
	 *
	 * @param string $date Birth date string.
	 * @return int Days until next birthday.
	 */
	public static function days_until_birthday( $date ) {
		try {
			$birth = new DateTime( $date );
			$today = new DateTime();
			$today->setTime( 0, 0, 0 );

			// Set birthday to this year.
			$next_birthday = new DateTime();
			$next_birthday->setDate(
				(int) $today->format( 'Y' ),
				(int) $birth->format( 'm' ),
				(int) $birth->format( 'd' )
			);
			$next_birthday->setTime( 0, 0, 0 );

			// If birthday has passed this year, use next year.
			if ( $next_birthday < $today ) {
				$next_birthday->modify( '+1 year' );
			}

			return (int) $today->diff( $next_birthday )->days;
		} catch ( Exception $e ) {
			return 0;
		}
	}
}
