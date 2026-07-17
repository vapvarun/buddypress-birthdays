<?php
/**
 * Settings tab: Email Notifications.
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

$bbd_option     = BP_Birthdays_Admin_Panel::OPTION_NAME;
$bbd_emails_url = BP_Birthdays_Admin_Panel::get_bp_emails_url();
$bbd_email_on   = ! empty( $settings['email_enabled'] );
$bbd_admin_on   = ! empty( $settings['admin_email_enabled'] );

// Sentinel: list the bool keys this tab renders so the sanitizer can
// clear a rendered-but-unchecked checkbox without touching keys owned by
// other tabs. See class-bp-birthdays-admin.php::sanitize_settings().
$bbd_rendered_keys = array( 'email_enabled', 'admin_email_enabled' );
foreach ( $bbd_rendered_keys as $bbd_rendered_key ) :
	?>
	<input type="hidden" name="<?php echo esc_attr( $bbd_option ); ?>[bp_birthdays_tab_rendered_keys][]" value="<?php echo esc_attr( $bbd_rendered_key ); ?>">
<?php endforeach; ?>

<div class="bbd-card">
	<div class="bbd-card__head">
		<p class="bbd-card__title"><?php esc_html_e( 'Birthday Greeting Emails', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-card__desc"><?php esc_html_e( 'Send members an automatic greeting on their birthday. The subject and message are managed in BuddyPress Emails.', 'buddypress-birthdays' ); ?></p>
	</div>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Birthday Emails', 'buddypress-birthdays' ); ?></th>
			<td>
				<label>
					<input type="hidden" name="<?php echo esc_attr( $bbd_option ); ?>[email_enabled]" value="0">
					<input type="checkbox"
							name="<?php echo esc_attr( $bbd_option ); ?>[email_enabled]"
							value="1"
							<?php checked( $bbd_email_on ); ?>>
					<?php esc_html_e( 'Send automatic birthday greeting emails to members', 'buddypress-birthdays' ); ?>
				</label>
			</td>
		</tr>
		<tr class="email-dependent">
			<th scope="row"><?php esc_html_e( 'Customize Email Content', 'buddypress-birthdays' ); ?></th>
			<td>
				<p>
					<?php
					printf(
						wp_kses(
							/* translators: %s: URL to BuddyPress Emails admin page. */
							__( 'Birthday email content is managed in <a href="%s">BuddyPress Emails</a>. Look for <strong>"Birthday Greeting"</strong> to customize the subject and message.', 'buddypress-birthdays' ),
							array(
								'a'      => array( 'href' => array() ),
								'strong' => array(),
							)
						),
						esc_url( $bbd_emails_url )
					);
					?>
				</p>
				<p class="description">
					<?php esc_html_e( 'Available tokens: {{{recipient.name}}}, {{{birthday.age}}}, {{{site.name}}}', 'buddypress-birthdays' ); ?>
				</p>
			</td>
		</tr>
		<tr class="email-dependent">
			<th scope="row">
				<label for="email_send_time"><?php esc_html_e( 'Send Time', 'buddypress-birthdays' ); ?></label>
			</th>
			<td>
				<input type="time"
						name="<?php echo esc_attr( $bbd_option ); ?>[email_send_time]"
						id="email_send_time"
						value="<?php echo esc_attr( isset( $settings['email_send_time'] ) ? $settings['email_send_time'] : '09:00' ); ?>">
				<p class="description">
					<?php
					printf(
						/* translators: %s: Site timezone */
						esc_html__( 'Time to send birthday emails (site timezone: %s)', 'buddypress-birthdays' ),
						esc_html( wp_timezone_string() )
					);
					?>
				</p>
			</td>
		</tr>
	</table>
</div>

<div class="bbd-card">
	<div class="bbd-card__head">
		<p class="bbd-card__title"><?php esc_html_e( 'Admin Summary', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-card__desc"><?php esc_html_e( 'Get a daily digest of which members have a birthday today.', 'buddypress-birthdays' ); ?></p>
	</div>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Daily Summary Email', 'buddypress-birthdays' ); ?></th>
			<td>
				<label>
					<input type="hidden" name="<?php echo esc_attr( $bbd_option ); ?>[admin_email_enabled]" value="0">
					<input type="checkbox"
							name="<?php echo esc_attr( $bbd_option ); ?>[admin_email_enabled]"
							value="1"
							<?php checked( $bbd_admin_on ); ?>>
					<?php esc_html_e( 'Send a daily summary of birthdays to the admin', 'buddypress-birthdays' ); ?>
				</label>
			</td>
		</tr>
		<tr class="admin-email-dependent">
			<th scope="row">
				<label for="bbd_admin_email"><?php esc_html_e( 'Summary Recipient', 'buddypress-birthdays' ); ?></label>
			</th>
			<td>
				<input type="email"
						id="bbd_admin_email"
						name="<?php echo esc_attr( $bbd_option ); ?>[admin_email]"
						value="<?php echo esc_attr( isset( $settings['admin_email'] ) ? $settings['admin_email'] : '' ); ?>"
						placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
						class="regular-text">
				<p class="description">
					<?php esc_html_e( 'Leave empty to use the site admin email.', 'buddypress-birthdays' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>
