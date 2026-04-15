<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          WooCommerce Product Bundles
    * Since Version:        5.13.0
    */

    class WooGC_Compatibility_WooCommerce_Product_Bundles
        {
            
            public function __construct( ) 
                {
                    
                     
                    global $WooGC;
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'woocommerce_check_cart_items', 'WC_PB_Cart', 'check_cart_items', 15 );
                    
                    // Validate bundle configuration in cart.
                    add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_items' ), 15 );
                      
                }
                
                
            /**
             * Check bundle cart item configurations on cart load.
             */
            public function check_cart_items() 
                {

                    foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) 
                        {

                            switch_to_blog ( $cart_item['blog_id'] ) ;
                            
                            if ( wc_pb_is_bundle_container_cart_item( $cart_item ) ) {

                                $configuration = isset( $cart_item[ 'stamp' ] ) ? $cart_item[ 'stamp' ] : WC_PB()->cart->get_posted_bundle_configuration( $cart_item[ 'data' ] );

                                WC_PB()->cart->validate_bundle_configuration( $cart_item[ 'data' ], $cart_item[ 'quantity' ], $configuration, 'cart' );
                            }
                            
                            restore_current_blog();
                        }
                }
                
        
            
            
        }

        
    new WooGC_Compatibility_WooCommerce_Product_Bundles();



?>