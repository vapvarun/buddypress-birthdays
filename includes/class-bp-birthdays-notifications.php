<?php
/**
 * Birthday Notifications Handler
 *
 * Handles email notifications, activity feed posts, and BP notifications.
 *
 * @package BP_Birthdays
 * @since 2.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BP_Birthdays_Notifications
 *
 * Manages all birthday notification features.
 */
class BP_Birthdays_Notifications {

	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'bp_birthdays_daily_check';

	/**
	 * Option name for tracking sent notifications.
	 *
	 * @var string
	 */
	const SENT_TRACKING_OPTION = 'bp_birthdays_sent_today';

	/**
	 * Instance of this class.
	 *
	 * @var BP_Birthdays_Notifications
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return BP_Birthdays_Notifications
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Schedule cron job.
		add_action( 'init', array( $this, 'schedule_cron' ) );

		// Handle cron execution.
		add_action( self::CRON_HOOK, array( $this, 'process_daily_birthdays' ) );

		// Clear tracking on new day.
		add_action( 'init', array( $this, 'maybe_clear_tracking' ) );

		// Register BP notification component.
		add_action( 'bp_setup_globals', array( $this, 'register_notification_component' ) );

		// Format BP notifications.
		add_filter( 'bp_notifications_get_registered_components', array( $this, 'register_notification_component_filter' ) );
		add_filter( 'bp_notifications_get_notifications_for_user', array( $this, 'format_notification' ), 10, 8 );
	}

	/**
	 * Schedule the daily cron job.
	 */
	public function schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			// Get configured send time.
			$send_time = $this->get_setting( 'email_send_time', '09:00' );
			$timezone  = wp_timezone();

			// Calculate next run time.
			$now       = new DateTime( 'now', $timezone );
			$scheduled = new DateTime( 'today ' . $send_time, $timezone );

			// If time has passed today, schedule for tomorrow.
			if ( $now > $scheduled ) {
				$scheduled->modify( '+1 day' );
			}

			wp_schedule_event( $scheduled->getTimestamp(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Clear sent tracking at the start of a new day.
	 */
	public function maybe_clear_tracking() {
		$last_date = get_option( 'bp_birthdays_last_check_date', '' );
		$today     = wp_date( 'Y-m-d' );

		if ( $last_date !== $today ) {
			delete_option( self::SENT_TRACKING_OPTION );
			update_option( 'bp_birthdays_last_check_date', $today );
		}
	}

	/**
	 * Process daily birthdays - main cron handler.
	 */
	public function process_daily_birthdays() {
		$birthdays = $this->get_todays_birthdays();

		if ( empty( $birthdays ) ) {
			return;
		}

		$sent_tracking = get_option( self::SENT_TRACKING_OPTION, array() );

		foreach ( $birthdays as $user_id => $birthday_data ) {
			// Skip if already processed today.
			if ( isset( $sent_tracking[ $user_id ] ) ) {
				continue;
			}

			// Send email notification.
			if ( $this->get_setting( 'email_enabled' ) ) {
				$this->send_birthday_email( $user_id, $birthday_data );
			}

			// Post to activity feed.
			if ( $this->get_setting( 'activity_enabled' ) ) {
				$this->post_birthday_activity( $user_id, $birthday_data );
			}

			// Send BP notifications.
			if ( $this->get_setting( 'notification_enabled' ) ) {
				$this->send_bp_notifications( $user_id, $birthday_data );
			}

			// Mark as processed.
			$sent_tracking[ $user_id ] = time();
		}

		update_option( self::SENT_TRACKING_OPTION, $sent_tracking );

		// Send admin summary if enabled.
		if ( $this->get_setting( 'admin_email_enabled' ) && ! empty( $birthdays ) ) {
			$this->send_admin_summary( $birthdays );
		}
	}

	/**
	 * Get today's birthdays.
	 *
	 * @return array Array of user_id => birthday_data.
	 */
	private function get_todays_birthdays() {
		global $wpdb;

		$field_id = $this->get_setting( 'default_field_id' );

		if ( empty( $field_id ) ) {
			// Try to auto-detect a date field.
			$field_id = $wpdb->get_var(
				"SELECT id FROM {$wpdb->prefix}bp_xprofile_fields WHERE type IN ('datebox', 'birthdate') LIMIT 1"
			);
		}

		if ( empty( $field_id ) ) {
			return array();
		}

		$today_month_day = wp_date( 'm-d' );

		// Query users with birthday today.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Birthday lookup needs fresh data.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.user_id, d.value, u.display_name, u.user_email
				FROM {$wpdb->prefix}bp_xprofile_data d
				JOIN {$wpdb->users} u ON d.user_id = u.ID
				WHERE d.field_id = %d
				AND d.value != ''
				AND DATE_FORMAT(d.value, '%%m-%%d') = %s",
				$field_id,
				$today_month_day
			),
			ARRAY_A
		);

		$birthdays = array();

		if ( $results ) {
			foreach ( $results as $row ) {
				$birth_date = new DateTime( $row['value'] );
				$today      = new DateTime();
				$age        = $today->format( 'Y' ) - $birth_date->format( 'Y' );

				$birthdays[ $row['user_id'] ] = array(
					'user_id'      => $row['user_id'],
					'display_name' => $row['display_name'],
					'user_email'   => $row['user_email'],
					'birth_date'   => $row['value'],
					'age'          => $age,
				);
			}
		}

		return $birthdays;
	}

