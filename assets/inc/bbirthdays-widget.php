<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

class Widget_Buddypress_Birthdays extends WP_Widget {

	/**
	 * @internal
	 */
	function __construct() {
		/* Set up optional widget args. */
		$widget_ops = array(
			'classname'   => 'widget_bp_birthdays widget buddypress',
			'description' => __( 'BuddyPress Birthdays widget to display the birthdays of the member in an elegant way.', 'bb' ),
		);

		/* Set up the widget. */
		parent::__construct(
			false,
			__( '(BuddyPress) Birthdays', 'bb' ),
			$widget_ops
		);
	}
	/**
	 * @param array $args
	 * @param array $instance
	 */
	function widget( $args, $instance ) {

		$birthdays = $this->bbirthdays_get_array( $instance );

		if ( ! empty( $birthdays ) ) {

			echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo $args['before_title'] . $instance['title'] . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$max_items = (int) $instance['birthdays_to_display'];
			$c         = 0;
			$date_ymd  = date( 'Ymd' );
			foreach ( $birthdays as $user_id => $birthday ) {
				if ( $c === $max_items ) {
					break;
				}

				$activation_key = get_user_meta( $user_id, 'activation_key' );
				if ( empty( $activation_key ) ) {
					$name_to_display = $this->get_name_to_display( $user_id );

					$age = $birthday['years_old'];

					$emoji = isset( $instance['emoji'] ) ? $instance['emoji'] : '';

					// We don't display negative ages
					if ( $age > 0 ) {
						echo '<li class="clearfix">';
						if ( function_exists( 'bp_is_active' ) ) :
							echo '<a href="' . esc_url( bp_core_get_user_domain( $user_id ) ) . '">';
							echo get_avatar( $user_id );
							echo '</a>';
						else :
							echo get_avatar( $user_id );
						endif;
						echo '<span class="birthday-item-content">';
						echo '<strong>' . esc_html( $name_to_display ) . '</strong>';
						if ( isset( $instance['display_age'] ) && $instance['display_age'] == 'yes' ) {
							echo '<i>(' . esc_html( $age ) . ')</i>';
						}
						switch ( $emoji ) {
							case 'none':
								echo '';
								break;
							case 'cake':
								echo '<span>&#x1F382;</span>';
								break;
							case 'party':
								echo '<span>&#x1F389;</span>';
								break;
							default:
								echo '<span>&#x1F388;</span>';
						}
						echo '<div class="bbirthday_action">';
						echo '<span class="badge-wrap"> ', esc_html_x( 'on', 'happy birthday ON 25-06', 'bb' );
						$date_format = $instance['birthday_date_format'];
						$date_format = ( ! empty( $date_format ) ) ? $date_format : 'F d';
						echo ' <span class="badge badge-primary badge-pill">' . esc_html( date( $date_format, $birthday['datetime']->getTimestamp() ) ) . '</span></span>';
						$happy_birthday_label = '';
						if ( $birthday['next_celebration_comparable_string'] == $date_ymd ) {
							$happy_birthday_label = '<span class="badge badge-primary badge-pill">' . __( 'Happy Birthday!', 'bb' ) . '</span>';
						}

						if ( 'yes' == $instance['birthday_send_message'] ) {
							echo '<a href=" ' . esc_url( $this->bbirthday_get_send_private_message_to_user_url( $user_id ) ) . '"/><i class="fas fa-envelope"></i></a>';
						}
						echo '</div>';
						/**
						 * The label "Happy birthday", if today is the birthday of an user
						 *
						 * @param string $happy_birthday_label The text of the label (contains some HTML)
						 * @param int $user_id
						 */
						echo apply_filters( 'bbirthdays_today_happy_birthday_label', $happy_birthday_label, $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						echo '</span>';
						echo '</li>';

						$c++;
					}
				}
			}
		}
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}


	/**
	 * Get a link to send PM to the given User.
	 *
	 * @param int $user_id user id.
	 *
	 * @return string
	 */
	function bbirthday_get_send_private_message_to_user_url( $user_id ) {
		return wp_nonce_url( bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?r=' . bp_core_get_username( $user_id ) );
	}

	function bbirthdays_get_array( $data ) {

		$buddypress_wp_users = get_users( array( 'fields' => array( 'ID' ) ) );
		$members_birthdays   = array();

		// Get the Birthday field name
		$field_name = isset( $data['birthday_field_name'] ) ? $data['birthday_field_name'] : '';

		$field_name = str_replace( "'", "\'", $field_name );

		// Get the Birthday field ID
		$field_id = xprofile_get_field_id_from_name( $field_name );

		// Set all data for the date limit check
		$birthdays_limit = isset( $data['birthdays_range_limit'] ) ? $data['birthdays_range_limit'] : '';
		if ( $birthdays_limit == 'monthly' ) {
			$int_date_time = strtotime( '+30 day', time() );
			$max_date      = date( 'md', $int_date_time );
		} elseif ( $birthdays_limit == 'weekly' ) {
			$int_date_time = strtotime( '+7 day', time() );
			$max_date      = date( 'md', $int_date_time );
		} else {
			$max_date = 'all';
		}

		// We check if the member has a birthday set
		foreach ( $buddypress_wp_users as $buddypress_wp_user ) {

			$birthday_string = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $field_id, $buddypress_wp_user->ID ) );

			if ( empty( $birthday_string ) ) {
				continue;
			}

			// We transform the string in a date
			$birthday = DateTime::createFromFormat( 'Y-m-d H:i:s', $birthday_string );

			/**
			 * Filter if the current birthday (in the birthdays widget) can be displayed
			 *
			 * @param bool $is_displayed
			 * @param int $user_id
			 * @param DateTime $birthday
			 */
			$display_this_birthday = apply_filters( 'bbirthdays_display_this_birthday', true, $buddypress_wp_user->ID, $birthday );

			if ( $birthday !== false && $display_this_birthday ) {

				// Skip if birth date is not in the selected limit range
				if ( ! $this->bbirthday_is_in_range_limit( $birthday, $max_date ) ) {
					continue;
				}

				$celebration_year = ( date( 'md', $birthday->getTimestamp() ) >= date( 'md' ) ) ? date( 'Y' ) : date( 'Y', strtotime( '+1 years' ) );

				$years_old = (int) $celebration_year - (int) date( 'Y', $birthday->getTimestamp() );

				// If gone for this year already, we remove one year
				if ( date( 'md', $birthday->getTimestamp() ) >= date( 'md' ) ) {
					$years_old = $years_old - 1;
				}

				/**
				 * Filter bbirthdays_date_format
				 *
				 * Let you change the date format in which the birthday is displayed
				 * See: http://php.net/manual/en/function.date.php
				 *
				 * @param string - the date format PHP value
				 *
				 * @return string
				 */
				$format = apply_filters( 'bbirthdays_date_format', 'md' );

				$celebration_string = $celebration_year . date( $format, $birthday->getTimestamp() );

				$members_birthdays[ $buddypress_wp_user->ID ] = array(
					'datetime'                           => $birthday,
					'next_celebration_comparable_string' => $celebration_string,
					'years_old'                          => $years_old,
				);
			}
		}

		uasort( $members_birthdays, array( $this, 'date_comparison' ) );

		return $members_birthdays;
	}

