<?php
/**
 * This file initializes all BB Core components.
 *
 * @link              https://wbcomdesigns.com/contact/
 * @since             1.0.0
 * @package           BP_Birthdays
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} // end if

// Define Our Constants.
define( 'BB_CORE_INC', dirname( __FILE__ ) . '/assets/inc/' );
define( 'BB_CORE_IMG', plugins_url( 'assets/img/', __FILE__ ) );
define( 'BB_CORE_CSS', plugins_url( 'assets/css/', __FILE__ ) );
define( 'BB_CORE_JS', plugins_url( 'assets/js/', __FILE__ ) );
define( 'BB_CORE_VERSION', '2.0.0' ); // Add version for cache busting

/**
 * Global flag to track if assets are loaded
 */
$bb_assets_loaded = false;

/**
 * Register CSS with enhanced smart loading.
 */
function bb_register_core_css() {
	global $bb_assets_loaded;
	
	// Prevent duplicate loading
	if ( $bb_assets_loaded || wp_style_is( 'bb-core', 'enqueued' ) ) {
		return;
	}
	
	// Load on appropriate pages
	if ( bb_should_load_assets() ) {
		wp_enqueue_style( 
			'bb-core', 
			BB_CORE_CSS . 'bb-core.css', 
			array(), 
			BB_CORE_VERSION, 
			'all' 
		);
		
		// Add critical CSS inline for immediate styling
		bb_add_critical_css();
		
		$bb_assets_loaded = true;
	}
}
add_action( 'wp_enqueue_scripts', 'bb_register_core_css', 10 );

/**
 * Register JS with enhanced smart loading.
 */
function bb_register_core_js() {
	global $bb_assets_loaded;
	
	// Prevent duplicate loading
	if ( wp_script_is( 'bb-core', 'enqueued' ) ) {
		return;
	}
	
	// Load on appropriate pages
	if ( bb_should_load_assets() ) {
		wp_enqueue_script( 
			'bb-core', 
			BB_CORE_JS . 'bb-core.js', 
			array( 'jquery' ), 
			BB_CORE_VERSION, 
			true 
		);
		
		// Enhanced localization
		wp_localize_script( 'bb-core', 'bbBirthdays', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'bb_birthdays_nonce' ),
			'plugin_url' => plugins_url( '', __FILE__ ),
			'version' => BB_CORE_VERSION,
			'debug' => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'strings' => array(
				'loading'        => __( 'Loading...', 'buddypress-birthdays' ),
				'error'          => __( 'Error occurred', 'buddypress-birthdays' ),
				'send_wishes'    => __( 'Send my wishes', 'buddypress-birthdays' ),
				'wishes_sent'    => __( 'Birthday wishes sent!', 'buddypress-birthdays' ),
				'happy_birthday' => __( 'Happy Birthday!', 'buddypress-birthdays' ),
				'no_birthdays'   => __( 'No upcoming birthdays', 'buddypress-birthdays' ),
				'today'          => __( 'Today', 'buddypress-birthdays' ),
				'tomorrow'       => __( 'Tomorrow', 'buddypress-birthdays' ),
			),
			'settings' => array(
				'animation_speed' => apply_filters( 'bb_birthdays_animation_speed', 300 ),
				'tooltip_delay'   => apply_filters( 'bb_birthdays_tooltip_delay', 300 ),
				'cache_duration'  => apply_filters( 'bb_birthdays_cache_duration', 1800 ), // 30 minutes
			)
		) );
	}
}
add_action( 'wp_enqueue_scripts', 'bb_register_core_js', 10 );

/**
 * Add critical CSS for immediate styling
 */
function bb_add_critical_css() {
	$critical_css = '
	.widget_bp_birthdays {
		background: #ffffff;
		border-radius: 12px;
		box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
		border: 1px solid #e5e7eb;
		overflow: hidden;
		margin-bottom: 24px;
		transition: box-shadow 0.3s ease;
	}
	.widget_bp_birthdays .widget-title {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: #ffffff;
		margin: 0;
		padding: 20px 24px;
		font-size: 18px;
		font-weight: 600;
		letter-spacing: 0.025em;
		border: none;
		position: relative;
		text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
	}
	.widget_bp_birthdays .widget-title::before {
		content: "🎂";
		margin-right: 8px;
		font-size: 20px;
		filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
	}
	.bp-birthday-users-list {
		margin: 0;
		padding: 16px;
		list-style: none;
		background: #ffffff;
	}
	.bp-birthday-users-list li {
		display: flex;
		align-items: flex-start;
		padding: 16px;
		margin-bottom: 12px;
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		border-radius: 8px;
		transition: all 0.2s ease;
		position: relative;
		overflow: hidden;
	}
	.bp-birthday-users-list li:hover {
		background: #f1f5f9;
		border-color: #cbd5e1;
		transform: translateY(-1px);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}
	';
	
	wp_add_inline_style( 'bb-core', $critical_css );
}

