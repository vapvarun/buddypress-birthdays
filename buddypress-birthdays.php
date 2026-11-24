<?php
/**
 * Plugin Name: Wbcom Designs - Birthday Widget for BuddyPress
 * Plugin URI: https://wbcomdesigns.com/downloads/buddypress-birthdays/
 * Description: Display upcoming birthdays with optimized performance and memory usage
 * Version: 2.5.1
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * Text Domain: buddypress-birthdays
 * Domain Path: /languages
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 6.5
 * Requires PHP: 8.1
 *
 * @link              https://wbcomdesigns.com/contact/
 * @since             1.0.0
 * @package           BP_Birthdays
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// One copy only: stop if this plugin is already loaded from another folder.
if ( defined( 'BIRTHDAY_WIDGET_FOR_BUDDYPRESS_LOADED' ) ) {
	return;
}
define( 'BIRTHDAY_WIDGET_FOR_BUDDYPRESS_LOADED', __FILE__ );

define( 'BIRTHDAY_WIDGET_VERSION', '2.5.1' );
define( 'BIRTHDAY_WIDGET_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BIRTHDAY_WIDGET_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

if ( ! function_exists( 'bb_birthdays_load_textdomain' ) ) {
	/**
	 * Load the plugin textdomain from the bundled languages/ directory.
	 *
	 * Required for this self-hosted (EDD) distribution: WordPress only
	 * auto-loads translations for plugins hosted on WordPress.org, so without
	 * this call the bundled .mo files are never loaded.
	 */
	function bb_birthdays_load_textdomain() {
		load_plugin_textdomain( 'buddypress-birthdays', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
}
add_action( 'init', 'bb_birthdays_load_textdomain' );

if ( ! function_exists( 'bb_birthdays_xprofile_active' ) ) {
	/**
	 * Whether the BuddyPress Extended Profiles component is on.
	 *
	 * Birthdays live in an xProfile date field, so every feature of the plugin
	 * (widget, shortcode, notifications, settings) needs it. Read from the stored
	 * component list so it answers correctly at any point after BuddyPress loads.
	 *
	 * @return bool
	 */
	function bb_birthdays_xprofile_active() {
		$components = function_exists( 'bp_get_option' ) ? bp_get_option( 'bp-active-components', array() ) : array();

		return is_array( $components ) && isset( $components['xprofile'] );
	}
}

if ( ! function_exists( 'bb_birthdays_xprofile_notice' ) ) {
	/**
	 * Tell admins why nothing is registered while Extended Profiles is off.
	 */
	function bb_birthdays_xprofile_notice() {
		if ( ! current_user_can( 'manage_options' ) || bb_birthdays_xprofile_active() ) {
			return;
		}

		$url = admin_url( 'options-general.php?page=bp-components' );
		echo '<div class="notice notice-warning"><p>'
		/* translators: %s: BuddyPress component settings link. */
		. sprintf( wp_kses_post( __( 'The Birthday Widget needs the BuddyPress Extended Profiles component, which is off. Turn it on under %s to use the birthday widget, shortcode and notifications.', 'buddypress-birthdays' ) ), '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings > BuddyPress', 'buddypress-birthdays' ) . '</a>' )
		. '</p></div>';
	}
}
add_action( 'admin_notices', 'bb_birthdays_xprofile_notice' );

// Let's Initialize Everything.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'core-init.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'core-init.php';
}

// Load admin: the card-panel controller (menu, enqueue, render) plus the
// legacy settings class which is retained ONLY for its sanitizer +
// settings getter/defaults (its menu/enqueue/render are no longer hooked).
if ( is_admin() ) {
	if ( file_exists( plugin_dir_path( __FILE__ ) . 'admin/class-bp-birthdays-admin.php' ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-bp-birthdays-admin.php';
	}
	if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/admin/class-bp-birthdays-admin-panel.php' ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/admin/class-bp-birthdays-admin-panel.php';
		add_action(
			'plugins_loaded',
			function () {
				if ( bb_birthdays_xprofile_active() ) {
					( new BP_Birthdays_Admin_Panel() )->register();
				}
			}
		);
	}
}

