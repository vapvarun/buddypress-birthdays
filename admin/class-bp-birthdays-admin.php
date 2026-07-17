<?php
/**
 * Admin Settings Page for BuddyPress Birthdays
 *
 * @package BP_Birthdays
 * @since 2.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BP_Birthdays_Admin
 *
 * Legacy admin service class. As of 2.5.0 the admin UI (menu, enqueue,
 * settings page render) moved to BP_Birthdays_Admin_Panel — the
 * card-panel controller under the shared WB Plugins (wbcomplugins) hub.
 *
 * This class is retained ONLY for the option contract that the new panel
 * delegates to:
 *  - sanitize_settings()  — the canonical sanitizer for all 13 subkeys.
 *  - get_settings()       — settings getter with the defaults applied.
 *  - get_defaults()       — the single source of truth for defaults.
 *
 * Its menu/enqueue/render methods (add_admin_menu, register_settings,
 * enqueue_admin_assets, render_settings_page and the per-tab renderers)
 * are no longer hooked; they remain as reference only. The plugin no
 * longer registers a page under bp-settings / options-general.php.
 */
class BP_Birthdays_Admin {

	/**
	 * Option name for storing settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'bp_birthdays_settings';

	/**
	 * Default settings — the single source of truth for every subkey.
	 *
	 * Built by a method rather than a property initializer because two of the
	 * defaults (activity_message, notification_text) are member-facing copy
	 * that must pass through __(). PHP does not allow a function call in a
	 * property default, and resolving a label before the textdomain loads on
	 * init:10 would return English anyway (docs/standards/i18n.md trap 4).
	 * Every caller reaches these through get_settings(), which runs at render
	 * time, so the textdomain is always loaded by then.
	 *
	 * @since 2.5.0
	 *
	 * @return array Default settings.
	 */
	public static function get_defaults() {
		return array(
			// General.
			'default_field_id'          => '',
			'cache_duration'            => 30,
			// Email Notifications (content is managed in BP Emails).
			'email_enabled'             => false,
			'email_send_time'           => '09:00',
			'admin_email_enabled'       => false,
			'admin_email'               => '',
			// Activity Feed. {name}/{age}/{profile_url} are placeholders the
			// site owner can re-order, so the whole sentence is one unit.
			'activity_enabled'          => false,
			'activity_message'          => __( "Today is {name}'s birthday! Send your wishes!", 'buddypress-birthdays' ),
			// BP Notifications.
			'notification_enabled'      => false,
			'notification_friends_only' => false,
			'notification_text'         => __( "It's {name}'s birthday today!", 'buddypress-birthdays' ),
			// Display Extras.
			'confetti_enabled'          => false,
			'zodiac_enabled'            => false,
		);
	}

