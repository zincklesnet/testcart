<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_Cart 
        {
            
            function __construct()
                {
                    add_action( 'init',                                     array( $this, 'on_action__init') );
                    
                    add_filter('woocommerce_add_cart_item',                 array( $this, 'woocommerce_add_cart_item'),             10, 2 );
                    add_filter('woocommerce_cart_id',                       array( $this, 'woocommerce_cart_id'),  999, 5 );
                    
                }
                
                
            /**
            * Trigger on WordPress Init action
            * 
            */
            function on_action__init( )
                {
                    
                    //custom handler for undo_item
                    add_action('woocommerce_restore_cart_item',             array( $this, 'woocommerce_restore_cart_item' ), 999, 2 );
        
                }
                       
            
            
            public static function woocommerce_restore_cart_item( $cart_item_key, $cart )
                {
                    
                    $cart->cart_contents[ $cart_item_key ] = $cart->removed_cart_contents[ $cart_item_key ];
                    
                    $cart_item  =   $cart->cart_contents[ $cart_item_key ];
                    
                    if(!isset($cart_item['blog_id']))
                        return;
                        
                    switch_to_blog( $cart_item['blog_id'] );
                    
                    $cart->cart_contents[ $cart_item_key ]['data'] = wc_get_product( $cart->cart_contents[ $cart_item_key ]['variation_id'] ? $cart->cart_contents[ $cart_item_key ]['variation_id'] : $cart->cart_contents[ $cart_item_key ]['product_id'] );                   
                    
                    restore_current_blog();   
                    
                }
            
            
            function woocommerce_add_cart_item( $cart_item_data, $cart_item_key )
                {
                    
                    global $blog_id;
            
                    $cart_item_data['blog_id']          =   absint($blog_id);
                    //$cart_item_data['data']->site_id    =   absint($blog_id);
                    
                    return $cart_item_data;   
                    
                }

            
                
            function woocommerce_cart_id( $cart_item_id, $product_id, $variation_id, $variation, $cart_item_data)
                {
                    
                    global $blog_id;
                    
                    $id_parts = array( $product_id );

                    if ( $variation_id && 0 != $variation_id ) 
                        {
                            $id_parts[] = $variation_id;
                        }

                    if ( is_array( $variation ) && ! empty( $variation ) ) 
                        {
                            $variation_key = '';
                            foreach ( $variation as $key => $value ) 
                                {
                                    $variation_key .= trim( $key ) . trim( $value );
                                }
                            $id_parts[] = $variation_key;
                        }

                    if ( is_array( $cart_item_data ) && ! empty( $cart_item_data ) ) 
                        {
                            $cart_item_data_key = '';
                            foreach ( $cart_item_data as $key => $value ) 
                                {
                                    if ( is_array( $value ) || is_object( $value ) ) 
                                        {
                                            $value = http_build_query( $value );
                                        }
                                    $cart_item_data_key .= trim( $key ) . trim( $value );

                                }
                            $id_parts[] = $cart_item_data_key;
                        }
                        
                    $id_parts[] =   $blog_id;
                    
                    $cart_item_id   =   md5( implode( '_', $id_parts ) );
                    
                    return $cart_item_id;
                       
                }
                
        }

    new WooGC_Cart();

?>