<?php
/**
 * Admin page shell: page header, sidebar nav, body slot.
 *
 * Receives from BP_Birthdays_Admin_Panel::render_page():
 *
 * @var array  $bbd_tabs            Tab registry keyed by slug.
 * @var string $active              Active tab slug.
 * @var string $page_url            Base URL (admin.php?page=bp-birthday-settings).
 * @var array  $settings            bp_birthdays_settings option (defaults applied).
 * @var string $view                View slug (e.g. 'overview', 'settings-general').
 * @var string $view_path           Absolute path to the partial.
 * @var bool   $in_settings_group   True when the active tab is a Settings tab.
 * @var string $settings_form_group register_setting() group for settings_fields().
 *
 * @package BP_Birthdays
 * @since   2.5.0
 */

defined( 'ABSPATH' ) || exit;

$bbd_version = defined( 'BIRTHDAY_WIDGET_VERSION' ) ? BIRTHDAY_WIDGET_VERSION : '';
?>
<div class="wrap bbd-admin">

	<header class="bbd-page-header">
		<div class="bbd-page-header__title">
			<span class="dashicons dashicons-buddicons-community" aria-hidden="true"></span>
			<div>
				<h1><?php esc_html_e( 'Birthday Widget for BuddyPress', 'buddypress-birthdays' ); ?></h1>
				<p class="bbd-page-header__subtitle"><?php esc_html_e( 'Show upcoming member birthdays in a widget or shortcode, with optional birthday emails, activity posts, and notifications.', 'buddypress-birthdays' ); ?></p>
			</div>
		</div>
		<div class="bbd-page-header__actions">
			<?php if ( $bbd_version ) : ?>
				<span class="bbd-version-pill">v<?php echo esc_html( $bbd_version ); ?></span>
			<?php endif; ?>
		</div>
	</header>

	<?php
	/*
	 * Without this marker, core's common.js re-parents every .notice to
	 * sit right after the first <h1> it finds, which slots the "Settings
	 * saved" banner between our title and its subtitle instead of below
	 * the whole header.
	 */
	?>
	<hr class="wp-header-end">

	<div class="bbd-settings-layout">

		<aside class="bbd-settings-sidebar">
			<div class="bbd-settings-sidebar-brand">
				<span class="bbd-settings-brand-icon" aria-hidden="true">
					<span class="dashicons dashicons-buddicons-community"></span>
				</span>
				<div class="bbd-settings-brand-text">
					<p class="bbd-settings-brand-name"><?php esc_html_e( 'Birthdays', 'buddypress-birthdays' ); ?></p>
					<p class="bbd-settings-brand-sub"><?php esc_html_e( 'Plugin', 'buddypress-birthdays' ); ?></p>
				</div>
			</div>
			<nav class="bbd-settings-sidebar-nav" aria-label="<?php esc_attr_e( 'Birthdays navigation', 'buddypress-birthdays' ); ?>">
				<?php
				$bbd_printed_groups = array();
				$bbd_group_labels   = array(
					'settings' => esc_html__( 'Settings', 'buddypress-birthdays' ),
					'discover' => esc_html__( 'More Tools', 'buddypress-birthdays' ),
				);
				foreach ( $bbd_tabs as $bbd_slug => $bbd_tab ) {
					$bbd_group = isset( $bbd_tab['group'] ) ? $bbd_tab['group'] : 'main';
					if ( 'main' !== $bbd_group && ! in_array( $bbd_group, $bbd_printed_groups, true ) ) {
						echo '<div class="bbd-snav-divider" role="separator"></div>';
						if ( isset( $bbd_group_labels[ $bbd_group ] ) ) {
							echo '<p class="bbd-snav-section-label">' . esc_html( $bbd_group_labels[ $bbd_group ] ) . '</p>';
						}
						$bbd_printed_groups[] = $bbd_group;
					}
					$bbd_classes  = 'bbd-snav-link';
					$bbd_classes .= $active === $bbd_slug ? ' bbd-snav-link--active' : '';
					echo '<a href="' . esc_url( $page_url . '&tab=' . $bbd_slug ) . '" class="' . esc_attr( $bbd_classes ) . '">';
					echo '<span class="dashicons ' . esc_attr( $bbd_tab['icon'] ) . '" aria-hidden="true"></span>';
					echo esc_html( $bbd_tab['label'] );
					echo '</a>';
				}
				?>

				<div class="bbd-snav-divider" role="separator"></div>
				<p class="bbd-snav-section-label"><?php esc_html_e( 'Resources', 'buddypress-birthdays' ); ?></p>
				<a href="https://docs.wbcomdesigns.com/docs/buddypress-birthday/" class="bbd-snav-link" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-book" aria-hidden="true"></span>
					<?php esc_html_e( 'Documentation', 'buddypress-birthdays' ); ?>
					<span class="dashicons dashicons-external bbd-snav-link__ext" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'buddypress-birthdays' ); ?></span>
				</a>
			</nav>
		</aside>

		<div class="bbd-settings-main">
			<?php settings_errors( 'bp_birthdays_settings' ); ?>
			<?php settings_errors(); ?>
			<?php if ( $in_settings_group ) : ?>
				<form method="post" action="options.php" id="bbd-settings-form">
					<?php settings_fields( $settings_form_group ); ?>
					<?php
					if ( file_exists( $view_path ) ) {
						include $view_path;
					}
					?>
					<div class="bbd-save-bar">
						<?php submit_button( __( 'Save Settings', 'buddypress-birthdays' ), 'primary bbd-btn bbd-btn-primary', 'submit', false ); ?>
					</div>
				</form>
			<?php else : ?>
				<?php
				if ( file_exists( $view_path ) ) {
					include $view_path;
				}
				?>
			<?php endif; ?>
		</div>

	</div>
</div>
