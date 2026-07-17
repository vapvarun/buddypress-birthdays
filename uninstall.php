<?php
/**
 * Uninstall handler for Wbcom Designs - Birthday Widget for BuddyPress.
 *
 * Removes all plugin data when the plugin is deleted via the WordPress
 * admin: the settings option, the notification tracking options, the
 * per-user wish-tracking meta, and any scheduled cron events.
 *
 * @package BP_Birthdays
 */

// Exit if uninstall is not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Plugin options.
delete_option( 'bp_birthdays_settings' );
delete_option( 'bp_birthdays_emails_installed' );
delete_option( 'bp_birthdays_last_check_date' );
delete_option( 'bp_birthdays_sent_today' );

// Multisite: remove per-site options on every site.
if ( is_multisite() ) {
	delete_site_option( 'bp_birthdays_settings' );
	delete_site_option( 'bp_birthdays_emails_installed' );
	delete_site_option( 'bp_birthdays_last_check_date' );
	delete_site_option( 'bp_birthdays_sent_today' );
}

// Wish-tracking user meta (all users).
delete_metadata( 'user', 0, 'bb_birthday_wished_users', '', true );

// Scheduled events (already cleared on deactivation; belt and braces).
wp_clear_scheduled_hook( 'bb_cleanup_old_wishes' );
wp_clear_scheduled_hook( 'bp_birthdays_daily_check' );
