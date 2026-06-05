<?php
/**
 * WB Plugins hub — landing dashboard at ?page=wbcomplugins.
 *
 * Lists every Wbcom plugin that has registered a submenu under the
 * shared wbcomplugins parent. No manual registration needed: peer
 * plugins appear automatically once their admin page is in the
 * WordPress menu table.
 *
 * Legacy wbcom-wrapper plugins register four boilerplate helper pages
 * (Our Plugins, Our Themes, Support, License) under this hub. Those are
 * not real plugins and must not appear as cards — we filter them out via
 * wbcom_hub_wrapper_helper_slugs.
 * See references/wbcom-wrapper-migration.md Part 16.
 *
 * @package BP_Birthdays
 * @since   2.5.0
 */

defined( 'ABSPATH' ) || exit;

$bbd_submenu_entries = isset( $GLOBALS['submenu']['wbcomplugins'] ) && is_array( $GLOBALS['submenu']['wbcomplugins'] )
	? $GLOBALS['submenu']['wbcomplugins']
	: array();

// Slugs of the legacy wbcom-wrapper boilerplate helper pages. Every
// un-migrated Wbcom plugin still registers these four shared pages under
// the hub. They are not plugins, so they must not appear as cards on the
// dashboard. Filterable so Pro modules or future wrapper slugs can be
// added.
$bbd_wrapper_helper_slugs = apply_filters(
	'wbcom_hub_wrapper_helper_slugs',
	array(
		'wbcom-plugins-page',
		'wbcom-themes-page',
		'wbcom-support-page',
		'wbcom-license-page',
	)
);

$bbd_plugins = array();
foreach ( $bbd_submenu_entries as $bbd_entry ) {
	$bbd_slug = isset( $bbd_entry[2] ) ? (string) $bbd_entry[2] : '';
	if ( '' === $bbd_slug || 'wbcomplugins' === $bbd_slug ) {
		continue;
	}
	if ( in_array( $bbd_slug, $bbd_wrapper_helper_slugs, true ) ) {
		continue;
	}
	$bbd_plugins[] = array(
		'slug'       => $bbd_slug,
		'menu_title' => isset( $bbd_entry[0] ) ? wp_strip_all_tags( (string) $bbd_entry[0] ) : $bbd_slug,
		'page_title' => isset( $bbd_entry[3] ) ? wp_strip_all_tags( (string) $bbd_entry[3] ) : '',
		'url'        => admin_url( 'admin.php?page=' . rawurlencode( $bbd_slug ) ),
	);
}

$bbd_plugin_count = count( $bbd_plugins );
?>
<div class="wrap bbd-admin">
	<header class="bbd-page-header">
		<div class="bbd-page-header__title">
			<span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
			<div>
				<h1><?php esc_html_e( 'WB Plugins', 'buddypress-birthdays' ); ?></h1>
				<p class="bbd-page-header__subtitle">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: active Wbcom plugin count */
							_n(
								'%d Wbcom plugin active on this site.',
								'%d Wbcom plugins active on this site.',
								$bbd_plugin_count,
								'buddypress-birthdays'
							),
							$bbd_plugin_count
						)
					);
					?>
				</p>
			</div>
		</div>
	</header>

	<?php if ( 0 === $bbd_plugin_count ) : ?>
		<div class="bbd-empty-state">
			<span class="bbd-empty-state__icon" aria-hidden="true">
				<span class="dashicons dashicons-lightbulb"></span>
			</span>
			<p class="bbd-empty-state__title"><?php esc_html_e( 'No Wbcom plugins attached to this hub yet', 'buddypress-birthdays' ); ?></p>
			<p class="bbd-empty-state__desc">
				<?php esc_html_e( 'Activate one or more Wbcom plugins and they will appear here automatically.', 'buddypress-birthdays' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="bbd-hub-grid">
			<?php foreach ( $bbd_plugins as $bbd_p ) : ?>
				<a href="<?php echo esc_url( $bbd_p['url'] ); ?>" class="bbd-hub-card">
					<span class="bbd-hub-card__icon" aria-hidden="true">
						<span class="dashicons dashicons-admin-plugins"></span>
					</span>
					<span class="bbd-hub-card__title"><?php echo esc_html( $bbd_p['menu_title'] ); ?></span>
					<?php if ( ! empty( $bbd_p['page_title'] ) && $bbd_p['page_title'] !== $bbd_p['menu_title'] ) : ?>
						<span class="bbd-hub-card__subtitle"><?php echo esc_html( $bbd_p['page_title'] ); ?></span>
					<?php endif; ?>
					<span class="bbd-hub-card__cta">
						<?php esc_html_e( 'Open settings', 'buddypress-birthdays' ); ?>
						<span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="bbd-card bbd-card--spaced">
		<div class="bbd-card__head">
			<p class="bbd-card__title"><?php esc_html_e( 'About WB Plugins', 'buddypress-birthdays' ); ?></p>
		</div>
		<div class="bbd-card__body">
			<p class="bbd-card__lead">
				<?php esc_html_e( 'This hub is the single entry point for every Wbcom Designs plugin installed on your site. Each plugin lives on its own page under this menu and keeps its own settings, licence, and data.', 'buddypress-birthdays' ); ?>
			</p>
			<p>
				<a href="https://wbcomdesigns.com/" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Visit wbcomdesigns.com for more plugins and themes →', 'buddypress-birthdays' ); ?>
				</a>
			</p>
		</div>
	</div>
</div>
