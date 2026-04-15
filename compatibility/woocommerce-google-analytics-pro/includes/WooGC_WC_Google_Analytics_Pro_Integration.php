<?php


    class WooGCWC_Google_Analytics_Pro_Integration  extends WC_Google_Analytics_Pro_Integration
        {
            
            /**
             * Tracks a product cart removal event.
             *
             * @since 1.0.0
             * @param string $cart_item_key the unique cart item ID
             */
            public function removed_from_cart( $cart_item_key ) {

                if ( isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {

                    $item    = WC()->cart->cart_contents[ $cart_item_key ];
                    
                    switch_to_blog( $item['blog_id']);
                    
                    $product = ! empty( $item['variation_id'] ) ? wc_get_product( $item['variation_id'] ) : wc_get_product( $item['product_id'] );

                    $properties = array(
                        'eventCategory' => 'Cart',
                        'eventLabel'    => htmlentities( $product->get_title(), ENT_QUOTES, 'UTF-8' ),
                    );

                    $ec = array( 'remove_from_cart' => array( 'product' => $product ) );

                    $this->api_record_event( $this->event_name['removed_from_cart'], $properties, $ec );
                    
                    restore_current_blog();
                }
            }
            
            
            
            /**
             * Tracks the cart changed quantity event.
             *
             * @since 1.0.0
             * @param string $cart_item_key the unique cart item ID
             * @param int $quantity the changed quantity
             */
            public function changed_cart_quantity( $cart_item_key, $quantity ) {;

                if ( isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {

                    $item    = WC()->cart->cart_contents[ $cart_item_key ];
                    
                    switch_to_blog( $item['blog_id']);
                    
                    $product = wc_get_product( $item['product_id'] );

                    $properties = array(
                        'eventCategory' => 'Cart',
                        'eventLabel'    => htmlentities( $product->get_title(), ENT_QUOTES, 'UTF-8' ),
                    );

                    $this->api_record_event( $this->event_name['changed_cart_quantity'], $properties );
                    
                    restore_current_blog();
                }
            }   
            
            
            
        }



?>