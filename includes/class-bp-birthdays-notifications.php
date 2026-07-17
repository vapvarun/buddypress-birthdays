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

		// Register birthday celebration activity action.
		add_action( 'bp_register_activity_actions', array( $this, 'register_activity_action' ) );
	}

	/**
	 * Register birthday celebration activity action with BuddyPress.
	 */
	public function register_activity_action() {
		bp_activity_set_action(
			'birthdays',                                    // Component.
			'birthday_celebration',                         // Action type.
			__( 'Birthday Celebrations', 'buddypress-birthdays' ),  // Action name.
			array( $this, 'format_activity_action' ),       // Format callback.
			__( 'Birthday Celebrations', 'buddypress-birthdays' ),  // Action label.
			array( 'activity', 'member' )                    // Contexts.
		);
	}

	/**
	 * Format birthday celebration activity action for display.
	 *
	 * @param string $action   The activity action string.
	 * @param object $activity The activity object.
	 * @return string The formatted activity action.
	 */
	public function format_activity_action( $action, $activity ) {
		// Use the original action if it contains the birthday message.
		if ( ! empty( $action ) && false === strpos( $action, 'posted an update' ) ) {
			return $action;
		}

		// Fallback for activities without proper action set.
		if ( function_exists( 'bp_is_active' ) ) {
			if ( function_exists( 'buddypress' ) && version_compare( buddypress()->version, '12.0', '>=' ) ) {
				$user_link = bp_members_get_user_url( $activity->user_id );
			} else {
				$user_link = bp_core_get_user_domain( $activity->user_id );
			}
			$user_name = bp_core_get_user_displayname( $activity->user_id );

			$action = sprintf(
				/* translators: %1$s: User display name with link. */
				__( '%1$s posted a birthday celebration', 'buddypress-birthdays' ),
				'<a href="' . esc_url( $user_link ) . '">' . esc_html( $user_name ) . '</a>'
			);
		}

		return $action;
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
			'description'      => __( 'A member receives a birthday greeting from the site.', 'buddypress-birthdays' ),
			'named_salutation' => true,
		);

		$type_schema[ self::EMAIL_TYPE_ADMIN_SUMMARY ] = array(
			'description'      => __( 'Site admin receives a daily summary of member birthdays.', 'buddypress-birthdays' ),
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
		$content = '<p>' . __( '<strong>Happy Birthday!</strong>', 'buddypress-birthdays' ) . '</p>' . "\n\n";

		$content .= '<p>' . __( 'Wishing you a fantastic birthday filled with joy, laughter, and wonderful moments! The entire {{{site.name}}} community sends you warm birthday wishes on your special day.', 'buddypress-birthdays' ) . '</p>' . "\n\n";

		$content .= '<p>' . __( 'Cheers to turning <strong>{{{birthday.age}}}</strong>! May this new year of life bring you happiness and success.', 'buddypress-birthdays' ) . '</p>' . "\n\n";

		$content .= '<p>' . __( 'Visit your profile to see birthday wishes from your friends:', 'buddypress-birthdays' ) . '</p>' . "\n\n";

		$content .= '<p><a class="btn" href="{{{recipient.url}}}">' . __( 'View My Profile', 'buddypress-birthdays' ) . '</a></p>' . "\n\n";

		$content .= '<p>' . __( 'Best wishes,', 'buddypress-birthdays' ) . '<br>' . "\n";
		$content .= __( 'The {{{site.name}}} Team', 'buddypress-birthdays' ) . '</p>';

		return $content;
	}

	/**
	 * Get birthday email plaintext content.
	 *
	 * @return string
	 */
	private function get_birthday_email_plaintext() {
		$content = __( 'Happy Birthday!', 'buddypress-birthdays' ) . "\n\n";

		$content .= __( 'Wishing you a fantastic birthday filled with joy, laughter, and wonderful moments! The entire {{{site.name}}} community sends you warm birthday wishes on your special day.', 'buddypress-birthdays' ) . "\n\n";

		$content .= __( 'Cheers to turning {{{birthday.age}}}! May this new year of life bring you happiness and success.', 'buddypress-birthdays' ) . "\n\n";

		$content .= __( 'Visit your profile: {{{recipient.url}}}', 'buddypress-birthdays' ) . "\n\n";

		$content .= __( 'Best wishes,', 'buddypress-birthdays' ) . "\n";
		$content .= __( 'The {{{site.name}}} Team', 'buddypress-birthdays' );

		return $content;
	}

	/**
	 * Get admin summary HTML content.
	 *
	 * @return string
	 */
	private function get_admin_summary_content() {
		$content = '<p>' . __( 'Hi Admin,', 'buddypress-birthdays' ) . '</p>' . "\n\n";

		$content .= '<p>' . __( 'Here are the members celebrating their birthday today:', 'buddypress-birthdays' ) . '</p>' . "\n\n";

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
		$content = __( 'Hi Admin,', 'buddypress-birthdays' ) . "\n\n";

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
	 *
	 * Previously this guarded on function_exists( 'bp_get_email_post' ) —
	 * a function that does not exist in any supported BuddyPress version
	 * (BP exposes bp_get_email_post_type() / bp_get_email()), so the guard
	 * always returned early and the birthday email posts were never
	 * created. Emails appeared enabled in settings but silently never
	 * fired. We now check the real prerequisites (the bp-email post type
	 * and its taxonomy) and detect existing templates via a taxonomy
	 * lookup, which is how BuddyPress itself resolves email types.
	 */
	public function maybe_install_emails() {
		// Check if already installed.
		if ( get_option( 'bp_birthdays_emails_installed' ) ) {
			return;
		}

		// Check the BP email APIs this install path actually uses.
		if ( ! function_exists( 'bp_get_email_post_type' ) || ! function_exists( 'bp_get_email_tax_type' ) ) {
			return;
		}

		// The bp-email post type is registered on bp_init; bail (and retry on
		// the next admin_init) if it is not available yet.
		if ( ! post_type_exists( bp_get_email_post_type() ) || ! taxonomy_exists( bp_get_email_tax_type() ) ) {
			return;
		}

		// Install birthday greeting email.
		if ( ! $this->email_post_exists( self::EMAIL_TYPE_BIRTHDAY ) ) {
			$this->create_email_post( self::EMAIL_TYPE_BIRTHDAY );
		}

		// Install admin summary email.
		if ( ! $this->email_post_exists( self::EMAIL_TYPE_ADMIN_SUMMARY ) ) {
			$this->create_email_post( self::EMAIL_TYPE_ADMIN_SUMMARY );
		}

		update_option( 'bp_birthdays_emails_installed', true );
	}

	/**
	 * Check whether a published bp-email post exists for the given email type.
	 *
	 * Mirrors how BuddyPress resolves an email type: a published post of the
	 * bp-email post type assigned the type's term in the bp-email-type
	 * taxonomy.
	 *
	 * @param string $email_type Email type slug (e.g. 'birthday-greeting').
	 * @return bool True if a template post exists.
	 */
	private function email_post_exists( $email_type ) {
		$posts = get_posts(
			array(
				'post_type'        => bp_get_email_post_type(),
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Single bounded lookup on a tiny post type, mirrors BP core's own email resolution.
					array(
						'taxonomy' => bp_get_email_tax_type(),
						'field'    => 'slug',
						'terms'    => $email_type,
					),
				),
			)
		);

		return ! empty( $posts );
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
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$field_id = $wpdb->get_var(
				"SELECT id FROM {$wpdb->prefix}bp_xprofile_fields WHERE type IN ('datebox', 'birthdate') LIMIT 1"
			);
		}

		if ( empty( $field_id ) ) {
			return array();
		}

		$today_month_day = wp_date( 'm-d' );

		/*
		 * BB-2 scale fix: scope the scan to the configured birthday field_id,
		 * which is indexed on {prefix}bp_xprofile_data (the `field_id` key).
		 *
		 * The `d.field_id = %d` predicate is sargable and lets MySQL use the
		 * field_id index, so the non-sargable `DATE_FORMAT(d.value,'%m-%d')`
		 * comparison only runs on THIS field's rows (one row per member who set
		 * a birthday) -- not a full scan of every xProfile value in the table.
		 * USE INDEX (field_id) is an explicit hint so a stale optimizer can't
		 * fall back to a full table scan.
		 *
		 * Residual cost: DATE_FORMAT still runs per candidate row because DOB is
		 * stored as a free-form date string in `value` (format varies per field
		 * via the date_format meta), so there is no stored month-day column to
		 * index against. A fully sargable rewrite would require a generated
		 * column or a parallel index table, which is out of scope here and would
		 * change BuddyPress-owned storage. The field_id scoping keeps the per-run
		 * cost proportional to "members with a birthday set", not "all xProfile
		 * data rows". Date-match semantics (timezone via wp_date, leap-day) are
		 * unchanged.
		 */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.user_id, d.value, u.display_name, u.user_email
				FROM {$wpdb->prefix}bp_xprofile_data d USE INDEX (field_id)
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
				// GDPR: skip members who opted out of showing their birthday.
				// This single gate excludes them from greeting emails, activity
				// posts, BuddyPress notifications and the admin summary at once.
				if ( class_exists( 'BP_Birthdays_Helpers' ) && BP_Birthdays_Helpers::is_user_opted_out( $row['user_id'] ) ) {
					continue;
				}

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

		$message = '<p>' . sprintf(
			/* translators: %s: user name */
			__( 'Hi %s,', 'buddypress-birthdays' ),
			esc_html( $birthday_data['display_name'] )
		) . '</p>';

		$message .= '<p><strong>' . __( 'Happy Birthday!', 'buddypress-birthdays' ) . '</strong></p>';

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

		// Cron/CLI fallback: BP_Birthdays_Admin (which owns the canonical
		// default) is only loaded in is_admin() context, so this literal is
		// what a site that has never saved the Activity tab actually posts —
		// it needs its own __() home. {name}/{age}/{profile_url} are
		// placeholders the site owner may re-order, so the whole sentence
		// stays one translatable unit.
		$message = $this->get_setting(
			'activity_message',
			__( "Today is {name}'s birthday! Send your wishes!", 'buddypress-birthdays' )
		);

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
	 * Log a diagnostic message (debug builds only).
	 *
	 * Used to surface non-fatal scale conditions (e.g. notification fan-out
	 * truncation) without adding noise to production sites.
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	private function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug-gated diagnostic.
			error_log( $message );
		}
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
			/**
			 * Filter the maximum number of recipients for the non-friends
			 * birthday notification fan-out.
			 *
			 * BB-4: previously hard-capped at 500 with no notice. The cap still
			 * exists to protect against unbounded notification writes on very
			 * large communities, but it is now adjustable and the truncation is
			 * logged below instead of being silent.
			 *
			 * @param int $limit   Default 500. Use 0 / -1 for no cap (unbounded).
			 * @param int $user_id The birthday user whose birthday is being announced.
			 */
			$recipient_limit = (int) apply_filters( 'bb_birthdays_notification_recipient_limit', 500, $user_id );
			$query_number    = ( $recipient_limit > 0 ) ? $recipient_limit : -1;

			$recipients = get_users(
				array(
					'fields'  => 'ID',
					'number'  => $query_number,
					'exclude' => array( $user_id ),
				)
			);

			// Surface the truncation rather than silently dropping recipients.
			// Gated on WP_DEBUG up front so the count_users() call below (which
			// is itself expensive on large sites) only runs on debug builds.
			if ( $recipient_limit > 0 && count( $recipients ) >= $recipient_limit
				&& defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$total_members = (int) count_users()['total_users'];

				if ( $total_members - 1 > $recipient_limit ) {
					$this->log(
						sprintf(
							/* translators: 1: cap, 2: total candidate members, 3: birthday user id */
							'BP Birthdays: non-friends notification fan-out truncated to %1$d of %2$d eligible members for birthday user %3$d. Raise via the "bb_birthdays_notification_recipient_limit" filter.',
							$recipient_limit,
							max( 0, $total_members - 1 ),
							$user_id
						)
					);
				}
			}
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

		// Cron/CLI fallback default — see the note in post_birthday_activity().
		$text = $this->get_setting(
			'notification_text',
			__( "It's {name}'s birthday today!", 'buddypress-birthdays' )
		);
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

			$avatar = function_exists( 'bp_core_fetch_avatar' )
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

			$list_html .= '<li style="margin-bottom:10px;padding:10px;background:#f9f9f9;border-radius:5px;">';

			// Avatar block.
			if ( $avatar ) {
				$list_html .= '<span style="display:inline-block;vertical-align:middle;margin-right:8px;">' . $avatar . '</span>';
			}

			// Text block.
			$list_html .= '<span style="display:inline-block;vertical-align:middle;">';
			$list_html .= '<a href="' . esc_url( $profile_url ) . '" style="color:#007CFF;text-decoration:none;">';
			$list_html .= '<strong>' . esc_html( $birthday['display_name'] ) . '</strong>';
			$list_html .= '</a>';
			$list_html .= '<span style="color:#666;"> ' . sprintf(
				/* translators: %d: Age the person is turning. */
				__( '(Turning %d)', 'buddypress-birthdays' ),
				$birthday['age']
			) . '</span>';
			$list_html .= '</span>';

			$list_html .= '</li>';

			$list_plain .= '• ' . sprintf(
				/* translators: 1: Member display name, 2: Age the member is turning. */
				__( '%1$s (Turning %2$d)', 'buddypress-birthdays' ),
				$birthday['display_name'],
				$birthday['age']
			) . "\n";
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
