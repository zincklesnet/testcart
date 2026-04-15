<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_general_filters 
        {
            
            var $functions;
               
            function __construct()   
                {
                    global $WooGC;
                    
                    $this->functions    =   $WooGC->functions;
                    
                    add_filter( 'woocommerce_cart_item_product',                array($this, 'woocommerce_cart_item_product'), 99, 3 );
                                        
                    //exclude blog_id when retreiving formated order product meta 
                    add_filter('woocommerce_order_items_meta_get_formatted',    array($this, 'woocommerce_order_items_meta_get_formatted'), 999, 2);
                    
                    
                    add_filter('wp_loaded',                                     array ( $this, 'wp_loaded'));  
                    
                    
                    add_filter('woocommerce_cart_subtotal',                     array ( $this, 'woocommerce_cart_subtotal' )  , 999, 3 );
                    add_filter('woocommerce_cart_totals_order_total_html',      array ( $this, 'woocommerce_cart_totals_order_total_html' )  , 999 );
                    
                    add_filter('woocommerce_product_add_to_cart_url',           array ( $this, 'woocommerce_product_add_to_cart_url'), 99, 2 );
                    
                }
            
            
            function wp_loaded()
                {
                    add_action('switch_blog',                                   array( $this, 'switch_blog'), 999, 2 );
                }
                
            function woocommerce_cart_item_product ( $cart_item_data, $cart_item, $cart_item_key )
                {
                    
                    if (    !isset($cart_item['blog_id'])    ||  $cart_item['blog_id']   < 1   )
                        return $cart_item_data;
                    
                    global $blog_id;
                    
                    if ( $cart_item['blog_id'] ==   $blog_id )
                        return $cart_item_data;
                    
                    $product_id     =   $cart_item_data->get_ID();
                        
                    switch_to_blog( $cart_item['blog_id'] );
                    
                    $cart_item_data =   wc_get_product( $product_id ) ;
                    
                    restore_current_blog();
                       
                    return $cart_item_data;   
                }
                
                            
                
            function woocommerce_order_items_meta_get_formatted( $formatted_meta, $WC_Order_Item_Meta_Object )
                {
                    
                    foreach ( $formatted_meta   as  $key    =>  $formatted_item_meta )
                        {
                            
                            if ( $formatted_item_meta['key']    ==  'blog_id' )
                                unset( $formatted_meta[$key] );
                            
                        }
                    
                    return $formatted_meta;
                       
                }
                
                
            /**
            * Attempt to populate the txonomies with appropiate data for current site.
            *     
            * @param mixed $new_blog
            * @param mixed $prev_blog_id
            */
            function switch_blog( $new_blog, $prev_blog_id )  
                {
                    global $wp_taxonomies, $wp_switch_taxonomies_stack;
                    
                    if  ( ! is_array ( $wp_switch_taxonomies_stack ) )
                        $wp_switch_taxonomies_stack =   array(); 
                    
                    if  ( ! isset( $wp_switch_taxonomies_stack[$prev_blog_id] ) )
                        $wp_switch_taxonomies_stack[$prev_blog_id]  =   $wp_taxonomies;
                        
                    if ( isset( $wp_switch_taxonomies_stack[$new_blog] ) )
                        {
                            $wp_taxonomies  =   $wp_switch_taxonomies_stack[$new_blog];
                            return;   
                        }
                        
                    //attempt to create a list of taxonomies
                    global $wpdb;
                    $mysql_query    =   "SELECT taxonomy FROM " . $wpdb->term_taxonomy . " GROUP BY taxonomy";
                    $results        =   $wpdb->get_results( $mysql_query );
                    
                    foreach ( $results  as  $result )
                        {
                            if  ( isset ( $wp_taxonomies[ $result->taxonomy ] ))
                                continue;
                            
                            $name   =   $result->taxonomy;
                            $taxonomy_data  = array();
                            
                            if ( strpos($result->taxonomy, 'pa_' ) === 0 )
                                {
                                    $label  =   str_replace("pa_", "", $result->taxonomy);
                                    $label  =   ucfirst( $label );
                                    
                                    $taxonomy_data  = array(
                                                                'hierarchical'          => false,
                                                                'update_count_callback' => '_update_post_term_count',
                                                                'labels'                => array(
                                                                    /* translators: %s: attribute name */
                                                                    'name'              => sprintf( _x( 'Product %s', 'Product Attribute', 'woocommerce' ), $label ),
                                                                    'singular_name'     => $label,
                                                                    /* translators: %s: attribute name */
                                                                    'search_items'      => sprintf( __( 'Search %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'all_items'         => sprintf( __( 'All %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'parent_item'       => sprintf( __( 'Parent %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'parent_item_colon' => sprintf( __( 'Parent %s:', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'edit_item'         => sprintf( __( 'Edit %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'update_item'       => sprintf( __( 'Update %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'add_new_item'      => sprintf( __( 'Add new %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'new_item_name'     => sprintf( __( 'New %s', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'not_found'         => sprintf( __( 'No &quot;%s&quot; found', 'woocommerce' ), $label ),
                                                                    /* translators: %s: attribute name */
                                                                    'back_to_items'     => sprintf( __( '&larr; Back to "%s" attributes', 'woocommerce' ), $label ),
                                                                ),
                                                                'show_ui'               => true,
                                                                'show_in_quick_edit'    => false,
                                                                'show_in_menu'          => false,
                                                                'meta_box_cb'           => false,
                                                                'query_var'             => false,
                                                                'rewrite'               => false,
                                                                'sort'                  => false,
                                                                'public'                => false,
                                                                'show_in_nav_menus'     => false,
                                                                'capabilities'          => array(
                                                                    'manage_terms' => 'manage_product_terms',
                                                                    'edit_terms'   => 'edit_product_terms',
                                                                    'delete_terms' => 'delete_product_terms',
                                                                    'assign_terms' => 'assign_product_terms',
                                                                ),
                                                            );
                                }
                            
                            //presume is being used by product post type    
                            $new_taxonomy = new WP_Taxonomy( $name, 'product', $taxonomy_data );
                            
                            $wp_taxonomies[ $name ]   =   $new_taxonomy;
                            
                        }   
                    
                    $wp_switch_taxonomies_stack[$new_blog]  =   $wp_taxonomies;
                }
                
                
            
            /**
            * Group/format the SubTotal price, if the shops use different curencies
            * 
            * @param mixed $cart_subtotal
            * @param mixed $compound
            * @param mixed $cart
            */
            function woocommerce_cart_subtotal( $cart_subtotal, $compound, $cart )
                {
                    $options    =   $this->functions->get_options();   
                    if( $options['cart_checkout_type']  !=  'each_store' )
                        return $cart_subtotal;
                    
                    //check what curencies each of the sites with a product in the cart, uses 
                    $currency_map   =   array();
                    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) 
                        {
                            switch_to_blog( $cart_item['blog_id'] );
                            
                            $shop_currency  =   get_option('woocommerce_currency');
                            if ( ! isset( $currency_map[ $shop_currency ] ))
                                $currency_map[ $shop_currency ]     =   array( $cart_item['blog_id'] );
                                else
                                $currency_map[ $shop_currency ][]     =   $cart_item['blog_id']; 
                            restore_current_blog();
                        }

                    if ( count  ( $currency_map )  === 1  )
                        {
                            $shop_currency  =   get_option('woocommerce_currency');
                            
                            reset ( $currency_map );
                            
                            $cart_item_currency = key ( $currency_map );
                            
                            if ( $cart_item_currency    ==  $shop_currency )
                                return $cart_subtotal;
                        }
                    
                    $prices =   array();
                    
                    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) 
                        {
                            switch_to_blog( $cart_item['blog_id'] );
                            
                            $shop_currency  =   get_option('woocommerce_currency');
                            
                            $item_subtotal  =   0;
                            if ( $compound ) {
                                $item_subtotal =    $cart_item['line_total'] + $cart->get_shipping_total() + $cart_item['line_tax'];

                            } elseif ( $cart->display_prices_including_tax() ) {
                                $item_subtotal =    $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];

                            } else {
                                $item_subtotal =    $cart_item['line_subtotal'];
                            }        
                    
                            if ( isset ( $prices[ $shop_currency ] ) )
                                $prices[ $shop_currency ]   +=  $item_subtotal;
                                else
                                $prices[ $shop_currency ]   =  $item_subtotal;
                    
                            restore_current_blog();
                        }
                    
                    $cart_subtotal  =   '';
                    
                    foreach  ( $prices  as  $currency => $price )
                        {
                            if ( ! empty ( $cart_subtotal ) )
                                $cart_subtotal  .=  ' &#43; ';
                            $cart_subtotal  .=   wc_price ( $price , array ( 'currency'           => $currency ) );
                        }
                        
                    return $cart_subtotal;
                       
                }
                
            
            
            /**
            * Group/format the SubTotal price, if the shops use different curencies
            *     
            * @param mixed $value
            */
            function woocommerce_cart_totals_order_total_html( $total_value )
                {
                    $options    =   $this->functions->get_options();   
                    if( $options['cart_checkout_type']  !=  'each_store' )
                        return $total_value;
                        
                    //check what curencies each of the sites with a product in the cart, uses 
                    $currency_map   =   array();
                    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) 
                        {
                            switch_to_blog( $cart_item['blog_id'] );
                            
                            $shop_currency  =   get_option('woocommerce_currency');
                            if ( ! isset( $currency_map[ $shop_currency ] ))
                                $currency_map[ $shop_currency ]     =   array( $cart_item['blog_id'] );
                                else
                                $currency_map[ $shop_currency ][]     =   $cart_item['blog_id']; 
                            restore_current_blog();
                        }
                    
                    if ( count  ( $currency_map )  === 1  )
                        {
                            $shop_currency  =   get_option('woocommerce_currency');
                            
                            reset ( $currency_map );
                            
                            $cart_item_currency = key ( $currency_map );
                            
                            if ( $cart_item_currency    ==  $shop_currency )
                                return $total_value;
                        }
                               
                    $prices =   array();
                    
                    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) 
                        {
                            switch_to_blog( $cart_item['blog_id'] );
                            
                            $shop_currency  =   get_option('woocommerce_currency');
                    
                            if ( isset ( $prices[ $shop_currency ] ) )
                                $prices[ $shop_currency ]   +=  $cart_item['line_total'];
                                else
                                $prices[ $shop_currency ]   =  $cart_item['line_total'];
                                
                            if ( wc_tax_enabled() )
                                {
                                    $prices[ $shop_currency ]   +=  $cart_item['line_tax'];
                                }
                    
                            restore_current_blog();
                        }
                    
                    $local_total    =   FALSE;
                    $shop_currency  =   get_option('woocommerce_currency');
                    if ( count  ( $currency_map )  > 1  )
                        {
                            //check if there's any curency in the map
                            if ( isset( $currency_map[ $shop_currency ] ))
                                {
                                    $include_shops  =   $currency_map[ $shop_currency ];
                                    $include_shops  =   array_unique($include_shops);
                                    
                                    $default_cart_contents  =   WC()->cart->cart_contents;
                    
                                    foreach ( WC()->cart->cart_contents as  $cart_item_hash =>  $cart_item )
                                        {
                                            if ( ! in_array( $cart_item['blog_id'], $include_shops))
                                                unset( WC()->cart->cart_contents[$cart_item_hash] );
                                        }
                                    WC()->cart->calculate_totals();
                                    
                                    remove_filter('woocommerce_cart_totals_order_total_html',      array ( $this, 'woocommerce_cart_totals_order_total_html' )  , 999 );
                                    
                                    ob_start();
                                    wc_cart_totals_order_total_html();
                                    $local_total =    ob_get_contents();
                                    ob_clean();
                                    add_filter('woocommerce_cart_totals_order_total_html',      array ( $this, 'woocommerce_cart_totals_order_total_html' )  , 999 );
                                    
                                    //restore
                                    WC()->cart->cart_contents   =   $default_cart_contents;
                                    WC()->cart->calculate_totals();
                                }
                        }
                    
                    
                    $total_value  =   '';
                    
                    foreach  ( $prices  as  $currency => $price )
                        {
                                
                            if ( empty ( $total_value ) )
                                $total_value  =   '<strong>';
                                else
                                $total_value  .=  ' &#43; ';
                                
                            if  ( $currency == $shop_currency   &&  $local_total    !== FALSE )
                                {
                                    $total_value  .=    $local_total;
                                    continue;
                                }
                                
                            $total_value  .=   wc_price ( $price , array ( 'currency'           => $currency ) );
                        }
                    
                    //if the local currency not in the $prices, add the other fees to total
                    if ( ! isset ( $prices[ $shop_currency ] ))
                        {    
                            $cart_totals = WC()->cart->get_totals( );
                            $total_value  .= ' &#43; ' . wc_price ( (float)$cart_totals['total'] - (float)$cart_totals['cart_contents_total'], array ( 'currency'           => $shop_currency ) );
                        }
                    
                    $total_value  .=   '</strong>';
                        
                    return $total_value;   
                    
                    
                }
                
                
            /**
            * Get the add to url used mainly in loops.
            *     
            * @param mixed $url
            * @param mixed $product
            */
            function woocommerce_product_add_to_cart_url( $url, $product )
                {
                    
                    if ( ! isset ( $product->_context ) ||  $product->_context != 'woogc_shortcode' )
                        return $url;
                    
                    switch ( $product->get_type() )
                        {
                            case 'external' :
                                                $url    =   $product->get_permalink();
                                                break;
                                                
                            case 'variable' :
                                                $url    =   $product->get_permalink();
                                                break;
                            
                            case 'simple' : 
                                                $url = $product->is_purchasable() && $product->is_in_stock() ? remove_query_arg(
                                                        'added-to-cart',
                                                        add_query_arg(
                                                            array(
                                                                'add-to-cart' => $product->get_id(),
                                                            ),
                                                            $product->get_permalink()
                                                        )
                                                    ) : $product->get_permalink();
                                                break;
                            
                        }
                    
                    return $url;    
                }
            
        }


    new WooGC_general_filters();
        
?>