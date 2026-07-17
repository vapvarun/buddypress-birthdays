<?php
/**
 * Discover partial: ecosystem cross-promotion (read-only display view).
 *
 * Rendered inside shell.php. This is a pure presentation view - product
 * cards with outbound links to other free Wbcom Designs tools. No forms,
 * no settings, no options, no AJAX.
 *
 * @package BP_Birthdays
 * @since   2.5.0
 */

defined( 'ABSPATH' ) || exit;

$bbd_ecosystem_img = BIRTHDAY_WIDGET_PLUGIN_URL . 'assets/images/ecosystem/';

/*
 * Each product: brand mark (shipped in assets/images/ecosystem/), name,
 * a short admin-specific blurb (worded differently from readme.txt), and
 * the wbcomdesigns.com download URL.
 */
$bbd_ecosystem = array(
	array(
		'name' => 'BuddyX',
		'logo' => 'buddyx.png',
		'icon' => 'admin-appearance',
		'desc' => __( 'A free, fast community theme for BuddyPress, BuddyBoss and PeepSo with a modern layout and dark mode.', 'buddypress-birthdays' ),
		'url'  => 'https://wbcomdesigns.com/downloads/buddyx-theme/',
	),
	array(
		'name' => 'BuddyNext',
		'logo' => 'buddynext.svg',
		'desc' => __( 'A full social network for WordPress with feeds, profiles, and messaging.', 'buddypress-birthdays' ),
		'url'  => 'https://wbcomdesigns.com/downloads/buddynext/',
	),
	array(
		'name' => 'Jetonomy',
		'logo' => 'jetonomy.svg',
		'desc' => __( 'Forums, Q&A, and idea spaces that scale and moderate themselves.', 'buddypress-birthdays' ),
		'url'  => 'https://wbcomdesigns.com/downloads/jetonomy/',
	),
	array(
		'name' => 'Mediaverse',
		'logo' => 'mediaverse.svg',
		'desc' => __( 'Photo and video sharing with albums, reactions, and private chat.', 'buddypress-birthdays' ),
		'url'  => 'https://wbcomdesigns.com/downloads/mediaverse/',
	),
	array(
		'name' => 'Eventonomy',
		'logo' => 'eventonomy.svg',
		'icon' => 'calendar-alt',
		'desc' => __( 'Run community events with RSVPs, calendars, and front-end submissions.', 'buddypress-birthdays' ),
		'url'  => 'https://wbcomdesigns.com/downloads/eventonomy/',
	),
	array(
		'name' => 'WB Gamification',
		'logo' => 'wb-gamification.svg',
		'icon' => 'awards',
		'desc' => __( 'Reward members with points, badges, and leaderboards to keep engagement high.', 'buddypress-birthdays' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wordpress-gamification-plugin/',
	),
	array(
		'name' => 'Listora',
		'logo' => 'listora.svg',
		'desc' => __( 'Searchable directories with reviews, maps, and member submissions.', 'buddypress-birthdays' ),
		'url'  => 'https://wbcomdesigns.com/downloads/listora/',
	),
	array(
		'name' => 'WP Career Board',
		'logo' => 'wp-career-board.svg',
		'icon' => 'businessman',
		'desc' => __( 'Add a job board with front-end listings, applications, and employer profiles.', 'buddypress-birthdays' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wp-career-board/',
	),
	array(
		'name' => 'Learnomy',
		'logo' => 'learnomy.svg',
		'desc' => __( 'A free LMS to sell courses, run quizzes, and award certificates.', 'buddypress-birthdays' ),
		'url'  => 'https://wbcomdesigns.com/downloads/learnomy/',
	),
	array(
		'name' => 'WP Sell Services',
		'logo' => 'wp-sell-services.svg',
		'icon' => 'cart',
		'desc' => __( 'A free service marketplace with vendor dashboards, an 11-status order flow, and Stripe or PayPal built in.', 'buddypress-birthdays' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wp-sell-services/',
	),
);
?>

<div class="bbd-card">
	<div class="bbd-card__head">
		<p class="bbd-card__title"><?php esc_html_e( 'More Free Tools from Wbcom Designs', 'buddypress-birthdays' ); ?></p>
		<p class="bbd-card__desc"><?php esc_html_e( 'Birthday reminders keep members coming back. These free Wbcom Designs plugins give your community more reasons to stay: the theme and network itself, forums, media, events, gamification, directories, jobs, courses, and services.', 'buddypress-birthdays' ); ?></p>
	</div>
	<div class="bbd-card__body">
		<div class="bbd-discover-grid">
			<?php foreach ( $bbd_ecosystem as $bbd_product ) : ?>
				<div class="bbd-discover-card">
					<span class="bbd-discover-card__logo" aria-hidden="true">
						<img src="<?php echo esc_url( $bbd_ecosystem_img . $bbd_product['logo'] ); ?>" alt="<?php echo esc_attr( $bbd_product['name'] ); ?>" width="52" height="52" loading="lazy" />
					</span>
					<h3 class="bbd-discover-card__title"><?php echo esc_html( $bbd_product['name'] ); ?></h3>
					<p class="bbd-discover-card__desc"><?php echo esc_html( $bbd_product['desc'] ); ?></p>
					<a class="bbd-btn bbd-btn-secondary bbd-discover-card__cta" href="<?php echo esc_url( $bbd_product['url'] ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'Get it free', 'buddypress-birthdays' ); ?>
						<span class="dashicons dashicons-external" aria-hidden="true"></span>
						<span class="screen-reader-text">
							<?php
							/* translators: %s: product name. */
							echo esc_html( sprintf( __( '%s (opens in a new tab)', 'buddypress-birthdays' ), $bbd_product['name'] ) );
							?>
						</span>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
