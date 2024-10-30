<?php
defined( 'ABSPATH' ) OR exit;



/**
 *
 * @package   cashback
 * @author    24/7 Discount <info@247discount.nl>
 * @license   GPL-2.0+
 * @link      https://www.247discount.nl/wordpress
 *
 * @wordpress-plugin
 * Plugin Name:       Cashback
 * Plugin URI:        https://www.247discount.nl/wordpress
 * Description:       Cashback will automaticly create hyperlinks to webshops which are mentioned in your blog posts.
 * Version:           1.1.0
 * Author:            24/7 Discount
 * Author URI:        info@247discount.nl
 * Text Domain:       cashback
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /lang
 *
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
//Include function
include( plugin_dir_path( __FILE__ ) . 'includes/CashBackApi.php' );
include( plugin_dir_path( __FILE__ ) . 'includes/CashBackCron.php' );
include( plugin_dir_path( __FILE__ ) . 'includes/CashBackI18n.php' );
include( plugin_dir_path( __FILE__ ) . 'includes/CashBackStatistics.php' );
include( plugin_dir_path( __FILE__ ) . 'includes/CashBackAdminPanel.php' );
include( plugin_dir_path( __FILE__ ) . 'includes/CashBackOffers.php' );
include( plugin_dir_path( __FILE__ ) . 'includes/CashBackContentScanner.php' );
include( plugin_dir_path( __FILE__ ) . 'includes/CashBackInstaller.php' );

//Admin
add_action( 'admin_menu', array( 'CashBackAdminPanel', 'adminInit' ) );
add_action( "admin_enqueue_scripts", "CashBackEnqueueMediaUploader" );
add_action( 'admin_init', array( 'CashBackInstaller', 'redirect_settings_page' ), 1 );
add_action( 'admin_init', array( 'CashBackAdminPanel', 'adminLogout' ) );
add_action( 'admin_init', array( 'CashBackAdminPanel', 'adminRefreshStatistics' ) );
add_action( 'wp_ajax_c247_process_register', array( 'CashBackAdminPanel', 'adminProcessRegister' ) );
add_action( 'wp_ajax_c247_process_login', array( 'CashBackAdminPanel', 'adminProcessLogin' ) );
add_action( 'wp_ajax_c247_process_image', array( 'CashBackAdminPanel', 'adminProcessImage' ) );
add_action( 'save_post', array( 'CashBackAdminPanel', 'adminProcessPost' ) );

//Activation
register_activation_hook( __FILE__, array( 'CashBackInstaller', 'install' ) );
register_deactivation_hook( __FILE__, array( 'CashBackInstaller', 'uninstall' ) );

//Language
add_action( 'plugins_loaded', array( 'CashBacki18n', 'loadPluginTextdomain' ) );

//The content
add_filter( 'the_content', array( 'CashBackContentScanner', 'ScanContent' ), 95, 2 );
add_filter( 'the_content', array( 'CashBackOffers', 'showOffers' ), 99, 2 );

//Cron
add_action( 'c247_daily_update', array( 'CashBackCron', 'DailyCron' ) );
add_action( 'c247_hourly_update', array( 'CashBackCron', 'HourlyCron' ) );

//After activation stuff
add_action( 'wp_ajax_c247_install', array( 'CashBackInstaller', 'activateSteps' ));
add_action( 'wp_ajax_c247_status_install', array( 'CashBackInstaller', 'statusInstall' ));

//Load image gallery
function CashBackEnqueueMediaUploader() {
	wp_enqueue_media();
	add_thickbox();
}

function CashBackEnqueueScripts(){
    wp_enqueue_script("jquery");
    wp_enqueue_style( 'c247-css', plugins_url('assets/css/c247.css', __FILE__) );
    wp_enqueue_script( 'c247-js', plugins_url('assets/js/c247.js', __FILE__) );
}
add_action( 'wp_enqueue_scripts', 'CashBackEnqueueScripts' );

function CashBackAdMinRegisterSession(){
    if( !session_id() ){
        session_start();
    }
}
add_action('admin_init','CashBackAdMinRegisterSession');