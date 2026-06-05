<?php
/**
 * Settings tab: BuddyPress Notifications.
 *
 * @package BP_Birthdays
 * @since   2.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Parent view provides $settings.
 *
 * @var array $settings bp_birthdays_settings (defaults applied).
 */

$bbd_option       = BP_Birthdays_Admin_Panel::OPTION_NAME;
$bbd_notif_active = function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' );
$bbd_notif_on     = ! empty( $settings['notification_enabled'] );
$bbd_friends_only = ! empty( $settings['notification_friends_only'] );

// Sentinel for this tab's bool keys.
$bbd_rendered_keys = array( 'notification_enabled', 'notification_friends_only' );
foreach ( $bbd_rendered_keys as $bbd_rendered_key ) :
	?>
	<input type="hidden" name="<?php echo esc_attr( $bbd_option ); ?>[bp_birthdays_tab_rendered_keys][]" value="<?php echo esc_attr( $bbd_rendered_key ); ?>">
<?php endforeach; ?>

<?php if ( ! $bbd_notif_active ) : ?>
	<div class="bbd-notice bbd-notice--warn">
		<span class="dashicons dashicons-warning" aria-hidden="true"></span>
		<span><?php esc_html_e( 'The BuddyPress Notifications component is not active. Enable it in BuddyPress → Settings → Components to use this feature.', 'buddypress-birthdays' ); ?></span>
	</div>
<?php endif; ?>

<div class="bbd-card">
	<div class="bbd-card__head">
		<p class="bbd-card__title"><?php esc_html_e( 'Birthday Notifications', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-card__desc"><?php esc_html_e( 'Send members a BuddyPress notification so they know about a birthday and can send their wishes.', 'buddypress-birthdays' ); ?></p>
	</div>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Notifications', 'buddypress-birthdays' ); ?></th>
			<td>
				<label>
					<input type="hidden" name="<?php echo esc_attr( $bbd_option ); ?>[notification_enabled]" value="0">
					<input type="checkbox"
							name="<?php echo esc_attr( $bbd_option ); ?>[notification_enabled]"
							value="1"
							<?php checked( $bbd_notif_on ); ?>
							<?php disabled( ! $bbd_notif_active ); ?>>
					<?php esc_html_e( 'Send BuddyPress notifications about member birthdays', 'buddypress-birthdays' ); ?>
				</label>
			</td>
		</tr>
		<tr class="notification-dependent">
			<th scope="row"><?php esc_html_e( 'Who to Notify', 'buddypress-birthdays' ); ?></th>
			<td>
				<label>
					<input type="hidden" name="<?php echo esc_attr( $bbd_option ); ?>[notification_friends_only]" value="0">
					<input type="checkbox"
							name="<?php echo esc_attr( $bbd_option ); ?>[notification_friends_only]"
							value="1"
							<?php checked( $bbd_friends_only ); ?>>
					<?php esc_html_e( 'Only notify friends of the birthday member', 'buddypress-birthdays' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'If unchecked, all members are notified.', 'buddypress-birthdays' ); ?>
				</p>
			</td>
		</tr>
		<tr class="notification-dependent">
			<th scope="row">
				<label for="notification_text"><?php esc_html_e( 'Notification Text', 'buddypress-birthdays' ); ?></label>
			</th>
			<td>
				<input type="text"
						name="<?php echo esc_attr( $bbd_option ); ?>[notification_text]"
						id="notification_text"
						value="<?php echo esc_attr( isset( $settings['notification_text'] ) ? $settings['notification_text'] : '' ); ?>"
						class="large-text">
				<p class="description">
					<?php esc_html_e( 'Available placeholder: {name}', 'buddypress-birthdays' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>
