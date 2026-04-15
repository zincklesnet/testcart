<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC 
        {
            
            var $functions;
            
            var $licence;
            
            var $user_login_logout_action       =   FALSE;
            var $user_login_sso_hash            =   FALSE;
            
            var $cache  =   array();
            
            function __construct()
                {
                    
                    $this->functions    =   new WooGC_Functions();
                
                    $this->licence      =   new WooGC_licence();

                    add_filter( 'woogc/disable_global_cart',     array ( $this , 'disable_global_cart' ), 10, 2 );
                    
                    $this->functions->check_required_structure();
                
                }    
            
            function init()
                {
                    
                    //Admin
                    if(is_admin())
                        {
                            //options interface
                            include_once(WOOGC_PATH . '/include/class.woogc.options.php');
                        }

                    if(!$this->licence->licence_key_verify())
                        return FALSE;
                    
                    /**
                    * Check for specific features / functionality disable
                    */
                    $_WooGC_Disable_GlobalCart  =   apply_filters( 'woogc/disable_global_cart',     FALSE);
                        
                    // Check if WooCommerce is enabled
                    if ( ! $this->functions->is_plugin_active( 'woocommerce/woocommerce.php' ) )
                        {
                            if ( ! $_WooGC_Disable_GlobalCart )
                                {
                                    add_action( 'admin_notices',                array( $this, 'WC_disabled_notice' ));
                                    add_action( 'network_admin_notices',        array( $this, 'WC_disabled_notice' ));
                                }
        
                            return FALSE;
                        }
                    
                    if ( ! $this->functions->is_plugin_active( 'woocommerce/woocommerce.php') )
                        {
                            //return;
                            $_WooGC_Disable_GlobalCart  =   TRUE;
                        }
                    
                    if( $_WooGC_Disable_GlobalCart  === FALSE )
                        {
                            $options    =   $this->functions->get_options();
                            if( $options['cart_checkout_type']  ==  'single_checkout' )
                                define( 'WOOGC_SINGLE_CHECKOUT', TRUE );
                            if( $options['cart_checkout_type']  ==  'each_store' )
                                define( 'WOOGC_EACH_STORE_CHECKOUT', TRUE );
                            
                            if( defined ( 'WOOGC/SHIPPING/COSTS_EACH_SHOP' ) && $options['calculate_shipping_costs_for_each_shops']  ==  'yes' )
                                {
                                    define( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP', TRUE );
                                    
                                    if ( $options['calculate_shipping_costs_for_each_shops__site_base_tax']  > 0 )
                                        define ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP__SITE_BASE_TAX', $options['calculate_shipping_costs_for_each_shops__site_base_tax'] );
                                }
                            
                            //general filters
                            include_once(WOOGC_PATH . '/include/class.woogc.general-filters.php');
                                                        
                            add_action( 'woocommerce_init',                      array($this, 'woocommerce_init'));
                            
                            //replace default session manager
                            add_filter( 'woocommerce_session_handler',           array( $this, 'woocommerce_session_handler' ), 999 ); 
                  
                            if( defined ( 'DOING_AJAX' ) )
                                {
                                    //AJAX calls 
                                    include(WOOGC_PATH . '/include/class.woogc.ajax.php');
                                    new WooGC_AJAX();
                                }
                                
                            add_action( 'wp_footer',                            array( $this, 'on_action_wp_footer') );
                            add_action( 'admin_footer',                         array( $this, 'on_action_wp_footer') );
                            add_action( 'login_footer',                         array( $this, 'on_action_wp_footer') );
                            
                            include_once ( WOOGC_PATH . '/include/class.woogc.compatibility.php');
                            
                            //include dependencies
                            include_once(WOOGC_PATH . '/include/class.woogc.form-handler.php');
                        }

                    if( is_admin() )
                        {
                            //plugin core updater check
                            include_once(WOOGC_PATH . '/include/class.woogc.updater.php');
                            
                            //include internal update procedures on update
                            include_once(WOOGC_PATH . '/include/class.woogc.on-update.php');
                            
                            //admin notices
                            add_action( 'admin_notices',                array(&$this, 'on__admin_notices'));
                            add_action( 'network_admin_notices',        array(&$this, 'on__admin_notices'));
                            
                        }
                                    
                    if( is_admin() &&   ! is_network_admin() )
                        {
                            if ( $_WooGC_Disable_GlobalCart  === FALSE )
                                include_once ( WOOGC_PATH . '/include/admin/class.admin.php');
                                
                            include_once(WOOGC_PATH . '/include/class.woogc.admin-menus.php');
                            new WooGC_admin_menus();
                        }
                        
                    //network stuff
                    if( is_network_admin() )
                        {
                            include_once(WOOGC_PATH . '/include/class.woogc.admin-menus.php');
                            new WooGC_admin_menus();
                        }

                    
                    if( $_WooGC_Disable_GlobalCart  === FALSE )
                        { 
                            add_action( 'plugins_loaded',                       array( $this, 'on_plugins_loaded') );

                            add_action( 'shutdown',                             array( $this, 'on__shutdown'), 1 );
                            
                            add_action( 'init',                                 array( $this, 'gc_on_action_init') );

                            //after item add to cart through AJAX make sure other blogs know about the session id
                            add_action( 'woocommerce_add_to_cart_fragments',    array( $this, 'on_action_woocommerce_add_to_cart_fragments'));
                            
                            //replicate the cart session to other blogs
                            add_action( 'shutdown',                             array( $this, 'on_action_shutdown_save__session_data' ), 9999 );
                            
                            add_filter ( 'woocommerce_get_order_item_classname', array( $this, 'woocommerce_get_order_item_classname' ), 999, 3 );
                            
                            //load the cart when REST API
                            add_filter( 'rest_authentication_errors', array( $this, 'maybe_init_cart_session' ), 10, 2 );
                            
                            //shiping
                            include_once ( WOOGC_PATH . '/include/shipping/class.shipping.php');
                            
                            //stock
                            include_once ( WOOGC_PATH . '/include/stock/class.stock.php');
                            
                            //Order
                            include_once ( WOOGC_PATH . '/include/order/class.order.php');
                            
                            //Cart
                            include_once ( WOOGC_PATH . '/include/cart/class.cart.php');
                                                            
                            //cart split                            
                            if( defined( 'WOOGC_SINGLE_CHECKOUT' )  &&  WOOGC_SINGLE_CHECKOUT   === TRUE  )
                                include_once ( WOOGC_PATH . '/include/checkout/class.single_checkout.php');
                            if( defined ( 'WOOGC_EACH_STORE_CHECKOUT' ) &&  WOOGC_EACH_STORE_CHECKOUT   === TRUE )
                                include_once ( WOOGC_PATH . '/include/cart-split/class.woogc.cart-split-core.php');
                                
                            if ( defined ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP' ) && defined ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP__SITE_BASE_TAX' ) )
                                include_once ( WOOGC_PATH . '/include/tax/class.tax.php');
                        }
                        
                    //Templates filters
                    include_once ( WOOGC_PATH . '/include/template/class.template.php');
                    
                    add_action( 'init',                                 array( $this, 'on_action_init') );    
   
                    //Global Coupons
                    include_once ( WOOGC_PATH . '/include/coupons/class-coupons.php');
                    
                    //Shortcodes
                    include_once ( WOOGC_PATH . '/include/shortcodes/class.woogc.shortcodes.php');
                        
                }
            
            
            /**
            * On woocommerce_init
            * 
            */
            function woocommerce_init()
                {
                    
                    //replace the default cart with an extended WC_Cart instance
                    include_once ( WOOGC_PATH . '/include/cart/class.wc-cart-extend.php');
                    include_once ( WOOGC_PATH . '/include/cart/class-wc-cart-totals.php');
                    include_once ( WOOGC_PATH . '/include/session/class-wc-cart-session-extend.php');
                    if(! is_null($GLOBALS['woocommerce']->cart))
                        {
                            $GLOBALS['woocommerce']->cart   =   new WOOGC_WC_Cart( );
                        }

                    add_action( 'woocommerce_checkout_init',            array( 'WOOGC_WC_Checkout', 'instance' ), 999 );

                    //replace the default checkout with an extended WC_Checkout instance
                    include_once ( WOOGC_PATH . '/include/checkout/class.wc-checkout-extend.php');
                    
                    include_once ( WOOGC_PATH . '/include/order/class-wc-order-item-product.php');
                    
                }
            
            function maybe_init_cart_session( $return, $request = false )
                {
                    // Pass through other errors.
                    if ( ! empty( $error ) ) 
                        {
                            return $error;
                        }    
                    
                    if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) 
                        {
                            include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
                            include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
                            wc_load_cart();
                        }
                    
                    if( ! is_null($GLOBALS['woocommerce']->cart ) )
                        {
                            $GLOBALS['woocommerce']->cart   =   new WOOGC_WC_Cart( TRUE );
                        }
                    
                    
                    return $return;
                    
                }
                  
            
            function on_plugins_loaded()
                {
                        
                    //turn on buffering
                    ob_start();
                    
                    include_once ( WOOGC_PATH . '/include/class-woogc-download-handler.php');
                    
                    //Relocate default WordPress shutdown hook
                    remove_action(  'shutdown',                   'wp_ob_end_flush_all',                      1    );
                    add_action(     'shutdown',                   'wp_ob_end_flush_all',                      2    );  
                        
                }
            
            
            /**
            * On WordPress shutdown
            * Change any checkout links to plugin option
            * 
            */
            function on__shutdown()
                {
                    global $blog_id, $woocommerce;
                    
                    if(!is_object($woocommerce->cart))
                        return;
                       
                    $options    =   $this->functions->get_options();
                    $blog_details   =   get_blog_details( $blog_id );
         
                    $levels = ob_get_level();
                    
                    if( $levels < 1 )
                        return; 
                    
                    for ( $i = 1; $i < $levels; $i++ )
                        {
                            
                            $flush_level   =   TRUE;
                            if  ( $i == ( $levels - 1 ) ) 
                                $flush_level   =   FALSE;
                              
                            //allow other ob handlers to force a break
                            $continue   =   apply_filters('woogc/on_shutdown/ob_buferring_output', TRUE, ob_get_status() );
                            if (  $continue !== TRUE )
                                return;
                            
                            if  ( $flush_level  === TRUE )
                                {
                                    ob_get_flush();
                                    continue;
                                }
                            
                            $thml   =   ob_get_clean();
                            //ob_end_clean();
                            
                            //replace any checkout links
                            if( $options['cart_checkout_type']  ==  'single_checkout'  &&  !   empty($options['cart_checkout_location'])   &&  $options['cart_checkout_location']  !=  $blog_id)
                                {
                                    $checkout_url   =   wc_get_checkout_url();
                                    $checkout_url   =   str_replace(array('http:', 'https:'), "", $checkout_url);
                                    $checkout_url   =   trailingslashit($checkout_url);
                                    
                                    $thml   =   str_replace( "//"   .   $blog_details->domain .  untrailingslashit($blog_details->path) . "/checkout/", $checkout_url, $thml);
                                
                                }
                                else if ( $options['cart_checkout_type']  ==  'each_store'  &&  isset ( $woocommerce->cart->cart_split ) )
                                        {
                                            $checkout_url   =   $woocommerce->cart->cart_split->get_checkout_url();
                                            $checkout_url   =   str_replace(array('http:', 'https:'), "", $checkout_url);
                                            $checkout_url   =   trailingslashit($checkout_url);
                                            
                                            $thml   =   str_replace( "//"   .   $blog_details->domain .  untrailingslashit($blog_details->path) . "/checkout/", $checkout_url, $thml);
                                        }
                            
                            echo $thml;
                            
                        }
                    
                }
            
            
            /**
            * Trigger on WordPress Init action
            * 
            */
            function gc_on_action_init( )
                {
                    
                    //unregistre certain WooCommerce filters and use custom
                    remove_action( 'wp_loaded',                 array( 'WC_Form_Handler', 'order_again' ), 20 );
                    remove_action( 'wp_loaded',                 array( 'WC_Form_Handler', 'update_cart_action' ), 20 );
                    remove_action( 'woocommerce_payment_complete', 'wc_maybe_reduce_stock_levels' );
                    
                    //register a custom one
                    add_action( 'wp_loaded',                    array( 'WooGC_Form_Handler', 'order_again' ), 20 );
                    add_action( 'wp_loaded',                    array( 'WooGC_Form_Handler', 'update_cart_action' ), 20 );

                }
                
            /**
            * Trigger on WordPress Init action
            * 
            */
            function on_action_init( )
                {
                                         
                    $options    =   $this->functions->get_options();
                    if($options['use_sequential_order_numbers'] ==  'yes')
                        include_once( WOOGC_PATH . '/include/class.woogc.sequential-order-numbers.php');
                    
                }

                                
            function on_action_woocommerce_add_to_cart_fragments( $mini_cart )
                {
                    return $mini_cart;
                    
                    //only when doing AJAX
                    if ( ! defined( 'DOING_AJAX' ) )
                        return $mini_cart;
                    
                    ob_start(); 
                    ?>
                    <script type='text/javascript'> WooGC_Sync.init(); </script>
                    <?php
                    $html   =   ob_get_contents();
                    ob_end_clean();
                    
                    $mini_cart['div.widget_shopping_cart_content'] .=   $html;
                    
                    return $mini_cart;
                       
                }
                
            
            /**
            * Trigger on WordPress wp_footer action
            * Output front side JavaScript code for syncronisation
            * 
            */
            function on_action_wp_footer()
                {
                    
                    //clear expired triggers
                    $this->clear_expired_triggers();
                        
                    if ( ! $this->functions->is_plugin_active( 'woocommerce/woocommerce.php') )
                        return;
                    
                    global $blog_id;
                                       
                    $sync_directory     =   WOOGC_URL   .   '/sync';
                       
                    $site_home  =   site_url();
                    $site_home  =   str_replace(array('http://', 'https://'), "", $site_home);
                    $site_home  =   trim($site_home, '/');
                      
                    $sync_directory_url     =   str_replace(array('http://', 'https://'), "", $sync_directory);
                    $sync_directory_url     =   str_replace($site_home, "", $sync_directory_url);
                    $sync_directory_url     =   apply_filters( 'woogc/sync_directory_url', $sync_directory_url );
                    
                    ?>
                    
                    
                    <div id="woogc_sync_wrapper" style="display: none"></div>
                    <script type='text/javascript'>
                    /* <![CDATA[ */
                    var WooGC_Sync_Url      =    '<?php echo $sync_directory_url ?>';
                    var WooGC_Sites = [<?php
                                                    
                            $first  =   TRUE;
                                                    
                            $processed_domains  =   array();
                            $blog_details   =   get_blog_details( $blog_id );
                            
                            //ignore current domain
                            $processed_domains[]    =   WooGC_get_domain( $blog_details->domain );
                            
                            $options    =   $this->functions->get_options();
                            
                            $sites      =   $this->functions->get_gc_sites( TRUE );
                            
                            $sites_ids  =   array();
                            foreach($sites  as  $site)
                                {
                                    if ( isset ( $options['use_global_cart_for_sites'][$site->blog_id] )    &&  $options['use_global_cart_for_sites'][$site->blog_id] == 'no' )
                                        continue;
                                        
                                    //check if the globalcart is disabled for site
                                    if ( apply_filters( 'woogc/disable_global_cart',     FALSE,  $site->blog_id ) === FALSE )
                                        $sites_ids[]    =   $site->blog_id;   
                                }
                            
                            $allowed_gc_sites   =   apply_filters('woogc/global_cart/sites', $sites_ids);

                            $disabled_gc_sites  =   array();

                            foreach( $sites  as  $site )
                                {
                                    
                                    //ignore the current site
                                    if( $site->blog_id   ==  $blog_id )
                                        {
                                            if  ( !in_array($blog_id, $allowed_gc_sites)) 
                                                {   
                                                    $disabled_gc_sites[]    =   $site->blog_id ;   
                                                }
                                            continue;
                                        }
                                        
                                    //no need to set for subfolder domains
                                    if($site->path  !=  '/')
                                        continue;
                                        
                                    $domain_root    =   WooGC_get_domain( $site->domain );
                                        
                                    //subdomain check  
                                    if( is_subdomain_install() )
                                        {
                                            $found  =   FALSE;
                                            
                                            foreach($processed_domains  as  $processed_domain)
                                                {
                                                    if (strpos($domain_root, "." . $processed_domain)    !== FALSE)
                                                        {
                                                            $found  =   TRUE;
                                                            break;   
                                                        }
                                                }
                                                
                                            if ( $found  === TRUE )
                                                continue;
                                        }
                                        
                                    //if domain already processed continue
                                    if( in_array( $domain_root, $processed_domains ) )
                                        continue;
                                    
                                    $processed_domains[]    =   $domain_root;
                                    
                                    if  ( in_array($site->blog_id, $allowed_gc_sites)) 
                                        {
                                            if ( !$first )
                                                echo ', ';
                                            echo "'//" . $site->domain . "'";

                                            $first  =   FALSE;
                                        }
                                        else
                                        {
                                            $disabled_gc_sites[]    =   $site->blog_id ;   
                                        }
                                }
                              
                        ?>];
                    var WooGC_sd    =   <?php  echo ( is_subdomain_install() ? 'true' : 'false' ); ?>;
                    <?php            
  
                        //output JavaScript variable for POST action to catch on specific methods
                        $WooGC_on_PostVars  =   apply_filters('woogc/sync/on_post_vars', array());
                        if(is_array($WooGC_on_PostVars) &&  count($WooGC_on_PostVars) > 0)
                            {
                                ?>
                                var WooGC_on_PostVars   =   [<?php  
                                    
                                    $first = TRUE;
                                    foreach ($WooGC_on_PostVars as $key =>  $value)
                                        {
                                            if($first === TRUE)
                                                $first  =   FALSE;
                                                else
                                                echo ", ";
                                                
                                            echo '"' . $value . '"';    
                                        }
                                
                                 ?>];
                                <?php
                            }
                            else
                            {
                                ?>
                                var WooGC_on_PostVars   =   [];
                                <?php   
                            }
                            
                    ?>
                    /* ]]> */
                    </script>
                    <script type='text/javascript' src='<?php echo str_replace(array('http:', 'https:'), "", WOOGC_URL) ?>/js/woogc-sync.js?ver=1.1'></script>
    
                    <?php                    
                     
      
                }
                
                
                
            function clear_expired_triggers()
                {
                    
                    global $wpdb;
                    
                    $mysql_query    =   "UPDATE "  .   $wpdb->base_prefix . 'woocommerce_woogc_sessions' . " 
                                                SET trigger_key =   '', trigger_key_expiry = '', trigger_user_hash = ''
                                                WHERE " . time() . " > trigger_key_expiry";
                    $wpdb->get_results( $mysql_query );
                }    
                
            
            function on_action_shutdown_save__session_data( )
                {
                    
                    if(is_admin()   &&  ( ! defined('DOING_AJAX') ||  (defined('DOING_AJAX') &&  DOING_AJAX  === FALSE )))
                        return;
                    
                    global $wpdb, $blog_id, $woocommerce;
                    
                    $session_key    =   '';
                    
                    if( is_object($woocommerce->session))
                        $session_key        =   $woocommerce->session->get_customer_id();
                    
                    if (empty($session_key ))
                        return;
                    
                    //check if there's a session saved
                    //retrieve the current session data
                    $mysql_query    =   $wpdb->prepare( "SELECT * FROM ". $wpdb->prefix . "woocommerce_sessions WHERE session_key = %s", $session_key );
                    $session_data   =   $wpdb->get_row( $mysql_query );

                    //if empty no need to continue
                    if ( !isset($session_data->session_id)   ||  empty($session_data->session_id) )
                        return;
                    
                    $options    =   $this->functions->get_options();
                                 
                    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                                        
                    $sites  =   $this->functions->get_gc_sites( TRUE );
                    foreach( $sites  as  $site )
                        {
                            if ( isset ( $options['use_global_cart_for_sites'][$site->blog_id] )    &&  $options['use_global_cart_for_sites'][$site->blog_id] == 'no' )
                                continue;
                                
                            if ( apply_filters( 'woogc/disable_global_cart',     FALSE,  $site->blog_id ) !== FALSE )
                                continue;
                                    
                            //no need to update current blog
                            if ( $blog_id    ==  $site->blog_id ) 
                                continue;
                             
                            switch_to_blog( $site->blog_id );
                            
                            //check if woocommerce is active for this site
                            if ( ! $this->functions->is_plugin_active( 'woocommerce/woocommerce.php' ) )
                                {
                                    restore_current_blog();
                                    continue;
                                }
                            
                            //check if the table exists
                            $mysql_query    =   "SHOW tables LIKE '" . $wpdb->prefix . "woocommerce_sessions'";
                            $found_table    =   $wpdb->get_var( $mysql_query );
                            if ( empty ( $found_table ) )
                                {
                                    restore_current_blog();
                                    continue;
                                }
                                                                
                            $mysql_query    =   $wpdb->prepare( "SELECT session_id FROM ". $wpdb->prefix . "woocommerce_sessions WHERE session_key = %s", $session_key );
                            $session_id     =   $wpdb->get_var( $mysql_query );
                            
                            if( empty($session_id) )
                                {
                                    //add new entry    
                                    $mysql_query    =   "INSERT INTO ". $wpdb->prefix . "woocommerce_sessions 
                                                            (`session_id`, `session_key`, `session_value`, `session_expiry`) 
                                                            VALUES (NULL, '". $session_key ."', '". esc_sql ( $session_data->session_value ) ."', '". $session_data->session_expiry ."')";
                                    $results        =   $wpdb->get_results( $mysql_query );
                                }
                                else
                                {
                                    //update the row   
                                    $mysql_query    =   "UPDATE ". $wpdb->prefix . "woocommerce_sessions 
                                                                SET `session_value` =   '". esc_sql( $session_data->session_value ) ."', `session_expiry`    =   '". $session_data->session_expiry ."'
                                                                WHERE session_id = " . $session_id;
                                    $results        =   $wpdb->get_results( $mysql_query );
                                }
                                
                            restore_current_blog();
                            
                        }
                      
                    
                }

                
            function woocommerce_session_handler()
                {
                    
                    include_once(WOOGC_PATH . '/include/class.woogc.wc-session-handler.php');
                    
                    return 'WooGC_WC_Session_Handler';    
                    
                }
                
                
            function on__admin_notices()
                {
                    
                    if(! $this->functions->check_mu_files())
                        {
                            echo "<div class='error'><p><strong>WooCommerce Global Cart:</strong> ". __('Unable to copy woo-gc.php to mu-plugins folder. Is this directory writable?', 'woo-global-cart')  ."</p></div>";
                        }
                        
                    //check for MU module starter issues
                    global $WooGC__MU_Module;
                    
                    if  ( ! is_array($WooGC__MU_Module)  )
                        $WooGC__MU_Module   =   array();
                    
                    if(isset($WooGC__MU_Module['issues'])   &&  count( $WooGC__MU_Module['issues'] )   >   0 )
                        {
                            foreach($WooGC__MU_Module['issues'] as  $issue_code)
                                {
                                    switch($issue_code)
                                        {
                                            case 'e01'      :
                                                                echo "<div class='error'><p><strong>WooCommerce Global Cart:</strong> ". __('COOKIE_DOMAIN constant already defined. The Global Cart feature possibly not fully functional.', 'woo-global-cart')  ."</p></div>";
                                                                break;   
                                            
                                        }
                                }
                        }
                    
                }
                
                
            function WC_disabled_notice()
                {
                    echo "<div class='error'><p><strong>WooCommerce Global Cart:</strong> ". __('WooCommerce plugin is required to be active.', 'woo-global-cart')  ."</p></div>";
                }

                
            function woocommerce_get_order_item_classname( $classname, $item_type, $id  )
                {
                    
                    switch ( $item_type ) 
                        {
                            case 'line_item' :
                            case 'product' :
                                $classname = 'WooGC_WC_Order_Item_Product';
                            break;
                 
                        }
                        
                    return $classname;
                       
                }
            
            
            function disable_global_cart( $status, $_blog_id = '' )
                {
                    global $blog_id;
             
                    if ( empty ( $_blog_id ) )
                        $_blog_id    =   $blog_id;
                                        
                    $options    =   $this->functions->get_options();
                     
                    if ( isset ( $options['use_global_cart_for_sites'][ $_blog_id ] )    &&  $options['use_global_cart_for_sites'][ $_blog_id ] == 'no' )
                        return TRUE;   
                         
                    return $status;   
                    
                    
                }
                       
        }
        
?>