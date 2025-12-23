<?php
/**
 * Admin Settings Page for BuddyPress Birthdays
 *
 * @package BP_Birthdays
 * @since 2.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BP_Birthdays_Admin
 *
 * Handles the admin settings page for BuddyPress Birthdays plugin.
 */
class BP_Birthdays_Admin {

	/**
	 * Option name for storing settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'bp_birthdays_settings';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $defaults = array(
		// General.
		'default_field_id'    => '',
		'cache_duration'      => 30,
		// Email Notifications.
		'email_enabled'       => false,
		'email_subject'       => 'Happy Birthday, {name}!',
		'email_message'       => '',
		'email_send_time'     => '09:00',
		'admin_email_enabled' => false,
		'admin_email'         => '',
		// Activity Feed.
		'activity_enabled'    => false,
		'activity_message'    => "Today is {name}'s birthday! Send your wishes!",
		// BP Notifications.
		'notification_enabled' => false,
		'notification_friends_only' => false,
		'notification_text'   => "It's {name}'s birthday today!",
		// Display Extras.
		'confetti_enabled'    => false,
		'zodiac_enabled'      => false,
	);

	/**
	 * Instance of this class.
	 *
	 * @var BP_Birthdays_Admin
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return BP_Birthdays_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add admin menu under BuddyPress.
	 */
	public function add_admin_menu() {
		// Try to add under BuddyPress menu first.
		$parent_slug = 'bp-settings';

		// Check if BuddyPress menu exists.
		global $admin_page_hooks;
		if ( ! isset( $admin_page_hooks['bp-settings'] ) ) {
			// Fallback to Settings menu.
			$parent_slug = 'options-general.php';
		}

		add_submenu_page(
			$parent_slug,
			__( 'Birthday Settings', 'buddypress-birthdays' ),
			__( 'Birthday Settings', 'buddypress-birthdays' ),
			'manage_options',
			'bp-birthday-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings with WordPress Settings API.
	 */
	public function register_settings() {
		register_setting(
			'bp_birthdays_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->defaults,
			)
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw input from form.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// General.
		$sanitized['default_field_id'] = isset( $input['default_field_id'] ) ? absint( $input['default_field_id'] ) : '';
		$sanitized['cache_duration']   = isset( $input['cache_duration'] ) ? absint( $input['cache_duration'] ) : 30;

		// Email Notifications.
		$sanitized['email_enabled']       = ! empty( $input['email_enabled'] );
		$sanitized['email_subject']       = isset( $input['email_subject'] ) ? sanitize_text_field( $input['email_subject'] ) : '';
		$sanitized['email_message']       = isset( $input['email_message'] ) ? wp_kses_post( $input['email_message'] ) : '';
		$sanitized['email_send_time']     = isset( $input['email_send_time'] ) ? sanitize_text_field( $input['email_send_time'] ) : '09:00';
		$sanitized['admin_email_enabled'] = ! empty( $input['admin_email_enabled'] );
		$sanitized['admin_email']         = isset( $input['admin_email'] ) ? sanitize_email( $input['admin_email'] ) : '';

		// Activity Feed.
		$sanitized['activity_enabled'] = ! empty( $input['activity_enabled'] );
		$sanitized['activity_message'] = isset( $input['activity_message'] ) ? sanitize_text_field( $input['activity_message'] ) : '';

		// BP Notifications.
		$sanitized['notification_enabled']      = ! empty( $input['notification_enabled'] );
		$sanitized['notification_friends_only'] = ! empty( $input['notification_friends_only'] );
		$sanitized['notification_text']         = isset( $input['notification_text'] ) ? sanitize_text_field( $input['notification_text'] ) : '';

		// Display Extras.
		$sanitized['confetti_enabled'] = ! empty( $input['confetti_enabled'] );
		$sanitized['zodiac_enabled']   = ! empty( $input['zodiac_enabled'] );

		return $sanitized;
	}

	/**
	 * Get plugin settings.
	 *
	 * @param string $key Optional. Specific setting key to retrieve.
	 * @return mixed All settings array or specific setting value.
	 */
	public static function get_settings( $key = null ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$instance = self::get_instance();
		$settings = wp_parse_args( $settings, $instance->defaults );

		if ( null !== $key ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
		}

		return $settings;
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'buddypress_page_bp-birthday-settings' !== $hook && 'settings_page_bp-birthday-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'bp-birthdays-admin',
			BIRTHDAY_WIDGET_PLUGIN_URL . 'admin/css/admin-settings.css',
			array(),
			'2.4.0'
		);

		wp_enqueue_script(
			'bp-birthdays-admin',
			BIRTHDAY_WIDGET_PLUGIN_URL . 'admin/js/admin-settings.js',
			array( 'jquery' ),
			'2.4.0',
			true
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		$settings    = self::get_settings();
		$active_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		$date_fields = $this->get_date_fields();
		?>
		<div class="wrap bp-birthdays-settings">
			<h1><?php esc_html_e( 'Birthday Settings', 'buddypress-birthdays' ); ?></h1>

			<?php settings_errors( 'bp_birthdays_settings' ); ?>

			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'general' ) ); ?>"
				   class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'General', 'buddypress-birthdays' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'email' ) ); ?>"
				   class="nav-tab <?php echo 'email' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Email Notifications', 'buddypress-birthdays' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'activity' ) ); ?>"
				   class="nav-tab <?php echo 'activity' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Activity Feed', 'buddypress-birthdays' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'notifications' ) ); ?>"
				   class="nav-tab <?php echo 'notifications' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Notifications', 'buddypress-birthdays' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'display' ) ); ?>"
				   class="nav-tab <?php echo 'display' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Display', 'buddypress-birthdays' ); ?>
				</a>
			</nav>

			<form method="post" action="options.php">
				<?php settings_fields( 'bp_birthdays_settings_group' ); ?>

				<?php if ( 'general' === $active_tab ) : ?>
					<?php $this->render_general_tab( $settings, $date_fields ); ?>
				<?php elseif ( 'email' === $active_tab ) : ?>
					<?php $this->render_email_tab( $settings ); ?>
				<?php elseif ( 'activity' === $active_tab ) : ?>
					<?php $this->render_activity_tab( $settings ); ?>
				<?php elseif ( 'notifications' === $active_tab ) : ?>
					<?php $this->render_notifications_tab( $settings ); ?>
				<?php elseif ( 'display' === $active_tab ) : ?>
					<?php $this->render_display_tab( $settings ); ?>
				<?php endif; ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render General settings tab.
	 *
	 * @param array $settings Current settings.
	 * @param array $date_fields Available date fields.
	 */
	private function render_general_tab( $settings, $date_fields ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="default_field_id"><?php esc_html_e( 'Default Birthday Field', 'buddypress-birthdays' ); ?></label>
				</th>
				<td>
					<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_field_id]" id="default_field_id">
						<option value=""><?php esc_html_e( '— Select Field —', 'buddypress-birthdays' ); ?></option>
						<?php foreach ( $date_fields as $field ) : ?>
							<option value="<?php echo esc_attr( $field['id'] ); ?>" <?php selected( $settings['default_field_id'], $field['id'] ); ?>>
								<?php echo esc_html( $field['name'] ); ?> (<?php echo esc_html( $field['type'] ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Select the default xProfile field for birthdays. Widgets can override this.', 'buddypress-birthdays' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cache_duration"><?php esc_html_e( 'Cache Duration', 'buddypress-birthdays' ); ?></label>
				</th>
				<td>
					<input type="number"
						   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[cache_duration]"
						   id="cache_duration"
						   value="<?php echo esc_attr( $settings['cache_duration'] ); ?>"
						   min="1"
						   max="1440"
						   class="small-text"> <?php esc_html_e( 'minutes', 'buddypress-birthdays' ); ?>
					<p class="description">
						<?php esc_html_e( 'How long to cache birthday data. Lower values mean more database queries.', 'buddypress-birthdays' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Email Notifications tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_email_tab( $settings ) {
		$default_message = $this->get_default_email_message();
		$email_message   = ! empty( $settings['email_message'] ) ? $settings['email_message'] : $default_message;
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Birthday Emails', 'buddypress-birthdays' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
							   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[email_enabled]"
							   value="1"
							   <?php checked( $settings['email_enabled'] ); ?>>
						<?php esc_html_e( 'Send automatic birthday greeting emails to members', 'buddypress-birthdays' ); ?>
					</label>
				</td>
			</tr>
			<tr class="email-dependent">
				<th scope="row">
					<label for="email_subject"><?php esc_html_e( 'Email Subject', 'buddypress-birthdays' ); ?></label>
				</th>
				<td>
					<input type="text"
						   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[email_subject]"
						   id="email_subject"
						   value="<?php echo esc_attr( $settings['email_subject'] ); ?>"
						   class="regular-text">
					<p class="description">
						<?php esc_html_e( 'Available placeholders: {name}, {first_name}, {site_name}', 'buddypress-birthdays' ); ?>
					</p>
				</td>
			</tr>
			<tr class="email-dependent">
				<th scope="row">
					<label for="email_message"><?php esc_html_e( 'Email Message', 'buddypress-birthdays' ); ?></label>
				</th>
				<td>
					<?php
					wp_editor(
						$email_message,
						'email_message',
						array(
							'textarea_name' => self::OPTION_NAME . '[email_message]',
							'textarea_rows' => 10,
							'media_buttons' => false,
							'teeny'         => true,
						)
					);
					?>
					<p class="description">
						<?php esc_html_e( 'Available placeholders: {name}, {first_name}, {age}, {site_name}, {site_url}, {profile_url}', 'buddypress-birthdays' ); ?>
					</p>
				</td>
			</tr>
			<tr class="email-dependent">
				<th scope="row">
					<label for="email_send_time"><?php esc_html_e( 'Send Time', 'buddypress-birthdays' ); ?></label>
				</th>
				<td>
					<input type="time"
						   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[email_send_time]"
						   id="email_send_time"
						   value="<?php echo esc_attr( $settings['email_send_time'] ); ?>">
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
			<tr>
				<th scope="row"><?php esc_html_e( 'Admin Summary', 'buddypress-birthdays' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
							   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[admin_email_enabled]"
							   value="1"
							   <?php checked( $settings['admin_email_enabled'] ); ?>>
						<?php esc_html_e( 'Send daily summary of birthdays to admin', 'buddypress-birthdays' ); ?>
					</label>
					<br><br>
					<input type="email"
						   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[admin_email]"
						   value="<?php echo esc_attr( $settings['admin_email'] ); ?>"
						   placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
						   class="regular-text">
					<p class="description">
						<?php esc_html_e( 'Leave empty to use site admin email.', 'buddypress-birthdays' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Activity Feed tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_activity_tab( $settings ) {
		$activity_active = function_exists( 'bp_is_active' ) && bp_is_active( 'activity' );
		?>
		<?php if ( ! $activity_active ) : ?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'BuddyPress Activity component is not active. Enable it to use this feature.', 'buddypress-birthdays' ); ?></p>
			</div>
		<?php endif; ?>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Activity Posts', 'buddypress-birthdays' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
							   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[activity_enabled]"
							   value="1"
							   <?php checked( $settings['activity_enabled'] ); ?>
							   <?php disabled( ! $activity_active ); ?>>
						<?php esc_html_e( 'Automatically post to activity feed on member birthdays', 'buddypress-birthdays' ); ?>
					</label>
				</td>
			</tr>
			<tr class="activity-dependent">
				<th scope="row">
					<label for="activity_message"><?php esc_html_e( 'Activity Message', 'buddypress-birthdays' ); ?></label>
				</th>
				<td>
					<input type="text"
						   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[activity_message]"
						   id="activity_message"
						   value="<?php echo esc_attr( $settings['activity_message'] ); ?>"
						   class="large-text">
					<p class="description">
						<?php esc_html_e( 'Available placeholders: {name}, {age}, {profile_url}', 'buddypress-birthdays' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Notifications tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_notifications_tab( $settings ) {
		$notifications_active = function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' );
		?>
		<?php if ( ! $notifications_active ) : ?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'BuddyPress Notifications component is not active. Enable it to use this feature.', 'buddypress-birthdays' ); ?></p>
			</div>
		<?php endif; ?>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Notifications', 'buddypress-birthdays' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
							   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[notification_enabled]"
							   value="1"
							   <?php checked( $settings['notification_enabled'] ); ?>
							   <?php disabled( ! $notifications_active ); ?>>
						<?php esc_html_e( 'Send BuddyPress notifications about member birthdays', 'buddypress-birthdays' ); ?>
					</label>
				</td>
			</tr>
			<tr class="notification-dependent">
				<th scope="row"><?php esc_html_e( 'Notify', 'buddypress-birthdays' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
							   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[notification_friends_only]"
							   value="1"
							   <?php checked( $settings['notification_friends_only'] ); ?>>
						<?php esc_html_e( 'Only notify friends of the birthday member', 'buddypress-birthdays' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'If unchecked, all members will be notified.', 'buddypress-birthdays' ); ?>
					</p>
				</td>
			</tr>
			<tr class="notification-dependent">
				<th scope="row">
					<label for="notification_text"><?php esc_html_e( 'Notification Text', 'buddypress-birthdays' ); ?></label>
				</th>
				<td>
					<input type="text"
						   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[notification_text]"
						   id="notification_text"
						   value="<?php echo esc_attr( $settings['notification_text'] ); ?>"
						   class="large-text">
					<p class="description">
						<?php esc_html_e( 'Available placeholders: {name}', 'buddypress-birthdays' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Display tab.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_display_tab( $settings ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Confetti Animation', 'buddypress-birthdays' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
							   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[confetti_enabled]"
							   value="1"
							   <?php checked( $settings['confetti_enabled'] ); ?>>
						<?php esc_html_e( 'Show confetti animation for today\'s birthdays', 'buddypress-birthdays' ); ?>
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
						<input type="checkbox"
							   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[zodiac_enabled]"
							   value="1"
							   <?php checked( $settings['zodiac_enabled'] ); ?>>
						<?php esc_html_e( 'Display zodiac sign next to birthday', 'buddypress-birthdays' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Shows the zodiac symbol (e.g., ♈ ♉ ♊) based on birth date.', 'buddypress-birthdays' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Get available date fields from BuddyPress xProfile.
	 *
	 * @return array Array of date fields with id, name, and type.
	 */
	private function get_date_fields() {
		global $wpdb;

		$fields = array();

		if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'xprofile' ) ) {
			return $fields;
		}

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
	 * Get default email message template.
	 *
	 * @return string Default HTML email template.
	 */
	private function get_default_email_message() {
		$message = '<p>' . __( 'Dear {first_name},', 'buddypress-birthdays' ) . '</p>';
		$message .= '<p>' . __( 'Wishing you a very Happy Birthday! May your special day be filled with joy, laughter, and wonderful moments.', 'buddypress-birthdays' ) . '</p>';
		$message .= '<p>' . __( 'The entire {site_name} community sends you warm birthday wishes!', 'buddypress-birthdays' ) . '</p>';
		$message .= '<p>' . __( 'Best wishes,', 'buddypress-birthdays' ) . '<br>{site_name}</p>';

		return $message;
	}
}

// Initialize the admin class.
add_action( 'plugins_loaded', array( 'BP_Birthdays_Admin', 'get_instance' ) );
