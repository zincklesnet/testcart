<?php
/*
Plugin Name: WP Global Cart
Plugin URI: https://wooglobalcart.com/
Description: Set a Global Cart for WooCommerce under a WordPress MultiSite environment
Author: Nsp Code
Author URI: https://wooglobalcart.com/ 
Version: 2.8.6
Text Domain: woo-global-cart
Domain Path: /languages/
Network: true
WC requires at least: 3.2.0
WC tested up to: 6.3.1
*/

    defined( 'ABSPATH' ) || exit;
    
    define('WOOGC_VERSION',             '2.8.6');            
    define('WOOGC_PATH',                plugin_dir_path(__FILE__));
    define('WOOGC_URL',                 plugins_url('', __FILE__));
    
    define('WOOGC_PRODUCT_ID',          'WooGC');
    define('WOOGC_INSTANCE',            preg_replace('/:[0-9]+/', '', str_replace(array ("https://" , "http://"), "", trim(network_site_url(), '/'))));
    define('WOOGC_UPDATE_API_URL',      'http://api.wooglobalcart.com/index.php');
    
    //SSO sessions are expiring after 10 secconds
    define('WOOGC_TRIGGER_KEY_EXPIRE',          15);
    
    //WooGC Global Cart cookie holder
    define('WOOGC_COOKIE_EXPIRE',         30 * 24 * 60 * 60);
        
    include_once(WOOGC_PATH . '/include/class.woogc.php');
    include_once(WOOGC_PATH . '/include/class.woogc.functions.php');
    include_once(WOOGC_PATH . '/include/class.woogc.licence.php');
    require_once(WOOGC_PATH . '/include/static-functions.php');
              
    //load language files
    add_action( 'plugins_loaded', 'WOOGC_load_textdomain'); 
    function WOOGC_load_textdomain() 
        {
            load_plugin_textdomain('woo-global-cart', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages'); 
        }
    
    
    register_activation_hook(   __FILE__, 'WOOGC_activated');
    register_deactivation_hook( __FILE__, 'WOOGC_deactivated');

    function WOOGC_activated($network_wide) 
        {
            global $WooGC;
            $WooGC->functions->create_tables();
        }

    function WOOGC_deactivated() 
        {
            //unlink MU files
            global $WooGC;
            $WooGC->functions->remove_mu_files();
            $WooGC->functions->remove_tables();
        }
        
    global $WooGC;
    
    $WooGC  =   new WOOGC();   
    $WooGC->init();
        
?>