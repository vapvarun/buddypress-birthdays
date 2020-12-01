<?php
// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
define( 'EDD_WBBPB_STORE_URL', 'https://wbcomdesigns.com/' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

define( 'EDD_WBBPB_ITEM_NAME', 'BuddyPress Birthdays' );

define( 'EDD_WBBPB_PLUGIN_LICENSE_PAGE', 'wbcom-license-page' );

if ( ! class_exists( 'EDD_WBBPB_Plugin_Updater' ) ) {
	// load our custom updater.
	include dirname( __FILE__ ) . '/EDD_WBBPB_Plugin_Updater.php';
}

function edd_WBBPB_plugin_updater() {
	// retrieve our license key from the DB.
	$license_key = trim( get_option( 'edd_wbcom_WBBPB_license_key' ) );

	// setup the updater
	$edd_updater = new EDD_WBBPB_Plugin_Updater(
		EDD_WBBPB_STORE_URL,
		WBBPB_PLUGIN_FILE,
		array(
			'version'   => WBBPB_PLUGIN_VERSION,             // current version number.
			'license'   => $license_key,        // license key (used get_option above to retrieve from DB).
			'item_name' => EDD_WBBPB_ITEM_NAME,  // name of this plugin.
			'author'    => 'wbcomdesigns',  // author of this plugin.
			'url'       => home_url(),
		)
	);
}
add_action( 'admin_init', 'edd_WBBPB_plugin_updater', 0 );

function edd_wbcom_WBBPB_register_option() {
	// creates our settings in the options table
	register_setting( 'edd_wbcom_WBBPB_license', 'edd_wbcom_WBBPB_license_key', 'edd_WBBPB_sanitize_license' );
}
add_action( 'admin_init', 'edd_wbcom_WBBPB_register_option' );

function edd_WBBPB_sanitize_license( $new ) {
	$old = get_option( 'edd_wbcom_WBBPB_license_key' );
	if ( $old && $old != $new ) {
		delete_option( 'edd_wbcom_WBBPB_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}



/************************************
 * this illustrates how to activate
 * a license key
 *************************************/

function edd_wbcom_WBBPB_activate_license() {
	// listen for our activate button to be clicked
	if ( isset( $_POST['edd_WBBPB_license_activate'] ) ) {
		// run a quick security check
		if ( ! check_admin_referer( 'edd_wbcom_WBBPB_nonce', 'edd_wbcom_WBBPB_nonce' ) ) {
			return; // get out if we didn't click the Activate button
		}

		// retrieve the license from the database
		$license = ! empty( $_POST['edd_wbcom_WBBPB_license_key'] ) ? $_POST['edd_wbcom_WBBPB_license_key'] : '' ;

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( EDD_WBBPB_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
			EDD_WBBPB_STORE_URL,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'buddypress-profile-pro' );
			}
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {
				switch ( $license_data->error ) {
					case 'expired':
						$message = sprintf(
							__( 'Your license key expired on %s.', 'buddypress-profile-pro' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;

					case 'revoked':
						$message = __( 'Your license key has been disabled.', 'buddypress-profile-pro' );
						break;

					case 'missing':
						$message = __( 'Invalid license.', 'buddypress-profile-pro' );
						break;

					case 'invalid':
					case 'site_inactive':
						$message = __( 'Your license is not active for this URL.', 'buddypress-profile-pro' );
						break;

					case 'item_name_mismatch':
						$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'buddypress-profile-pro' ), EDD_WBBPB_ITEM_NAME );
						break;

					case 'no_activations_left':
						$message = __( 'Your license key has reached its activation limit.', 'buddypress-profile-pro' );
						break;

					default:
						$message = __( 'An error occurred, please try again.', 'buddypress-profile-pro' );
						break;
				}
			}
		}

		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
			$base_url = admin_url( 'admin.php?page=' . EDD_WBBPB_PLUGIN_LICENSE_PAGE );
			$redirect = add_query_arg(
				array(
					'WBBPB_activation' => 'false',
					'message'          => urlencode( $message ),
				),
				$base_url
			);
			$license  = trim( $license );
			update_option( 'edd_wbcom_WBBPB_license_key', $license );
			update_option( 'edd_wbcom_WBBPB_license_status', $license_data->license );
			wp_redirect( $redirect );
			exit();
		}

		// $license_data->license will be either "valid" or "invalid"
		$license = trim( $license );
		update_option( 'edd_wbcom_WBBPB_license_key', $license );
		update_option( 'edd_wbcom_WBBPB_license_status', $license_data->license );
		wp_redirect( admin_url( 'admin.php?page=' . EDD_WBBPB_PLUGIN_LICENSE_PAGE ) );
		exit();
	}
}
add_action( 'admin_init', 'edd_wbcom_WBBPB_activate_license' );


/***********************************************
 * Illustrates how to deactivate a license key.
 * This will decrease the site count
 ***********************************************/

function edd_wbcom_WBBPB_deactivate_license() {
	// listen for our activate button to be clicked
	if ( isset( $_POST['edd_WBBPB_license_deactivate'] ) ) {

		// run a quick security check
		if ( ! check_admin_referer( 'edd_wbcom_WBBPB_nonce', 'edd_wbcom_WBBPB_nonce' ) ) {
			return; // get out if we didn't click the Activate button
		}

		// retrieve the license from the database
		$license = trim( get_option( 'edd_wbcom_WBBPB_license_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => urlencode( EDD_WBBPB_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
			EDD_WBBPB_STORE_URL,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'buddypress-profile-pro' );
			}

			$base_url = admin_url( 'admin.php?page=' . EDD_WBBPB_PLUGIN_LICENSE_PAGE );
			$redirect = add_query_arg(
				array(
					'WBBPB_activation' => 'false',
					'message'          => urlencode( $message ),
				),
				$base_url
			);

			wp_redirect( $redirect );
			exit();
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if ( $license_data->license == 'deactivated' ) {
			delete_option( 'edd_wbcom_WBBPB_license_status' );
		}

		wp_redirect( admin_url( 'admin.php?page=' . EDD_WBBPB_PLUGIN_LICENSE_PAGE ) );
		exit();
	}
}
add_action( 'admin_init', 'edd_wbcom_WBBPB_deactivate_license' );


/************************************
 * this illustrates how to check if
 * a license key is still valid
 * the updater does this for you,
 * so this is only needed if you
 * want to do something custom
 *************************************/

function edd_wbcom_WBBPB_check_license() {
	global $wp_version;

	$license = trim( get_option( 'edd_wbcom_WBBPB_license_key' ) );

	$api_params = array(
		'edd_action' => 'check_license',
		'license'    => $license,
		'item_name'  => urlencode( EDD_WBBPB_ITEM_NAME ),
		'url'        => home_url(),
	);

	// Call the custom API.
	$response = wp_remote_post(
		EDD_WBBPB_STORE_URL,
		array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params,
		)
	);

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	if ( $license_data->license == 'valid' ) {
		echo 'valid';
		exit;
		// this license is still valid
	} else {
		echo 'invalid';
		exit;
		// this license is no longer valid
	}
}

