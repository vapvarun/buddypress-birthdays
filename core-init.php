<?php
/**
 * This file initializes all BB Core components.
 *
 * @link              https://wbcomdesigns.com/contact/
 * @since             1.0.0
 * @package           BP_Birthdays
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define Our Constants.
define( 'BB_CORE_INC', __DIR__ . '/assets/inc/' );
define( 'BB_CORE_IMG', plugins_url( 'assets/img/', __FILE__ ) );
define( 'BB_CORE_CSS', plugins_url( 'assets/css/', __FILE__ ) );
define( 'BB_CORE_JS', plugins_url( 'assets/js/', __FILE__ ) );

/**
 * Enqueue the widget's CSS and JS.
 *
 * Called where a birthday list actually renders (the widget and the
 * [bp_birthdays] shortcode), so pages without one load nothing. A style
 * enqueued during render prints in the footer; WordPress handles that.
 */
function bb_enqueue_core_assets() {
	if ( wp_script_is( 'bb-core', 'enqueued' ) || wp_script_is( 'bb-core', 'done' ) ) {
		return;
	}

	// Minified builds unless SCRIPT_DEBUG; keep in step with gruntfile.js cssmin/uglify targets.
	$bb_min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	wp_enqueue_style( 'bb-core', BB_CORE_CSS . 'bb-core' . $bb_min . '.css', array(), BIRTHDAY_WIDGET_VERSION, 'all' );
	wp_enqueue_script( 'bb-core', BB_CORE_JS . 'bb-core' . $bb_min . '.js', array( 'jquery' ), BIRTHDAY_WIDGET_VERSION, true );

	// The confetti switch is read straight from the option: the admin class only loads in wp-admin.
	$confetti_enabled = false;
	$bb_opts          = get_option( 'bp_birthdays_settings', array() );
	if ( is_array( $bb_opts ) && isset( $bb_opts['confetti_enabled'] ) ) {
		$confetti_enabled = (bool) $bb_opts['confetti_enabled'];
	}

	wp_localize_script(
		'bb-core',
		'bbBirthdays',
		array( 'confetti' => $confetti_enabled )
	);
}

/**
 * Load the assets in the page head where we already know a list renders
 * (the shortcode in the main post), or where a site forces it.
 */
function bb_maybe_enqueue_core_assets() {
	$post = get_post();
	$load = is_singular() && $post && has_shortcode( $post->post_content, 'bp_birthdays' );

	/**
	 * Filters whether the birthday assets load in the head of the current page.
	 *
	 * The widget and shortcode enqueue them on render anyway; return true for
	 * markup the plugin cannot see (cached fragments, page builders).
	 *
	 * @param bool $load Whether to load. Default true only on a post using the shortcode.
	 */
	if ( apply_filters( 'bb_core_load_assets', $load ) ) {
		bb_enqueue_core_assets();
	}
}
add_action( 'wp_enqueue_scripts', 'bb_maybe_enqueue_core_assets' );


/**
 * Load the Widget File.
 */
if ( file_exists( BB_CORE_INC . 'buddypress-birthdays-widget.php' ) ) {
	require_once BB_CORE_INC . 'buddypress-birthdays-widget.php';
}

/**
 * Shortcode support with automatic asset loading
 *
 * @param array $atts Shortcode attributes.
 * @return string Shortcode output.
 */
