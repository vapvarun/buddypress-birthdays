<?php
/**
 * BuddyPress Birthdays widgets - PRODUCTION VERSION
 *
 * @package  BP_Birthdays/assets/inc
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * BuddyPress Birthdays widget class
 */
class Widget_Buddypress_Birthdays extends WP_Widget {

	/**
	 * Set up optional widget args.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'widget_bp_birthdays widget buddypress',
			'description' => __( 'BuddyPress Birthdays widget to display the birthdays of the member in an elegant way.', 'buddypress-birthdays' ),
		);

		/* Set up the widget. */
		parent::__construct(
			false,
			__( '(BuddyPress) Birthdays', 'buddypress-birthdays' ),
			$widget_ops
		);
	}

	/**
	 * Display the widget fields.
	 *
	 * @param array $args Arguments.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {
		// Simple validation.
		if ( empty( $instance['birthday_field_name'] ) || ! function_exists( 'bp_is_active' ) ) {
			return;
		}

		// For friends/followers filters, user must be logged in.
		// These filters require a logged-in user to determine whose friends/followers to show.
		// "All Members" filter is available to everyone including logged-out users.
		if ( isset( $instance['show_birthdays_of'] ) &&
			 in_array( $instance['show_birthdays_of'], array( 'friends', 'followers' ), true ) &&
			 ! is_user_logged_in() ) {
			return;
		}

		// Use object cache instead of transients for better performance on large sites.
		// Object cache is lighter weight and works better with persistent object cache backends.
		$cache_group = 'bp_birthdays';
		$cache_key   = md5( wp_json_encode( $instance ) );

		// Add user ID to cache key for user-specific filters (friends/followers).
		if ( isset( $instance['show_birthdays_of'] ) &&
			 in_array( $instance['show_birthdays_of'], array( 'friends', 'followers' ), true ) &&
			 is_user_logged_in() ) {
			$cache_key .= '_user_' . get_current_user_id();
		}

		$birthdays = wp_cache_get( $cache_key, $cache_group );

		if ( false === $birthdays ) {
			$birthdays = $this->bbirthdays_get_array( $instance );
			// Cache for 30 minutes using object cache.
			wp_cache_set( $cache_key, $birthdays, $cache_group, 30 * MINUTE_IN_SECONDS );
		}

		// Don't render widget at all if there are no birthdays to display.
		// This prevents empty widget containers from showing.
		if ( empty( $birthdays ) ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $birthdays ) ) {
			echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			$max_items = (int) $instance['birthdays_to_display'];
			$c         = 0;

			echo '<div class="bp-birthday-users-list">';

			foreach ( $birthdays as $user_id => $birthday ) {
				if ( $c === $max_items ) {
					break;
				}

				// Skip users who haven't activated their accounts yet.
				// Check both 'activation_key' (BuddyPress) and 'wp_user_activation_key' (WordPress).
				// Note: get_user_meta returns an array with single param = false, check properly.
				$activation_key = get_user_meta( $user_id, 'activation_key', true );
				if ( ! empty( $activation_key ) ) {
					continue;
				}

				$age               = $birthday['years_old'];
				$display_name_type = empty( $instance['display_name_type'] ) ? '' : $instance['display_name_type'];

				// Check if today is the birthday - compare only month and day, not year.
				$birth_date = $birthday['datetime'];
				$today      = current_datetime();
				$today_date = wp_date( 'Y-m-d' );
				$next_birthday_date = isset( $birthday['next_birthday_date'] ) ? $birthday['next_birthday_date'] : '';

				// Compare month-day only for "is today" check.
				$birth_month_day = $birth_date->format( 'm-d' );
				$today_month_day = $today->format( 'm-d' );
				$is_today = ( $birth_month_day === $today_month_day );
				$item_class = $is_today ? 'bp-birthday-item today-birthday' : 'bp-birthday-item';

				// We don't display negative ages.
				if ( $age > 0 ) {
					echo '<div class="' . esc_attr( $item_class ) . '">';

					// Avatar.
					echo '<div class="bp-birthday-avatar">';
					if ( function_exists( 'bp_is_active' ) ) {
						if ( function_exists( 'buddypress' ) && version_compare( buddypress()->version, '12.0', '>=' ) ) {
							$user_url = bp_members_get_user_url( $user_id );
						} else {
							$user_url = bp_core_get_user_domain( $user_id );
						}
						echo '<a href="' . esc_url( $user_url ) . '">';
						echo get_avatar( $user_id, 36 );
						echo '</a>';
					} else {
						echo get_avatar( $user_id, 36 );
					}
					echo '</div>';

					// Content.
					echo '<div class="bp-birthday-content">';

					// User name.
					echo '<div class="bp-birthday-name">';
					if ( function_exists( 'bp_is_active' ) ) {
						echo '<a href="' . esc_url( $user_url ) . '">';
					}

					// Get display name based on setting.
					$display_name = '';
					if ( 'user_name' === $display_name_type ) {
						if ( function_exists( 'buddypress' ) && version_compare( buddypress()->version, '12.0', '>=' ) ) {
							$display_name = bp_members_get_user_slug( $user_id );
						} else {
							$display_name = bp_core_get_username( $user_id );
						}
					} elseif ( 'nick_name' === $display_name_type ) {
						$display_name = get_user_meta( $user_id, 'nickname', true );
					} elseif ( 'first_name' === $display_name_type ) {
						$display_name = get_user_meta( $user_id, 'first_name', true );
					} else {
						$display_name = $this->get_name_to_display( $user_id );
					}

					echo esc_html( $display_name );

					if ( function_exists( 'bp_is_active' ) ) {
						echo '</a>';
					}
					echo '</div>';

					// Birthday details in one compact line.
					echo '<div class="bp-birthday-details">';

					// Age.
					if ( isset( $instance['display_age'] ) && 'yes' === $instance['display_age'] ) {
						echo '<span class="bp-birthday-age">' . sprintf( esc_html__( 'Turning %d', 'buddypress-birthdays' ), esc_html( $age ) ) . '</span>';
					}

					// Date.
					echo '<span class="bp-birthday-date">';
					if ( $is_today ) {
						echo '<strong>' . esc_html__( 'Today!', 'buddypress-birthdays' ) . '</strong>';
					} else {
						$date_format = $instance['birthday_date_format'];
						$date_format = ( ! empty( $date_format ) ) ? $date_format : 'M j';

						// Use next birthday date for display.
						$next_birthday_date = isset( $birthday['next_birthday_date'] ) ? $birthday['next_birthday_date'] : '';
						if ( $next_birthday_date ) {
							try {
								$wp_timezone    = wp_timezone();
								$next_birthday  = DateTime::createFromFormat( 'Y-m-d', $next_birthday_date, $wp_timezone );
								$formatted_date = '';
								if ( $next_birthday ) {
									$formatted_date = wp_date( $date_format, $next_birthday->getTimestamp() );
								} else {
									$formatted_date = wp_date( $date_format, $birthday['datetime']->getTimestamp() );
								}
							} catch ( Exception $e ) {
								$formatted_date = wp_date( $date_format, $birthday['datetime']->getTimestamp() );
							}
						} else {
							$formatted_date = wp_date( $date_format, $birthday['datetime']->getTimestamp() );
						}
						echo esc_html( $formatted_date );
					}
					echo '</span>';

					// Emoji (if enabled).
					$emoji = isset( $instance['emoji'] ) ? $instance['emoji'] : '';
					if ( $emoji && 'none' !== $emoji ) {
						echo '<span class="bp-birthday-emoji">';
						switch ( $emoji ) {
							case 'cake':
								echo '🎂';
								break;
							case 'party':
								echo '🎉';
								break;
							case 'balloon':
							default:
								echo '🎈';
						}
						echo '</span>';
					}

					echo '</div>'; // .bp-birthday-details
					echo '</div>'; // .bp-birthday-content

					// Send wishes button.
					if ( 'yes' === $instance['birthday_send_message'] && bp_is_active( 'messages' ) && is_user_logged_in() ) {
						echo '<div class="bp-birthday-action">';
						$message_url = $this->bbirthday_get_send_private_message_to_user_url( $user_id );
						echo '<a class="bp-send-wishes" href="' . esc_url( $message_url ) . '" title="' . esc_attr__( 'Send birthday wishes', 'buddypress-birthdays' ) . '">';
						echo '<span class="dashicons dashicons-email"></span>';
						echo '</a>';
						echo '</div>';
					}

					echo '</div>'; // .bp-birthday-item
					++$c;
				}
			}
			echo '</div>'; // .bp-birthday-users-list
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Get a link to send PM to the given User.
	 *
	 * @param int $user_id user id.
	 *
	 * @return string
	 */
	public function bbirthday_get_send_private_message_to_user_url( $user_id ) {
		if ( function_exists( 'buddypress' ) && version_compare( buddypress()->version, '12.0', '>=' ) ) {
			return wp_nonce_url( bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?r=' . bp_members_get_user_slug( $user_id ) );
		} else {
			return wp_nonce_url( bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?r=' . bp_core_get_username( $user_id ) );
		}
	}

	/**
	 * Fetches BuddyPress birthdays based on the specified criteria.
	 *
	 * @param array $data Configuration for fetching birthdays.
	 *
	 * @return array An array of users with their birthday details, sorted by the next birthday.
	 */
	public function bbirthdays_get_array( $data ) {
		$members = array();

		// Step 1: Initialize members based on the specified criteria.
		if ( isset( $data['show_birthdays_of'] ) && 'friends' === $data['show_birthdays_of'] && bp_is_active( 'friends' ) ) {
			$members = friends_get_friend_user_ids( get_current_user_id() );
		} elseif ( isset( $data['show_birthdays_of'] ) && 'followers' === $data['show_birthdays_of'] ) {
			if ( function_exists( 'bp_follow_get_following' ) ) {
				$members = bp_follow_get_following(
					array(
						'user_id' => bp_loggedin_user_id(),
					)
				);
			} elseif ( function_exists( 'bp_get_following_ids' ) ) {
				$members = bp_get_following_ids(
					array(
						'user_id' => bp_loggedin_user_id(),
					)
				);
				$members = explode( ',', $members );
			}
		} elseif ( isset( $data['show_birthdays_of'] ) && 'all' === $data['show_birthdays_of'] ) {
			$members = get_users(
				array(
					'fields' => 'ID',
					'number' => 200, // Reasonable limit.
				)
			);
		}

		$members_birthdays = array();
		$field_id          = isset( $data['birthday_field_name'] ) ? $data['birthday_field_name'] : '';

		if ( empty( $field_id ) ) {
			return $members_birthdays;
		}

		$wp_timezone     = wp_timezone();
		$today           = current_datetime();
		$current_user_id = get_current_user_id();

		// Define date range.
		$birthdays_limit = isset( $data['birthdays_range_limit'] ) ? $data['birthdays_range_limit'] : '';

		// Use standard DateTime with WordPress timezone.
		$today_start = new DateTime( 'now', $wp_timezone );
		$today_start->setTime( 0, 0, 0 );

		$end_date_end = new DateTime( 'now', $wp_timezone );

		if ( 'monthly' === $birthdays_limit ) {
			$end_date_end->modify( '+30 days' );
		} elseif ( 'weekly' === $birthdays_limit ) {
			$end_date_end->modify( '+7 days' );
		} else {
			$end_date_end->modify( '+365 days' );
		}

		$end_date_end->setTime( 23, 59, 59 );

		foreach ( $members as $user_id ) {
			// Skip current user.
			if ( (int) $user_id === (int) $current_user_id ) {
				continue;
			}

			$birthday_data = $this->get_user_birthday_data( $field_id, $user_id );
			$birthday_string = $birthday_data['raw_data'];
			$field_date_format = $birthday_data['date_format'];
			
			if ( empty( $birthday_string ) ) {
				continue;
			}

			// Check visibility.
			$visibility = xprofile_get_field_visibility_level( $field_id, $user_id );
			if ( 'onlyme' === $visibility ) {
				continue;
			}

			if ( 'public' === $visibility || $this->is_visible_to_user( $visibility, $user_id ) ) {

				// Clean and validate birthday string using field's configured format.
				$birthday_string = $this->clean_birthday_string( $birthday_string, $field_date_format );
				if ( ! $birthday_string ) {
					continue;
				}

				// Get next birthday.
				$next_birthday_str = $this->bbirthday_get_upcoming_birthday( $birthday_string );
				if ( ! $next_birthday_str ) {
					continue;
				}

				$next_birthday = DateTime::createFromFormat( 'Y-m-d', $next_birthday_str, $wp_timezone );
				if ( ! $next_birthday ) {
					continue;
				}

				// Set to start of day for proper comparison.
				$next_birthday->setTime( 0, 0, 0 );

				// Check if within range.
				if ( $next_birthday >= $today_start && $next_birthday <= $end_date_end ) {

					// Calculate age.
					$birth_date = DateTime::createFromFormat( 'Y-m-d', $birthday_string, $wp_timezone );
					if ( ! $birth_date ) {
						continue;
					}

					$celebration_year = (int) $next_birthday->format( 'Y' );
					$birth_year       = (int) $birth_date->format( 'Y' );
					$years_old        = $celebration_year - $birth_year;

					// We don't display negative ages.
					if ( $years_old > 0 ) {
						$celebration_string = $next_birthday->format( 'Ymd' );

						$members_birthdays[ $user_id ] = array(
							'datetime'                            => $birth_date,
							'next_celebration_comparable_string'  => $celebration_string,
							'next_birthday_date'                  => $next_birthday_str,
							'years_old'                           => $years_old,
						);
					}
				}
			}
		}

		// Sort by next celebration date - today first, then chronological by next birthday.
		uasort(
			$members_birthdays,
			function( $a, $b ) {
				$today = current_datetime();
				$today_month_day = $today->format( 'm-d' );
				
				// Check if either is today's birthday (month-day comparison only)
				$a_birth_month_day = $a['datetime']->format( 'm-d' );
				$b_birth_month_day = $b['datetime']->format( 'm-d' );
				
				$a_is_today = ( $a_birth_month_day === $today_month_day );
				$b_is_today = ( $b_birth_month_day === $today_month_day );
				
				// Today's birthdays always come first
				if ( $a_is_today && ! $b_is_today ) {
					return -1;
				}
				if ( $b_is_today && ! $a_is_today ) {
					return 1;
				}
				
				// If both are today, sort by name or age (optional secondary sort)
				if ( $a_is_today && $b_is_today ) {
					return 0; // Keep original order for same-day birthdays
				}
				
				// For non-today birthdays, sort by next occurrence date
				// Convert to timestamps for proper chronological comparison
				$wp_timezone = wp_timezone();
				
				$date_a = DateTime::createFromFormat( 'Ymd', $a['next_celebration_comparable_string'], $wp_timezone );
				$date_b = DateTime::createFromFormat( 'Ymd', $b['next_celebration_comparable_string'], $wp_timezone );
				
				if ( $date_a && $date_b ) {
					$timestamp_a = $date_a->getTimestamp();
					$timestamp_b = $date_b->getTimestamp();
					
					// Sort by timestamp (closest birthday first)
					return $timestamp_a <=> $timestamp_b;
				}
				
				// Fallback to string comparison if DateTime creation fails
				return strcmp( $a['next_celebration_comparable_string'], $b['next_celebration_comparable_string'] );
			}
		);

		return $members_birthdays;
	}

	/**
	 * Get user birthday data with multiple fallback methods
	 *
	 * @param string $field_id The field ID.
	 * @param int    $user_id The user ID.
	 * @return array Array with 'raw_data' and 'date_format' or empty array.
	 */
	private function get_user_birthday_data( $field_id, $user_id ) {
		$birthday_string = '';
		$date_format = 'Y-m-d'; // Default format

		// Get the configured date format from field metadata
		global $wpdb;
		$field_date_format = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->prefix}bp_xprofile_meta WHERE object_id = %d AND object_type = 'field' AND meta_key = 'date_format'",
				$field_id
			)
		);
		
		if ( ! empty( $field_date_format ) ) {
			$date_format = $field_date_format;
		}

		// Method 1: Standard BP XProfile method.
		if ( function_exists( 'BP_XProfile_ProfileData::get_value_byid' ) ) {
			$birthday_string = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $field_id, $user_id ) );
		}

		// Method 2: Direct database query if method 1 fails.
		if ( empty( $birthday_string ) ) {
			$birthday_string = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT value FROM {$wpdb->prefix}bp_xprofile_data WHERE field_id = %d AND user_id = %d",
					$field_id,
					$user_id
				)
			);
			if ( $birthday_string ) {
				$birthday_string = maybe_unserialize( $birthday_string );
			}
		}

		return array(
			'raw_data' => $birthday_string,
			'date_format' => $date_format,
		);
	}

	/**
	 * Clean birthday string from various formats using field's configured format
	 *
	 * @param mixed  $birthday_string The birthday string to clean.
	 * @param string $field_date_format The configured date format for this field.
	 * @return string|false The cleaned birthday string or false on error.
	 */
	private function clean_birthday_string( $birthday_string, $field_date_format = 'Y-m-d' ) {
		if ( empty( $birthday_string ) ) {
			return false;
		}

		// Handle serialized data.
		if ( is_string( $birthday_string ) && ( 0 === strpos( $birthday_string, 'a:' ) || 0 === strpos( $birthday_string, 's:' ) ) ) {
			$birthday_string = maybe_unserialize( $birthday_string );
		}

		// Handle array format.
		if ( is_array( $birthday_string ) ) {
			if ( isset( $birthday_string['date'] ) ) {
				$birthday_string = $birthday_string['date'];
			} elseif ( isset( $birthday_string[0] ) ) {
				$birthday_string = $birthday_string[0];
			} else {
				return false;
			}
		}

		// Handle object format.
		if ( is_object( $birthday_string ) ) {
			if ( isset( $birthday_string->date ) ) {
				$birthday_string = $birthday_string->date;
			} elseif ( method_exists( $birthday_string, '__toString' ) ) {
				$birthday_string = (string) $birthday_string;
			} else {
				return false;
			}
		}

		// Clean string.
		$birthday_string = trim( $birthday_string );

		// Try the field's configured format first
		$formats_to_try = array( $field_date_format );

		// Add datetime variations of the configured format
		if ( 'Y-m-d' === $field_date_format ) {
			$formats_to_try[] = 'Y-m-d H:i:s';
		} elseif ( 'd/m/Y' === $field_date_format ) {
			$formats_to_try[] = 'd/m/Y H:i:s';
		} elseif ( 'm/d/Y' === $field_date_format ) {
			$formats_to_try[] = 'm/d/Y H:i:s';
		}

		// Add common fallback formats
		$fallback_formats = array(
			'Y-m-d',
			'Y-m-d H:i:s',
			'd/m/Y',
			'm/d/Y',
			'd-m-Y',
			'm-d-Y',
			'Y/m/d',
			'd.m.Y',
			'm.d.Y',
			'Y.m.d',
		);

		// Merge without duplicates
		$formats_to_try = array_unique( array_merge( $formats_to_try, $fallback_formats ) );

		// Special handling for BuddyPress datetime format (Y-m-d H:i:s)
		if ( preg_match( '/^(\d{4}-\d{2}-\d{2})(\s+\d{2}:\d{2}:\d{2})?$/', $birthday_string, $matches ) ) {
			$date_part = $matches[1];
			// Validate it's a proper date
			$test_date = DateTime::createFromFormat( 'Y-m-d', $date_part );
			if ( $test_date && $test_date->format( 'Y-m-d' ) === $date_part ) {
				$year = (int) $test_date->format( 'Y' );
				$current_year = (int) wp_date( 'Y' );
				if ( $year >= 1900 && $year <= $current_year ) {
					return $date_part;
				}
			}
		}

		foreach ( $formats_to_try as $format ) {
			$date = DateTime::createFromFormat( $format, $birthday_string );
			if ( $date && $date->format( $format ) === $birthday_string ) {
				// Validate the date is reasonable.
				$year = (int) $date->format( 'Y' );
				$current_year = (int) wp_date( 'Y' );
				if ( $year >= 1900 && $year <= $current_year ) {
					return $date->format( 'Y-m-d' );
				}
			}
		}

		// Try strtotime as last resort.
		$timestamp = strtotime( $birthday_string );
		if ( false !== $timestamp ) {
			$year = (int) wp_date( 'Y', $timestamp );
			$current_year = (int) wp_date( 'Y' );
			if ( $year >= 1900 && $year <= $current_year ) {
				return wp_date( 'Y-m-d', $timestamp );
			}
		}

		return false;
	}

	/**
	 * Get the next birthday date for a given birthdate
	 *
	 * @param string $birthdate Format: Y-m-d.
	 * @return string|false Next birthday in Y-m-d format or false on error.
	 */
	public function bbirthday_get_upcoming_birthday( $birthdate ) {
		try {
			$wp_timezone = wp_timezone();

			// Parse birthdate consistently with timezone.
			$birth_date = DateTime::createFromFormat( 'Y-m-d', $birthdate, $wp_timezone );
			if ( ! $birth_date ) {
				return false;
			}

			// Get current date in site timezone.
			$today        = new DateTime( 'now', $wp_timezone );
			$current_year = (int) $today->format( 'Y' );

			// Create this year's birthday.
			$birth_month = $birth_date->format( 'm' );
			$birth_day   = $birth_date->format( 'd' );

			// Handle leap year edge case (Feb 29).
			if ( '02' === $birth_month && '29' === $birth_day ) {
				// If it's a leap year birthday but current year is not leap year.
				if ( ! $this->is_leap_year( $current_year ) ) {
					// Use Feb 28 instead.
					$birth_day = '28';
				}
			}

			$this_year_birthday = DateTime::createFromFormat(
				'Y-m-d H:i:s',
				$current_year . '-' . $birth_month . '-' . $birth_day . ' 00:00:00',
				$wp_timezone
			);

			// If birthday has passed this year, use next year.
			if ( $this_year_birthday < $today ) {
				$next_year = $current_year + 1;

				// Handle leap year for next year too.
				$next_birth_day = $birth_date->format( 'd' );
				if ( '02-29' === $birth_date->format( 'm-d' ) && ! $this->is_leap_year( $next_year ) ) {
					$next_birth_day = '28';
				}

				$this_year_birthday = DateTime::createFromFormat(
					'Y-m-d H:i:s',
					$next_year . '-' . $birth_month . '-' . $next_birth_day . ' 00:00:00',
					$wp_timezone
				);
			}

			return $this_year_birthday->format( 'Y-m-d' );

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Check if given year is leap year
	 *
	 * @param int $year Year to check.
	 * @return bool
	 */
	private function is_leap_year( $year ) {
		return ( ( 0 === $year % 4 ) && ( 0 !== $year % 100 ) ) || ( 0 === $year % 400 );
	}

	/**
	 * Helper function to check if the user visibility allows the current user to see the data.
	 *
	 * @param string $visibility Visibility level of the profile field.
	 * @param int    $user_id    User ID of the profile owner.
	 *
	 * @return bool True if visible, false otherwise.
	 */
	private function is_visible_to_user( $visibility, $user_id ) {
		switch ( $visibility ) {
			case 'adminsonly':
				return current_user_can( 'manage_options' );
			case 'loggedin':
				return is_user_logged_in();
			case 'friends':
				return function_exists( 'friends_check_friendship' ) ? friends_check_friendship( get_current_user_id(), $user_id ) : false;
			case 'onlyme':
				return false; // "Only Me" should not be visible to others.
			default:
				return true; // Public visibility or other custom levels.
		}
	}

	/**
	 * Display the user name.
	 *
	 * @param string|int|null $user Get a user info.
	 * @return string The display name.
	 */
	public function get_name_to_display( $user = null ) {

		if ( is_object( $user ) ) {
			$user_info = $user;
		} elseif ( is_numeric( $user ) ) {
			$user_info = get_userdata( $user );
		} else {
			$user_info = wp_get_current_user();
		}

		if ( ! isset( $user_info->user_login ) ) {
			return 'N/A';
		}

		if ( ( ! empty( $user_info->user_firstname ) || ! empty( $user_info->user_lastname ) ) ) {
			$display = $user_info->user_firstname . ' ' . $user_info->user_lastname;
		} else {
			$display = $user_info->user_login;
		}

		return esc_html( apply_filters( 'bbirthdays_get_name_to_display', $display, $user_info ) );
	}

	/**
	 * Update the user birthday data.
	 *
	 * @param  array $new_instance New instance.
	 * @param  array $old_instance Old instance.
	 * @return array Updated instance.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title']                 = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['birthday_date_format']  = ( ! empty( $new_instance['birthday_date_format'] ) ) ? $new_instance['birthday_date_format'] : '';
		$instance['display_age']           = ( ! empty( $new_instance['display_age'] ) ) ? $new_instance['display_age'] : '';
		$instance['birthdays_range_limit'] = ( ! empty( $new_instance['birthdays_range_limit'] ) ) ? $new_instance['birthdays_range_limit'] : '';
		$instance['show_birthdays_of']     = ( ! empty( $new_instance['show_birthdays_of'] ) ) ? $new_instance['show_birthdays_of'] : '';
		$instance['birthdays_to_display']  = ( ! empty( $new_instance['birthdays_to_display'] ) ) ? $new_instance['birthdays_to_display'] : '';
		$instance['birthday_field_name']   = ( ! empty( $new_instance['birthday_field_name'] ) ) ? $new_instance['birthday_field_name'] : '';
		$instance['emoji']                 = ( ! empty( $new_instance['emoji'] ) ) ? $new_instance['emoji'] : '';
		$instance['birthday_send_message'] = ( ! empty( $new_instance['birthday_send_message'] ) ) ? $new_instance['birthday_send_message'] : '';
		$instance['display_name_type']     = ( ! empty( $new_instance['display_name_type'] ) ) ? $new_instance['display_name_type'] : '';

		// Clear all birthday caches when settings change.
		// This ensures user-specific caches are also cleared.
		if ( function_exists( 'bb_clear_birthday_caches' ) ) {
			bb_clear_birthday_caches();
		}

		// Also clear object cache for this widget's cache group.
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'bp_birthdays' );
		} else {
			// Fallback: delete specific cache keys.
			wp_cache_delete( md5( wp_json_encode( $old_instance ) ), 'bp_birthdays' );
		}

		return $instance;
	}

	/**
	 * Widget settings form.
	 *
	 * @param array $instance Saved values from database.
	 */
	public function form( $instance ) {

		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title'                 => __( 'Upcoming Birthdays', 'buddypress-birthdays' ),
				'display_age'           => 'yes',
				'birthday_send_message' => 'yes',
				'birthday_date_format'  => 'F d',
				'birthdays_range_limit' => 'no_limit',
				'show_birthdays_of'     => 'all',
				'display_name_type'     => 'user_name',
				'birthdays_to_display'  => 5,
				'emoji'                 => 'balloon',
				'birthday_field_name'   => 'datebox',
			)
		);

		$profile_groups = bp_xprofile_get_groups(
			array(
				'fetch_fields'     => true,
				'fetch_field_data' => true,
			)
		);

		$fields = array();
		foreach ( $profile_groups as $single_group_details ) {
			if ( empty( $single_group_details->fields ) ) {
				continue;
			}
			foreach ( $single_group_details->fields as $group_single_field ) {
				if ( 'datebox' === $group_single_field->type || 'birthdate' === $group_single_field->type ) {
					$fields[ $group_single_field->id ] = $group_single_field->name;
				}
			}
		}

		// Buddyboss follow functionality support.
		$bb_follow_buttons = false;
		if ( function_exists( 'bp_admin_setting_callback_enable_activity_follow' ) ) {
			$bb_follow_buttons = bp_is_activity_follow_active();
		}

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'buddypress-birthdays' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>"/>
		</p>

		<p>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'display_age' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_age' ) ); ?>" type="checkbox" value="<?php echo esc_attr( 'yes' ); ?>" <?php checked( 'yes', $instance['display_age'] ); ?>/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'display_age' ) ); ?>"><?php esc_html_e( 'Show the age of the person', 'buddypress-birthdays' ); ?></label>
		</p>
		<p>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthday_send_message' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthday_send_message' ) ); ?>" type="checkbox" value="<?php echo esc_attr( 'yes' ); ?>" <?php checked( 'yes', $instance['birthday_send_message'] ); ?>/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthday_send_message' ) ); ?>"><?php esc_html_e( 'Enable option to wish them', 'buddypress-birthdays' ); ?></label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthday_date_format' ) ); ?>"><?php esc_html_e( 'Date Format', 'buddypress-birthdays' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthday_date_format' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthday_date_format' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['birthday_date_format'] ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthdays_range_limit' ) ); ?>"><?php esc_html_e( 'Birthday range limit', 'buddypress-birthdays' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthdays_range_limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthdays_range_limit' ) ); ?>">
				<option value="no_limit" <?php selected( 'no_limit', $instance['birthdays_range_limit'] ); ?>><?php esc_html_e( 'No Limit', 'buddypress-birthdays' ); ?></option>
				<option value="weekly" <?php selected( 'weekly', $instance['birthdays_range_limit'] ); ?>><?php esc_html_e( 'Next 7 Days', 'buddypress-birthdays' ); ?></option>
				<option value="monthly" <?php selected( 'monthly', $instance['birthdays_range_limit'] ); ?>><?php esc_html_e( 'Next 30 Days', 'buddypress-birthdays' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_birthdays_of' ) ); ?>"><?php esc_html_e( 'Show Birthdays of', 'buddypress-birthdays' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'show_birthdays_of' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_birthdays_of' ) ); ?>">
				<?php if ( bp_is_active( 'follow' ) ) : ?>
					<option value="followers" <?php selected( 'followers', $instance['show_birthdays_of'] ); ?>><?php esc_html_e( 'Followings', 'buddypress-birthdays' ); ?></option>
				<?php elseif ( $bb_follow_buttons && function_exists( 'bp_add_follow_button' ) ) : ?>
					<option value="followers" <?php selected( 'followers', $instance['show_birthdays_of'] ); ?>><?php esc_html_e( 'Followings', 'buddypress-birthdays' ); ?></option>
				<?php endif; ?>
				<?php if ( bp_is_active( 'friends' ) ) : ?>
					<option value="friends" <?php selected( 'friends', $instance['show_birthdays_of'] ); ?>><?php esc_html_e( 'Friends', 'buddypress-birthdays' ); ?></option>
				<?php endif; ?>
					<option value="all" <?php selected( 'all', $instance['show_birthdays_of'] ); ?>><?php esc_html_e( 'All Members', 'buddypress-birthdays' ); ?></option>
			</select>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'display_name_type' ) ); ?>"><?php esc_html_e( 'Display Name Type', 'buddypress-birthdays' ); ?></label>
			<select class='widefat' id="<?php echo esc_attr( $this->get_field_id( 'display_name_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_name_type' ) ); ?>">
				<option value="user_name" <?php selected( $instance['display_name_type'], 'user_name' ); ?>><?php esc_html_e( 'User name', 'buddypress-birthdays' ); ?></option>
				<option value="nick_name" <?php selected( $instance['display_name_type'], 'nick_name' ); ?>><?php esc_html_e( 'Nick name', 'buddypress-birthdays' ); ?></option>
				<option value="first_name" <?php selected( $instance['display_name_type'], 'first_name' ); ?>><?php esc_html_e( 'First Name', 'buddypress-birthdays' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthday_field_name' ) ); ?>"><?php esc_html_e( 'Field\'s name', 'buddypress-birthdays' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthday_field_name' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthday_field_name' ) ); ?>">
				<?php foreach ( $fields as $key => $field ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $instance['birthday_field_name'], $key ); ?>><?php echo esc_html( $field ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthdays_to_display' ) ); ?>"><?php esc_html_e( 'Number of birthdays to show', 'buddypress-birthdays' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthdays_to_display' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthdays_to_display' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['birthdays_to_display'] ); ?>"/>
		</p>
		<label><?php esc_html_e( 'Select Emoji', 'buddypress-birthdays' ); ?></label>
		<div class="bbirthday_emojis">
			<p style="display: inline-block; padding: 0 5px;">
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>_none" name="<?php echo esc_attr( $this->get_field_name( 'emoji' ) ); ?>" type="radio" value="none" <?php checked( $instance['emoji'], 'none' ); ?>/>
				<label for="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>_none"><?php esc_html_e( 'None', 'buddypress-birthdays' ); ?></label>
			</p>
			<p style="display: inline-block; padding: 0 5px;">
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>_cake" name="<?php echo esc_attr( $this->get_field_name( 'emoji' ) ); ?>" type="radio" value="cake" <?php checked( $instance['emoji'], 'cake' ); ?>/>
				<label for="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>_cake">&#x1F382;</label>
			</p>
			<p style="display: inline-block; padding: 0 5px;">
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>_balloon" name="<?php echo esc_attr( $this->get_field_name( 'emoji' ) ); ?>" type="radio" value="balloon" <?php checked( $instance['emoji'], 'balloon' ); ?>/>
				<label for="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>_balloon">&#x1F388;</label>
			</p>
			<p style="display: inline-block; padding: 0 5px;">
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>_party" name="<?php echo esc_attr( $this->get_field_name( 'emoji' ) ); ?>" type="radio" value="party" <?php checked( $instance['emoji'], 'party' ); ?>/>
				<label for="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>_party">&#127881;</label>
			</p>
	</div>
		<?php
	}
}

/**
 * Register BuddyPress Birthdays widget.
 */
function buddypress_birthdays_register_widget() {
	register_widget( 'Widget_Buddypress_Birthdays' );
}
add_action( 'widgets_init', 'buddypress_birthdays_register_widget' );