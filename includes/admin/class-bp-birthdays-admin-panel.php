<?php
/**
 * BuddyPress Birthdays admin panel: menu, enqueue, page rendering.
 *
 * Card-panel admin that lives under the shared WB Plugins (wbcomplugins)
 * hub. Replaces the legacy self-contained Settings-API tabbed page that
 * was registered under the BuddyPress menu (bp-settings) with an
 * options-general fallback. Follows the wp-plugin-development skill
 * Part 6 card-panel pattern and the
 * references/wbcom-wrapper-migration.md playbook (Parts 5, 6, 7).
 *
 * The single stored option (bp_birthdays_settings), its group
 * (bp_birthdays_settings_group), and the sanitizer
 * (BP_Birthdays_Admin::sanitize_settings) are reused byte-for-byte so
 * the save contract is unchanged. Only the admin chrome migrated.
 *
 * @package BP_Birthdays
 * @since   2.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Birthdays_Admin_Panel
 *
 * @since 2.5.0
 */
class BP_Birthdays_Admin_Panel {

	/**
	 * Menu slug (kept identical to the legacy page so bookmarks/links
	 * to ?page=bp-birthday-settings still resolve).
	 */
	const MENU_SLUG = 'bp-birthday-settings';

	/**
	 * Option group + option name. Identical to the legacy registration so
	 * settings_fields()/options.php save to the same row.
	 */
	const OPTION_GROUP = 'bp_birthdays_settings_group';
	const OPTION_NAME  = 'bp_birthdays_settings';

	/**
	 * All sidebar tabs rendered inside the one admin page, grouped into
	 * main + settings. Single source of truth for the sidebar nav.
	 *
	 * @return array<string, array{label:string, icon:string, group:string}>
	 */
	public static function get_tabs() {
		$tabs = array(
			'overview'      => array(
				'label' => __( 'Overview', 'buddypress-birthdays' ),
				'icon'  => 'dashicons-chart-bar',
				'group' => 'main',
			),
			'general'       => array(
				'label' => __( 'General', 'buddypress-birthdays' ),
				'icon'  => 'dashicons-admin-generic',
				'group' => 'settings',
			),
			'email'         => array(
				'label' => __( 'Email Notifications', 'buddypress-birthdays' ),
				'icon'  => 'dashicons-email',
				'group' => 'settings',
			),
			'activity'      => array(
				'label' => __( 'Activity Feed', 'buddypress-birthdays' ),
				'icon'  => 'dashicons-buddicons-activity',
				'group' => 'settings',
			),
			'notifications' => array(
				'label' => __( 'Notifications', 'buddypress-birthdays' ),
				'icon'  => 'dashicons-bell',
				'group' => 'settings',
			),
			'display'       => array(
				'label' => __( 'Display', 'buddypress-birthdays' ),
				'icon'  => 'dashicons-visibility',
				'group' => 'settings',
			),
		);
		return apply_filters( 'bp_birthdays_admin_tabs', $tabs );
	}

