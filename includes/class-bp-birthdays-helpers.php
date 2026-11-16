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
			$birth = new DateTime( $birth_date, wp_timezone() );
			$today = new DateTime( 'now', wp_timezone() );
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
			$today = new DateTime( 'now', wp_timezone() );
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
			$today = new DateTime( 'now', wp_timezone() );
			$today->setTime( 0, 0, 0 );

			// Set birthday to this year.
			$next_birthday = new DateTime( 'now', wp_timezone() );
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
	 * A member's profile URL on any supported BuddyPress version.
	 *
	 * BuddyPress 12.0 deprecated bp_core_get_user_domain() in favour of
	 * bp_members_get_user_url(). Every plugin surface links members through
	 * this one helper.
	 *
	 * @since 2.5.1
	 *
	 * @param int $user_id Member ID.
	 * @return string Profile URL.
	 */
	public static function get_user_url( $user_id ) {
		if ( function_exists( 'bp_members_get_user_url' ) ) {
			return bp_members_get_user_url( $user_id );
		}

		return bp_core_get_user_domain( $user_id );
	}

	/**
	 * The xProfile field that holds member birthdays.
	 *
	 * The General tab's Default Birthday Field when it still points at a real
	 * datebox/birthdate field, otherwise the first such field. The cron
	 * channels, the shortcode and the widget all resolve the field here, so a
	 * deleted field never leaves one surface reading nothing.
	 *
	 * @since 2.5.1
	 *
	 * @return int Field ID, 0 when the site has no date field.
	 */
	public static function get_birthday_field_id() {
		global $wpdb;

		$settings   = get_option( 'bp_birthdays_settings', array() );
		$configured = is_array( $settings ) && isset( $settings['default_field_id'] ) ? absint( $settings['default_field_id'] ) : 0;
		$table      = $wpdb->prefix . 'bp_xprofile_fields';

		if ( $configured ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Primary-key lookup; only the table name is interpolated.
			$field_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d AND type IN ('datebox', 'birthdate')", $configured ) );
			if ( $field_id ) {
				return $field_id;
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Static query, table name only.
		return (int) $wpdb->get_var( "SELECT id FROM {$table} WHERE type IN ('datebox', 'birthdate') ORDER BY id ASC LIMIT 1" );
	}

	/**
	 * Whether a BuddyPress field visibility level lets a viewer see a member's birthday.
	 *
	 * Mirrors BuddyPress' own levels. BuddyPress labels `adminsonly` "Only Me"
	 * (the member and site admins); BuddyBoss adds a stricter `onlyme`. A
	 * viewer ID of 0 is an anonymous visitor, which is who a public activity
	 * post is shown to.
	 *
	 * @since 2.5.1
	 *
	 * @param string $level     Visibility level (public, loggedin, friends, adminsonly, onlyme).
	 * @param int    $owner_id  Member whose birthday it is.
	 * @param int    $viewer_id Member looking at it, 0 for a visitor.
	 * @return bool
	 */
	public static function visibility_allows( $level, $owner_id, $viewer_id ) {
		$owner_id  = absint( $owner_id );
		$viewer_id = absint( $viewer_id );

		switch ( $level ) {
			case 'onlyme':
				return $viewer_id && $viewer_id === $owner_id;
			case 'adminsonly':
				return $viewer_id && ( $viewer_id === $owner_id || user_can( $viewer_id, 'manage_options' ) );
			case 'friends':
				return $viewer_id && ( $viewer_id === $owner_id || ( function_exists( 'friends_check_friendship' ) && friends_check_friendship( $viewer_id, $owner_id ) ) );
			case 'loggedin':
				return (bool) $viewer_id;
			default:
				return true;
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
