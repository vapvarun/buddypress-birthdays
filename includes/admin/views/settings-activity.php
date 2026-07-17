<?php
/**
 * Settings tab: Activity Feed.
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

$bbd_option          = BP_Birthdays_Admin_Panel::OPTION_NAME;
$bbd_activity_active = function_exists( 'bp_is_active' ) && bp_is_active( 'activity' );
$bbd_activity_on     = ! empty( $settings['activity_enabled'] );

// Sentinel for this tab's bool keys.
$bbd_rendered_keys = array( 'activity_enabled' );
foreach ( $bbd_rendered_keys as $bbd_rendered_key ) :
	?>
	<input type="hidden" name="<?php echo esc_attr( $bbd_option ); ?>[bp_birthdays_tab_rendered_keys][]" value="<?php echo esc_attr( $bbd_rendered_key ); ?>">
<?php endforeach; ?>

<?php if ( ! $bbd_activity_active ) : ?>
	<div class="bbd-notice bbd-notice--warn">
		<span class="dashicons dashicons-warning" aria-hidden="true"></span>
		<span><?php esc_html_e( 'The BuddyPress Activity component is not active. Enable it in BuddyPress → Settings → Components to use this feature.', 'buddypress-birthdays' ); ?></span>
	</div>
<?php endif; ?>

<div class="bbd-card">
	<div class="bbd-card__head">
		<p class="bbd-card__title"><?php esc_html_e( 'Activity Feed Posts', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-card__desc"><?php esc_html_e( 'Automatically add a post to the site activity feed when a member has a birthday, so the whole community can wish them.', 'buddypress-birthdays' ); ?></p>
	</div>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Activity Posts', 'buddypress-birthdays' ); ?></th>
			<td>
				<label>
					<input type="hidden" name="<?php echo esc_attr( $bbd_option ); ?>[activity_enabled]" value="0">
					<input type="checkbox"
							name="<?php echo esc_attr( $bbd_option ); ?>[activity_enabled]"
							value="1"
							<?php checked( $bbd_activity_on ); ?>
							<?php disabled( ! $bbd_activity_active ); ?>>
					<?php esc_html_e( 'Post to the activity feed on member birthdays', 'buddypress-birthdays' ); ?>
				</label>
			</td>
		</tr>
		<tr class="activity-dependent">
			<th scope="row">
				<label for="activity_message"><?php esc_html_e( 'Activity Message', 'buddypress-birthdays' ); ?></label>
			</th>
			<td>
				<input type="text"
						name="<?php echo esc_attr( $bbd_option ); ?>[activity_message]"
						id="activity_message"
						value="<?php echo esc_attr( isset( $settings['activity_message'] ) ? $settings['activity_message'] : '' ); ?>"
						class="large-text">
				<p class="description">
					<?php esc_html_e( 'Available placeholders: {name}, {age}, {profile_url}', 'buddypress-birthdays' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>
