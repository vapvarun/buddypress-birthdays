<?php
/**
 * Settings tab: Display Extras (confetti + zodiac).
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

$bbd_option      = BP_Birthdays_Admin_Panel::OPTION_NAME;
$bbd_confetti_on = ! empty( $settings['confetti_enabled'] );
$bbd_zodiac_on   = ! empty( $settings['zodiac_enabled'] );

// Sentinel for this tab's bool keys.
$bbd_rendered_keys = array( 'confetti_enabled', 'zodiac_enabled' );
foreach ( $bbd_rendered_keys as $bbd_rendered_key ) :
	?>
	<input type="hidden" name="<?php echo esc_attr( $bbd_option ); ?>[bp_birthdays_tab_rendered_keys][]" value="<?php echo esc_attr( $bbd_rendered_key ); ?>">
<?php endforeach; ?>

<div class="bbd-card">
	<div class="bbd-card__head">
		<p class="bbd-card__title"><?php esc_html_e( 'Display Extras', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-card__desc"><?php esc_html_e( 'Optional flourishes shown in the birthday widget and shortcode output.', 'buddypress-birthdays' ); ?></p>
	</div>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Confetti Animation', 'buddypress-birthdays' ); ?></th>
			<td>
				<label>
					<input type="hidden" name="<?php echo esc_attr( $bbd_option ); ?>[confetti_enabled]" value="0">
					<input type="checkbox"
							name="<?php echo esc_attr( $bbd_option ); ?>[confetti_enabled]"
							value="1"
							<?php checked( $bbd_confetti_on ); ?>>
					<?php esc_html_e( 'Show a confetti animation for today\'s birthdays', 'buddypress-birthdays' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Adds a celebratory confetti effect when viewing today\'s birthdays.', 'buddypress-birthdays' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Zodiac Sign', 'buddypress-birthdays' ); ?></th>
			<td>
				<label>
					<input type="hidden" name="<?php echo esc_attr( $bbd_option ); ?>[zodiac_enabled]" value="0">
					<input type="checkbox"
							name="<?php echo esc_attr( $bbd_option ); ?>[zodiac_enabled]"
							value="1"
							<?php checked( $bbd_zodiac_on ); ?>>
					<?php esc_html_e( 'Display the zodiac sign next to each birthday', 'buddypress-birthdays' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Shows the zodiac symbol (e.g. ♈ ♉ ♊) based on the birth date.', 'buddypress-birthdays' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>
