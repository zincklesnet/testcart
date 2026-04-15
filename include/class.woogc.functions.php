<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC_Functions 
        {
                            
            /**
            * Return Options
            * 
            */
            static public function get_options()
                {
                    
                    $_options   =   get_site_option('woogc_options');
                    
                    $defaults = array (
                                             'version'                                  =>  '1.0',
                                             'db_version'                               =>  '1.0',
                                             
                                             'cart_checkout_type'                       =>  'single_checkout',
                                             'cart_checkout_location'                   =>  '',
                                             'cart_checkout_split_orders'               =>  'no',
                                             
                                             'calculate_shipping_costs_for_each_shops'  =>  'no',
                                             'calculate_shipping_costs_for_each_shops__site_base_tax'  =>  'no',
                                             
                                             'use_sequential_order_numbers'             =>  'no',
                                             'show_product_attributes'                  =>  'no',
                                             
                                             'use_global_cart_for_sites'                =>  array()
                                             
                                       );
                    
                    $options = wp_parse_args( $_options, $defaults );
                          
                    return $options;  
                    
                }
            
            /**
            * Update Options
            *     
            * @param mixed $options
            */
            static public function update_options($options)
                {
                    
                    update_site_option('woogc_options', $options);
                    
                    
                }
            
                  
            /**
            * Return current url
            * 
            */
            function current_url()
                {
                    
                    $current_url    =   'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                    
                    return $current_url;
                    
                }
            
            
            
            /**
            * Return a list of blogs to be used along with the plugin
            * 
            * @param mixed $WooCommerce_Active
            * @param mixed $_usage_area
            */
            function get_gc_sites( $WooCommerce_Active = FALSE, $context = 'view' )
                {
                    
                    $args   =   array(
                                        'number'    =>  9999,
                                        'public'    =>  1,
                                        'archived'  =>  0
                                        );
                    $sites  =   get_sites( $args );
                    
                    if($WooCommerce_Active  === FALSE)
                        return $sites;
                        
                    foreach ($sites as  $key    =>  $site)
                        {
                            switch_to_blog($site->blog_id);
                            
                            if (! $this->is_plugin_active( 'woocommerce/woocommerce.php') )
                                {
                                    unset($sites[$key]);
                                }
                                
                            restore_current_blog();
                               
                        }
                        
                    $sites  =   array_values($sites);
                    
                    $sites  =   apply_filters( 'woogc/get_gc_sites' , $sites, $context );
                    
                    return $sites;   
                    
                }
            
                       
            
            /**
            * Remove Class Filter Without Access to Class Object
            *
            * In order to use the core WordPress remove_filter() on a filter added with the callback
            * to a class, you either have to have access to that class object, or it has to be a call
            * to a static method.  This method allows you to remove filters with a callback to a class
            * you don't have access to.
            *
            * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
            * Updated 2-27-2017 to use internal WordPress removal for 4.7+ (to prevent PHP warnings output)
            *
            * @param string $tag         Filter to remove
            * @param string $class_name  Class name for the filter's callback
            * @param string $method_name Method name for the filter's callback
            * @param int    $priority    Priority of the filter (default 10)
            *
            * @return bool Whether the function is removed.
            */
            function remove_class_filter( $tag, $class_name = '', $method_name = '', $priority = '' ) 
                {
                    
                    global $wp_filter;
                    
                    // Check that filter actually exists first
                    if ( ! isset( $wp_filter[ $tag ] ) ) 
                        return FALSE;
                        
                    /**
                    * If filter config is an object, means we're using WordPress 4.7+ and the config is no longer
                    * a simple array, rather it is an object that implements the ArrayAccess interface.
                    *
                    * To be backwards compatible, we set $callbacks equal to the correct array as a reference (so $wp_filter is updated)
                    *
                    * @see https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
                    */
                    if ( is_object( $wp_filter[ $tag ] ) && isset( $wp_filter[ $tag ]->callbacks ) ) 
                        {
                            // Create $fob object from filter tag, to use below
                            $fob = $wp_filter[ $tag ];
                            $callbacks = &$wp_filter[ $tag ]->callbacks;
                        } 
                        else 
                        {
                            $callbacks = &$wp_filter[ $tag ];
                        }
                        
                    // Exit if there aren't any callbacks for specified priority
                    if ( ! empty ( $priority ) && ( ! isset ( $callbacks[ $priority ] ) || empty ( $callbacks[ $priority ] ) ) )
                        return FALSE;
                    
                    foreach ( (array) $callbacks    as  $callbacks_priority =>  $group )
                        {
                            if ( ! empty ( $priority) &&    $priority   !=  $callbacks_priority )
                                continue;
                                
                            // Loop through each filter for the specified priority, looking for our class & method
                            foreach( (array) $callbacks[ $callbacks_priority ] as $filter_id => $filter ) 
                                {
                                    // Filter should always be an array - array( $this, 'method' ), if not goto next
                                    if ( ! isset( $filter[ 'function' ]  ) ) 
                                        continue;
                                    
                                    //remove static    
                                    if ( ! is_array( $filter[ 'function' ] ) )
                                        {
                                            if( $filter[ 'function' ]   ==  $class_name . '::'  .  $method_name)
                                                {
                                                    unset( $callbacks[ $callbacks_priority ][ $filter_id ] );
                                                    return TRUE;   
                                                }
                                            continue;   
                                        }
                                        
                                    // If first value in array is not an object, it can't be a class
                                    if ( ! is_object( $filter[ 'function' ][ 0 ] ) &&   empty ( $filter[ 'function' ][ 0 ] ) ) 
                                        continue;
                                        
                                    // Method doesn't match the one we're looking for, goto next
                                    if ( $filter[ 'function' ][ 1 ] !== $method_name ) 
                                        continue;
                                        
                                    // Method matched, now let's check the Class
                                    if ( is_object( $filter[ 'function' ][ 0 ] ) &&  get_class( $filter[ 'function' ][ 0 ] ) === $class_name ) 
                                        {
                                            // WordPress 4.7+ use core remove_filter() since we found the class object
                                            if( isset( $fob ) )
                                                {
                                                    // Handles removing filter, reseting callback priority keys mid-iteration, etc.
                                                    $fob->remove_filter( $tag, $filter['function'], $callbacks_priority );
                                                } 
                                            else 
                                                {
                                                    // Use legacy removal process (pre 4.7)
                                                    unset( $callbacks[ $callbacks_priority ][ $filter_id ] );
                                                    
                                                    // and if it was the only filter in that priority, unset that priority
                                                    if ( empty( $callbacks[ $callbacks_priority ] ) ) 
                                                        {
                                                            unset( $callbacks[ $callbacks_priority ] );
                                                        }
                                                        
                                                    // and if the only filter for that tag, set the tag to an empty array
                                                    if ( empty( $callbacks ) ) 
                                                        {
                                                            $callbacks = array();
                                                        }

                                                    
                                                }
                                                
                                            return TRUE;
                                            
                                        }
                                        else
                                        {
                                            // Use legacy removal process (pre 4.7)
                                            unset( $callbacks[ $callbacks_priority ][ $filter_id ] );
                                            
                                            // and if it was the only filter in that priority, unset that priority
                                            if ( empty( $callbacks[ $callbacks_priority ] ) ) 
                                                {
                                                    unset( $callbacks[ $callbacks_priority ] );
                                                }
                                                
                                            // and if the only filter for that tag, set the tag to an empty array
                                            if ( empty( $callbacks ) ) 
                                                {
                                                    $callbacks = array();
                                                }
                                            
                                        }
                                }
                        }
                        
                    return FALSE;
                    
                }
            
            
            /**
            * Remove Class Action Without Access to Class Object
            *
            * In order to use the core WordPress remove_action() on an action added with the callback
            * to a class, you either have to have access to that class object, or it has to be a call
            * to a static method.  This method allows you to remove actions with a callback to a class
            * you don't have access to.
            *
            * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
            *
            * @param string $tag         Action to remove
            * @param string $class_name  Class name for the action's callback
            * @param string $method_name Method name for the action's callback
            * @param int    $priority    Priority of the action (default 10)
            *
            * @return bool               Whether the function is removed.
            */
            function remove_class_action( $tag, $class_name = '', $method_name = '', $priority = '' ) 
                {
                    
                    $this->remove_class_filter( $tag, $class_name, $method_name, $priority );
                    
                }
                
            
            /**
            * Replace a filter / action from anonymous object
            * 
            * @param mixed $tag
            * @param mixed $class
            * @param mixed $method
            * @param mixed $priority
            */
            function remove_anonymous_object_filter( $tag, $class, $method, $priority = '' ) 
                {
                    $filters = false;

                    if ( isset( $GLOBALS['wp_filter'][$tag] ) )
                        $filters = $GLOBALS['wp_filter'][$tag];

                    if ( $filters )
                    foreach ( $filters as $filter_priority => $filter ) 
                        {
                            if ( ! empty ( $priority )  &&   $priority != $filter_priority )
                                continue;
                                
                            foreach ( $filter as $identifier => $function ) 
                                {                                   
                                    if ( ! isset ( $function['function'] ) || ! is_array ( $function['function'] ) )
                                        continue;
                                    
                                    if ( is_string( $function['function'][0] )  &&  $function['function'][0]    == $class   &&  $function['function'][1]    ==  $method )
                                        remove_filter($tag, array( $function['function'][0], $method ), $filter_priority );
                                    else if ( is_object( $function['function'][0] )  &&  get_class( $function['function'][0] )    == $class   &&  $function['function'][1]    ==  $method ) 
                                        remove_filter($tag, array( $function['function'][0], $method ), $filter_priority );
                                }
                        }
                }
                
                
            function createInstanceWithoutConstructor($class)
                {
                    
                    $reflector  = new ReflectionClass($class);
                    $properties = $reflector->getProperties();
                    $defaults   = $reflector->getDefaultProperties();
                           
                    $serealized = "O:" . strlen($class) . ":\"$class\":".count($properties) .':{';
                    foreach ($properties as $property)
                        {
                            $name = $property->getName();
                            if($property->isProtected())
                                {
                                    $name = chr(0) . '*' .chr(0) .$name;
                                } 
                            elseif($property->isPrivate())
                                {
                                    $name = chr(0)  . $class.  chr(0).$name;
                                }
                            
                            $serealized .= serialize($name);
                            
                            if(array_key_exists($property->getName(),$defaults) )
                                {
                                    $serealized .= serialize($defaults[$property->getName()]);
                                } 
                            else 
                                {
                                    $serealized .= serialize(null);
                                }
                        }
                        
                    $serealized .="}";
                    
                    return unserialize($serealized);
                    
                }
                
                
                
            function is_plugin_active( $plugin_slug )
                {
                    
                    include_once(ABSPATH.'wp-admin/includes/plugin.php');
                    
                    $found_plugin   =   is_plugin_active($plugin_slug);   
                    
                    if ( $found_plugin &&  ! file_exists( trailingslashit ( WP_PLUGIN_DIR ) . $plugin_slug ) )
                        $found_plugin   =   FALSE;
                    
                    return $found_plugin;
                    
                }
                
            
            
            
            /**
            * Check different requires
            * 
            */
            public function check_required_structure()
                {
                    
                    //check if the mu files exists
                    if( ! $this->check_mu_files())
                        $this->copy_mu_files( );
                        
                    //check if outdated
                    if ( ! defined('WOOGC_MULOADER_VERSION')    ||  version_compare( WOOGC_MULOADER_VERSION, '1.3', '<' ) )
                        $this->copy_mu_files( TRUE );
                }
                
                
                
            
            /**
            * Check if MU files exists
            * 
            */
            public function check_mu_files()
                {
                    
                    if( file_exists(WPMU_PLUGIN_DIR . '/woo-gc.php' ))
                        return TRUE;
                        
                    return FALSE;
                    
                }
            
                
                
            /**
            * Attempt to copy the mu files to mu-plugins folder
            * 
            */
            public function copy_mu_files( $force_overwrite    =   FALSE   )
                {
                    
                    //check if mu-plugins folder exists
                    if(! is_dir( WPMU_PLUGIN_DIR ))
                        {
                            if (! wp_mkdir_p( WPMU_PLUGIN_DIR ) )
                                return;
                        }
                    
                    //check if file actually exists already
                    if( !   $force_overwrite    )
                        {
                            if( file_exists(WPMU_PLUGIN_DIR . '/woo-gc.php' ))
                                return;
                        }
                        
                    //attempt to copy the file
                    @copy( WP_PLUGIN_DIR . '/woo-global-cart/mu-files/woo-gc.php', WPMU_PLUGIN_DIR . '/woo-gc.php' );
                    
                }
                
                
            
            /**
            * Remove MU plugin files
            * 
            */
            public function remove_mu_files()
                {
                    
                    //check if file actually exists already
                    if( !file_exists(WPMU_PLUGIN_DIR . '/woo-gc.php' ))
                        return;
                        
                    //attempt to copy the file
                    @unlink ( WPMU_PLUGIN_DIR . '/woo-gc.php' );    
                    
                }
                
            
            /**
            * Create required tables
            * 
            */
            public function create_tables()
                {
                    
                    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                    
                    global $wpdb;
                    
                    $collate = $wpdb->get_charset_collate();
                    
                    $query = "CREATE TABLE `". $wpdb->base_prefix ."woocommerce_woogc_sessions` (
                              `session_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                              `session_key` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                              `woogc_session_key` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                              `session_expiry` bigint(20) UNSIGNED NOT NULL,
                              `trigger_key` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                              `trigger_key_expiry` bigint(20) UNSIGNED NOT NULL,
                              `trigger_user_hash` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                                PRIMARY KEY (`session_id`),
                                UNIQUE KEY `woogc_session_key` (`woogc_session_key`) USING BTREE,
                                KEY `trigger_key` (`trigger_key`)
                            ) " . $collate;
                    dbDelta( $query );
                    
                }
                
                
            /**
            * Remove tables
            * 
            */
            public function remove_tables()
                {
                                        
                    global $wpdb;
                                        
                    $query = "DROP TABLE `". $wpdb->base_prefix ."woocommerce_woogc_sessions`";
                    $wpdb->query( $query );
                    
                }
            
            
            /**
            * Check if filter / action exists for anonymous object
            * 
            * @param mixed $tag
            * @param mixed $class
            * @param mixed $method
            */
            function anonymous_object_filter_exists($tag, $class, $method)
                {
                    if ( !  isset( $GLOBALS['wp_filter'][$tag] ) )
                        return FALSE;
                    
                    $filters = $GLOBALS['wp_filter'][$tag];
                    
                    if ( !  $filters )
                        return FALSE;
                        
                    foreach ( $filters as $priority => $filter ) 
                        {
                            foreach ( $filter as $identifier => $function ) 
                                {
                                    if ( ! is_array( $function ) )
                                        continue;
                                    
                                    if ( ! $function['function'][0] instanceof $class )
                                        continue;
                                    
                                    if ( $method == $function['function'][1] ) 
                                        {
                                            return TRUE;
                                        }
                                }
                        }
                        
                    return FALSE;
                }
                
                
            
            /**
            * Cretae a field collation to unify across database
            * 
            */
            function get_collated_column_name( $field_name, $table_name )
                {
                        
                    global $wpdb, $WooGC;
                    
                    //try a cached
                    if( ! isset($WooGC->cache['database'])   ||  ! isset($WooGC->cache['database']['table_collation']) )
                        {
                            //attempt to get all tables collation
                            $mysql_query    =   "SELECT TABLE_NAME, TABLE_COLLATION FROM INFORMATION_SCHEMA.`TABLES` 
                                                    WHERE TABLE_SCHEMA = '" .  DB_NAME  ."'";
                            $results        =   $wpdb->get_results( $mysql_query );
                            
                            if ( count ( $results ) >   0 )
                                {
                                    $WooGC->cache['database']['table_collation']    =   array();
                                    
                                    foreach ( $results  as  $result )
                                        {
                                            $WooGC->cache['database']['table_collation'][ $result->TABLE_NAME ] =   $result->TABLE_COLLATION;
                                        }
                                    
                                }
                                else
                                    {
                                        //something went wrong
                                        $WooGC->cache['database']['table_collation']    =   FALSE;
                                    }
                        }
                    
                    //try the cache
                    if ( $WooGC->cache['database']['table_collation']   !== FALSE   &&  isset ( $WooGC->cache['database']['table_collation'][$table_name] ))
                        {
                            
                            $table_collation    =   explode( "_", $WooGC->cache['database']['table_collation'][$table_name]);
                            $charset            =   $table_collation[0];
                            
                            $collation          =   explode( "_", $wpdb->collate );
                            $collation[0]       =   $charset;
                            $use_collation      =   implode("_", $collation);
                            
                            return $field_name . " COLLATE " . $use_collation . " AS " . $field_name;
                        }
                        else
                        {
                            //regular approach
                            $db_collation =   $wpdb->collate;
                            
                            if(empty($db_collation))
                                return $field_name;
                                
                            return $field_name . " COLLATE " . $db_collation . " AS " . $field_name; 
                        }                   
                    
                }
                
                
                
            /**
            * Create a Lock functionality using the MySql 
            * 
            * @param mixed $lock_name
            * @param mixed $release_timeout
            * 
            * @return bool False if a lock couldn't be created or if the lock is still valid. True otherwise.
            */
            function create_lock( $lock_name, $release_timeout = null ) 
                {
                    
                    global $wpdb, $blog_id;
                    
                    if ( ! $release_timeout ) {
                        $release_timeout = 10;
                    }
                    $lock_option = $lock_name . '.lock';
                                     
                    // Try to lock.
                    $lock_result = $wpdb->query( $wpdb->prepare( "INSERT INTO `". $wpdb->sitemeta ."` (`site_id`, `meta_key`, `meta_value`) 
                                                                    SELECT %s, %s, %s FROM DUAL
                                                                    WHERE NOT EXISTS (SELECT * FROM `". $wpdb->sitemeta ."` 
                                                                          WHERE `meta_key` = %s AND `meta_value` != '') 
                                                                    LIMIT 1", $blog_id, $lock_option, time(), $lock_option) );
                                        
                    if ( ! $lock_result ) 
                        {
                            $lock_result    =   $this->get_lock( $lock_option );

                            // If a lock couldn't be created, and there isn't a lock, bail.
                            if ( ! $lock_result ) {
                                return false;
                            }

                            // Check to see if the lock is still valid. If it is, bail.
                            if ( $lock_result > ( time() - $release_timeout ) ) {
                                return false;
                            }

                            // There must exist an expired lock, clear it and re-gain it.
                            $this->release_lock( $lock_name );

                            return $this->create_lock( $lock_name, $release_timeout );
                        }

                    // Update the lock, as by this point we've definitely got a lock, just need to fire the actions.
                    $this->update_lock( $lock_option, time() );

                    return true;
                    
                }

            
            /**
            * Retrieve a lock value
            * 
            * @param mixed $lock_name
            * @param mixed $return_full_row
            */
            private function get_lock( $lock_name, $return_full_row =   FALSE )
                {
                    
                    global $wpdb;
                    
                    $mysq_query =   $wpdb->get_row( $wpdb->prepare("SELECT `site_id`, `meta_key`, `meta_value` FROM  `". $wpdb->sitemeta ."`
                                                                    WHERE `meta_key`    =   %s", $lock_name ) );
                    
                    
                    if ( $return_full_row   === TRUE )
                        return $mysq_query;
                        
                    if ( is_object($mysq_query) && isset ( $mysq_query->meta_value ) )
                        return $mysq_query->meta_value;
                        
                    return FALSE;
                    
                }
                
                
            /**
            * Update lock value
            *     
            * @param mixed $lock_name
            * @param mixed $lock_value
            */
            private function update_lock( $lock_name, $lock_value )
                {
                    
                    global $wpdb;
                    
                    $mysq_query =   $wpdb->query( $wpdb->prepare("UPDATE `". $wpdb->sitemeta ."` 
                                                                    SET meta_value = %s
                                                                    WHERE meta_key = %s", $lock_value, $lock_name) );
                    
                    
                    return $mysq_query;
                    
                }
                
            
            /**
            * Releases an upgrader lock.
            *
            * @param string $lock_name The name of this unique lock.
            * @return bool True if the lock was successfully released. False on failure.
            */
            function release_lock( $lock_name ) 
                {
                    
                    global $wpdb;
                    
                    $lock_option = $lock_name . '.lock';
                    
                    $mysq_query =   $wpdb->query( $wpdb->prepare( "DELETE FROM `". $wpdb->sitemeta ."` 
                                                                    WHERE meta_key = %s", $lock_option ) );
                    
                    return $mysq_query;
                    
                }
                               
                
        }


?>