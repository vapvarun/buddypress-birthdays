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
	 * The one timezone every birthday date is read and shown in.
	 *
	 * A birthday is a calendar date, so "today", "upcoming" and age all follow
	 * the site's day, never the server's or the browser's. Filterable so a
	 * site can show each member their own day.
	 *
	 * @since 2.5.1
	 *
	 * @param int $user_id Member the date is shown to, 0 for the site.
	 * @return DateTimeZone
	 */
	public static function display_timezone( $user_id = 0 ) {
		/**
		 * Filters the timezone birthdays are calculated and shown in.
		 *
		 * @since 2.5.1
		 *
		 * @param DateTimeZone $timezone The site's timezone.
		 * @param int          $user_id  Member the date is shown to, 0 for the site.
		 */
		$timezone = apply_filters( 'bp_birthdays_display_timezone', wp_timezone(), absint( $user_id ) );

		return $timezone instanceof DateTimeZone ? $timezone : wp_timezone();
	}

	/**
	 * Today, at the start of the display timezone's day.
	 *
	 * @since 2.5.1
	 *
	 * @param int $user_id Member the date is shown to, 0 for the site.
	 * @return DateTimeImmutable
	 */
	public static function today( $user_id = 0 ) {
		return ( new DateTimeImmutable( 'now', self::display_timezone( $user_id ) ) )->setTime( 0, 0, 0 );
	}

	/**
	 * The one formatter for a birthday date shown to a person.
	 *
	 * Takes a calendar date (Y-m-d, optionally with a time that is ignored) and
	 * prints it with the site's language in the display timezone, so the day
	 * never shifts. Month and day names are translated by WordPress.
	 *
	 * @since 2.5.1
	 *
	 * @param string|DateTimeInterface $date     Calendar date.
	 * @param string                   $format   PHP date format.
	 * @param string                   $fallback Returned when the date cannot be read.
	 * @param int                      $user_id  Member the date is shown to, 0 for the site.
	 * @return string
	 */
	public static function date_label( $date, $format = 'F j', $fallback = '', $user_id = 0 ) {
		$timezone = self::display_timezone( $user_id );

		if ( $date instanceof DateTimeInterface ) {
			$ymd = $date->format( 'Y-m-d' );
		} else {
			$ymd = substr( trim( (string) $date ), 0, 10 );
		}

		$day = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, $timezone );
		if ( ! $day || $day->format( 'Y-m-d' ) !== $ymd ) {
			return $fallback;
		}

		// time-ok: the one formatter; midnight in the display timezone, printed in that timezone.
		return wp_date( $format, $day->getTimestamp(), $timezone );
	}

	/**
	 * Calculate age from birth date.
	 *
	 * @param string $birth_date Birth date string.
	 * @return int|false Age in years or false on error.
	 */
	public static function calculate_age( $birth_date ) {
		try {
			$timezone = self::display_timezone();
			$birth    = new DateTimeImmutable( substr( trim( (string) $birth_date ), 0, 10 ), $timezone );

			return $birth->diff( self::today() )->y;
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
		return self::date_label( $date, $format );
	}

	/**
	 * Check if a date is today.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	public static function is_birthday_today( $date ) {
		try {
			$birth = new DateTimeImmutable( substr( trim( (string) $date ), 0, 10 ), self::display_timezone() );

			return in_array( $birth->format( 'm-d' ), self::birthday_month_days( self::today() ), true );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Stored birthday month-days celebrated on a date.
	 *
	 * A Feb 29 birthday is celebrated on Feb 28 when the year has no Feb 29.
	 * The cron and the widget's "Today!" check both read this rule.
	 *
	 * @since 2.5.1
	 *
	 * @param DateTimeInterface $date Day, site timezone.
	 * @return string[] One or two m-d values.
	 */
	public static function birthday_month_days( DateTimeInterface $date ) {
		$month_day = $date->format( 'm-d' );

		return ( '02-28' === $month_day && '0' === $date->format( 'L' ) ) ? array( '02-28', '02-29' ) : array( $month_day );
	}

	/**
	 * Get days until next birthday.
	 *
	 * @param string $date Birth date string.
	 * @return int Days until next birthday.
	 */
	public static function days_until_birthday( $date ) {
		try {
			$today     = self::today();
			$month_day = ( new DateTimeImmutable( substr( trim( (string) $date ), 0, 10 ), self::display_timezone() ) )->format( 'm-d' );

			// Next day it is celebrated: this year, else next. Feb 29 falls on Feb 28 in a year without one.
			foreach ( array( 0, 1 ) as $add_years ) {
				$year    = (int) $today->format( 'Y' ) + $add_years;
				$leap    = '1' === $today->setDate( $year, 1, 1 )->format( 'L' );
				$day     = ( '02-29' === $month_day && ! $leap ) ? '02-28' : $month_day;
				$next    = $today->setDate( $year, (int) substr( $day, 0, 2 ), (int) substr( $day, 3, 2 ) );
				if ( $next >= $today ) {
					return (int) $today->diff( $next )->days;
				}
			}

			return 0;
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

		// BuddyPress and BuddyBoss know every level, including ones an add-on registers, and hide
		// the field for the viewer by their own rules. Ask them first.
		if ( function_exists( 'bp_xprofile_get_hidden_field_types_for_user' ) ) {
			return ! in_array( (string) $level, (array) bp_xprofile_get_hidden_field_types_for_user( $owner_id, $viewer_id ), true );
		}

		switch ( $level ) {
			case 'public':
				return true;
			case 'onlyme':
				return $viewer_id && $viewer_id === $owner_id;
			case 'adminsonly':
				return $viewer_id && ( $viewer_id === $owner_id || user_can( $viewer_id, 'manage_options' ) );
			case 'friends':
				return $viewer_id && ( $viewer_id === $owner_id || ( function_exists( 'friends_check_friendship' ) && friends_check_friendship( $viewer_id, $owner_id ) ) );
			case 'loggedin':
				return (bool) $viewer_id;
			default:
				// A level this plugin does not know stays hidden.
				return false;
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
