<?php
/**
 * Uninstall handler for Wbcom Designs - Birthday Widget for BuddyPress.
 *
 * Removes plugin CONFIGURATION and DERIVED data when the plugin is deleted via
 * the WordPress admin: the settings option, the notification tracking options,
 * the per-user wish-tracking meta, and any scheduled cron events.
 *
 * DELIBERATELY RETAINED - the `bb_birthday_hidden` user meta:
 *
 * That meta is the member's own GDPR opt-out ("hide my birthday everywhere",
 * BP_Birthdays_Helpers::OPTOUT_META_KEY, set from BuddyPress Settings >
 * General). It is a consent decision made BY THE MEMBER, not configuration set
 * by the site owner, so it is not this handler's to discard. Deleting it would
 * fail OPEN: uninstall + reinstall (a routine troubleshooting or migration
 * step) would silently republish the birthday of every member who had asked to
 * be hidden, without asking them again. Keeping it fails CLOSED - the worst
 * case is a stale row for a member who never returns, which is strictly safer
 * than re-exposing personal data. It is also cheap to reverse: the member can
 * untick the box themselves at any time (bb_save_member_privacy_field() calls
 * delete_user_meta() when unchecked), and an admin can clear it in bulk.
 *
 * This retention is intentional and owner-facing. If the policy is ever changed
 * to purge the opt-out on uninstall, update this docblock in the same commit.
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