/**
 * Force load assets - for widgets displayed in footer or dynamic content
 */
function bb_force_load_assets() {
	global $bb_assets_loaded;
	
	if ( ! $bb_assets_loaded ) {
		bb_register_core_css();
		bb_register_core_js();
	}
}

/**
 * Enhanced asset loading check - runs in footer for late-loaded widgets
 */
function bb_footer_asset_check() {
	// Check if widget is active but assets weren't loaded
	if ( is_active_widget( false, false, 'widget_buddypress_birthdays' ) ) {
		if ( ! wp_style_is( 'bb-core', 'enqueued' ) && ! wp_style_is( 'bb-core', 'done' ) ) {
			// Force load assets in footer
			bb_force_load_assets();
		}
	}
	
	// Also check for shortcode in dynamic content
	global $post;
	if ( $post && has_shortcode( $post->post_content, 'bp_birthdays' ) ) {
		if ( ! wp_style_is( 'bb-core', 'enqueued' ) && ! wp_style_is( 'bb-core', 'done' ) ) {
			bb_force_load_assets();
		}
	}
}
add_action( 'wp_footer', 'bb_footer_asset_check', 5 );

/**
 * Check if assets should be loaded - Enhanced for BuddyPress and all scenarios.
 */
function bb_should_load_assets() {
	// Always load if widget is active (most important check)
	if ( is_active_widget( false, false, 'widget_buddypress_birthdays' ) ) {
		return true;
	}

	// Load in admin/customizer
	if ( is_admin() || is_customize_preview() ) {
		return true;
	}

	// Check for shortcode in current post content
	global $post;
	if ( $post && has_shortcode( $post->post_content, 'bp_birthdays' ) ) {
		return true;
	}

	// Load on all BuddyPress pages
	if ( function_exists( 'bp_is_directory' ) && bp_is_directory() ) {
		return true;
	}

	if ( function_exists( 'bp_is_user' ) && bp_is_user() ) {
		return true;
	}

	if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
		return true;
	}

	if ( function_exists( 'bp_is_activity_component' ) && bp_is_activity_component() ) {
		return true;
	}

	// Load on BuddyBoss pages
	if ( function_exists( 'buddyboss_theme' ) ) {
		return true;
	}

	// Load if Youzify is active (they often use widgets everywhere)
	if ( function_exists( 'youzify' ) || defined( 'YOUZIFY_VERSION' ) ) {
		return true;
	}

	// Load on common pages where widgets might appear
	if ( is_home() || is_front_page() || is_page() || is_single() || is_archive() ) {
		return true;
	}

	// Load if we're in a widget area context
	if ( bb_check_widget_areas() ) {
		return true;
	}

	// Allow themes/plugins to force loading
	return apply_filters( 'bb_core_load_assets', false );
}

/**
 * Check if birthday widget is present in any widget area
 */
function bb_check_widget_areas() {
	$sidebars_widgets = wp_get_sidebars_widgets();
	
	if ( ! is_array( $sidebars_widgets ) ) {
		return false;
	}
	
	foreach ( $sidebars_widgets as $sidebar_id => $widgets ) {
		if ( is_array( $widgets ) ) {
			foreach ( $widgets as $widget ) {
				if ( strpos( $widget, 'widget_buddypress_birthdays' ) !== false ) {
					return true;
				}
			}
		}
	}
	
	return false;
}

/**
 * Load plugin textdomain.
 */