/**
 * This is a means of catching errors from the activation method above and displaying it to the customer
 */
function edd_wbcom_WBBPB_admin_notices() {
	if ( isset( $_GET['WBBPB_activation'] ) && ! empty( $_GET['message'] ) ) {
		switch ( $_GET['WBBPB_activation'] ) {
			case 'false':
				$message = urldecode( $_GET['message'] );
				?>
				<div class="error">
					<p><?php echo $message; ?></p>
				</div>
				<?php
				break;

			case 'true':
			default:
				// Developers can put a custom success message here for when activation is successful if they way.
				break;
		}
	}
}
add_action( 'admin_notices', 'edd_wbcom_WBBPB_admin_notices' );


add_action( 'wbcom_add_plugin_license_code', 'wbcom_wbbpp_render_license_section' );
function wbcom_wbbpp_render_license_section() {

	$license = get_option( 'edd_wbcom_WBBPB_license_key', true );
	$status  = get_option( 'edd_wbcom_WBBPB_license_status' );

	$plugin_data = get_plugin_data( WBBPB_PLUGIN_PATH . '/buddypress-birthdays.php', $markup = true, $translate = true );

	if ( $status !== false && $status == 'valid' ) {
		$status_class = 'active';
		$status_text  = 'Active';
	} else {
		$status_class = 'inactive';
		$status_text  = 'Inactive';
	}
	?>
	<table class="form-table wb-license-form-table mobile-license-headings">
		<thead>
			<tr>
				<th class="wb-product-th"><?php esc_html_e( 'Product', 'buddypress-profile-pro' ); ?></th>
				<th class="wb-version-th"><?php esc_html_e( 'Version', 'buddypress-profile-pro' ); ?></th>
				<th class="wb-key-th"><?php esc_html_e( 'Key', 'buddypress-profile-pro' ); ?></th>
				<th class="wb-status-th"><?php esc_html_e( 'Status', 'buddypress-profile-pro' ); ?></th>
				<th class="wb-action-th"><?php esc_html_e( 'Action', 'buddypress-profile-pro' ); ?></th>
				<th></th>
			</tr>
		</thead>
	</table>
	<form method="post" action="options.php">
		<?php settings_fields( 'edd_wbcom_WBBPB_license' ); ?>
		<table class="form-table wb-license-form-table">
			<tr>
				<td class="wb-plugin-name"><?php esc_attr_e( $plugin_data['Name'], 'buddypress-profile-pro' ); ?></td>
				<td class="wb-plugin-version"><?php esc_attr_e( $plugin_data['Version'], 'buddypress-profile-pro' ); ?></td>
				<td class="wb-plugin-license-key"><input id="edd_wbcom_WBBPB_license_key" name="edd_wbcom_WBBPB_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license, 'buddypress-profile-pro' ); ?>" /></td>
				<td class="wb-license-status <?php echo $status_class; ?>"><?php esc_attr_e( $status_text, 'buddypress-profile-pro' ); ?></td>
				<td class="wb-license-action">
					<?php
					if ( $status !== false && $status == 'valid' ) {
						wp_nonce_field( 'edd_wbcom_WBBPB_nonce', 'edd_wbcom_WBBPB_nonce' );
						?>
						<input type="submit" class="button-secondary" name="edd_WBBPB_license_deactivate" value="<?php _e( 'Deactivate License', 'buddypress-profile-pro' ); ?>"/>
						<?php
					} else {
						wp_nonce_field( 'edd_wbcom_WBBPB_nonce', 'edd_wbcom_WBBPB_nonce' );
						?>
						<input type="submit" class="button-secondary" name="edd_wbbpp_license_activate" value="<?php _e( 'Activate License', 'buddypress-profile-pro' ); ?>"/>
					<?php } ?>
				</td>
			</tr>
		</table>
	</form>

	<?php
}

