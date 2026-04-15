<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Plugin Name:     WooCommerce Google Analytics Pro
    * Since:         1.7.1
    */

    class WooGC_woocommerce_google_analytics_pro
        {
           
            function __construct() 
                {
                    
                    $this->init();
                                  
                }
                
                
            function init()
                {
                      
                    add_filter( 'woocommerce_integrations',     array ( $this, 'woocommerce_integrations'), 999 );
                    
                }

            function woocommerce_integrations( $load_integrations )
                {
                    
                    include_once ( WOOGC_PATH . '/compatibility/woocommerce-google-analytics-pro/includes/WooGC_WC_Google_Analytics_Pro_Integration.php');  
                    
                    foreach ( $load_integrations as $key    =>  $value )
                        {
                            if  ( $key  !==  'google_analytics_pro' )
                                continue;
                                
                            $load_integrations[ $key ]  =   'WooGCWC_Google_Analytics_Pro_Integration';
                        }
                    
                    return $load_integrations;
                    
                }
                
        }

    new WooGC_woocommerce_google_analytics_pro();

?>