<?php
/**
 * Plugin Name: Wbcom Designs - Birthday Widget for BuddyPress
 * Plugin URI: https://wbcomdesigns.com/downloads/buddypress-birthdays/
 * Description: Display upcoming birthdays with optimized performance and memory usage
 * Version: 2.5.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * Text Domain: buddypress-birthdays
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 6.9
 *
 * @link              https://wbcomdesigns.com/contact/
 * @since             1.0.0
 * @package           BP_Birthdays
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'BIRTHDAY_WIDGET_VERSION', '2.5.0' );
define( 'BIRTHDAY_WIDGET_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BIRTHDAY_WIDGET_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

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
				( new BP_Birthdays_Admin_Panel() )->register();
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

/**
 * Activation: schedule the plugin's daily cron events.
 *
 * Both events also self-heal while the plugin is active (core-init.php and
 * BP_Birthdays_Notifications::schedule_cron() re-schedule on init if
 * missing), but activation is the canonical point so the pair with the
 * deactivation cleanup below is complete.
 */
function bb_birthdays_activate() {
	if ( ! wp_next_scheduled( 'bb_cleanup_old_wishes' ) ) {
		wp_schedule_event( time(), 'daily', 'bb_cleanup_old_wishes' );
	}

	if ( class_exists( 'BP_Birthdays_Notifications' ) ) {
		BP_Birthdays_Notifications::get_instance()->schedule_cron();
	}
}
register_activation_hook( __FILE__, 'bb_birthdays_activate' );

/**
 * Deactivation: clear BOTH daily cron events so no orphaned cron persists.
 */
function bb_birthdays_deactivate() {
	wp_clear_scheduled_hook( 'bb_cleanup_old_wishes' );
	wp_clear_scheduled_hook( 'bp_birthdays_daily_check' );
}
register_deactivation_hook( __FILE__, 'bb_birthdays_deactivate' );

/**
 * Check BuddyPress is not activated.
 */
function bb_check_bp_active() {
	if ( ! class_exists( 'BuddyPress' ) ) {
		add_action( 'admin_notices', 'bb_dependent_plugin_notice' );
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
}
add_action( 'admin_init', 'bb_check_bp_active' );

/**
 * Display dependent plugin admin notice.
 */
function bb_dependent_plugin_notice() {
	$bb_plugin = esc_html__( 'Wbcom Designs - Birthday Widget for BuddyPress', 'buddypress-birthdays' );
	$bp_plugin = esc_html__( 'BuddyPress', 'buddypress-birthdays' );

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
