<?php
/**
 * BuddyPress Birthdays widgets.
 *
 * @package  BP_Birthdays/assets/inc
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * BuddyPress Birthdays widget class.
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
		// Simple validation
		if ( empty( $instance['birthday_field_name'] ) || ! function_exists( 'bp_is_active' ) ) {
			return;
		}

		// Simple cache - 30 minutes, shared for all users
		$cache_key = 'bp_birthdays_' . md5( serialize( $instance ) );
		$birthdays = get_transient( $cache_key );

		if ( false === $birthdays ) {
			$birthdays = $this->bbirthdays_get_array( $instance );
			set_transient( $cache_key, $birthdays, 30 * MINUTE_IN_SECONDS );
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $birthdays ) ) {
			echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			
			$max_items = (int) $instance['birthdays_to_display'];
			$c = 0;
			$date_ymd = gmdate( 'Ymd' );

			echo '<div class="bp-birthday-users-list">';
			
			foreach ( $birthdays as $user_id => $birthday ) {
				if ( $c === $max_items ) {
					break;
				}

				$activation_key = get_user_meta( $user_id, 'activation_key' );
				if ( empty( $activation_key ) ) {
					$age = $birthday['years_old'];
					$display_name_type = empty( $instance['display_name_type'] ) ? '' : $instance['display_name_type'];
					
					// Check if today is the birthday
					$is_today = ( $birthday['next_celebration_comparable_string'] === $date_ymd );
					$item_class = $is_today ? 'bp-birthday-item today-birthday' : 'bp-birthday-item';
					
					// We don't display negative ages
					if ( $age > 0 ) {
						echo '<div class="' . esc_attr( $item_class ) . '">';
						
						// Avatar
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
						
						// Content
						echo '<div class="bp-birthday-content">';
						
						// User name
						echo '<div class="bp-birthday-name">';
						if ( function_exists( 'bp_is_active' ) ) {
							echo '<a href="' . esc_url( $user_url ) . '">';
						}
						
						// Get display name based on setting
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
						
						// Birthday details in one compact line
						echo '<div class="bp-birthday-details">';
						
						// Age
						if ( isset( $instance['display_age'] ) && 'yes' === $instance['display_age'] ) {
							echo '<span class="bp-birthday-age">' . sprintf( esc_html__( 'Turning %d', 'buddypress-birthdays' ), esc_html( $age ) ) . '</span>';
						}
						
						// Date
						$date_format = $instance['birthday_date_format'];
						$date_format = ( ! empty( $date_format ) ) ? $date_format : 'M j';
						$formatted_date = wp_date( $date_format, $birthday['datetime']->getTimestamp() );
						
						echo '<span class="bp-birthday-date">';
						if ( $is_today ) {
							echo '<strong>' . esc_html__( 'Today!', 'buddypress-birthdays' ) . '</strong>';
						} else {
							echo esc_html( $formatted_date );
						}
						echo '</span>';
						
						// Emoji (if enabled)
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
						
						// Send wishes button
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
			}
			echo '</div>'; // .bp-birthday-users-list
			
		} else {
			// Clean empty state
			echo '<div class="bp-birthday-empty">';
			if ( 'friends' === $instance['show_birthdays_of'] ) {
				if ( ! bp_is_active( 'friends' ) ) {
					esc_html_e( 'Friends component not active.', 'buddypress-birthdays' );
				} else {
					esc_html_e( 'No upcoming birthdays from friends.', 'buddypress-birthdays' );
				}
			} elseif ( 'followers' === $instance['show_birthdays_of'] ) {
				esc_html_e( 'No upcoming birthdays from followers.', 'buddypress-birthdays' );
			} else {
				esc_html_e( 'No upcoming birthdays.', 'buddypress-birthdays' );
			}
			echo '</div>';
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
	 *                    - show_birthdays_of: Criteria for filtering users (friends, followers, or all).
	 *                    - birthday_field_name: The field name or ID for the birthday.
	 *                    - birthdays_range_limit: Range limit (weekly, monthly, or yearly).
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
					'number' => 200, // Reasonable limit
				)
			);
		}
		$members_birthdays = array();

		// Step 2: Validate the birthday field name or ID.
		$field_name = isset( $data['birthday_field_name'] ) ? $data['birthday_field_name'] : '';
		$wp_time_zone = ! empty( get_option( 'timezone_string' ) ) ? new DateTimeZone( get_option( 'timezone_string' ) ) : wp_timezone();
		$field_id = $field_name;

		if ( empty( $field_id ) ) {
			return $members_birthdays; // Return empty if the birthday field is not specified.
		}

		// Step 3: Define the date range for filtering birthdays.
		$birthdays_limit = isset( $data['birthdays_range_limit'] ) ? $data['birthdays_range_limit'] : '';
		$today = new DateTime( 'now', $wp_time_zone );
		$end = new DateTime( 'now', $wp_time_zone );

		if ( 'monthly' === $birthdays_limit ) {
			$end->modify( '+30 days' );
		} elseif ( 'weekly' === $birthdays_limit ) {
			$end->modify( '+7 days' );
		} else {
			$end->modify( '+365 days' );
		}

		$current_user_id = get_current_user_id();

		// Step 4: Process users - simple approach
		foreach ( $members as $user_id ) {
			// Skip current user
			if ( $user_id == $current_user_id ) {
				continue;
			}

			$birthday_string = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $field_id, $user_id ) );
			if( empty( $birthday_string  ) ){
				continue;
			}
			$visibility = xprofile_get_field_visibility_level( $field_id, $user_id );
			
			// Exclude users with "Only Me" visibility.
			if ( 'onlyme' === $visibility ) {
				continue;
			}

			// Include public data or those accessible by friends or followers.
			if ( 'public' === $visibility || $this->is_visible_to_user( $visibility, $user_id ) ) {
				// Parse the birthday string into a DateTime object.
				try {
					$birthday = new DateTime( $birthday_string, $wp_time_zone );
				} catch ( Exception $e ) {
					continue; // Skip invalid date formats.
				}
				
				// Calculate next birthday
				$birthday_this_year = $this->bbirthday_get_upcoming_birthday($birthday->format( 'Y-m-d' ));
				
				// Check if the birthday falls within the range, including today.
				if ( ( $birthday_this_year >= $today->format( 'Y-m-d' ) && $birthday_this_year <= $end->format( 'Y-m-d' ) ) || ( $birthday_this_year === $today->format( 'Y-m-d' ) ) ) {
					$celebration_year = ( gmdate( 'md', $birthday->getTimestamp() ) >= gmdate( 'md' ) ) ? gmdate( 'Y' ) : gmdate( 'Y', strtotime( '+1 years' ) );
					$years_old = (int) $celebration_year - (int) gmdate( 'Y', $birthday->getTimestamp() );

					$format = apply_filters( 'bbirthdays_date_format', 'md' );
					$celebration_string = $celebration_year . $birthday->format( $format );

					$members_birthdays[ $user_id ] = array(
						'datetime'  => $birthday,
						'next_celebration_comparable_string' => $celebration_string,
						'years_old' => $years_old,
					);
				}
			}
		}
			
		// Step 5: Sort the birthdays by the next celebration date.
		uasort( $members_birthdays, function( $a, $b ) {
			return strtotime( $a['next_celebration_comparable_string'] ) - strtotime( $b['next_celebration_comparable_string'] );
		});
		return $members_birthdays;
	}

	/**
	 * Fetch the upcoming date withing 1 year .
	 *
	 * @param string $birthdate Get a user info.
	 */
	public function bbirthday_get_upcoming_birthday( $birthdate ) {
		// Validate and parse the birthdate using DateTime
		$birth_date = DateTime::createFromFormat('Y-m-d', $birthdate);
	
		if (!$birth_date) {
			throw new InvalidArgumentException("Invalid birthdate format. Please use 'YYYY-MM-DD'.");
		}
	
		// Extract day and month
		$birth_day = $birth_date->format('d');
		$birth_month = $birth_date->format('m');
	
		// Get the current year
		$current_year = date('Y');
	
		// Create a DateTime object for the upcoming birthday
		$upcoming_birthday = DateTime::createFromFormat('Y-m-d', "{$current_year}-{$birth_month}-{$birth_day}");
	
		// If the birthday has already passed this year, increment the year
		if ($upcoming_birthday->getTimestamp() < time()) {
			$upcoming_birthday->modify('+1 year');
		}
	
		return $upcoming_birthday->format('Y-m-d'); // Return the formatted date
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
				return friends_check_friendship( get_current_user_id(), $user_id );
			case 'onlyme':
				return false; // "Only Me" should not be visible to others.
			default:
				return true; // Public visibility or other custom levels.
		}
	}

	/**
	 * Display the user name.
	 *
	 * @param string $user Get a user info.
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
	 * @param  mixed $new_instance New instance.
	 * @param  mixed $old_instance Old instance.
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

		// Clear cache when settings change
		delete_transient( 'bp_birthdays_' . md5( serialize( $old_instance ) ) );

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

		// Buddyboss follow functionality support
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
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'display_age' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_age' ) ); ?>" type="checkbox" value="<?php echo esc_attr( 'yes' ); ?>" <?php echo checked( 'yes', $instance['display_age'] ); ?>/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'display_age' ) ); ?>"><?php esc_html_e( 'Show the age of the person', 'buddypress-birthdays' ); ?></label>
		</p>
		<p>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthday_send_message' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthday_send_message' ) ); ?>" type="checkbox" value="<?php echo esc_attr( 'yes' ); ?>" <?php echo checked( 'yes', $instance['birthday_send_message'] ); ?>/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthday_send_message' ) ); ?>"><?php esc_html_e( 'Enable option to wish them', 'buddypress-birthdays' ); ?></label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthday_date_format' ) ); ?>"><?php esc_html_e( 'Date Format', 'buddypress-birthdays' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthday_date_format' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthday_date_format' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['birthday_date_format'] ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthdays_range_limit' ) ); ?>"><?php esc_html_e( 'Birthday range limit', 'buddypress-birthdays' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthdays_range_limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthdays_range_limit' ) ); ?>">
				<option value="no_limit" <?php echo selected( 'no_limit', $instance['birthdays_range_limit'] ); ?>><?php esc_html_e( 'No Limit', 'buddypress-birthdays' ); ?></option>
				<option value="weekly" <?php echo selected( 'weekly', $instance['birthdays_range_limit'] ); ?>><?php esc_html_e( 'Next 7 Days', 'buddypress-birthdays' ); ?></option>
				<option value="monthly" <?php echo selected( 'monthly', $instance['birthdays_range_limit'] ); ?>><?php esc_html_e( 'Next 30 Days', 'buddypress-birthdays' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_birthdays_of' ) ); ?>"><?php esc_html_e( 'Show Birthdays of', 'buddypress-birthdays' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'show_birthdays_of' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_birthdays_of' ) ); ?>">
				<?php if ( bp_is_active( 'follow' ) ) : ?>
					<option value="followers" <?php echo selected( 'followers', $instance['show_birthdays_of'] ); ?>><?php esc_html_e( 'Followings', 'buddypress-birthdays' ); ?></option>
				<?php elseif ( $bb_follow_buttons && function_exists( 'bp_add_follow_button' ) ) : ?>
					<option value="followers" <?php echo selected( 'followers', $instance['show_birthdays_of'] ); ?>><?php esc_html_e( 'Followings', 'buddypress-birthdays' ); ?></option>
				<?php endif; ?>
				<?php if ( bp_is_active( 'friends' ) ) : ?>
					<option value="friends" <?php echo selected( 'friends', $instance['show_birthdays_of'] ); ?>><?php esc_html_e( 'Friends', 'buddypress-birthdays' ); ?></option>
				<?php endif; ?>
					<option value="all" <?php echo selected( 'all', $instance['show_birthdays_of'] ); ?>><?php esc_html_e( 'All Members', 'buddypress-birthdays' ); ?></option>
			</select>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'display_name_type' ) ); ?>"><?php esc_html_e( 'Display Name Type', 'buddypress-birthdays' ); ?></label>
			<select class='widefat' id="<?php echo esc_attr( $this->get_field_id( 'display_name_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_name_type' ) ); ?>">
				<option value="user_name" <?php echo selected( $instance['display_name_type'], 'user_name' ); ?>><?php esc_html_e( 'User name', 'buddypress-birthdays' ); ?></option>
				<option value="nick_name" <?php echo selected( $instance['display_name_type'], 'nick_name' ); ?>><?php esc_html_e( 'Nick name', 'buddypress-birthdays' ); ?></option>
				<option value="first_name" <?php echo selected( $instance['display_name_type'], 'first_name' ); ?>><?php esc_html_e( 'First Name', 'buddypress-birthdays' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthday_field_name' ) ); ?>"><?php esc_html_e( 'Field\'s name', 'buddypress-birthdays' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthday_field_name' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthday_field_name' ) ); ?>">
				<?php foreach ( $fields as $key => $field ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php echo selected( $instance['birthday_field_name'], $key ); ?>><?php echo esc_attr( $field ); ?></option>
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
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'emoji' ) ); ?>" type="radio" value="none" <?php checked( $instance['emoji'], 'none' ); ?>/>
				<label for="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>"><?php esc_html_e( 'None', 'buddypress-birthdays' ); ?></label>
			</p>
			<p style="display: inline-block; padding: 0 5px;">
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'emoji' ) ); ?>" type="radio" value="cake" <?php checked( $instance['emoji'], 'cake' ); ?>/>
				<label for="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>">&#x1F382;</label>
			</p>
			<p style="display: inline-block; padding: 0 5px;">
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'emoji' ) ); ?>" type="radio" value="balloon" <?php checked( $instance['emoji'], 'balloon' ); ?>/>
				<label for="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>">&#x1F388;</label>
			</p>
			<p style="display: inline-block; padding: 0 5px;">
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'emoji' ) ); ?>" type="radio" value="party" <?php checked( $instance['emoji'], 'party' ); ?>/>
				<label for="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>">&#127881;</label>
			</p>
	</div>
		<p style="background: #f9f9f9; padding: 10px; margin-top: 10px;">
			<small><strong>Performance:</strong> Cache refreshes every 30 minutes. Works with BuddyPress, BuddyBoss & Youzify.</small>
		</p>
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