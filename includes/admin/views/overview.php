<?php
/**
 * Overview dashboard partial: rendered inside shell.php.
 *
 * @package BP_Birthdays
 * @since   2.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Parent view provides $settings + $page_url.
 *
 * @var array  $settings bp_birthdays_settings (defaults applied).
 * @var string $page_url Base URL (admin.php?page=bp-birthday-settings).
 */

$bbd_field_id    = isset( $settings['default_field_id'] ) ? (int) $settings['default_field_id'] : 0;
$bbd_date_fields = BP_Birthdays_Admin_Panel::get_date_fields();
$bbd_field_count = count( $bbd_date_fields );

// Resolve the configured field's label, if any.
$bbd_field_label = '';
foreach ( $bbd_date_fields as $bbd_field ) {
	if ( (int) $bbd_field['id'] === $bbd_field_id && $bbd_field_id > 0 ) {
		$bbd_field_label = $bbd_field['name'];
		break;
	}
}

// Feature status flags.
$bbd_email_on        = ! empty( $settings['email_enabled'] );
$bbd_admin_email_on  = ! empty( $settings['admin_email_enabled'] );
$bbd_activity_on     = ! empty( $settings['activity_enabled'] );
$bbd_notification_on = ! empty( $settings['notification_enabled'] );
$bbd_confetti_on     = ! empty( $settings['confetti_enabled'] );
$bbd_zodiac_on       = ! empty( $settings['zodiac_enabled'] );

// Count of channels currently switched on.
$bbd_channels_on = (int) $bbd_email_on + (int) $bbd_activity_on + (int) $bbd_notification_on;

$bbd_cache_minutes = isset( $settings['cache_duration'] ) ? (int) $settings['cache_duration'] : 30;

$bbd_general_url  = $page_url . '&tab=general';
$bbd_email_url    = $page_url . '&tab=email';
$bbd_activity_url = $page_url . '&tab=activity';
$bbd_notif_url    = $page_url . '&tab=notifications';
$bbd_display_url  = $page_url . '&tab=display';
?>

<?php if ( 0 === $bbd_field_id ) : ?>
	<div class="bbd-notice bbd-notice--warn">
		<span class="dashicons dashicons-warning" aria-hidden="true"></span>
		<span>
			<?php esc_html_e( 'No default birthday field is set yet. Pick the xProfile date field that holds member birthdays so emails, activity posts, and notifications know where to read from.', 'buddypress-birthdays' ); ?>
			<a href="<?php echo esc_url( $bbd_general_url ); ?>" class="bbd-inline-hint"><?php esc_html_e( 'Set it now', 'buddypress-birthdays' ); ?></a>
		</span>
	</div>
<?php endif; ?>

