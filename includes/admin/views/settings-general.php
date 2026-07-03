<?php
/**
 * Settings tab: General (default field + cache duration).
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

$bbd_date_fields = BP_Birthdays_Admin_Panel::get_date_fields();
$bbd_field_id    = isset( $settings['default_field_id'] ) ? $settings['default_field_id'] : '';
$bbd_cache       = isset( $settings['cache_duration'] ) ? (int) $settings['cache_duration'] : 30;
?>

<div class="bbd-card">
	<div class="bbd-card__head">
		<p class="bbd-card__title"><?php esc_html_e( 'Birthday Field', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-card__desc"><?php esc_html_e( 'Tell the plugin which member profile field holds the birthday date. Everything else — emails, activity posts, notifications, the widget — reads from this field.', 'buddypress-birthdays' ); ?></p>
	</div>
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="default_field_id"><?php esc_html_e( 'Default Birthday Field', 'buddypress-birthdays' ); ?></label>
			</th>
			<td>
				<select name="<?php echo esc_attr( BP_Birthdays_Admin_Panel::OPTION_NAME ); ?>[default_field_id]" id="default_field_id">
					<option value=""><?php esc_html_e( '— Select Field —', 'buddypress-birthdays' ); ?></option>
					<?php foreach ( $bbd_date_fields as $bbd_field ) : ?>
						<option value="<?php echo esc_attr( $bbd_field['id'] ); ?>" <?php selected( (string) $bbd_field_id, (string) $bbd_field['id'] ); ?>>
							<?php echo esc_html( $bbd_field['name'] ); ?> (<?php echo esc_html( $bbd_field['type'] ); ?>)
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Select the default xProfile field for birthdays. Widgets can override this.', 'buddypress-birthdays' ); ?>
				</p>
				<?php if ( empty( $bbd_date_fields ) ) : ?>
					<p class="description bbd-text-danger">
						<?php esc_html_e( 'No datebox or birthdate xProfile fields were found. Create one under Users → Profile Fields first.', 'buddypress-birthdays' ); ?>
					</p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="cache_duration"><?php esc_html_e( 'Cache Duration', 'buddypress-birthdays' ); ?></label>
			</th>
			<td>
				<input type="number"
						name="<?php echo esc_attr( BP_Birthdays_Admin_Panel::OPTION_NAME ); ?>[cache_duration]"
						id="cache_duration"
						value="<?php echo esc_attr( (string) $bbd_cache ); ?>"
						min="1"
						max="1440"
						class="small-text"> <?php esc_html_e( 'minutes', 'buddypress-birthdays' ); ?>
				<p class="description">
					<?php esc_html_e( 'How long to cache birthday data. Lower values mean fresher data but more database queries.', 'buddypress-birthdays' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>
