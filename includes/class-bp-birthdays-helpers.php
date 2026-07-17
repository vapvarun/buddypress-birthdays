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
	 * The `name` values are stable ENGLISH IDENTIFIERS, not display strings —
	 * the date-range matching in get_zodiac_sign() compares against them, so
	 * they must never be translated in place. Resolve a display label with
	 * get_zodiac_label() at render time instead (a static property cannot call
	 * __() anyway, and doing so would resolve before the textdomain loads).
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
	 * Get the translated display label for a zodiac sign identifier.
	 *
	 * The identifiers in self::$zodiac_signs are matching keys, not display
	 * text. This maps one to the member-facing label. Called at render time so
	 * the textdomain (loaded on init:10) is always available.
	 *
	 * @since 2.5.0
	 *
	 * @param string $name Zodiac sign identifier (e.g. 'Capricorn').
	 * @return string Translated label, or the identifier when unknown.
	 */
	public static function get_zodiac_label( $name ) {
		$labels = array(
			'Capricorn'   => __( 'Capricorn', 'buddypress-birthdays' ),
			'Aquarius'    => __( 'Aquarius', 'buddypress-birthdays' ),
			'Pisces'      => __( 'Pisces', 'buddypress-birthdays' ),
			'Aries'       => __( 'Aries', 'buddypress-birthdays' ),
			'Taurus'      => __( 'Taurus', 'buddypress-birthdays' ),
			'Gemini'      => __( 'Gemini', 'buddypress-birthdays' ),
			'Cancer'      => __( 'Cancer', 'buddypress-birthdays' ),
			'Leo'         => __( 'Leo', 'buddypress-birthdays' ),
			'Virgo'       => __( 'Virgo', 'buddypress-birthdays' ),
			'Libra'       => __( 'Libra', 'buddypress-birthdays' ),
			'Scorpio'     => __( 'Scorpio', 'buddypress-birthdays' ),
			'Sagittarius' => __( 'Sagittarius', 'buddypress-birthdays' ),
		);

		return isset( $labels[ $name ] ) ? $labels[ $name ] : $name;
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

		// The sign's `name` is an identifier; the tooltip and the visible name
		// must render the translated label.
		$label = self::get_zodiac_label( $sign['name'] );

		$html  = '<span class="bp-birthday-zodiac" title="' . esc_attr( $label ) . '">';
		$html .= '<span class="zodiac-symbol">' . esc_html( $sign['symbol'] ) . '</span>';

		if ( $include_name ) {
			$html .= ' <span class="zodiac-name">' . esc_html( $label ) . '</span>';
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
			$birth = new DateTime( $date );
			$today = new DateTime();
			return $birth->format( 'm-d' ) === $today->format( 'm-d' );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Convert a PHP date format string to its MySQL DATE_FORMAT / STR_TO_DATE
	 * equivalent.
	 *
	 * BuddyPress stores a datebox field's `date_format` meta as a PHP date
	 * format (e.g. `Y-m-d`). MySQL's STR_TO_DATE()/DATE_FORMAT() use their own
	 * `%`-prefixed specifiers (e.g. `%Y-%m-%d`), so passing the PHP format
	 * straight into SQL makes STR_TO_DATE() return NULL for every row.
	 *
	 * Tokens without a MySQL equivalent (ordinal suffix `S`, timezone tokens)
	 * are dropped; backslash-escaped characters become literals; a literal `%`
	 * is escaped as `%%` for MySQL.
	 *
	 * @since 2.5.0
	 *
	 * @param string $php_format PHP date format (e.g. 'Y-m-d', 'd/m/Y').
	 * @return string MySQL format string (e.g. '%Y-%m-%d', '%d/%m/%Y').
	 */
	public static function php_to_mysql_date_format( $php_format ) {
		$map = array(
			// Day.
			'd' => '%d',
			'j' => '%e',
			'D' => '%a',
			'l' => '%W',
			'N' => '%w',
			'w' => '%w',
			'z' => '%j',
			'S' => '', // Ordinal suffix — no standalone MySQL token.
			// Month.
			'm' => '%m',
			'n' => '%c',
			'M' => '%b',
			'F' => '%M',
			// Year.
			'Y' => '%Y',
			'y' => '%y',
			'o' => '%Y',
			// Time.
			'H' => '%H',
			'G' => '%k',
			'h' => '%h',
			'g' => '%l',
			'i' => '%i',
			's' => '%s',
			'A' => '%p',
			'a' => '%p',
			'u' => '%f',
			// No MySQL equivalent — dropped.
			'v' => '',
			'e' => '',
			'T' => '',
			'P' => '',
			'O' => '',
			'U' => '',
		);

		$php_format = (string) $php_format;
		$mysql      = '';
		$length     = strlen( $php_format );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $php_format[ $i ];

			// A backslash escapes the next character to a literal in PHP formats.
			if ( '\\' === $char && $i + 1 < $length ) {
				++$i;
				$literal = $php_format[ $i ];
				$mysql  .= ( '%' === $literal ) ? '%%' : $literal;
				continue;
			}

			if ( isset( $map[ $char ] ) ) {
				$mysql .= $map[ $char ];
			} elseif ( '%' === $char ) {
				$mysql .= '%%';
			} else {
				$mysql .= $char;
			}
		}

		return $mysql;
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

	/**
	 * User meta key that stores a member's per-user birthday opt-out flag.
	 *
	 * When set to 'yes' the member has chosen to hide their birthday from every
	 * plugin surface (widget, shortcode, activity, notifications, emails).
	 *
	 * @since 2.5.0
	 * @var string
	 */
	const OPTOUT_META_KEY = 'bb_birthday_hidden';

	/**
	 * Whether a member has opted out of having their birthday shown.
	 *
	 * GDPR: birthday month/day is personal data. A member can suppress it from
	 * every plugin surface by enabling the opt-out on their BuddyPress
	 * Settings > General screen. This is the single gate consulted by the widget,
	 * shortcode, activity posts, BuddyPress notifications and greeting emails, so
	 * an opted-out member never appears anywhere the plugin renders or messages.
	 *
	 * @since 2.5.0
	 *
	 * @param int $user_id The member ID to test.
	 * @return bool True when the member has opted out (their birthday must be hidden).
	 */
	public static function is_user_opted_out( $user_id ) {
		$user_id = absint( $user_id );

		$opted_out = ( $user_id && 'yes' === get_user_meta( $user_id, self::OPTOUT_META_KEY, true ) );

		/**
		 * Filter whether a member's birthday is hidden everywhere.
		 *
		 * Return true to hide the member's birthday from all plugin surfaces,
		 * false to always show it. Site owners can wire this to a privacy plugin
		 * or a global consent store without touching the stored user meta.
		 *
		 * @since 2.5.0
		 *
		 * @param bool $opted_out Whether the member has opted out.
		 * @param int  $user_id   The member ID being tested.
		 */
		return (bool) apply_filters( 'bb_birthday_user_opted_out', $opted_out, $user_id );
	}
}
