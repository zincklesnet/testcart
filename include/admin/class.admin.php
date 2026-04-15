<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC_Admin 
        {
            var $WooGC;
            
            function __construct()
                {
                    
                    global $WooGC;
                    $this->WooGC    =   $WooGC;
                    
                    add_action( 'init',                                 array( $this, 'on_init'),999 );
                                        
                }
                       
            
            function on_init()
                {
                    
                    //backward compatibility with 3.0 and down
                    global $woocommerce;
                    if( version_compare( $woocommerce->version, '3.0', "<" ) ) 
                        {
                            $this->WooGC->functions->remove_class_action('manage_shop_order_posts_custom_column', 'WC_Admin_Post_Types', 'render_shop_order_columns', 2);
        
                            include_once ( WOOGC_PATH . '/include/admin/class-woogc-admin-post-types.php');
                            $WooGC_Admin_Post_Types =   $this->WooGC->functions->createInstanceWithoutConstructor( 'WooGC_Admin_Post_Types' );
                            add_action      ( 'manage_shop_order_posts_custom_column', array( $WooGC_Admin_Post_Types, 'render_shop_order_columns' ), 2 );        
                        }
                    
                }

                
        }

    new WooGC_Admin();

?>