function bb_birthdays_shortcode( $atts ) {
	bb_enqueue_core_assets();

	$raw  = (array) $atts;
	$atts = shortcode_atts(
		array(
			'title'                => __( 'Upcoming Birthdays', 'buddypress-birthdays' ),
			'limit'                => 5,
			'show_age'             => 'yes',
			'show_message_button'  => 'yes',
			'date_format'          => 'F d',
			'range_limit'          => 'no_limit',
			'show_birthdays_of'    => 'all',
			'display_name_type'    => 'user_name',
			'emoji'                => 'balloon',
			'field_name'           => '',
			'birthdays_per_page'   => 10,
			// Alias of limit, the widget's own setting name. limit wins when both are given.
			'birthdays_to_display' => 0,
		),
		$atts,
		'bp_birthdays'
	);

	// Check if widget class exists.
	if ( ! class_exists( 'Widget_Buddypress_Birthdays' ) ) {
		return '<p>' . esc_html__( 'Birthday widget not available.', 'buddypress-birthdays' ) . '</p>';
	}

	// No field_name attribute: use the General tab's Default Birthday Field.
	if ( empty( $atts['field_name'] ) ) {
		$atts['field_name'] = BP_Birthdays_Helpers::get_birthday_field_id();
	}

	// limit wins when given, then the alias, then limit's default. widget() turns anything below 1 into 5.
	$count = ( isset( $raw['birthdays_to_display'] ) && ! isset( $raw['limit'] ) ) ? $atts['birthdays_to_display'] : $atts['limit'];

	// Create widget instance.
	$widget = new Widget_Buddypress_Birthdays();

	// Convert shortcode atts to widget instance format.
	$instance = array(
		'title'                 => $atts['title'],
		'birthdays_to_display'  => (int) $count,
		'display_age'           => $atts['show_age'],
		'birthday_send_message' => $atts['show_message_button'],
		'birthday_date_format'  => $atts['date_format'],
		'birthdays_range_limit' => $atts['range_limit'],
		'show_birthdays_of'     => $atts['show_birthdays_of'],
		'display_name_type'     => $atts['display_name_type'],
		'emoji'                 => $atts['emoji'],
		'birthday_field_name'   => (int) $atts['field_name'],
		'birthdays_per_page'    => (int) $atts['birthdays_per_page'],
	);

	$args = array(
		'before_widget' => '<div class="bp-birthdays-shortcode widget_bp_birthdays">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	);

	ob_start();
	$widget->widget( $args, $instance );
	return ob_get_clean();
}
add_action(
	'init',
	function () {
		if ( bb_birthdays_xprofile_active() ) {
			add_shortcode( 'bp_birthdays', 'bb_birthdays_shortcode' );
		}
	}
);

/**
 * Admin-only debug panel: add ?debug_birthdays=1 to a page while WP_DEBUG is on.
 *
 * Lists whether the widget is placed, whether the assets are loaded and where
 * the shortcode is used. Styled with BuddyPress's own feedback notice.
 */
function bb_debug_widget_loading() {
	$show = defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'manage_options' ) && filter_input( INPUT_GET, 'debug_birthdays', FILTER_SANITIZE_NUMBER_INT );
	if ( ! $show ) {
		return;
	}

	$post     = get_post();
	$sidebars = function_exists( 'wp_get_sidebars_widgets' ) ? wp_get_sidebars_widgets() : array();
	$placed   = array();
	foreach ( $sidebars as $sidebar_id => $widgets ) {
		foreach ( (array) $widgets as $widget ) {
			if ( 0 === strpos( (string) $widget, 'buddypress_birthdays-' ) ) {
				$placed[] = $sidebar_id;
			}
		}
	}

	$rows = array(
		'Widget class'    => class_exists( 'Widget_Buddypress_Birthdays' ) ? 'loaded' : 'missing',
		'Widget placed in' => $placed ? implode( ', ', array_unique( $placed ) ) : 'no sidebar',
		'Shortcode here'  => ( $post && has_shortcode( $post->post_content, 'bp_birthdays' ) ) ? 'yes' : 'no',
		'CSS'             => wp_style_is( 'bb-core', 'done' ) ? 'loaded' : ( wp_style_is( 'bb-core', 'enqueued' ) ? 'queued' : 'not loaded' ),
		'JS'              => wp_script_is( 'bb-core', 'done' ) ? 'loaded' : ( wp_script_is( 'bb-core', 'enqueued' ) ? 'queued' : 'not loaded' ),
		'BuddyPress'      => defined( 'BP_VERSION' ) ? BP_VERSION : 'not active',
	);

	echo '<div class="bp-feedback info"><p><strong>' . esc_html__( 'Birthday Widget debug', 'buddypress-birthdays' ) . '</strong><br>';
	foreach ( $rows as $label => $value ) {
		echo esc_html( $label . ': ' . $value ) . '<br>';
	}
	echo '</p></div>';
}
add_action( 'wp_footer', 'bb_debug_widget_loading', 999 );

/**
 * Retire the 2.5.0 wish-tracking leftovers.
 *
 * Up to 2.5.0 the send-wishes button recorded each wish in user meta and a daily
 * event pruned it, but nothing ever read the record. It is gone in 2.5.1. The
 * queued daily event is the marker: while it exists the old meta is removed
 * once, then the event is unscheduled, so a normal request pays one cron lookup.
 */
function bb_retire_wish_tracking() {
	if ( ! wp_next_scheduled( 'bb_cleanup_old_wishes' ) ) {
		return;
	}

	delete_metadata( 'user', 0, 'bb_birthday_wished_users', '', true );
	wp_unschedule_hook( 'bb_cleanup_old_wishes' );
}
add_action( 'init', 'bb_retire_wish_tracking' );

/**
 * Cache group of the birthday lists.
 */
const BB_CACHE_GROUP = 'bp_birthdays';

/**
 * Clear all birthday widget caches.
 *
 * Every list key carries the group's last-changed token, so moving the token
 * retires every cached list at once on any cache backend, including ones that
 * cannot flush a single group.
 *
 * @since 2.0.0
 */
function bb_clear_birthday_caches() {
	wp_cache_set_last_changed( BB_CACHE_GROUP );
}

/**
 * Clear birthday caches when xprofile field is updated.
 *
 * @param BP_XProfile_ProfileData $field_data The field data object.
 */
function bb_clear_cache_on_xprofile_update( $field_data ) {
	bb_clear_birthday_caches();
}
add_action( 'xprofile_data_after_save', 'bb_clear_cache_on_xprofile_update' );

