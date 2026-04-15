<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          Divi Shop Builder
    * Since:        1.2.16
    */

    class WooGC_divi_shop_builder
        {
            
            function __construct( $dependencies = array() ) 
                {
                    
                    add_action( 'woocommerce_checkout_init',            array( $this, 'woocommerce_checkout_init' ), 10 );
                      
                }
                
            function woocommerce_checkout_init()
                {
                    remove_action( 'woocommerce_checkout_billing', array( WC()->checkout(), 'checkout_form_billing' ), 10 ); // remove wc default checkout billing
                    remove_action( 'woocommerce_checkout_shipping', array( WC()->checkout(), 'checkout_form_shipping' ), 10 ); // remove wc default checkout billing
                }         
            
        }

        
    new WooGC_divi_shop_builder();

?>