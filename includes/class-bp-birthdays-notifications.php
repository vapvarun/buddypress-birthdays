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
	 * Temporary progress of a notification fan-out that spans several chunks: one entry per
	 * birthday and day, removed when its last chunk is written. Autoload off.
	 *
	 * @var string
	 */
	const NOTIFY_STATE_OPTION = 'bp_birthdays_notify_state';

	/**
	 * Hook of the scheduled action that writes the next chunk of a notification fan-out.
	 *
	 * @var string
	 */
	const NOTIFY_CHUNK_HOOK = 'bp_birthdays_notify_chunk';

	/**
	 * Action Scheduler group of the plugin's queued work.
	 *
	 * @var string
	 */
	const AS_GROUP = 'buddypress-birthdays';

	/**
	 * Day (Y-m-d) the daily check is processing, so a fan-out knows which day it belongs to.
	 *
	 * @var string
	 */
	private $processing_day = '';

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
	 * Value of bp_birthdays_emails_installed once both email posts exist and
	 * their types are described. Older installs stored true (1), so they run
	 * the check once more and get the type descriptions.
	 *
	 * @var int
	 */
	const EMAILS_INSTALLED_VERSION = 2;

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

		// Handle cron execution. A retry event carries the date it retries.
		add_action( self::CRON_HOOK, array( $this, 'process_daily_birthdays' ) );

		// Later chunks of a large notification fan-out, one scheduled action each.
		add_action( self::NOTIFY_CHUNK_HOOK, array( $this, 'run_notification_chunk' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'notice_unfinished_fanout' ) );

		// Register BP notification component.
		add_action( 'bp_setup_globals', array( $this, 'register_notification_component' ) );

		// Format BP notifications.
		add_filter( 'bp_notifications_get_registered_components', array( $this, 'register_notification_component_filter' ) );
		add_filter( 'bp_notifications_get_notifications_for_user', array( $this, 'format_notification' ), 10, 8 );

		// Install the email posts after BuddyPress installs its own (fresh
		// install, upgrades, Tools > Reinstall emails). The types are kept out
		// of bp_email_get_schema: BuddyPress would insert them too (duplicates)
		// and read a type description it has no filter for (PHP warnings).
		add_action( 'bp_core_install_emails', array( $this, 'install_emails' ) );

		// The admin summary greets the admin in its own body.
		add_filter( 'bp_email_get_salutation', array( $this, 'filter_admin_summary_salutation' ) );

		// Create emails on plugin load if they don't exist.
		add_action( 'admin_init', array( $this, 'maybe_install_emails' ) );

		// Register birthday celebration activity action.
		add_action( 'bp_register_activity_actions', array( $this, 'register_activity_action' ) );
	}

	/**
	 * Register birthday celebration activity action with BuddyPress.
	 */
	public function register_activity_action() {
		if ( ! function_exists( 'bp_activity_set_action' ) ) {
			return;
		}

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
			$user_link = BP_Birthdays_Helpers::get_user_url( $activity->user_id );
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
	 * Email post content for our custom email types.
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
	 * Situation text BuddyPress shows for an email type under Emails.
	 *
	 * @param string $email_type Email type.
	 * @return string
	 */
	private function get_email_description( $email_type ) {
		return self::EMAIL_TYPE_ADMIN_SUMMARY === $email_type
			? __( 'Site admin receives a daily summary of member birthdays.', 'buddypress-birthdays' )
			: __( 'A member receives a birthday greeting from the site.', 'buddypress-birthdays' );
	}

	/**
	 * Drop BuddyPress' "Hi {{recipient.name}}," from the admin summary.
	 *
	 * The summary goes to an address, not a member, and opens with its own
	 * "Hi Admin,". BuddyPress has no filter to mark a plugin email type as
	 * unnamed, so the salutation is cleared for this type here.
	 *
	 * @param string $salutation Salutation BuddyPress is about to print.
	 * @return string
	 */
	public function filter_admin_summary_salutation( $salutation ) {
		if ( function_exists( 'bp_email_get_type' ) && self::EMAIL_TYPE_ADMIN_SUMMARY === bp_email_get_type() ) {
			return '';
		}

		return $salutation;
	}

	/**
	 * Get birthday email HTML content.
	 *
	 * @return string
	 */
	private function get_birthday_email_content() {
		$content = '<p>' . '<strong>' . __( 'Happy Birthday!', 'buddypress-birthdays' ) . '</strong>' . '</p>' . "\n\n";

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
	 * Install email templates after BuddyPress installs its own.
	 */
	public function install_emails() {
		$this->ensure_email_posts();
	}

	/**
	 * Create each birthday email that has no published post, and describe its type.
	 *
	 * @return bool True when both email posts exist.
	 */
	private function ensure_email_posts() {
		$installed = true;

		foreach ( array( self::EMAIL_TYPE_BIRTHDAY, self::EMAIL_TYPE_ADMIN_SUMMARY ) as $email_type ) {
			if ( ! $this->email_post_exists( $email_type ) && ! $this->create_email_post( $email_type ) ) {
				$installed = false;
				continue;
			}

			$term = get_term_by( 'slug', $email_type, bp_get_email_tax_type() );
			if ( $term && '' === $term->description ) {
				wp_update_term( $term->term_id, bp_get_email_tax_type(), array( 'description' => $this->get_email_description( $email_type ) ) );
			}
		}

		return $installed;
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
		if ( self::EMAILS_INSTALLED_VERSION <= (int) get_option( 'bp_birthdays_emails_installed' ) ) {
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

		// A failed insert leaves the flag unset, so the next admin_init retries.
		if ( $this->ensure_email_posts() ) {
			update_option( 'bp_birthdays_emails_installed', self::EMAILS_INSTALLED_VERSION );
		}
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
	 * @return bool True when the post was created with its email type.
	 */
	private function create_email_post( $email_type ) {
		$schema = $this->register_email_schema( array() );

		if ( ! isset( $schema[ $email_type ] ) ) {
			return false;
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

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return false;
		}

		// Same call BuddyPress uses: creates the type term on first use.
		$terms = wp_set_object_terms( $post_id, $email_type, bp_get_email_tax_type() );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			// A post without its type is never sent; drop it so the retry
			// does not leave a stray template behind.
			wp_delete_post( $post_id, true );
			return false;
		}

		return true;
	}

	/**
	 * The configured Send Time (HH:MM, site time), 09:00 when unset or invalid.
	 *
	 * @return string
	 */
	private function get_send_time() {
		$send_time = (string) $this->get_setting( 'email_send_time', '09:00' );

		return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $send_time ) ? $send_time : '09:00';
	}

	/**
	 * Schedule the daily cron job at the configured Send Time.
	 *
	 * Runs on every init. When an event already exists at a different time of
	 * day (the Send Time was changed, or a DST shift moved the fixed 24h
	 * interval) it is cleared and scheduled again at the configured time.
	 */
	public function schedule_cron() {
		$send_time = $this->get_send_time();
		$timezone  = wp_timezone();
		$next      = wp_next_scheduled( self::CRON_HOOK );

		if ( $next ) {
			$next_time = ( new DateTime( '@' . $next ) )->setTimezone( $timezone )->format( 'H:i' );
			if ( $next_time === $send_time ) {
				return;
			}
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}

		$now       = new DateTime( 'now', $timezone );
		$scheduled = new DateTime( 'today ' . $send_time, $timezone );

		if ( $now > $scheduled ) {
			$scheduled->modify( '+1 day' );
		}

		wp_schedule_event( $scheduled->getTimestamp(), 'daily', self::CRON_HOOK );
	}

	/**
	 * Process the birthdays of one day - main cron handler.
	 *
	 * The tracking option holds, per day, which channels already went out for
	 * each member, plus whether the admin summary was sent. A channel that
	 * fails is left unrecorded and a retry for that same day is queued an hour
	 * later, so a failed send is retried without repeating the channels that
	 * worked, even when the retry runs after midnight. Older tracking rows (a
	 * plain timestamp) count as fully sent.
	 *
	 * @param string $date Day to process (Y-m-d, site time). Empty for the
	 *                     daily event: see resolve_target_date().
	 */
	public function process_daily_birthdays( $date = '' ) {
		$target    = $this->resolve_target_date( $date );
		$day       = $target->format( 'Y-m-d' );
		$birthdays = $this->get_todays_birthdays( $target );

		if ( empty( $birthdays ) ) {
			return;
		}

		$this->processing_day = $day;
		$tracking             = $this->get_sent_tracking( $target );
		$sent_tracking        = isset( $tracking[ $day ] ) ? $tracking[ $day ] : array();

		$channels = array(
			'email'        => 'send_birthday_email',
			'activity'     => 'post_birthday_activity',
			'notification' => 'send_bp_notifications',
		);
		$failed   = false;

		foreach ( $birthdays as $user_id => $birthday_data ) {
			$done = isset( $sent_tracking[ $user_id ] ) ? $sent_tracking[ $user_id ] : array();
			if ( ! is_array( $done ) ) {
				continue; // Pre-2.5.1 row: this member was fully processed.
			}

			foreach ( $channels as $channel => $method ) {
				if ( ! empty( $done[ $channel ] ) || ! $this->get_setting( $channel . '_enabled' ) ) {
					continue;
				}

				$sent = ( 'email' === $channel )
					? $this->in_recipient_locale( $user_id, self::EMAIL_TYPE_BIRTHDAY, array( $this, $method ), array( $user_id, $birthday_data ) )
					: $this->$method( $user_id, $birthday_data );

				if ( 'pending' === $sent ) {
					continue; // A later chunk finishes it; the scheduled action marks the channel done.
				}

				if ( $sent ) {
					$done[ $channel ] = time();
				} else {
					$failed = true;
				}
			}

			$sent_tracking[ $user_id ] = $done;
		}

		// One admin summary per day, however many times the cron runs.
		if ( $this->get_setting( 'admin_email_enabled' ) && empty( $sent_tracking['admin_summary'] ) ) {
			$summary_to   = (string) $this->get_setting( 'admin_email', get_option( 'admin_email' ) );
			$summary_user = get_user_by( 'email', $summary_to );

			if ( $this->in_recipient_locale( $summary_user ? $summary_user->ID : 0, self::EMAIL_TYPE_ADMIN_SUMMARY, array( $this, 'send_admin_summary' ), array( $birthdays ) ) ) {
				$sent_tracking['admin_summary'] = time();
			} else {
				$failed = true;
			}
		}

		// A chunk action may have finished a member's notifications while this run was busy.
		$latest = get_option( self::SENT_TRACKING_OPTION, array() );
		if ( isset( $latest[ $day ] ) && is_array( $latest[ $day ] ) ) {
			foreach ( $latest[ $day ] as $member_id => $member_done ) {
				if ( is_array( $member_done ) && ! empty( $member_done['notification'] ) && isset( $sent_tracking[ $member_id ] ) && is_array( $sent_tracking[ $member_id ] ) && empty( $sent_tracking[ $member_id ]['notification'] ) ) {
					$sent_tracking[ $member_id ]['notification'] = $member_done['notification'];
				}
			}
		}

		$tracking[ $day ] = $sent_tracking;
		update_option( self::SENT_TRACKING_OPTION, $tracking, false );

		// Retries stop once the day is older than yesterday, the same window
		// the tracking keeps (get_sent_tracking()).
		$yesterday = current_datetime()->modify( '-1 day' )->format( 'Y-m-d' );
		if ( $failed && $day >= $yesterday && ! wp_next_scheduled( self::CRON_HOOK, array( $day ) ) ) {
			wp_schedule_single_event( time() + HOUR_IN_SECONDS, self::CRON_HOOK, array( $day ) );
		}
	}

	/**
	 * The day a cron run processes.
	 *
	 * A retry passes its own day. The daily event runs at the Send Time, so a
	 * run before today's Send Time is a late run of yesterday's event (a quiet
	 * site, or a send time just before midnight) and processes yesterday.
	 *
	 * @param string $date Y-m-d from a retry event, or empty.
	 * @return DateTimeImmutable Midnight of the day, site timezone.
	 */
	private function resolve_target_date( $date ) {
		$now = current_datetime();

		$target = is_string( $date ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date )
			? DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $now->getTimezone() )
			: false;

		if ( ! $target ) {
			$target = $now->setTime( 0, 0 );
			if ( $now->format( 'H:i' ) < $this->get_send_time() ) {
				$target = $target->modify( '-1 day' );
			}
		}

		return $target;
	}

	/**
	 * Sent tracking keyed by day, pruned to yesterday and later.
	 *
	 * Rows written before 2.5.1 (one day, not keyed) move under the day
	 * bp_birthdays_last_check_date recorded for them.
	 *
	 * @param DateTimeImmutable $target Day being processed.
	 * @return array Y-m-d => tracking rows.
	 */
	private function get_sent_tracking( DateTimeImmutable $target ) {
		$tracking = get_option( self::SENT_TRACKING_OPTION, array() );
		if ( ! is_array( $tracking ) ) {
			$tracking = array();
		}

		if ( $tracking && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) array_key_first( $tracking ) ) ) {
			$legacy_day = (string) get_option( 'bp_birthdays_last_check_date', '' );
			$tracking   = '' !== $legacy_day ? array( $legacy_day => $tracking ) : array();
			delete_option( 'bp_birthdays_last_check_date' );
		}

		$oldest = min( $target->format( 'Y-m-d' ), current_datetime()->modify( '-1 day' )->format( 'Y-m-d' ) );
		foreach ( array_keys( $tracking ) as $day ) {
			if ( (string) $day < $oldest ) {
				unset( $tracking[ $day ] );
			}
		}

		return $tracking;
	}

	/**
	 * Get the birthdays celebrated on a day.
	 *
	 * @param DateTimeImmutable $target Day, site timezone.
	 * @return array Array of user_id => birthday_data.
	 */
	private function get_todays_birthdays( DateTimeImmutable $target ) {
		global $wpdb;

		$field_id = BP_Birthdays_Helpers::get_birthday_field_id();

		if ( empty( $field_id ) ) {
			return array();
		}

		// One or two stored month-days: Feb 29 birthdays are celebrated on
		// Feb 28 in other years, the same rule the widget lists them by.
		$month_days = BP_Birthdays_Helpers::birthday_month_days( $target );

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
		 * data rows".
		 */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.user_id, d.value, u.display_name, u.user_email
				FROM {$wpdb->prefix}bp_xprofile_data d USE INDEX (field_id)
				JOIN {$wpdb->users} u ON d.user_id = u.ID
				WHERE d.field_id = %d
				AND d.value != ''
				AND DATE_FORMAT(d.value, '%%m-%%d') IN (%s, %s)",
				$field_id,
				$month_days[0],
				end( $month_days )
			),
			ARRAY_A
		);

		$birthdays = array();

		if ( $results ) {
			foreach ( $results as $row ) {
				// GDPR: skip members who opted out of showing their birthday.
				// This single gate excludes them from greeting emails, activity
				// posts, BuddyPress notifications and the admin summary at once.
				if ( BP_Birthdays_Helpers::is_user_opted_out( $row['user_id'] ) ) {
					continue;
				}

				// The age turned on this day: right for a late or retried run
				// and for a Feb 29 birthday kept on Feb 28.
				$age = (int) $target->format( 'Y' ) - (int) substr( $row['value'], 0, 4 );

				$birthdays[ $row['user_id'] ] = array(
					'user_id'      => $row['user_id'],
					'display_name' => $row['display_name'],
					'user_email'   => $row['user_email'],
					'birth_date'   => $row['value'],
					'age'          => $age,
					// BuddyPress field privacy, honoured per channel: the
					// activity post, notification recipients and admin summary
					// only see a birthday the member allows them to see.
					'visibility'   => function_exists( 'xprofile_get_field_visibility_level' )
						? xprofile_get_field_visibility_level( $field_id, (int) $row['user_id'] )
						: 'public',
				);
			}
		}

		return $birthdays;
	}

	/**
	 * Send birthday email to user using BuddyPress email system.
	 *
	 * The greeting goes only to the member, so it is sent whatever their field
	 * visibility (the opt-out still removes them from every channel).
	 *
	 * @param int   $user_id User ID.
	 * @param array $birthday_data Birthday data.
	 * @return bool Whether the greeting was handed to a mailer.
	 */
	private function send_birthday_email( $user_id, $birthday_data ) {
		$user = get_userdata( $user_id );

		if ( ! $user || empty( $user->user_email ) ) {
			return true; // Nothing to send to; retrying would not help.
		}

		if ( function_exists( 'bp_send_email' ) ) {
			$result = bp_send_email(
				self::EMAIL_TYPE_BIRTHDAY,
				$user_id,
				array(
					'tokens' => array(
						'recipient.name' => $birthday_data['display_name'],
						'recipient.url'  => BP_Birthdays_Helpers::get_user_url( $user_id ),
						'birthday.age'   => $birthday_data['age'],
						'site.name'      => get_bloginfo( 'name' ),
						'site.url'       => home_url(),
					),
				)
			);

			if ( ! is_wp_error( $result ) ) {
				return true;
			}

			// A missing or trashed BP email template, or a delivery error:
			// send the plain greeting instead of dropping it.
			$this->log_failure( 'birthday greeting to user ' . $user_id . ' via BuddyPress', $result );
		}

		$sent = $this->send_birthday_email_fallback( $user_id, $birthday_data );
		if ( ! $sent ) {
			$this->log_failure( 'birthday greeting to user ' . $user_id . ' via wp_mail' );
		}

		return $sent;
	}

	/**
	 * Fallback email method using wp_mail.
	 *
	 * @param int   $user_id User ID.
	 * @param array $birthday_data Birthday data.
	 * @return bool wp_mail() result.
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
		$sent = wp_mail( $user->user_email, $subject, $message );
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		return $sent;
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
	 * The sitewide feed can be read by visitors, so only a birthday whose
	 * field visibility is public is announced there.
	 *
	 * @param int   $user_id User ID.
	 * @param array $birthday_data Birthday data.
	 * @return bool False only when BuddyPress failed to save the post.
	 */
	private function post_birthday_activity( $user_id, $birthday_data ) {
		if ( ! function_exists( 'bp_activity_add' ) || ! bp_is_active( 'activity' ) ) {
			return true;
		}

		if ( ! BP_Birthdays_Helpers::visibility_allows( $birthday_data['visibility'], $user_id, 0 ) ) {
			return true;
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

		$profile_url = BP_Birthdays_Helpers::get_user_url( $user_id );
		$name_link   = '<a href="' . esc_url( $profile_url ) . '">' . esc_html( $birthday_data['display_name'] ) . '</a>';

		$replacements = array(
			'{name}'        => $name_link,
			'{age}'         => $birthday_data['age'],
			'{profile_url}' => $profile_url,
		);

		$action = str_replace( array_keys( $replacements ), array_values( $replacements ), $message );

		return (bool) bp_activity_add(
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
	 * Record a failed birthday send in the PHP error log.
	 *
	 * Always logged (not WP_DEBUG-gated): a lost email is something the site
	 * owner needs to find, and it only fires on failure.
	 *
	 * @param string        $what  What failed.
	 * @param WP_Error|null $error The error, when there is one.
	 * @return void
	 */
	private function log_failure( $what, $error = null ) {
		$detail = is_wp_error( $error ) ? ' (' . $error->get_error_code() . ': ' . $error->get_error_message() . ')' : '';

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Failure record for the site owner.
		error_log( 'BP Birthdays: could not send ' . $what . $detail . '.' );
	}

	/**
	 * Send BuddyPress notifications about birthday.
	 *
	 * Recipients are limited to members who may see the birthday under the
	 * field's visibility, and members who opted out never get these pings.
	 *
	 * @param int   $user_id User ID.
	 * @param array $birthday_data Birthday data.
	 * @return bool|string True when done, false when BuddyPress could not save a row (the retry
	 *                     writes it), 'pending' while later chunks are queued.
	 */
	private function send_bp_notifications( $user_id, $birthday_data ) {
		if ( ! function_exists( 'bp_notifications_add_notification' ) || ! bp_is_active( 'notifications' ) ) {
			return true;
		}

		// "Only Me" (BuddyPress stores it as adminsonly, BuddyBoss as onlyme)
		// is never broadcast, not even to the admins who can see the profile, and
		// neither is a level nobody registered.
		$visibility = $birthday_data['visibility'];
		if ( in_array( $visibility, array( 'onlyme', 'adminsonly' ), true ) || ! BP_Birthdays_Helpers::is_known_visibility( $visibility ) ) {
			return true;
		}

		$day = '' !== $this->processing_day ? $this->processing_day : BP_Birthdays_Helpers::today()->format( 'Y-m-d' );

		return $this->notify_chunk( $day, $user_id, $visibility );
	}

	/**
	 * Write one chunk of a member's birthday notifications and arrange the rest.
	 *
	 * The audience is every member BuddyPress lets see the birthday: all members, or the
	 * member's friends for the Friends level and the Notify Friends Only setting. It is read in
	 * chunks of bb_birthdays_notification_recipient_limit members by ascending ID after a
	 * cursor, and each chunk is filtered for visibility and opt-out, so a restricted level never
	 * loses its audience to a cut made before the filter. The cursor is kept in a temporary
	 * option until the last chunk; each further chunk is its own scheduled action.
	 *
	 * @param string $day        Day (Y-m-d) the birthday belongs to.
	 * @param int    $user_id    Birthday member.
	 * @param string $visibility Visibility level of the birthday.
	 * @return bool|string True when every chunk is written, false when BuddyPress could not
	 *                     save a row (retried), 'pending' when more chunks are queued.
	 */
	private function notify_chunk( $day, $user_id, $visibility ) {
		global $wpdb;

		$lock = 'bp_birthdays_lock_' . md5( $day . ':' . $user_id );
		if ( ! add_option( $lock, time(), '', false ) ) {
			// Another run holds this member. A lock older than 10 minutes is a crashed run's.
			if ( time() - (int) get_option( $lock ) < 10 * MINUTE_IN_SECONDS ) {
				return 'pending';
			}
			update_option( $lock, time(), false );
		}

		try {
			$friends_active = function_exists( 'friends_get_friend_user_ids' ) && bp_is_active( 'friends' );
			$friends_only   = $this->get_setting( 'notification_friends_only' ) || 'friends' === $visibility;

			// Friends only, by setting or by field privacy. Without the Friends component nobody
			// qualifies: never fall through to every member.
			if ( $friends_only && ! $friends_active ) {
				return true;
			}

			/**
			 * Filters how many members one chunk of the birthday notification fan-out reads.
			 *
			 * Every eligible member is notified whatever the value; it only sets the size of the
			 * scheduled chunks. Below 1 uses 500.
			 *
			 * @param int $size    Default 500.
			 * @param int $user_id The birthday member.
			 */
			$size = (int) apply_filters( 'bb_birthdays_notification_recipient_limit', 500, $user_id );
			$size = $size < 1 ? 500 : $size;

			$key    = $day . ':' . $user_id;
			$states = get_option( self::NOTIFY_STATE_OPTION, array() );
			$states = is_array( $states ) ? $states : array();
			$cursor = isset( $states[ $key ]['cursor'] ) ? (int) $states[ $key ]['cursor'] : 0;

			if ( $friends_only ) {
				$ids = array_map( 'absint', (array) friends_get_friend_user_ids( $user_id ) );
				sort( $ids );
				$chunk = array_slice( array_values( array_filter( $ids, static function ( $id ) use ( $cursor ) {
					return $id > $cursor;
				} ) ), 0, $size );
			} else {
				// Primary key, ascending, after the cursor: one cheap indexed read per chunk.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cursor read of the core users table, no WP API pages by ID.
				$chunk = array_map( 'absint', (array) $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE ID > %d AND ID <> %d ORDER BY ID ASC LIMIT %d", $cursor, $user_id, $size ) ) );
			}

			$last_id = $chunk ? max( $chunk ) : $cursor;

			// Each recipient must be someone BuddyPress lets see this birthday: the same rule the
			// widget uses, so a level an add-on registers is honoured too. The batch's friendships
			// are read in one query first, so the per-recipient check runs from cache.
			// One meta query for the chunk: the visibility rules, add-ons hooked into them, and the
			// opt-out below all read user meta.
			update_meta_cache( 'user', $chunk );

			$recipients = $chunk;
			if ( $recipients && $friends_active && method_exists( 'BP_Friends_Friendship', 'update_bp_friends_cache' ) ) {
				BP_Friends_Friendship::update_bp_friends_cache( $user_id, $recipients );
			}
			$recipients = array_values(
				array_filter(
					$recipients,
					static function ( $recipient_id ) use ( $visibility, $user_id ) {
						return BP_Birthdays_Helpers::visibility_allows( $visibility, $user_id, $recipient_id );
					}
				)
			);

			// GDPR: a member who opted out gets no birthday notifications either.
			$recipients = array_values(
				array_filter(
					$recipients,
					static function ( $recipient_id ) {
						return ! BP_Birthdays_Helpers::is_user_opted_out( $recipient_id );
					}
				)
			);

			// BuddyPress refuses (returns false) to add a notification when the member still has an
			// unread one for the same item and action, which is what a birthday is every year, so
			// duplicates are allowed and today's rows are skipped here, one query for the chunk.
			// That also makes a re-run, an overlap or a resumed chunk write nothing twice.
			$failed = false;
			if ( $recipients ) {
				$start_utc = BP_Birthdays_Helpers::today()->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
				$existing  = BP_Notifications_Notification::get(
					array(
						'user_id'          => $recipients,
						'item_id'          => $user_id,
						'component_name'   => 'birthdays',
						'component_action' => 'birthday_today',
						'is_new'           => 'both',
						'per_page'         => false,
						'date_query'       => array( array( 'after' => $start_utc, 'inclusive' => true ) ),
					)
				);
				$have      = array_map( 'intval', wp_list_pluck( (array) $existing, 'user_id' ) );

				foreach ( array_diff( $recipients, $have ) as $recipient_id ) {
					$id = bp_notifications_add_notification(
						array(
							'user_id'           => $recipient_id,
							'item_id'           => $user_id,
							'secondary_item_id' => 0,
							'component_name'    => 'birthdays',
							'component_action'  => 'birthday_today',
							'date_notified'     => bp_core_current_time(),
							'is_new'            => 1,
							'allow_duplicate'   => true,
						)
					);

					if ( ! $id ) {
						$failed = true;
					}
				}
			}

			if ( $failed ) {
				// The cursor stays, so the hourly retry rewrites this chunk and skips what is saved.
				$this->log( sprintf( 'BP Birthdays: a birthday notification for member %d could not be saved; it will be retried.', $user_id ) );
				return false;
			}

			if ( count( $chunk ) < $size ) {
				unset( $states[ $key ] );
				$states ? update_option( self::NOTIFY_STATE_OPTION, $states, false ) : delete_option( self::NOTIFY_STATE_OPTION );
				return true;
			}

			$states[ $key ] = array(
				'cursor'  => $last_id,
				'updated' => time(),
			);
			update_option( self::NOTIFY_STATE_OPTION, $states, false );
			$this->queue_notification_chunk( $day, $user_id );

			return 'pending';
		} finally {
			delete_option( $lock );
		}
	}

	/**
	 * Queue the next chunk: Action Scheduler when present, WP-Cron otherwise.
	 *
	 * @param string $day     Day (Y-m-d).
	 * @param int    $user_id Birthday member.
	 */
	private function queue_notification_chunk( $day, $user_id ) {
		$args = array( $day, (int) $user_id );

		if ( function_exists( 'as_enqueue_async_action' ) && function_exists( 'as_has_scheduled_action' ) ) {
			if ( ! as_has_scheduled_action( self::NOTIFY_CHUNK_HOOK, $args, self::AS_GROUP ) ) {
				as_enqueue_async_action( self::NOTIFY_CHUNK_HOOK, $args, self::AS_GROUP );
			}
			return;
		}

		if ( ! wp_next_scheduled( self::NOTIFY_CHUNK_HOOK, $args ) ) {
			wp_schedule_single_event( time() + 10, self::NOTIFY_CHUNK_HOOK, $args );
		}
	}

	/**
	 * Scheduled action: write the next chunk of a member's birthday notifications.
	 *
	 * @param string $day     Day (Y-m-d).
	 * @param int    $user_id Birthday member.
	 */
	public function run_notification_chunk( $day, $user_id ) {
		$day     = (string) $day;
		$user_id = absint( $user_id );
		$field   = BP_Birthdays_Helpers::get_birthday_field_id();

		if ( ! $user_id || ! $field || ! function_exists( 'xprofile_get_field_visibility_level' ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $day ) ) {
			return;
		}

		$result = $this->notify_chunk( $day, $user_id, xprofile_get_field_visibility_level( $field, $user_id ) );

		if ( true === $result ) {
			$tracking = get_option( self::SENT_TRACKING_OPTION, array() );
			if ( isset( $tracking[ $day ][ $user_id ] ) && is_array( $tracking[ $day ][ $user_id ] ) ) {
				$tracking[ $day ][ $user_id ]['notification'] = time();
				update_option( self::SENT_TRACKING_OPTION, $tracking, false );
			}
		} elseif ( false === $result ) {
			// BuddyPress could not save a row: try this chunk again in an hour.
			wp_schedule_single_event( time() + HOUR_IN_SECONDS, self::NOTIFY_CHUNK_HOOK, array( $day, $user_id ) );
		}
	}

	/**
	 * Tell the admin on the plugin's screen when a notification fan-out has stopped part way.
	 */
	public function notice_unfinished_fanout() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( (string) $screen->id, 'bp-birthday-settings' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$states = get_option( self::NOTIFY_STATE_OPTION, array() );
		$stuck  = 0;
		foreach ( (array) $states as $state ) {
			if ( isset( $state['updated'] ) && time() - (int) $state['updated'] > 6 * HOUR_IN_SECONDS ) {
				++$stuck;
			}
		}

		if ( $stuck ) {
			echo '<div class="notice notice-warning"><p>' . esc_html(
				sprintf(
					/* translators: %d: number of birthdays */
					_n(
						'The notifications for %d birthday have not finished sending. They resume on the next scheduled run; check that WP-Cron or Action Scheduler is running.',
						'The notifications for %d birthdays have not finished sending. They resume on the next scheduled run; check that WP-Cron or Action Scheduler is running.',
						$stuck,
						'buddypress-birthdays'
					),
					$stuck
				)
			) . '</p></div>';
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
		$user_link = BP_Birthdays_Helpers::get_user_url( $item_id );

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
	 * Site admins can see a BuddyPress "Only Me" (adminsonly) field on the
	 * profile, so it stays in their summary. BuddyBoss' stricter onlyme
	 * level is left out.
	 *
	 * @param array $birthdays Today's birthdays.
	 * @return bool Whether the summary was handed to a mailer (true when there is nothing to send).
	 */
	private function send_admin_summary( $birthdays ) {
		// The summary goes to one address. A member's birthday is listed when its level is known,
		// is not the strict Only Me, and, for a level an add-on registers (not one of the four
		// BuddyPress ships), when BuddyPress lets the account that owns this address see it.
		$summary_to   = (string) $this->get_setting( 'admin_email', get_option( 'admin_email' ) );
		$summary_user = get_user_by( 'email', $summary_to );
		$built_in     = array( 'public', 'loggedin', 'friends', 'adminsonly' );

		$birthdays = array_filter(
			$birthdays,
			static function ( $birthday ) use ( $summary_user, $built_in ) {
				$level = $birthday['visibility'];

				if ( 'onlyme' === $level || ! BP_Birthdays_Helpers::is_known_visibility( $level ) ) {
					return false;
				}

				return in_array( $level, $built_in, true )
					|| ( $summary_user && BP_Birthdays_Helpers::visibility_allows( $level, $birthday['user_id'], $summary_user->ID ) );
			}
		);

		if ( empty( $birthdays ) ) {
			return true;
		}

		$admin_email = $this->get_setting( 'admin_email' );

		if ( empty( $admin_email ) ) {
			$admin_email = get_option( 'admin_email' );
		}

		// Build birthday list HTML.
		$list_html  = '<ul style="list-style: none; padding: 0;">';
		$list_plain = '';

		foreach ( $birthdays as $birthday ) {
			$profile_url = BP_Birthdays_Helpers::get_user_url( $birthday['user_id'] );

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

			$result = bp_send_email( self::EMAIL_TYPE_ADMIN_SUMMARY, $admin_email, $args );
			if ( ! is_wp_error( $result ) ) {
				return true;
			}

			$this->log_failure( 'admin birthday summary via BuddyPress', $result );
		}

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
		$sent = wp_mail( $admin_email, $subject, $message );
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		if ( ! $sent ) {
			$this->log_failure( 'admin birthday summary via wp_mail' );
		}

		return $sent;
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private function get_setting( $key, $default = null ) {
		// Read the option directly: BP_Birthdays_Admin is only loaded in
		// is_admin(), and cron must behave the same in both contexts.
		$settings = get_option( 'bp_birthdays_settings', array() );
		$value    = is_array( $settings ) && isset( $settings[ $key ] ) ? $settings[ $key ] : null;

		// A cleared text box (Notification Text, Activity Message, ...) means
		// "use the default", never an empty notification or post.
		if ( null === $value || ( is_string( $value ) && '' === trim( $value ) ) ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Run a send in the recipient's language.
	 *
	 * BuddyPress renders its emails in the site language, and the two birthday templates are
	 * stored once, at install, in the site language. So the send runs under the recipient's
	 * locale, and a template that is still the untouched default is swapped for the default in
	 * that locale. A template the site owner edited is sent exactly as edited.
	 *
	 * @param int      $user_id    Recipient, 0 when the address belongs to no member.
	 * @param string   $email_type BuddyPress email type being sent.
	 * @param callable $send       The send method.
	 * @param array    $args       Its arguments.
	 * @return bool What the send returned.
	 */
	private function in_recipient_locale( $user_id, $email_type, $send, array $args ) {
		$switched = $user_id ? switch_to_user_locale( $user_id ) : false;
		$fields   = array(
			'bp_email_set_subject'           => 'post_title',
			'bp_email_set_content_html'      => 'post_content',
			'bp_email_set_content_plaintext' => 'post_excerpt',
		);
		$filters  = array();

		if ( $switched ) {
			foreach ( $fields as $hook => $field ) {
				$filters[ $hook ] = function ( $value ) use ( $email_type, $field ) {
					$here = $this->register_email_schema( array() );

					// The default as it was installed: the site's language.
					switch_to_locale( (string) ( get_option( 'WPLANG' ) ? get_option( 'WPLANG' ) : 'en_US' ) );
					$installed = $this->register_email_schema( array() );
					restore_previous_locale();

					return ( isset( $installed[ $email_type ][ $field ] ) && $value === $installed[ $email_type ][ $field ] ) ? $here[ $email_type ][ $field ] : $value;
				};
				add_filter( $hook, $filters[ $hook ] );
			}
		}

		try {
			return (bool) call_user_func_array( $send, $args );
		} finally {
			foreach ( $filters as $hook => $callback ) {
				remove_filter( $hook, $callback );
			}
			if ( $switched ) {
				restore_previous_locale();
			}
		}
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
add_action(
	'bp_loaded',
	function () {
		if ( bb_birthdays_xprofile_active() ) {
			BP_Birthdays_Notifications::get_instance();
		}
	}
);