// Load helper functions.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/class-bp-birthdays-helpers.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bp-birthdays-helpers.php';
}

// Load notifications handler (emails, activity feed, BP notifications).
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/class-bp-birthdays-notifications.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bp-birthdays-notifications.php';
}

if ( ! function_exists( 'bb_birthdays_activate' ) ) {
	/**
	 * Activation: schedule the daily birthday check.
	 *
	 * The event also self-heals while the plugin is active
	 * (BP_Birthdays_Notifications::schedule_cron() re-schedules on init if
	 * missing), but activation is the canonical point so the pair with the
	 * deactivation cleanup below is complete.
	 */
	function bb_birthdays_activate() {
		if ( bb_birthdays_xprofile_active() && class_exists( 'BP_Birthdays_Notifications' ) ) {
			BP_Birthdays_Notifications::get_instance()->schedule_cron();
		}
	}
}
register_activation_hook( __FILE__, 'bb_birthdays_activate' );

if ( ! function_exists( 'bb_birthdays_deactivate' ) ) {
	/**
	 * Deactivation: clear the daily check so no orphaned cron persists.
	 */
	function bb_birthdays_deactivate() {
		// Unschedule every event on the hook, including a queued retry run.
		wp_unschedule_hook( 'bp_birthdays_daily_check' );
		wp_unschedule_hook( 'bp_birthdays_notify_chunk' );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( '', array(), 'buddypress-birthdays' );
		}
		delete_option( 'bp_birthdays_notify_state' );
	}
}
register_deactivation_hook( __FILE__, 'bb_birthdays_deactivate' );

if ( ! function_exists( 'bb_birthdays_activation_redirect' ) ) {
	/**
	 * After a single, manual activation, open the plugin's settings page.
	 *
	 * Runs on activated_plugin, after WordPress has saved the plugin as active,
	 * so no state needs storing. Bulk, network-wide, WP-CLI and AJAX activations
	 * keep WordPress' normal flow.
	 *
	 * @param string $plugin       Activated plugin basename.
	 * @param bool   $network_wide Whether it was activated network-wide.
	 */
	function bb_birthdays_activation_redirect( $plugin, $network_wide ) {
		if ( plugin_basename( __FILE__ ) !== $plugin || $network_wide || wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- Read-only check of the core activate action; WordPress verified its nonce.
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		if ( 'activate' !== $action || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=bp-birthday-settings' ) );
		exit;
	}
}
add_action( 'activated_plugin', 'bb_birthdays_activation_redirect', 10, 2 );

if ( ! function_exists( 'bb_check_bp_active' ) ) {
	/**
	 * Check BuddyPress is not activated.
	 */
	function bb_check_bp_active() {
		if ( ! class_exists( 'BuddyPress' ) ) {
			add_action( 'admin_notices', 'bb_dependent_plugin_notice' );
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
	}
}
add_action( 'admin_init', 'bb_check_bp_active' );

if ( ! function_exists( 'bb_dependent_plugin_notice' ) ) {
	/**
	 * Display dependent plugin admin notice.
	 */
	function bb_dependent_plugin_notice() {
		$bb_plugin = esc_html( 'Wbcom Designs - Birthday Widget for BuddyPress' );
		$bp_plugin = 'BuddyPress';

		echo '<div class="error"><p>'
		/* translators: %1$s: Wbcom Designs - Birthday Widget for BuddyPress, %2$s: BuddyPress */
		. sprintf( esc_html__( '%1$s is ineffective as it requires %2$s to be installed and active.', 'buddypress-birthdays' ), '<strong>' . esc_html( $bb_plugin ) . '</strong>', '<strong>' . esc_html( $bp_plugin ) . '</strong>' )
		. '</p></div>';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking if parameter exists to hide activation notice.
		if ( isset( $_GET['activate'] ) ) {
			$activate = sanitize_text_field( wp_unslash( $_GET['activate'] ) );
			unset( $_GET['activate'] );
		}
	}
}