function bb_load_textdomain() {
	load_plugin_textdomain( 'buddypress-birthdays', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'bb_load_textdomain' );

/**
 * Load the Widget File.
 */
if ( file_exists( BB_CORE_INC . 'buddypress-birthdays-widget.php' ) ) {
	require_once BB_CORE_INC . 'buddypress-birthdays-widget.php';
}

/**
 * Shortcode support with automatic asset loading
 */
function bb_birthdays_shortcode( $atts ) {
	// Force load assets when shortcode is used
	bb_force_load_assets();
	
	$atts = shortcode_atts( array(
		'title' => __( 'Upcoming Birthdays', 'buddypress-birthdays' ),
		'limit' => 5,
		'show_age' => 'yes',
		'show_message_button' => 'yes',
		'date_format' => 'F d',
		'range_limit' => 'no_limit',
		'show_birthdays_of' => 'all',
		'display_name_type' => 'user_name',
		'emoji' => 'balloon',
		'field_name' => get_option( 'bb_birthdays_default_field', 'datebox' )
	), $atts, 'bp_birthdays' );
	
	// Check if widget class exists
	if ( ! class_exists( 'Widget_Buddypress_Birthdays' ) ) {
		return '<p>' . __( 'Birthday widget not available.', 'buddypress-birthdays' ) . '</p>';
	}
	
	// Create widget instance
	$widget = new Widget_Buddypress_Birthdays();
	
	// Convert shortcode atts to widget instance format
	$instance = array(
		'title' => $atts['title'],
		'birthdays_to_display' => (int) $atts['limit'],
		'display_age' => $atts['show_age'],
		'birthday_send_message' => $atts['show_message_button'],
		'birthday_date_format' => $atts['date_format'],
		'birthdays_range_limit' => $atts['range_limit'],
		'show_birthdays_of' => $atts['show_birthdays_of'],
		'display_name_type' => $atts['display_name_type'],
		'emoji' => $atts['emoji'],
		'birthday_field_name' => $atts['field_name']
	);
	
	$args = array(
		'before_widget' => '<div class="bp-birthdays-shortcode widget_bp_birthdays">',
		'after_widget' => '</div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>'
	);
	
	ob_start();
	$widget->widget( $args, $instance );
	return ob_get_clean();
}
add_shortcode( 'bp_birthdays', 'bb_birthdays_shortcode' );

/**
 * Enhanced debug function to check widget loading (for development).
 */
function bb_debug_widget_loading() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'manage_options' ) && isset( $_GET['debug_birthdays'] ) ) {
		echo '<div style="background: #f0f0f0; padding: 15px; margin: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: monospace; font-size: 12px;">';
		echo '<strong style="color: #333; font-size: 14px;">🎂 Birthday Widget Debug Info:</strong><br><br>';
		
		// Widget status
		echo '<strong>Widget Status:</strong><br>';
		echo '&nbsp;&nbsp;Active: ' . ( is_active_widget( false, false, 'widget_buddypress_birthdays' ) ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>' ) . '<br>';
		echo '&nbsp;&nbsp;Class Exists: ' . ( class_exists( 'Widget_Buddypress_Birthdays' ) ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>' ) . '<br><br>';
		
		// BuddyPress status
		echo '<strong>BuddyPress Status:</strong><br>';
		echo '&nbsp;&nbsp;Active: ' . ( function_exists( 'bp_is_active' ) ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>' ) . '<br>';
		echo '&nbsp;&nbsp;Version: ' . ( defined( 'BP_VERSION' ) ? BP_VERSION : 'N/A' ) . '<br><br>';
		
		// Page context
		echo '<strong>Page Context:</strong><br>';
		echo '&nbsp;&nbsp;Current: ';
		if ( is_home() ) echo 'Home';
		elseif ( is_front_page() ) echo 'Front Page';
		elseif ( is_page() ) echo 'Page';
		elseif ( is_single() ) echo 'Single Post';
		elseif ( function_exists( 'bp_is_user' ) && bp_is_user() ) echo 'BP User Profile';
		elseif ( function_exists( 'bp_is_directory' ) && bp_is_directory() ) echo 'BP Directory';
		else echo 'Other';
		echo '<br><br>';
		
		// Asset loading status
		echo '<strong>Asset Loading:</strong><br>';
		echo '&nbsp;&nbsp;Should Load: ' . ( bb_should_load_assets() ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>' ) . '<br>';
		echo '&nbsp;&nbsp;CSS Loaded: ' . ( wp_style_is( 'bb-core', 'done' ) ? '<span style="color: green;">YES</span>' : ( wp_style_is( 'bb-core', 'enqueued' ) ? '<span style="color: orange;">QUEUED</span>' : '<span style="color: red;">NO</span>' ) ) . '<br>';
		echo '&nbsp;&nbsp;JS Loaded: ' . ( wp_script_is( 'bb-core', 'done' ) ? '<span style="color: green;">YES</span>' : ( wp_script_is( 'bb-core', 'enqueued' ) ? '<span style="color: orange;">QUEUED</span>' : '<span style="color: red;">NO</span>' ) ) . '<br><br>';
		
		// Widget locations
		echo '<strong>Widget Locations:</strong><br>';
		$sidebars = wp_get_sidebars_widgets();
		$found_widgets = array();
		foreach ( $sidebars as $sidebar_id => $widgets ) {
			if ( !empty( $widgets ) && is_array( $widgets ) ) {
				foreach ( $widgets as $widget ) {
					if ( strpos( $widget, 'widget_buddypress_birthdays' ) !== false ) {
						$found_widgets[] = $sidebar_id;
					}
				}
			}
		}
		
		if ( ! empty( $found_widgets ) ) {
			echo '&nbsp;&nbsp;Found in: <span style="color: green;">' . implode( ', ', $found_widgets ) . '</span><br>';
		} else {
			echo '&nbsp;&nbsp;<span style="color: red;">No widgets found in any sidebar</span><br>';
		}
		
		// Shortcode check
		global $post;
		if ( $post && has_shortcode( $post->post_content, 'bp_birthdays' ) ) {
			echo '&nbsp;&nbsp;Shortcode: <span style="color: green;">FOUND in current post</span><br>';
		} else {
			echo '&nbsp;&nbsp;Shortcode: <span style="color: gray;">Not found</span><br>';
		}
		
		echo '<br><strong>URLs:</strong><br>';
		echo '&nbsp;&nbsp;CSS: ' . BB_CORE_CSS . 'bb-core.css<br>';
		echo '&nbsp;&nbsp;JS: ' . BB_CORE_JS . 'bb-core.js<br>';
		
		echo '<br><small style="color: #666;">Add ?debug_birthdays=1 to any URL to see this debug info</small>';
		echo '</div>';
	}
}
add_action( 'wp_footer', 'bb_debug_widget_loading', 999 );

/**
 * AJAX handler for birthday-related actions
 */
function bb_birthdays_ajax_handler() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'bb_birthdays_nonce' ) ) {
		wp_die( 'Security check failed' );
	}
	
	$action = sanitize_text_field( $_POST['birthday_action'] ?? '' );
	
	switch ( $action ) {
		case 'refresh_widget':
			// Clear birthday cache
			delete_transient( 'bp_birthdays_cache' );
			wp_send_json_success( array( 'message' => 'Widget refreshed' ) );
			break;
			
		case 'mark_wished':
			// Mark that user has been wished
			$user_id = intval( $_POST['user_id'] ?? 0 );
			$current_user_id = get_current_user_id();
			
			if ( $user_id && $current_user_id ) {
				$wished_users = get_user_meta( $current_user_id, 'bb_birthday_wished_users', true );
				if ( ! is_array( $wished_users ) ) {
					$wished_users = array();
				}
				
				$today = date( 'Y-m-d' );
				if ( ! isset( $wished_users[ $today ] ) ) {
					$wished_users[ $today ] = array();
				}
				
				if ( ! in_array( $user_id, $wished_users[ $today ] ) ) {
					$wished_users[ $today ][] = $user_id;
					update_user_meta( $current_user_id, 'bb_birthday_wished_users', $wished_users );
				}
				
				wp_send_json_success( array( 'message' => 'Wish recorded' ) );
			}
			break;
			
		default:
			wp_send_json_error( 'Invalid action' );
	}
}
add_action( 'wp_ajax_bb_birthdays_action', 'bb_birthdays_ajax_handler' );
add_action( 'wp_ajax_nopriv_bb_birthdays_action', 'bb_birthdays_ajax_handler' );

/**
 * Clean up old wished users data (runs daily)
 */
function bb_cleanup_old_wishes() {
	global $wpdb;
	
	// Remove wish data older than 7 days
	$old_date = date( 'Y-m-d', strtotime( '-7 days' ) );
	
	$users = $wpdb->get_results( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'bb_birthday_wished_users'" );
	
	foreach ( $users as $user ) {
		$wished_data = maybe_unserialize( $user->meta_value );
		if ( is_array( $wished_data ) ) {
			$cleaned_data = array();
			foreach ( $wished_data as $date => $user_ids ) {
				if ( $date >= $old_date ) {
					$cleaned_data[ $date ] = $user_ids;
				}
			}
			
			if ( empty( $cleaned_data ) ) {
				delete_user_meta( $user->user_id, 'bb_birthday_wished_users' );
			} else {
				update_user_meta( $user->user_id, 'bb_birthday_wished_users', $cleaned_data );
			}
		}
	}
}

// Schedule daily cleanup
if ( ! wp_next_scheduled( 'bb_cleanup_old_wishes' ) ) {
	wp_schedule_event( time(), 'daily', 'bb_cleanup_old_wishes' );
}
add_action( 'bb_cleanup_old_wishes', 'bb_cleanup_old_wishes' );