	function get_name_to_display( $user = null ) {

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
	function date_comparison( $a, $b ) {
		return ( $a['next_celebration_comparable_string'] > $b['next_celebration_comparable_string'] );
	}
	function bbirthday_is_in_range_limit( $birth_date, $max_date ) {
		if ( $max_date == 'all' ) {
			return true;
		}

		$target_date = date( 'md', $birth_date->getTimestamp() );
		$now_date    = date( 'md' );

		return $max_date >= $target_date && $target_date >= $now_date;
	}

	function update( $new_instance, $old_instance ) {

		$instance = array();
		// $instance = wp_parse_args( (array) $new_instance, $old_instance );
		$instance['title']                 = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['birthday_date_format']  = ( ! empty( $new_instance['birthday_date_format'] ) ) ? $new_instance['birthday_date_format'] : '';
		$instance['display_age']           = ( ! empty( $new_instance['display_age'] ) ) ? $new_instance['display_age'] : '';
		$instance['birthdays_range_limit'] = ( ! empty( $new_instance['birthdays_range_limit'] ) ) ? $new_instance['birthdays_range_limit'] : '';
		$instance['birthdays_to_display']  = ( ! empty( $new_instance['birthdays_to_display'] ) ) ? $new_instance['birthdays_to_display'] : '';
		$instance['birthday_field_name']   = ( ! empty( $new_instance['birthday_field_name'] ) ) ? $new_instance['birthday_field_name'] : '';
		$instance['emoji']                 = ( ! empty( $new_instance['emoji'] ) ) ? $new_instance['emoji'] : '';
		$instance['birthday_send_message'] = ( ! empty( $new_instance['birthday_send_message'] ) ) ? $new_instance['birthday_send_message'] : '';

		return $instance;
	}

	/**
	 * Widget settings form.
	 */
	function form( $instance ) {

		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title'                 => __( 'Upcoming Birthdays', 'bb' ),
				'display_age'           => 'yes',
				'birthday_send_message' => 'yes',
				'birthday_date_format'  => 'F d',
				'birthdays_range_limit' => 'no_limit',
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
				if ( $group_single_field->type == 'datebox' || 'birthdate' === $group_single_field->type ) {
					$fields[] = $group_single_field->name;
				}
			}
		}

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'bb' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>"/>
		</p>

