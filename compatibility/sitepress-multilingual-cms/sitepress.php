<?php

    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:     WPML Multilingual CMS
    * Since:         4.2.7.1
    */
    
    
    class WooGC_wpml
        {
           
            function __construct() 
                {
                    
                    $this->init();
                                  
                }
                
                
            function init()
                {
                      
                    add_filter( 'woogc/on_shutdown/ob_buferring_output',   array( $this, '_on_shutdown_ob_buferring_output') );
                    
                      
                                      
                }
                
                
            function _on_shutdown_ob_buferring_output( $continue )
                {
                    return FALSE;   
                }
            
            

     
     
        }

    new WooGC_wpml();



?>