<div class="bbd-stats-grid">
	<div class="bbd-stat">
		<p class="bbd-stat__label"><?php esc_html_e( 'Birthday Field', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-stat__value">
			<?php
			if ( '' !== $bbd_field_label ) {
				echo esc_html( $bbd_field_label );
			} else {
				echo esc_html__( 'Not set', 'buddypress-birthdays' );
			}
			?>
		</p>
		<p class="bbd-stat__trend"><?php esc_html_e( 'xProfile field used for birthdays', 'buddypress-birthdays' ); ?></p>
	</div>
	<div class="bbd-stat">
		<p class="bbd-stat__label"><?php esc_html_e( 'Available Date Fields', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-stat__value"><?php echo esc_html( number_format_i18n( $bbd_field_count ) ); ?></p>
		<p class="bbd-stat__trend"><?php esc_html_e( 'Datebox / birthdate xProfile fields', 'buddypress-birthdays' ); ?></p>
	</div>
	<div class="bbd-stat">
		<p class="bbd-stat__label"><?php esc_html_e( 'Channels On', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-stat__value"><?php echo esc_html( number_format_i18n( $bbd_channels_on ) ); ?> / 3</p>
		<p class="bbd-stat__trend"><?php esc_html_e( 'Email, activity, notifications', 'buddypress-birthdays' ); ?></p>
	</div>
	<div class="bbd-stat">
		<p class="bbd-stat__label"><?php esc_html_e( 'Cache Duration', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-stat__value">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: number of minutes */
					_n( '%s min', '%s min', $bbd_cache_minutes, 'buddypress-birthdays' ),
					number_format_i18n( $bbd_cache_minutes )
				)
			);
			?>
		</p>
		<p class="bbd-stat__trend"><?php esc_html_e( 'How long birthday data is cached', 'buddypress-birthdays' ); ?></p>
	</div>
</div>

<div class="bbd-card">
	<div class="bbd-card__head">
		<p class="bbd-card__title"><?php esc_html_e( 'Current Configuration', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-card__desc"><?php esc_html_e( 'What happens right now on member birthdays.', 'buddypress-birthdays' ); ?></p>
	</div>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Birthday emails', 'buddypress-birthdays' ); ?></th>
			<td>
				<?php if ( $bbd_email_on ) : ?>
					<span class="bbd-status bbd-status--on"><?php esc_html_e( 'On: members get a greeting email on their birthday', 'buddypress-birthdays' ); ?></span>
				<?php else : ?>
					<span class="bbd-status bbd-status--off"><?php esc_html_e( 'Off', 'buddypress-birthdays' ); ?></span>
					<a href="<?php echo esc_url( $bbd_email_url ); ?>" class="bbd-inline-hint"><?php esc_html_e( 'Turn on', 'buddypress-birthdays' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Admin summary email', 'buddypress-birthdays' ); ?></th>
			<td>
				<?php if ( $bbd_admin_email_on ) : ?>
					<span class="bbd-status bbd-status--on"><?php esc_html_e( 'On: a daily summary of birthdays is emailed to the admin', 'buddypress-birthdays' ); ?></span>
				<?php else : ?>
					<span class="bbd-status bbd-status--off"><?php esc_html_e( 'Off', 'buddypress-birthdays' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Activity feed posts', 'buddypress-birthdays' ); ?></th>
			<td>
				<?php if ( $bbd_activity_on ) : ?>
					<span class="bbd-status bbd-status--on"><?php esc_html_e( 'On: a post is added to the activity feed on each birthday', 'buddypress-birthdays' ); ?></span>
				<?php else : ?>
					<span class="bbd-status bbd-status--off"><?php esc_html_e( 'Off', 'buddypress-birthdays' ); ?></span>
					<a href="<?php echo esc_url( $bbd_activity_url ); ?>" class="bbd-inline-hint"><?php esc_html_e( 'Turn on', 'buddypress-birthdays' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'BuddyPress notifications', 'buddypress-birthdays' ); ?></th>
			<td>
				<?php if ( $bbd_notification_on ) : ?>
					<span class="bbd-status bbd-status--on"><?php esc_html_e( 'On: members are notified about birthdays', 'buddypress-birthdays' ); ?></span>
				<?php else : ?>
					<span class="bbd-status bbd-status--off"><?php esc_html_e( 'Off', 'buddypress-birthdays' ); ?></span>
					<a href="<?php echo esc_url( $bbd_notif_url ); ?>" class="bbd-inline-hint"><?php esc_html_e( 'Turn on', 'buddypress-birthdays' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Display extras', 'buddypress-birthdays' ); ?></th>
			<td>
				<?php if ( $bbd_confetti_on || $bbd_zodiac_on ) : ?>
					<span class="bbd-status bbd-status--on">
						<?php
						if ( $bbd_confetti_on && $bbd_zodiac_on ) {
							esc_html_e( 'Confetti animation and zodiac signs are on', 'buddypress-birthdays' );
						} elseif ( $bbd_confetti_on ) {
							esc_html_e( 'Confetti animation is on', 'buddypress-birthdays' );
						} else {
							esc_html_e( 'Zodiac signs are on', 'buddypress-birthdays' );
						}
						?>
					</span>
				<?php else : ?>
					<span class="bbd-status bbd-status--off"><?php esc_html_e( 'Off', 'buddypress-birthdays' ); ?></span>
					<a href="<?php echo esc_url( $bbd_display_url ); ?>" class="bbd-inline-hint"><?php esc_html_e( 'Configure', 'buddypress-birthdays' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
	</table>
</div>

<div class="bbd-card">
	<div class="bbd-card__head">
		<p class="bbd-card__title"><?php esc_html_e( 'Quick Actions', 'buddypress-birthdays' ); ?></p>
	</div>
	<div class="bbd-card__body">
		<div class="bbd-save-bar bbd-save-bar--wrap">
			<a href="<?php echo esc_url( $bbd_general_url ); ?>" class="bbd-btn bbd-btn-secondary">
				<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
				<?php esc_html_e( 'General Settings', 'buddypress-birthdays' ); ?>
			</a>
			<a href="<?php echo esc_url( $bbd_email_url ); ?>" class="bbd-btn bbd-btn-secondary">
				<span class="dashicons dashicons-email" aria-hidden="true"></span>
				<?php esc_html_e( 'Email Notifications', 'buddypress-birthdays' ); ?>
			</a>
			<a href="<?php echo esc_url( $bbd_notif_url ); ?>" class="bbd-btn bbd-btn-secondary">
				<span class="dashicons dashicons-bell" aria-hidden="true"></span>
				<?php esc_html_e( 'Notifications', 'buddypress-birthdays' ); ?>
			</a>
		</div>
	</div>
</div>