	/**
	 * Send birthday email to user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $birthday_data Birthday data.
	 */
	private function send_birthday_email( $user_id, $birthday_data ) {
		$user = get_userdata( $user_id );

		if ( ! $user || empty( $user->user_email ) ) {
			return;
		}

		$subject = $this->get_setting( 'email_subject', 'Happy Birthday, {name}!' );
		$message = $this->get_setting( 'email_message' );

		if ( empty( $message ) ) {
			$message = $this->get_default_email_message();
		}

		// Replace placeholders.
		$replacements = array(
			'{name}'        => $birthday_data['display_name'],
			'{first_name}'  => $user->first_name ? $user->first_name : $birthday_data['display_name'],
			'{age}'         => $birthday_data['age'],
			'{site_name}'   => get_bloginfo( 'name' ),
			'{site_url}'    => home_url(),
			'{profile_url}' => function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $user_id ) : get_author_posts_url( $user_id ),
		);

		$subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $subject );
		$message = str_replace( array_keys( $replacements ), array_values( $replacements ), $message );

		// Set content type to HTML.
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		wp_mail( $user->user_email, $subject, $message );

		// Remove content type filter.
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
	}

	/**
	 * Set email content type to HTML.
	 *
	 * @return string
	 */
	public function set_html_content_type() {
		return 'text/html';
	}

	/**
	 * Post birthday to activity feed.
	 *
	 * @param int   $user_id User ID.
	 * @param array $birthday_data Birthday data.
	 */
	private function post_birthday_activity( $user_id, $birthday_data ) {
		if ( ! function_exists( 'bp_activity_add' ) || ! bp_is_active( 'activity' ) ) {
			return;
		}

		$message = $this->get_setting( 'activity_message', "Today is {name}'s birthday! Send your wishes!" );

		// Replace placeholders.
		$profile_url = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $user_id ) : '#';
		$name_link   = '<a href="' . esc_url( $profile_url ) . '">' . esc_html( $birthday_data['display_name'] ) . '</a>';

		$replacements = array(
			'{name}'        => $name_link,
			'{age}'         => $birthday_data['age'],
			'{profile_url}' => $profile_url,
		);

		$action = str_replace( array_keys( $replacements ), array_values( $replacements ), $message );

		bp_activity_add(
			array(
				'user_id'   => $user_id,
				'action'    => $action,
				'content'   => '',
				'component' => 'birthdays',
				'type'      => 'birthday_celebration',
			)
		);
	}

	/**
	 * Send BuddyPress notifications about birthday.
	 *
	 * @param int   $user_id User ID.
	 * @param array $birthday_data Birthday data.
	 */
	private function send_bp_notifications( $user_id, $birthday_data ) {
		if ( ! function_exists( 'bp_notifications_add_notification' ) || ! bp_is_active( 'notifications' ) ) {
			return;
		}

		$friends_only = $this->get_setting( 'notification_friends_only' );

		// Get recipients.
		if ( $friends_only && function_exists( 'friends_get_friend_user_ids' ) ) {
			$recipients = friends_get_friend_user_ids( $user_id );
		} else {
			// Get all members (limit to reasonable number).
			$recipients = get_users(
				array(
					'fields'  => 'ID',
					'number'  => 500,
					'exclude' => array( $user_id ),
				)
			);
		}

		if ( empty( $recipients ) ) {
			return;
		}

		foreach ( $recipients as $recipient_id ) {
			bp_notifications_add_notification(
				array(
					'user_id'           => $recipient_id,
					'item_id'           => $user_id,
					'secondary_item_id' => 0,
					'component_name'    => 'birthdays',
					'component_action'  => 'birthday_today',
					'date_notified'     => bp_core_current_time(),
					'is_new'            => 1,
				)
			);
		}
	}

	/**
	 * Register notification component.
	 */
	public function register_notification_component() {
		if ( ! class_exists( 'BP_Component' ) ) {
			return;
		}

		buddypress()->birthdays = new stdClass();
		buddypress()->birthdays->id = 'birthdays';
		buddypress()->birthdays->slug = 'birthdays';
	}

	/**
	 * Register notification component filter.
	 *
	 * @param array $components Registered components.
	 * @return array
	 */
	public function register_notification_component_filter( $components ) {
		$components[] = 'birthdays';
		return array_unique( $components );
	}

	/**
	 * Format birthday notification for display.
	 *
	 * @param string $content Notification content.
	 * @param int    $item_id Item ID (birthday user).
	 * @param int    $secondary_item_id Secondary item ID.
	 * @param int    $total_items Total items.
	 * @param string $format Format type.
	 * @param string $component_action Component action.
	 * @param string $component_name Component name.
	 * @param int    $id Notification ID.
	 * @return string
	 */
	public function format_notification( $content, $item_id, $secondary_item_id, $total_items, $format, $component_action, $component_name, $id ) {
		if ( 'birthdays' !== $component_name || 'birthday_today' !== $component_action ) {
			return $content;
		}

		$user      = get_userdata( $item_id );
		$user_name = $user ? $user->display_name : __( 'Someone', 'buddypress-birthdays' );
		$user_link = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $item_id ) : '#';

		$text = $this->get_setting( 'notification_text', "It's {name}'s birthday today!" );
		$text = str_replace( '{name}', $user_name, $text );

		if ( 'string' === $format ) {
			return '<a href="' . esc_url( $user_link ) . '">' . esc_html( $text ) . '</a>';
		}

		return array(
			'link' => $user_link,
			'text' => $text,
		);
	}

	/**
	 * Send admin summary email.
	 *
	 * @param array $birthdays Today's birthdays.
	 */
	private function send_admin_summary( $birthdays ) {
		$admin_email = $this->get_setting( 'admin_email' );

		if ( empty( $admin_email ) ) {
			$admin_email = get_option( 'admin_email' );
		}

		$subject = sprintf(
			/* translators: %d: number of birthdays, %s: site name */
			__( '[%2$s] %1$d Birthday(s) Today', 'buddypress-birthdays' ),
			count( $birthdays ),
			get_bloginfo( 'name' )
		);

		$message = '<h2>' . __( 'Today\'s Birthdays', 'buddypress-birthdays' ) . '</h2>';
		$message .= '<ul>';

		foreach ( $birthdays as $birthday ) {
			$message .= sprintf(
				'<li>%s (Turning %d)</li>',
				esc_html( $birthday['display_name'] ),
				$birthday['age']
			);
		}

		$message .= '</ul>';

		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
		wp_mail( $admin_email, $subject, $message );
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
	}

	/**
	 * Get default email message.
	 *
	 * @return string
	 */
	private function get_default_email_message() {
		$message  = '<p>' . __( 'Dear {first_name},', 'buddypress-birthdays' ) . '</p>';
		$message .= '<p>' . __( 'Wishing you a very Happy Birthday! May your special day be filled with joy, laughter, and wonderful moments.', 'buddypress-birthdays' ) . '</p>';
		$message .= '<p>' . __( 'The entire {site_name} community sends you warm birthday wishes!', 'buddypress-birthdays' ) . '</p>';
		$message .= '<p>' . __( 'Best wishes,', 'buddypress-birthdays' ) . '<br>{site_name}</p>';

		return $message;
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private function get_setting( $key, $default = null ) {
		if ( class_exists( 'BP_Birthdays_Admin' ) ) {
			$value = BP_Birthdays_Admin::get_settings( $key );
			return null !== $value ? $value : $default;
		}

		return $default;
	}

	/**
	 * Manually trigger birthday processing (for testing).
	 */
	public static function trigger_now() {
		$instance = self::get_instance();
		$instance->process_daily_birthdays();
	}
}

// Initialize.
add_action( 'bp_loaded', array( 'BP_Birthdays_Notifications', 'get_instance' ) );
