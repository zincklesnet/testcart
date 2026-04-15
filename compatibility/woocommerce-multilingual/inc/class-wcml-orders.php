<?php

    class WOOGC_WCML_Orders extends WCML_Orders {

        public function __construct( &$woocommerce_wpml, &$sitepress ){
            $this->woocommerce_wpml = $woocommerce_wpml;
            $this->sitepress = $sitepress;
            
            global $WooGC;

            $WooGC->functions->remove_class_filter ( 'woocommerce_order_get_items', 'WCML_Orders', 'woocommerce_order_get_items', 10);
            
            add_filter( 'woocommerce_order_get_items', array( $this, 'woocommerce_order_get_items' ), 10, 2 );
        }


       function woocommerce_order_get_items( $items, $order ){

	        if ( $this->is_order_saving_action() ) {
		        return $items;
	        }

            if( isset( $_GET[ 'post' ] ) && get_post_type( $_GET[ 'post' ] ) == 'shop_order' ) {
                // on order edit page use admin default language
                $language_to_filter = $this->sitepress->get_user_admin_language( get_current_user_id(), true );
            }elseif( isset( $_GET[ 'action' ] ) && ( $_GET['action'] == 'woocommerce_mark_order_complete' || $_GET['action'] == 'woocommerce_mark_order_status' || $_GET['action'] == 'mark_processing') ){
                //backward compatibility for WC < 2.7
                $order_id = method_exists( 'WC_Order', 'get_id' ) ? $order->get_id() : $order->id;
                $order_language = get_post_meta( $order_id, 'wpml_language', true );
                $language_to_filter = $order_language ? $order_language : $this->sitepress->get_default_language();
            }else{
                $language_to_filter = $this->sitepress->get_current_language();
            }

            foreach( $items as $index => $item ){
                if( is_array( $item ) ){
                    // WC < 2.7
                    foreach( $item as $key => $item_data ){
                        if( $key == 'product_id' ){
                            $tr_product_id = apply_filters( 'translate_object_id', $item_data, 'product', false, $language_to_filter );
                            if( !is_null( $tr_product_id ) ){
                                $items[ $index ][ $key ] = $tr_product_id;
                                $items[ $index ][ 'name'] = get_post( $tr_product_id )->post_title;
                            }
                        }
                        if( $key == 'variation_id' ){
                            $tr_variation_id = apply_filters( 'translate_object_id', $item_data, 'product_variation', false, $language_to_filter );
                            if( !is_null($tr_variation_id)){
                                $items[$index][$key] = $tr_variation_id;
                            }
                        }

                        if (substr($key, 0, 3) == 'pa_') {
                            //attr is taxonomy

                            $term_id = $this->woocommerce_wpml->terms->wcml_get_term_id_by_slug( $key, $item_data );
                            $tr_id = apply_filters( 'translate_object_id', $term_id, $key, false, $language_to_filter );

                            if(!is_null($tr_id)){
                                $translated_term = $this->woocommerce_wpml->terms->wcml_get_term_by_id( $tr_id, $key);
                                $items[$index][$key] = $translated_term->slug;
                            }
                        }

                        if( $key == 'type' && $item_data == 'shipping' && isset( $item[ 'method_id' ] ) ){

                            $items[ $index ][ 'name' ] = $this->woocommerce_wpml->shipping->translate_shipping_method_title( $item[ 'name' ], $item[ 'method_id' ], $language_to_filter );

                        }
                    }
                }else{
                    // WC >= 2.7
                    if( $item instanceof WC_Order_Item_Product ){
                        if( $item->get_type() == 'line_item' ){
                            $item_product_id = $item->get_product_id();
                            
                            $blog_id    =   $item->get_meta('blog_id', TRUE);
                            switch_to_blog( $blog_id );
                            
                            if( get_post_type( $item_product_id ) == 'product_variation' ){
                                $item_product_id = wp_get_post_parent_id( $item_product_id );
                            }

                            $tr_product_id = apply_filters( 'translate_object_id', $item_product_id, 'product', false, $language_to_filter );

                            if( !is_null( $tr_product_id ) ){
                                $item->set_product_id( $tr_product_id );
                                $item->set_name( get_post( $tr_product_id )->post_title );
                            }

                            $tr_variation_id = apply_filters( 'translate_object_id', $item->get_variation_id(), 'product_variation', false, $language_to_filter );
	                        if ( ! is_null( $tr_variation_id ) ) {
		                        $item->set_variation_id( $tr_variation_id );
		                        $item->set_name( wc_get_product( $tr_variation_id )->get_name() );
	                        }
                            
                            restore_current_blog();
                        }                   
                    }elseif( $item instanceof WC_Order_Item_Shipping ){
                        if( $item->get_method_id() ){

	                        $shipping_id = $item->get_method_id();
	                        if( method_exists( $item ,'get_instance_id' ) ){
		                        $shipping_id .= $item->get_instance_id();
                            }

                            $item->set_method_title(
                                    $this->woocommerce_wpml->shipping->translate_shipping_method_title(
                                        $item->get_method_title(),
                                        $shipping_id,
                                        $language_to_filter
                                    )
                            );
                        }
                    }
                }
            }

            return $items;

       }
            
        
       function is_order_saving_action(){
            return isset( $_POST['post_type'] ) && $_POST['post_type'] === 'shop_order' && isset( $_POST['wc_order_action'] );
        }
      


    }

    
?>