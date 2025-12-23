<?php
/**
 * Plugin Name: Wbcom Designs - Birthday Widget for BuddyPress
 * Plugin URI: https://wbcomdesigns.com/downloads/buddypress-birthdays/
 * Description: Display upcoming birthdays with optimized performance and memory usage
 * Version: 2.3.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * Text Domain: buddypress-birthdays
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @link              https://wbcomdesigns.com/contact/
 * @since             1.0.0
 * @package           BP_Birthdays
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'BIRTHDAY_WIDGET_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BIRTHDAY_WIDGET_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Let's Initialize Everything.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'core-init.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'core-init.php';
}

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
		unset( $_GET['activate'] );
	}
}