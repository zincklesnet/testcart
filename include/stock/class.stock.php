<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_Stock 
        {
            
            function __construct()
                {

                    add_filter('woocommerce_can_reduce_order_stock',    array( $this, 'woocommerce_can_reduce_order_stock'),    999, 2 );
                    
                    add_filter('woocommerce_can_restore_order_stock',   array( $this, 'woocommerce_can_restore_order_stock'),    999, 2 );
                                        
                }
                               
                
            /**
            * Check if the order contain network products or locala and reduce the stock
            * 
            * @param mixed $can_reduce
            * @param mixed $order
            */
            function woocommerce_can_reduce_order_stock( $can_reduce, $order )
                {
                    global $blog_id;
                    
                    $order_id       = $order->get_id();
                    $order_items    = $order->get_items();
                    
                    foreach($order_items    as  $order_itme_id  =>  $order_item_data)
                        {
                            $_blog_id   =   $order_item_data->get_meta('blog_id');
                            
                            if( !empty( $_blog_id ) &&   $blog_id    !=  $_blog_id )
                                {
                                    $can_reduce  =   FALSE;
                                    break;
                                }   
                        }
                    
                    if ( $can_reduce ===    TRUE)
                        return $can_reduce;
                    
                    
                    $changes    =   array();
                                     
                    // Loop over all items.
                    foreach ( $order->get_items() as $item ) 
                        {
                            if ( ! $item->is_type( 'line_item' ) ) {
                                continue;
                            }
                            
                            $_blog_id   =   $item->get_meta('blog_id');
                                    
                            if( !empty( $_blog_id ) )
                                switch_to_blog( $_blog_id );

                            // Only reduce stock once for each item.
                            $product            = $item->get_product();
                            $item_stock_reduced = $item->get_meta( '_reduced_stock', true );

                            if ( $item_stock_reduced || ! $product || ! $product->managing_stock() ) {
                                if( !empty( $_blog_id ) )
                                    restore_current_blog();
                                    
                                continue;
                            }

                            $qty       = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );
                            $item_name = $product->get_formatted_name();
                            $new_stock = wc_update_product_stock( $product, $qty, 'decrease' );

                            if ( is_wp_error( $new_stock ) ) {
                                
                                if( !empty( $_blog_id ) )
                                    restore_current_blog();
                                
                                /* translators: %s item name. */
                                $order->add_order_note( sprintf( __( 'Unable to reduce stock for item %s.', 'woocommerce' ), $item_name ) );
                                continue;
                            }
                            
                            if( !empty( $_blog_id ) )
                                restore_current_blog();

                            $item->add_meta_data( '_reduced_stock', $qty, true );
                            $item->save();

                            $changes[] = array(
                                'product'   => $product,
                                'from'      => $new_stock + $qty,
                                'to'        => $new_stock,
                                'blog_id'   => $_blog_id
                            );
                        }

                    $this->wc_trigger_stock_change_notifications( $order, $changes );

                    do_action( 'woocommerce_reduce_order_stock', $order );
                    
                    return FALSE;
                       
                }
                
            
            
            /**
            * After stock change events, triggers emails and adds order notes.
            *
            * @since 3.5.0
            * @param WC_Order $order order object.
            * @param array    $changes Array of changes.
            */
            function wc_trigger_stock_change_notifications( $order, $changes ) 
                {
                    if ( empty( $changes ) ) {
                        return;
                    }

                    $order_notes     = array();
                    $no_stock_amount = absint( get_option( 'woocommerce_notify_no_stock_amount', 0 ) );

                    foreach ( $changes as $change ) 
                        {
                            $_blog_id   =   $change['blog_id'];
                                    
                            if( !empty( $_blog_id ) )
                                switch_to_blog( $_blog_id );
                            
                            $product    =   wc_get_product ( $change['product']->get_id() );
                            
                            $order_notes[]    = $change['product']->get_formatted_name() . ' ' . $change['from'] . '&rarr;' . $change['to'];
                            $low_stock_amount = absint( wc_get_low_stock_amount( $product ) );
                            if ( $change['to'] <= $no_stock_amount ) {
                                do_action( 'woocommerce_no_stock', $product );
                            } elseif ( $change['to'] <= $low_stock_amount ) {
                                do_action( 'woocommerce_low_stock', $product );
                            }
                            
                            if( !empty( $_blog_id ) )
                                restore_current_blog(); 

                            if ( $change['to'] < 0 ) {
                                do_action(
                                    'woocommerce_product_on_backorder',
                                    array(
                                        'product'  => $product,
                                        'order_id' => $order->get_id(),
                                        'quantity' => abs( $change['from'] - $change['to'] ),
                                    )
                                );
                            }
                        }

                    $order->add_order_note( __( 'Stock levels reduced:', 'woocommerce' ) . ' ' . implode( ', ', $order_notes ) );
                }
                
                
            
            /**
            * Increase stock levels for items within an order.
            *
            * @since 3.0.0
            * @param int|WC_Order $order_id Order ID or order instance.
            */    
            function woocommerce_can_restore_order_stock( $can_increase, $order )
                {
                    
                    global $blog_id;
                    
                    $order_id       = $order->get_id();
                    $order_items    = $order->get_items();
                    
                    foreach($order_items    as  $order_itme_id  =>  $order_item_data)
                        {
                            $_blog_id   =   $order_item_data->get_meta('blog_id');
                            
                            if( !empty( $_blog_id ) &&   $blog_id    !=  $_blog_id )
                                {
                                    $can_increase  =   FALSE;
                                    break;
                                }   
                        }
                    
                    if ( $can_increase ===    TRUE)
                        return $can_increase;   
                        
                        
                    $changes = array();

                    // Loop over all items.
                    foreach ( $order->get_items() as $item ) 
                        {
                            if ( ! $item->is_type( 'line_item' ) ) {
                                continue;
                            }
                            
                            $_blog_id   =   $item->get_meta('blog_id');
                                    
                            if( !empty( $_blog_id ) )
                                switch_to_blog( $_blog_id );

                            // Only increase stock once for each item.
                            $product            = $item->get_product();
                            $item_stock_reduced = $item->get_meta( '_reduced_stock', true );

                            if ( ! $item_stock_reduced || ! $product || ! $product->managing_stock() ) {
                                if( !empty( $_blog_id ) )
                                    restore_current_blog();
                                
                                continue;
                            }

                            $item_name = $product->get_formatted_name();
                            $new_stock = wc_update_product_stock( $product, $item_stock_reduced, 'increase' );

                            if ( is_wp_error( $new_stock ) ) {
                                
                                if( !empty( $_blog_id ) )
                                    restore_current_blog();
                                
                                /* translators: %s item name. */
                                $order->add_order_note( sprintf( __( 'Unable to restore stock for item %s.', 'woocommerce' ), $item_name ) );
                                
                                continue;
                            }

                            if( !empty( $_blog_id ) )
                                restore_current_blog();
                            
                            $item->delete_meta_data( '_reduced_stock' );
                            $item->save();

                            $changes[] = $item_name . ' ' . ( $new_stock - $item_stock_reduced ) . '&rarr;' . $new_stock;
                            
                        }

                    if ( $changes ) 
                        {
                            $order->add_order_note( __( 'Stock levels increased:', 'woocommerce' ) . ' ' . implode( ', ', $changes ) );
                        }

                    do_action( 'woocommerce_restore_order_stock', $order ); 
                    
                    return FALSE;
                    
                }
                
        }

    new WooGC_Stock();

?>