function edd_wbcom_WBBPB_activate_license_button() {
	// listen for our activate button to be clicked
	if ( isset( $_POST['edd_wbbpp_license_activate'] ) ) {
		// run a quick security check
		if ( ! check_admin_referer( 'edd_wbcom_WBBPB_nonce', 'edd_wbcom_WBBPB_nonce' ) ) {
			return; // get out if we didn't click the Activate button
		}

		// retrieve the license from the database
		// $license =  trim(get_option('edd_wbcom_WBBPB_license_key'));
		$license = isset( $_POST['edd_wbcom_WBBPB_license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['edd_wbcom_WBBPB_license_key'] ) ) : '';

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( EDD_WBBPB_ITEM_NAME ), // the name of our product in EDD
			'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
			EDD_WBBPB_STORE_URL,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'buddypress-profile-pro' );
			}
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {
				switch ( $license_data->error ) {
					case 'expired':
						$message = sprintf(
							__( 'Your license key expired on %s.', 'buddypress-profile-pro' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;

					case 'revoked':
						$message = __( 'Your license key has been disabled.', 'buddypress-profile-pro' );
						break;

					case 'missing':
						$message = __( 'Invalid license.', 'buddypress-profile-pro' );
						break;

					case 'invalid':
					case 'site_inactive':
						$message = __( 'Your license is not active for this URL.', 'buddypress-profile-pro' );
						break;

					case 'item_name_mismatch':
						$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'buddypress-profile-pro' ), EDD_WBBPB_ITEM_NAME );
						break;

					case 'no_activations_left':
						$message = __( 'Your license key has reached its activation limit.', 'buddypress-profile-pro' );
						break;

					default:
						$message = __( 'An error occurred, please try again.', 'buddypress-profile-pro' );
						break;
				}
			}
		}

		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
			$base_url = admin_url( 'admin.php?page=' . EDD_WBBPB_PLUGIN_LICENSE_PAGE );
			$redirect = add_query_arg(
				array(
					'WBBPB_activation' => 'false',
					'message'          => urlencode( $message ),
				),
				$base_url
			);
			$license  = trim( $license );
			update_option( 'edd_wbcom_WBBPB_license_key', $license );
			update_option( 'edd_wbcom_WBBPB_license_status', $license_data->license );
			wp_redirect( $redirect );
			exit();
		}

		// $license_data->license will be either "valid" or "invalid"
		$license = trim( $license );
		update_option( 'edd_wbcom_WBBPB_license_key', $license );
		update_option( 'edd_wbcom_WBBPB_license_status', $license_data->license );
		wp_redirect( admin_url( 'admin.php?page=' . EDD_WBBPB_PLUGIN_LICENSE_PAGE ) );
		exit();
	}
}
add_action( 'admin_init', 'edd_wbcom_WBBPB_activate_license_button' );