	/**
	 * Bootstrap hooks.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		// Priority 999 runs after every other plugin has registered its
		// admin_menu entries. Used to reclaim the hub's landing render
		// when a legacy wbcom-wrapper plugin was the first to register
		// the wbcomplugins top-level menu.
		add_action( 'admin_menu', array( $this, 'takeover_hub_landing' ), 999 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'in_admin_header', array( $this, 'suppress_foreign_notices' ), 1 );
	}

	/**
	 * Attach as a single submenu under the shared WB Plugins hub.
	 */
	public function add_menu() {
		$cap = apply_filters( 'bp_birthdays_admin_capability', 'manage_options' );

		// First Wbcom plugin to load creates the shared WB Plugins hub.
		// Whichever plugin wins the race provides the hub's landing
		// dashboard (a card grid of every Wbcom plugin attached to the
		// hub). Peer plugins are auto-discovered via
		// $GLOBALS['submenu']['wbcomplugins'].
		if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) ) {
			add_menu_page(
				esc_html__( 'WB Plugins', 'buddypress-birthdays' ),
				esc_html__( 'WB Plugins', 'buddypress-birthdays' ),
				$cap,
				'wbcomplugins',
				array( $this, 'render_hub' ),
				'dashicons-lightbulb',
				59
			);
		}

		add_submenu_page(
			'wbcomplugins',
			esc_html__( 'Birthday Widget for BuddyPress', 'buddypress-birthdays' ),
			esc_html__( 'Birthdays', 'buddypress-birthdays' ),
			$cap,
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Force the shared hub's landing page to render our clean card-panel
	 * dashboard, overriding whatever a legacy wbcom-wrapper plugin may
	 * have registered. See references/wbcom-wrapper-migration.md Part 16.
	 *
	 * Runs at admin_menu priority 999 so every other registration is
	 * complete; we strip any existing callback on the hub's page action
	 * and install our render_hub. Migrated peer plugins run the same
	 * routine — they overwrite each other harmlessly because the hub
	 * views are visually equivalent across plugins.
	 */
	public function takeover_hub_landing() {
		global $admin_page_hooks;
		if ( empty( $admin_page_hooks['wbcomplugins'] ) ) {
			return;
		}
		remove_all_actions( 'toplevel_page_wbcomplugins' );
		add_action( 'toplevel_page_wbcomplugins', array( $this, 'render_hub' ) );
	}

	/**
	 * Register the settings option used by the form. Sanitization is
	 * delegated to the legacy admin class so the save contract is
	 * byte-identical to the pre-2.5.0 form.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitizer. Delegates to the battle-tested
	 * BP_Birthdays_Admin::sanitize_settings() so all 13 subkeys are
	 * coerced exactly as before — keeping the option-save contract
	 * identical to the legacy UI.
	 *
	 * The legacy sanitizer already merges on top of the stored option
	 * (sentinel-guarded merge, Playbook 7.1): it starts from
	 * get_option( self::OPTION_NAME ) and only overwrites a subkey when
	 * that subkey is present in $input. Every card-panel settings tab
	 * posts a hidden value="0" companion before each checkbox, so an
	 * unchecked box always arrives in $input as '0' and the bool keys
	 * coerce correctly. Saving one tab therefore never blanks keys owned
	 * by another tab.
	 *
	 * @param mixed $input Raw form input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();

		if ( class_exists( 'BP_Birthdays_Admin' ) ) {
			return BP_Birthdays_Admin::get_instance()->sanitize_settings( $input );
		}

		// Defensive fallback: merge onto the stored option so untouched
		// tabs keep their keys even if the legacy class is unavailable.
		$existing = get_option( self::OPTION_NAME, array() );
		return is_array( $existing ) ? array_merge( $existing, $input ) : $input;
	}

	/**
	 * Enqueue admin assets only on our screen + the shared hub landing.
	 *
	 * @param string $hook_suffix Current admin page hook (unused — we
	 *                            inspect the screen object instead).
	 */
	public function enqueue_assets( $hook_suffix ) {
		unset( $hook_suffix );
		$screen = get_current_screen();
		if ( ! $screen || ! $this->is_our_screen( $screen ) ) {
			return;
		}

		$version = defined( 'BIRTHDAY_WIDGET_VERSION' ) ? BIRTHDAY_WIDGET_VERSION : '2.5.0'; // phpcs:ignore Generic.Strings.UnnecessaryStringConcat.Found

		wp_enqueue_style(
			'bp-birthdays-admin',
			BIRTHDAY_WIDGET_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			$version
		);

		wp_enqueue_script(
			'bp-birthdays-admin',
			BIRTHDAY_WIDGET_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			$version,
			true
		);

		wp_localize_script(
			'bp-birthdays-admin',
			'bbdAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'saved'           => __( 'Settings saved.', 'buddypress-birthdays' ),
					'saveFailed'      => __( 'Could not save. Please try again.', 'buddypress-birthdays' ),
					'confirmDanger'   => __( 'Are you sure? This cannot be undone.', 'buddypress-birthdays' ),
					'confirmContinue' => __( 'Continue', 'buddypress-birthdays' ),
					'confirmCancel'   => __( 'Cancel', 'buddypress-birthdays' ),
				),
			)
		);
	}

	/**
	 * Suppress 3rd-party admin notices on our screen.
	 */
	public function suppress_foreign_notices() {
		$screen = get_current_screen();
		if ( ! $screen || ! $this->is_our_screen( $screen ) ) {
			return;
		}
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * True if the current screen is the plugin's admin page OR the shared
	 * hub landing (our callback owns the hub render too, so our CSS/JS
	 * must load there).
	 *
	 * @param WP_Screen $screen Current screen.
	 * @return bool
	 */
	private function is_our_screen( $screen ) {
		if ( empty( $screen->id ) ) {
			return false;
		}
		return (bool) preg_match( '/_page_' . preg_quote( self::MENU_SLUG, '/' ) . '$/', $screen->id )
			|| 'toplevel_page_wbcomplugins' === $screen->id;
	}

	/**
	 * Render the shared WB Plugins hub landing page.
	 */
	public function render_hub() {
		$view = BIRTHDAY_WIDGET_PLUGIN_PATH . 'includes/admin/views/hub.php';
		if ( file_exists( $view ) ) {
			include $view;
		}
	}

	/**
	 * Render the single admin page. Routes to the active tab.
	 */
	public function render_page() {
		$bbd_tabs  = self::get_tabs();
		$tab_slugs = array_keys( $bbd_tabs );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab switch, no state change.
		$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : $tab_slugs[0];
		if ( ! isset( $bbd_tabs[ $active ] ) ) {
			$active = $tab_slugs[0];
		}

		$page_url = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		$settings = self::get_settings();

		$view_map            = array(
			'overview'      => 'overview',
			'general'       => 'settings-general',
			'email'         => 'settings-email',
			'activity'      => 'settings-activity',
			'notifications' => 'settings-notifications',
			'display'       => 'settings-display',
		);
		$view                = isset( $view_map[ $active ] ) ? $view_map[ $active ] : 'overview';
		$in_settings_group   = isset( $bbd_tabs[ $active ]['group'] ) && 'settings' === $bbd_tabs[ $active ]['group'];
		$settings_form_group = self::OPTION_GROUP;

		$view_path = BIRTHDAY_WIDGET_PLUGIN_PATH . 'includes/admin/views/' . $view . '.php';
		$shell     = BIRTHDAY_WIDGET_PLUGIN_PATH . 'includes/admin/views/shell.php';

		include $shell;
	}

	/**
	 * Get settings with defaults applied. Delegates to the legacy admin
	 * getter so defaults stay in one place.
	 *
	 * @param string|null $key Optional specific key.
	 * @return mixed
	 */
	public static function get_settings( $key = null ) {
		if ( class_exists( 'BP_Birthdays_Admin' ) ) {
			return BP_Birthdays_Admin::get_settings( $key );
		}
		$settings = get_option( self::OPTION_NAME, array() );
		if ( null !== $key ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
		}
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Get available xProfile date fields for the General tab select.
	 *
	 * @return array<int, array{id:int|string, name:string, type:string}>
	 */
	public static function get_date_fields() {
		global $wpdb;

		$fields = array();

		if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'xprofile' ) ) {
			return $fields;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			"SELECT id, name, type FROM {$wpdb->prefix}bp_xprofile_fields WHERE type IN ('datebox', 'birthdate') ORDER BY name",
			ARRAY_A
		);

		if ( $results ) {
			$fields = $results;
		}

		return $fields;
	}

	/**
	 * URL to the BuddyPress/BuddyBoss Emails admin screen.
	 *
	 * @return string
	 */
	public static function get_bp_emails_url() {
		return admin_url( 'edit.php?post_type=bp-email' );
	}
}