	/**
	 * Instance of this class.
	 *
	 * @var BP_Birthdays_Admin
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return BP_Birthdays_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * No-op as of 2.5.0. The admin UI hooks (menu/enqueue/render) moved
	 * to BP_Birthdays_Admin_Panel; this class is retained only for the
	 * sanitizer + settings getter + defaults.
	 */
	private function __construct() {}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw input from form.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		// Start from the CURRENTLY stored option, not an empty array. The
		// card-panel admin (2.5.0+) splits this single option across five
		// settings tabs (general, email, activity, notifications, display)
		// and each tab posts only its own fields. Rebuilding from an empty
		// array would wipe every key owned by the other four tabs. Merging
		// on top of the stored value keeps untouched tabs intact — the
		// sentinel-guarded merge from references/wbcom-wrapper-migration.md
		// Part 7.1.
		$existing_settings = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $existing_settings ) ) {
			$existing_settings = array();
		}

		$sanitized = $existing_settings;

		// Sentinel: every card-panel settings tab emits a hidden
		// bp_birthdays_settings[bp_birthdays_tab_rendered_keys][] list of
		// the bool keys it rendered. A checkbox left unchecked drops out of
		// $_POST entirely; the sentinel lets us tell "this tab never
		// rendered the key" (keep stored value) apart from "this tab
		// rendered it but the user unchecked it" (set to false). Each tab
		// view ALSO posts a hidden value="0" companion before each
		// checkbox, so the per-key isset() branches below already coerce
		// correctly — the sentinel is belt-and-suspenders for programmatic
		// callers that omit the companion inputs.
		$bool_keys     = array(
			'email_enabled',
			'admin_email_enabled',
			'activity_enabled',
			'notification_enabled',
			'notification_friends_only',
			'confetti_enabled',
			'zodiac_enabled',
		);
		$rendered_keys = array();
		if ( isset( $input['bp_birthdays_tab_rendered_keys'] ) && is_array( $input['bp_birthdays_tab_rendered_keys'] ) ) {
			foreach ( $input['bp_birthdays_tab_rendered_keys'] as $raw_key ) {
				$rendered_keys[] = sanitize_key( $raw_key );
			}
			unset( $input['bp_birthdays_tab_rendered_keys'] );
		}
		foreach ( $bool_keys as $bool_key ) {
			if ( in_array( $bool_key, $rendered_keys, true ) && ! isset( $input[ $bool_key ] ) ) {
				$sanitized[ $bool_key ] = false;
			}
		}

		// General.
		if ( isset( $input['default_field_id'] ) ) {
			$sanitized['default_field_id'] = absint( $input['default_field_id'] );
		}
		if ( isset( $input['cache_duration'] ) ) {
			$sanitized['cache_duration'] = absint( $input['cache_duration'] );
		}

		// Email Notifications (content managed in BP Emails).
		if ( isset( $input['email_enabled'] ) ) {
			$sanitized['email_enabled'] = '1' === $input['email_enabled'];
		}
		if ( isset( $input['email_send_time'] ) ) {
			$sanitized['email_send_time'] = sanitize_text_field( $input['email_send_time'] );
		}
		if ( isset( $input['admin_email_enabled'] ) ) {
			$sanitized['admin_email_enabled'] = '1' === $input['admin_email_enabled'];
		}
		if ( isset( $input['admin_email'] ) ) {
			$sanitized['admin_email'] = sanitize_email( $input['admin_email'] );
		}

		// Activity Feed.
		if ( isset( $input['activity_enabled'] ) ) {
			$sanitized['activity_enabled'] = '1' === $input['activity_enabled'];
		}
		if ( isset( $input['activity_message'] ) ) {
			$sanitized['activity_message'] = sanitize_text_field( $input['activity_message'] );
		}

		// BP Notifications.
		if ( isset( $input['notification_enabled'] ) ) {
			$sanitized['notification_enabled'] = '1' === $input['notification_enabled'];
		}
		if ( isset( $input['notification_friends_only'] ) ) {
			$sanitized['notification_friends_only'] = '1' === $input['notification_friends_only'];
		}
		if ( isset( $input['notification_text'] ) ) {
			$sanitized['notification_text'] = sanitize_text_field( $input['notification_text'] );
		}

		// Display Extras.
		if ( isset( $input['confetti_enabled'] ) ) {
			$sanitized['confetti_enabled'] = '1' === $input['confetti_enabled'];
		}
		if ( isset( $input['zodiac_enabled'] ) ) {
			$sanitized['zodiac_enabled'] = '1' === $input['zodiac_enabled'];
		}

		return $sanitized;
	}

	/**
	 * Get plugin settings.
	 *
	 * @param string $key Optional. Specific setting key to retrieve.
	 * @return mixed All settings array or specific setting value.
	 */
	public static function get_settings( $key = null ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$settings = wp_parse_args( $settings, self::get_defaults() );

		if ( null !== $key ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
		}

		return $settings;
	}
}

// Initialize the admin class.
add_action( 'plugins_loaded', array( 'BP_Birthdays_Admin', 'get_instance' ) );