		<p>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'display_age' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_age' ) ); ?>" type="checkbox" value="<?php echo esc_attr( 'yes' ); ?>" <?php echo checked( 'yes', $instance['display_age'] ); ?>/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'display_age' ) ); ?>"><?php esc_html_e( 'Show the age of the person', 'bb' ); ?></label>
		</p>
		<p>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthday_send_message' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthday_send_message' ) ); ?>" type="checkbox" value="<?php echo esc_attr( 'yes' ); ?>" <?php echo checked( 'yes', $instance['birthday_send_message'] ); ?>/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthday_send_message' ) ); ?>"><?php esc_html_e( 'Enable option to wish them', 'bb' ); ?></label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthday_date_format' ) ); ?>"><?php esc_html_e( 'Date Format', 'bb' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthday_date_format' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthday_date_format' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['birthday_date_format'] ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthdays_range_limit' ) ); ?>"><?php esc_html_e( 'Birthday range limit', 'bb' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthdays_range_limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthdays_range_limit' ) ); ?>">
				<option value="no_limit" <?php echo selected( 'no_limit', $instance['birthdays_range_limit'] ); ?>><?php esc_html_e( 'No Limit', 'bb' ); ?></option>
				<option value="weekly" <?php echo selected( 'weekly', $instance['birthdays_range_limit'] ); ?>><?php esc_html_e( 'Weekly', 'bb' ); ?></option>
				<option value="monthly" <?php echo selected( 'monthly', $instance['birthdays_range_limit'] ); ?>><?php esc_html_e( 'Monthly', 'bb' ); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthday_field_name' ) ); ?>"><?php esc_html_e( 'Field\'s name', 'bb' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthday_field_name' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthday_field_name' ) ); ?>">
				<?php foreach ( $fields as $field ) : ?>
					<option value="<?php echo esc_attr( $field ); ?>" <?php echo selected( $instance['birthday_field_name'], $field ); ?>><?php echo esc_attr( $field ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'birthdays_to_display' ) ); ?>"><?php esc_html_e( 'Number of birthdays to show', 'bb' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'birthdays_to_display' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'birthdays_to_display' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['birthdays_to_display'] ); ?>"/>
		</p>
		<label><?php esc_html_e( 'Select Emoji', 'bb' ); ?></label>
		<div class="bbirthday_emojis">
			<p style="display: inline-block; padding: 0 5px;">
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'emoji' ) ); ?>" type="radio" value="none" <?php checked( $instance['emoji'], 'none' ); ?>/>
				<label for="<?php echo esc_attr( $this->get_field_id( 'emoji' ) ); ?>"><?php esc_html_e( 'None', 'bb' ); ?></label>
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
		<?php

	}

}

function buddypress_birthdays_register_widget() {
	register_widget( 'Widget_Buddypress_Birthdays' );
}
add_action( 'widgets_init', 'buddypress_birthdays_register_widget' );