/**
 * Clear birthday caches when a friendship is accepted.
 *
 * @param int $id                Friendship ID.
 * @param int $initiator_user_id User ID of the initiator.
 * @param int $friend_user_id    User ID of the friend.
 */
function bb_clear_cache_on_friendship_accepted( $id, $initiator_user_id, $friend_user_id ) {
	bb_clear_birthday_caches();
}
add_action( 'friends_friendship_accepted', 'bb_clear_cache_on_friendship_accepted', 10, 3 );

/**
 * Clear birthday caches when a friendship is deleted.
 *
 * @param int $id                Friendship ID.
 * @param int $initiator_user_id User ID of the initiator.
 * @param int $friend_user_id    User ID of the friend.
 */
function bb_clear_cache_on_friendship_deleted( $id, $initiator_user_id, $friend_user_id ) {
	bb_clear_birthday_caches();
}
add_action( 'friends_friendship_deleted', 'bb_clear_cache_on_friendship_deleted', 10, 3 );

/**
 * Clear birthday caches when a friendship is withdrawn.
 *
 * @param int $friendship_id Friendship ID.
 */
function bb_clear_cache_on_friendship_withdrawn( $friendship_id ) {
	bb_clear_birthday_caches();
}
add_action( 'friends_friendship_withdrawn', 'bb_clear_cache_on_friendship_withdrawn' );

/**
 * Clear birthday caches when a user is deleted.
 *
 * @param int $user_id The user ID being deleted.
 */
function bb_clear_cache_on_user_deleted( $user_id ) {
	bb_clear_birthday_caches();
}
add_action( 'delete_user', 'bb_clear_cache_on_user_deleted' );
add_action( 'wpmu_delete_user', 'bb_clear_cache_on_user_deleted' );

/**
 * Clear birthday caches when a user is registered.
 *
 * @param int $user_id The user ID being registered.
 */
function bb_clear_cache_on_user_registered( $user_id ) {
	bb_clear_birthday_caches();
}
add_action( 'user_register', 'bb_clear_cache_on_user_registered' );

/**
 * Clear birthday caches when follow/unfollow happens (BP Follow plugin).
 *
 * @param BP_Follow $follow The follow object.
 */
function bb_clear_cache_on_follow_change( $follow ) {
	bb_clear_birthday_caches();
}
add_action( 'bp_follow_start_following', 'bb_clear_cache_on_follow_change' );
add_action( 'bp_follow_stop_following', 'bb_clear_cache_on_follow_change' );

/**
 * Render the per-user birthday privacy opt-out on the member's
 * BuddyPress "Settings > General" screen.
 *
 * GDPR: birthday month/day is personal data. This gives every member a
 * self-service switch to hide their birthday from every plugin surface
 * (widget, shortcode, activity, notifications and greeting emails). The
 * enforcement lives in BP_Birthdays_Helpers::is_user_opted_out().
 *
 * @since 2.5.0
 */
function bb_render_member_privacy_field() {
	if ( ! is_user_logged_in() || ! function_exists( 'bp_displayed_user_id' ) || ! class_exists( 'BP_Birthdays_Helpers' ) ) {
		return;
	}

	$user_id = bp_displayed_user_id();
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	$is_hidden = ( 'yes' === get_user_meta( $user_id, BP_Birthdays_Helpers::OPTOUT_META_KEY, true ) );
	?>
	<label for="bb-birthday-hidden" class="bb-birthday-privacy-label">
		<input type="checkbox" name="bb_birthday_hidden" id="bb-birthday-hidden" value="yes" <?php checked( $is_hidden ); ?> />
		<?php esc_html_e( 'Hide my birthday everywhere on this site (widgets, activity, notifications and emails).', 'buddypress-birthdays' ); ?>
	</label>
	<?php
}
add_action( 'bp_core_general_settings_before_submit', 'bb_render_member_privacy_field' );

/**
 * Persist the member's birthday privacy opt-out when the BuddyPress
 * "Settings > General" form is saved.
 *
 * Fires after BuddyPress has already verified the bp_settings_general nonce and
 * saved the core fields; the nonce is re-checked here for defence in depth.
 *
 * @since 2.5.0
 */
function bb_save_member_privacy_field() {
	if ( ! function_exists( 'bp_displayed_user_id' ) || ! class_exists( 'BP_Birthdays_Helpers' ) ) {
		return;
	}

	$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'bp_settings_general' ) ) {
		return;
	}

	$user_id = bp_displayed_user_id();
	if ( ! $user_id ) {
		return;
	}

	$hide = ( isset( $_POST['bb_birthday_hidden'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['bb_birthday_hidden'] ) ) );

	if ( $hide ) {
		update_user_meta( $user_id, BP_Birthdays_Helpers::OPTOUT_META_KEY, 'yes' );
	} else {
		delete_user_meta( $user_id, BP_Birthdays_Helpers::OPTOUT_META_KEY );
	}

	// A visibility change must invalidate the cached birthday lists.
	bb_clear_birthday_caches();
}
add_action( 'bp_core_general_settings_after_save', 'bb_save_member_privacy_field' );
