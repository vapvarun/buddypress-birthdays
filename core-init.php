<?php 
/*
*
*	***** BuddyPress Birthdays *****
*
*	This file initializes all BBIRTHDAYS Core components
*	
*/
// If this file is called directly, abort. //
if ( ! defined( 'WPINC' ) ) {die;} // end if
// Define Our Constants
define('BBIRTHDAYS_CORE_INC',dirname( __FILE__ ).'/assets/inc/');
define('BBIRTHDAYS_CORE_IMG',plugins_url( 'assets/img/', __FILE__ ));
define('BBIRTHDAYS_CORE_CSS',plugins_url( 'assets/css/', __FILE__ ));
define('BBIRTHDAYS_CORE_JS',plugins_url( 'assets/js/', __FILE__ ));
/*
*
*  Register CSS
*
*/
function bbirthdays_register_core_css(){
wp_enqueue_style('bbirthdays-core', BBIRTHDAYS_CORE_CSS . 'bbirthdays-core.css',null,time(),'all');
};
add_action( 'wp_enqueue_scripts', 'bbirthdays_register_core_css' );    
/*
*
*  Register JS/Jquery Ready
*
*/
function bbirthdays_register_core_js(){
// Register Core Plugin JS	
wp_enqueue_script('bbirthdays-core', BBIRTHDAYS_CORE_JS . 'bbirthdays-core.js','jquery',time(),true);
};
add_action( 'wp_enqueue_scripts', 'bbirthdays_register_core_js' );    
/*
*
*  Includes
*
*/ 
// Load the Functions
if ( file_exists( BBIRTHDAYS_CORE_INC . 'bbirthdays-core-functions.php' ) ) {
	require_once BBIRTHDAYS_CORE_INC . 'bbirthdays-core-functions.php';
}     
// Load the ajax Request
if ( file_exists( BBIRTHDAYS_CORE_INC . 'bbirthdays-ajax-request.php' ) ) {
	require_once BBIRTHDAYS_CORE_INC . 'bbirthdays-ajax-request.php';
} 
// Load the Shortcodes
if ( file_exists( BBIRTHDAYS_CORE_INC . 'bbirthdays-shortcodes.php' ) ) {
	require_once BBIRTHDAYS_CORE_INC . 'bbirthdays-shortcodes.php';
}