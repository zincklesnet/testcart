<?php

    defined( 'ABSPATH' ) || exit;

    /**
    * Compatibility for Plugin Name: Formidable WooCommerce
    * Compatibility checked on Version: 1.06.02
    */
    
    class WooGC_Compatibility_WC_Formidable
        {
            
            function __construct()
                {
                    
                    remove_action( 'plugins_loaded', array( 'WC_Formidable', 'get_instance' ) );
                    
                    include_once ( WOOGC_PATH . '/compatibility/formidable-woocommerce/woogc_wc_formidable.class.php');
                    WooGC_WC_Formidable::get_instance();
                    
                }
                                                                   
        }


    new WooGC_Compatibility_WC_Formidable();    
    
?>