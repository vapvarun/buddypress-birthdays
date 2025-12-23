<?php
/**
 * Birthday Notifications Handler
 *
 * Handles email notifications, activity feed posts, and BP notifications.
 * Uses BuddyPress email templates for consistent styling.
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
 * Manages all birthday notification features using BuddyPress templates.
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
	 * Email type for birthday greeting.
	 *
	 * @var string
	 */
	const EMAIL_TYPE_BIRTHDAY = 'birthday-greeting';

	/**
	 * Email type for admin summary.
	 *
	 * @var string
	 */
	const EMAIL_TYPE_ADMIN_SUMMARY = 'birthday-admin-summary';

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

		// Register email types with BuddyPress.
		add_action( 'bp_core_install_emails', array( $this, 'install_emails' ) );
		add_filter( 'bp_email_get_schema', array( $this, 'register_email_schema' ) );
		add_filter( 'bp_email_get_type_schema', array( $this, 'register_email_type_schema' ) );

		// Create emails on plugin load if they don't exist.
		add_action( 'admin_init', array( $this, 'maybe_install_emails' ) );
	}

	/**
	 * Register email schema for our custom email types.
	 *
	 * @param array $schema Email schema.
	 * @return array
	 */
	public function register_email_schema( $schema ) {
		$schema[ self::EMAIL_TYPE_BIRTHDAY ] = array(
			'post_title'   => __( '[{{{site.name}}}] Happy Birthday, {{{recipient.name}}}!', 'buddypress-birthdays' ),
			'post_content' => $this->get_birthday_email_content(),
			'post_excerpt' => $this->get_birthday_email_plaintext(),
		);

		$schema[ self::EMAIL_TYPE_ADMIN_SUMMARY ] = array(
			'post_title'   => __( '[{{{site.name}}}] {{birthdays.count}} Birthday(s) Today', 'buddypress-birthdays' ),
			'post_content' => $this->get_admin_summary_content(),
			'post_excerpt' => $this->get_admin_summary_plaintext(),
		);

		return $schema;
	}

	/**
	 * Register email type schema.
	 *
	 * @param array $type_schema Type schema.
	 * @return array
	 */
	public function register_email_type_schema( $type_schema ) {
		$type_schema[ self::EMAIL_TYPE_BIRTHDAY ] = array(
			'description' => __( 'A member receives a birthday greeting from the site.', 'buddypress-birthdays' ),
			'named_salutation' => true,
		);

		$type_schema[ self::EMAIL_TYPE_ADMIN_SUMMARY ] = array(
			'description' => __( 'Site admin receives a daily summary of member birthdays.', 'buddypress-birthdays' ),
			'named_salutation' => false,
		);

		return $type_schema;
	}

	/**
	 * Get birthday email HTML content.
	 *
	 * @return string
	 */
	private function get_birthday_email_content() {
		$content  = '{{#recipient.name}}' . "\n";
		$content .= __( 'Hi {{{recipient.name}}},', 'buddypress-birthdays' ) . "\n";
		$content .= '{{/recipient.name}}' . "\n\n";

		$content .= '<p>' . __( '🎂 <strong>Happy Birthday!</strong> 🎉', 'buddypress-birthdays' ) . '</p>' . "\n\n";

		$content .= '<p>' . __( 'Wishing you a fantastic birthday filled with joy, laughter, and wonderful moments! The entire {{site.name}} community sends you warm birthday wishes on your special day.', 'buddypress-birthdays' ) . '</p>' . "\n\n";

		$content .= '{{#birthday.age}}' . "\n";
		$content .= '<p>' . __( 'Cheers to turning <strong>{{birthday.age}}</strong>! May this new year of life bring you happiness and success.', 'buddypress-birthdays' ) . '</p>' . "\n";
		$content .= '{{/birthday.age}}' . "\n\n";

		$content .= '<p>' . __( 'Visit your profile to see birthday wishes from your friends:', 'buddypress-birthdays' ) . '</p>' . "\n\n";

		$content .= '<p><a class="btn" href="{{{recipient.url}}}">{{#recipient.name}}{{{recipient.name}}}\'s Profile{{/recipient.name}}</a></p>' . "\n\n";

		$content .= '<p>' . __( 'Best wishes,', 'buddypress-birthdays' ) . '<br>' . "\n";
		$content .= __( 'The {{site.name}} Team', 'buddypress-birthdays' ) . '</p>';

		return $content;
	}

	/**
	 * Get birthday email plaintext content.
	 *
	 * @return string
	 */
	private function get_birthday_email_plaintext() {
		$content  = "{{#recipient.name}}\n";
		$content .= __( 'Hi {{{recipient.name}}},', 'buddypress-birthdays' ) . "\n";
		$content .= "{{/recipient.name}}\n\n";

		$content .= __( 'Happy Birthday!', 'buddypress-birthdays' ) . "\n\n";

		$content .= __( 'Wishing you a fantastic birthday filled with joy, laughter, and wonderful moments! The entire {{site.name}} community sends you warm birthday wishes on your special day.', 'buddypress-birthdays' ) . "\n\n";

		$content .= "{{#birthday.age}}\n";
		$content .= __( 'Cheers to turning {{birthday.age}}! May this new year of life bring you happiness and success.', 'buddypress-birthdays' ) . "\n";
		$content .= "{{/birthday.age}}\n\n";

		$content .= __( 'Visit your profile: {{{recipient.url}}}', 'buddypress-birthdays' ) . "\n\n";

		$content .= __( 'Best wishes,', 'buddypress-birthdays' ) . "\n";
		$content .= __( 'The {{site.name}} Team', 'buddypress-birthdays' );

		return $content;
	}

	/**
	 * Get admin summary HTML content.
	 *
	 * @return string
	 */
	private function get_admin_summary_content() {
		$content  = '<p>' . __( 'Hi Admin,', 'buddypress-birthdays' ) . '</p>' . "\n\n";

		$content .= '<p>' . __( '🎂 Here are the members celebrating their birthday today:', 'buddypress-birthdays' ) . '</p>' . "\n\n";

		$content .= '{{{birthdays.list}}}' . "\n\n";

		$content .= '<p>' . __( 'Consider sending them a personal birthday wish to make their day special!', 'buddypress-birthdays' ) . '</p>' . "\n\n";

		$content .= '<p>' . __( 'Best,', 'buddypress-birthdays' ) . '<br>' . "\n";
		$content .= __( '{{site.name}} Birthday System', 'buddypress-birthdays' ) . '</p>';

		return $content;
	}

	/**
	 * Get admin summary plaintext content.
	 *
	 * @return string
	 */
	private function get_admin_summary_plaintext() {
		$content  = __( 'Hi Admin,', 'buddypress-birthdays' ) . "\n\n";

		$content .= __( 'Here are the members celebrating their birthday today:', 'buddypress-birthdays' ) . "\n\n";

		$content .= '{{{birthdays.list_plain}}}' . "\n\n";

		$content .= __( 'Consider sending them a personal birthday wish to make their day special!', 'buddypress-birthdays' ) . "\n\n";

		$content .= __( 'Best,', 'buddypress-birthdays' ) . "\n";
		$content .= __( '{{site.name}} Birthday System', 'buddypress-birthdays' );

		return $content;
	}

	/**
	 * Install email templates.
	 */
	public function install_emails() {
		$this->create_email_post( self::EMAIL_TYPE_BIRTHDAY );
		$this->create_email_post( self::EMAIL_TYPE_ADMIN_SUMMARY );
	}

	/**
	 * Maybe install emails if they don't exist.
	 */
	public function maybe_install_emails() {
		// Check if already installed.
		if ( get_option( 'bp_birthdays_emails_installed' ) ) {
			return;
		}

		// Check if BP email functions exist.
		if ( ! function_exists( 'bp_get_email_post' ) ) {
			return;
		}

		// Install birthday greeting email.
		$birthday_email = bp_get_email_post( self::EMAIL_TYPE_BIRTHDAY );
		if ( ! $birthday_email ) {
			$this->create_email_post( self::EMAIL_TYPE_BIRTHDAY );
		}

		// Install admin summary email.
		$admin_email = bp_get_email_post( self::EMAIL_TYPE_ADMIN_SUMMARY );
		if ( ! $admin_email ) {
			$this->create_email_post( self::EMAIL_TYPE_ADMIN_SUMMARY );
		}

		update_option( 'bp_birthdays_emails_installed', true );
	}

	/**
	 * Create an email post for BuddyPress.
	 *
	 * @param string $email_type Email type.
	 */
	private function create_email_post( $email_type ) {
		$schema = $this->register_email_schema( array() );

		if ( ! isset( $schema[ $email_type ] ) ) {
			return;
		}

		$email_data = $schema[ $email_type ];

		$post_id = wp_insert_post(
			array(
				'post_status'  => 'publish',
				'post_type'    => bp_get_email_post_type(),
				'post_title'   => $email_data['post_title'],
				'post_content' => $email_data['post_content'],
				'post_excerpt' => $email_data['post_excerpt'],
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			// Set the email type term.
			$term = term_exists( $email_type, bp_get_email_tax_type() );

			if ( ! $term ) {
				$term = wp_insert_term(
					$email_type,
					bp_get_email_tax_type(),
					array( 'slug' => $email_type )
				);
			}

			if ( ! is_wp_error( $term ) ) {
				$term_id = is_array( $term ) ? $term['term_id'] : $term;
				wp_set_object_terms( $post_id, (int) $term_id, bp_get_email_tax_type() );
			}
		}
	}

	/**
	 * Schedule the daily cron job.
	 */
	public function schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			$send_time = $this->get_setting( 'email_send_time', '09:00' );
			$timezone  = wp_timezone();

			$now       = new DateTime( 'now', $timezone );
			$scheduled = new DateTime( 'today ' . $send_time, $timezone );

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
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$field_id = $wpdb->get_var(
				"SELECT id FROM {$wpdb->prefix}bp_xprofile_fields WHERE type IN ('datebox', 'birthdate') LIMIT 1"
			);
		}

		if ( empty( $field_id ) ) {
			return array();
		}

		$today_month_day = wp_date( 'm-d' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
	 * Send birthday email to user using BuddyPress email system.
	 *
	 * @param int   $user_id User ID.
	 * @param array $birthday_data Birthday data.
	 */
	private function send_birthday_email( $user_id, $birthday_data ) {
		$user = get_userdata( $user_id );

		if ( ! $user || empty( $user->user_email ) ) {
			return;
		}

		// Use BuddyPress email system if available.
		if ( function_exists( 'bp_send_email' ) ) {
			$args = array(
				'tokens' => array(
					'recipient.name' => $birthday_data['display_name'],
					'recipient.url'  => bp_core_get_user_domain( $user_id ),
					'birthday.age'   => $birthday_data['age'],
					'site.name'      => get_bloginfo( 'name' ),
					'site.url'       => home_url(),
				),
			);

			bp_send_email( self::EMAIL_TYPE_BIRTHDAY, $user_id, $args );
		} else {
			// Fallback to wp_mail if BP emails not available.
			$this->send_birthday_email_fallback( $user_id, $birthday_data );
		}
	}

	/**
	 * Fallback email method using wp_mail.
	 *
	 * @param int   $user_id User ID.
	 * @param array $birthday_data Birthday data.
	 */
	private function send_birthday_email_fallback( $user_id, $birthday_data ) {
		$user = get_userdata( $user_id );

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Happy Birthday!', 'buddypress-birthdays' ),
			get_bloginfo( 'name' )
		);

		$message  = '<p>' . sprintf(
			/* translators: %s: user name */
			__( 'Hi %s,', 'buddypress-birthdays' ),
			esc_html( $birthday_data['display_name'] )
		) . '</p>';

		$message .= '<p>🎂 <strong>' . __( 'Happy Birthday!', 'buddypress-birthdays' ) . '</strong> 🎉</p>';

		$message .= '<p>' . sprintf(
			/* translators: %s: site name */
			__( 'Wishing you a fantastic birthday! The entire %s community sends you warm birthday wishes.', 'buddypress-birthdays' ),
			get_bloginfo( 'name' )
		) . '</p>';

		$message .= '<p>' . sprintf(
			/* translators: %d: age */
			__( 'Cheers to turning %d!', 'buddypress-birthdays' ),
			$birthday_data['age']
		) . '</p>';

		$message .= '<p>' . __( 'Best wishes,', 'buddypress-birthdays' ) . '<br>';
		$message .= sprintf(
			/* translators: %s: site name */
			__( 'The %s Team', 'buddypress-birthdays' ),
			get_bloginfo( 'name' )
		) . '</p>';

		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
		wp_mail( $user->user_email, $subject, $message );
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

		$message = $this->get_setting( 'activity_message', "🎂 Today is {name}'s birthday! Send your wishes! 🎉" );

		$profile_url = bp_core_get_user_domain( $user_id );
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

		if ( $friends_only && function_exists( 'friends_get_friend_user_ids' ) ) {
			$recipients = friends_get_friend_user_ids( $user_id );
		} else {
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

		buddypress()->birthdays       = new stdClass();
		buddypress()->birthdays->id   = 'birthdays';
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
	 * @return string|array
	 */
	public function format_notification( $content, $item_id, $secondary_item_id, $total_items, $format, $component_action, $component_name, $id ) {
		if ( 'birthdays' !== $component_name || 'birthday_today' !== $component_action ) {
			return $content;
		}

		$user      = get_userdata( $item_id );
		$user_name = $user ? $user->display_name : __( 'Someone', 'buddypress-birthdays' );
		$user_link = bp_core_get_user_domain( $item_id );

		$text = $this->get_setting( 'notification_text', "🎂 It's {name}'s birthday today!" );
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
	 * Send admin summary email using BuddyPress email system.
	 *
	 * @param array $birthdays Today's birthdays.
	 */
	private function send_admin_summary( $birthdays ) {
		$admin_email = $this->get_setting( 'admin_email' );

		if ( empty( $admin_email ) ) {
			$admin_email = get_option( 'admin_email' );
		}

		// Build birthday list HTML.
		$list_html  = '<ul style="list-style: none; padding: 0;">';
		$list_plain = '';

		foreach ( $birthdays as $birthday ) {
			$profile_url = bp_core_get_user_domain( $birthday['user_id'] );
			$avatar      = function_exists( 'bp_core_fetch_avatar' )
				? bp_core_fetch_avatar(
					array(
						'item_id' => $birthday['user_id'],
						'type'    => 'thumb',
						'width'   => 40,
						'height'  => 40,
						'html'    => true,
					)
				)
				: '';

			$list_html .= '<li style="margin-bottom: 10px; padding: 10px; background: #f9f9f9; border-radius: 5px;">';
			$list_html .= $avatar . ' ';
			$list_html .= '<a href="' . esc_url( $profile_url ) . '"><strong>' . esc_html( $birthday['display_name'] ) . '</strong></a>';
			$list_html .= ' <span style="color: #666;">' . sprintf(
				/* translators: %d: age */
				__( '(Turning %d)', 'buddypress-birthdays' ),
				$birthday['age']
			) . '</span>';
			$list_html .= '</li>';

			$list_plain .= '• ' . $birthday['display_name'] . ' (Turning ' . $birthday['age'] . ")\n";
		}

		$list_html .= '</ul>';

		// Use BuddyPress email system if available.
		if ( function_exists( 'bp_send_email' ) ) {
			$args = array(
				'tokens' => array(
					'birthdays.count'      => count( $birthdays ),
					'birthdays.list'       => $list_html,
					'birthdays.list_plain' => $list_plain,
					'site.name'            => get_bloginfo( 'name' ),
				),
			);

			bp_send_email( self::EMAIL_TYPE_ADMIN_SUMMARY, $admin_email, $args );
		} else {
			// Fallback to wp_mail.
			$subject = sprintf(
				/* translators: %1$d: birthday count, %2$s: site name */
				__( '[%2$s] %1$d Birthday(s) Today', 'buddypress-birthdays' ),
				count( $birthdays ),
				get_bloginfo( 'name' )
			);

			$message  = '<h2>' . __( "Today's Birthdays", 'buddypress-birthdays' ) . '</h2>';
			$message .= $list_html;

			add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
			wp_mail( $admin_email, $subject, $message );
			remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
		}
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private function get_setting( $key, $default = null ) {
		// Try Admin class first (available in admin context).
		if ( class_exists( 'BP_Birthdays_Admin' ) ) {
			$value = BP_Birthdays_Admin::get_settings( $key );
			return null !== $value ? $value : $default;
		}

		// Fallback: read directly from option (for cron/CLI context).
		$settings = get_option( 'bp_birthdays_settings', array() );
		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
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

	/**
	 * Reinstall email templates (for updates).
	 */
	public static function reinstall_emails() {
		delete_option( 'bp_birthdays_emails_installed' );
		$instance = self::get_instance();
		$instance->maybe_install_emails();
	}
}

// Initialize.
add_action( 'bp_loaded', array( 'BP_Birthdays_Notifications', 'get_instance' ) );
