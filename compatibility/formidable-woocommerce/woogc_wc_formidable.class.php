<?php
    
    class WooGC_WC_Formidable extends WC_Formidable
        {
            
            /**
             * Initialize the plugin.
             */
            private function __construct() {

                // Load plugin text domain
                add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

                // get required woo functions
                require_once( WP_PLUGIN_DIR . '/formidable-woocommerce/woo-includes/woo-functions.php' );

                // no sense in doing this if WC & FP aren't active
                if ( is_woocommerce_active() && function_exists( 'frm_forms_autoloader' ) ) {

                    add_action('admin_init', array( $this, 'include_updater' ), 1);

                    // load the classes
                    require_once( WP_PLUGIN_DIR . '/formidable-woocommerce/classes/class-wc-formidable-admin.php' );
                    $WC_Formidable_Admin = new WC_Formidable_Admin();

                    require_once( WP_PLUGIN_DIR . '/formidable-woocommerce/classes/class-wc-formidable-product.php' );
                    require_once( WOOGC_PATH . '/compatibility/formidable-woocommerce/classes/class-wc-formidable-product.php' );
                    $WC_Formidable_Product = new WooGC_WC_Formidable_Product();

                    require_once( WP_PLUGIN_DIR . '/formidable-woocommerce/woocommerce-formidable-functions.php' );

                } else {
                    // add admin notice about plugin requiring other plugins
                    add_action( 'admin_notices', array( $this, 'required_plugins_error' ) );
                }
            }
            
            public static function get_instance() {
                // If the single instance hasn't been set, set it now.
                if ( null == self::$instance ) {
                    self::$instance = new self;
                }

                return self::$instance;
            }
                                                                   
        }

    
?>