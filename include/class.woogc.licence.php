<?php   
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * V2.1       
    */       
    class WooGC_licence
        {
           
            
            /**
            * Retrieve licence details
            * 
            */
            public function get_licence_data()
                {
                    $licence_data = get_site_option('woogc_licence');
                    
                    $default =   array(
                                            'key'               =>  '',
                                            'last_check'        =>  '',
                                            'licence_status'    =>  '',
                                            'licence_expire'    =>  ''
                                            );    
                    $licence_data           =   wp_parse_args( $licence_data, $default );
                    
                    return $licence_data;
                }
            
                
            /**
            * Reset license data
            *     
            * @param mixed $licence_data
            */
            public function reset_licence_data( $licence_data )
                {
                    if  ( ! is_array( $licence_data ) ) 
                        $licence_data   =   array();
                        
                    $licence_data['key']                =   '';
                    $licence_data['last_check']         =   time();
                    $licence_data['licence_status']     =   '';
                    $licence_data['licence_expire']     =   '';
                    
                    return $licence_data;
                }
            
            /**
            * Set licence data
            *     
            * @param mixed $licence_data
            */
            public function update_licence_data( $licence_data )
                {
                    update_site_option('woogc_licence', $licence_data);   
                }
                
                                
            public function licence_key_verify()
                {

                    $licence_data = $this->get_licence_data();
                             
                    if ( ! isset ( $licence_data['key'] ) || $licence_data['key'] == '' )
                        return FALSE;
                        
                    return TRUE;
                }
                
                
            function licence_deactivation_check()
                {

                    if( ! $this->licence_key_verify() )
                        return;

                    if ( !  $this->create_lock( 'WOOGC__API_status-check', 50 ) )
                        return;
                        
                    if ( empty ( get_site_option( 'woogc_last_checked' ) ) )
                        return;
                    
                    $licence_data = $this->get_licence_data();
                    $licence_key = $licence_data['key'];
                    
                    if ( empty ( $licence_key ) )
                        {
                            $licence_data['last_check']   = time();
                            $this->update_licence_data( $licence_data );
                            $this->release_lock( 'WOOGC__API_status-check' );
                            return;
                        }
                    
                    $args = array(
                                                'woo_sl_action'         => 'status-check',
                                                'licence_key'           => $licence_key,
                                                'product_unique_id'     => WOOGC_PRODUCT_ID,
                                                'domain'                => WOOGC_INSTANCE,
                                                'code_version'          => WOOGC_VERSION,
                                                '_get_product_meta'     => '_sl_new_version'
                                            );
                    $request_uri    = WOOGC_UPDATE_API_URL . '?' . http_build_query( $args , '', '&');
                    $data           = wp_remote_get( $request_uri );
                    
                    if(is_wp_error( $data ) || $data['response']['code'] != 200)
                        {
                            $licence_data['last_check']   = time();    
                            $this->update_licence_data( $licence_data );
                            $this->release_lock( 'WOOGC__API_status-check' );
                            return;
                        }   
                    
                    $response_block = json_decode($data['body']);
                    
                    if(!is_array($response_block) || count($response_block) < 1)
                        {
                            $licence_data['last_check']   = time();    
                            $this->update_licence_data( $licence_data );
                            $this->release_lock( 'WOOGC__API_status-check' );
                            return;
                        }    
                        
                    $response_block = $response_block[count($response_block) - 1];
                    if (is_object($response_block))
                        {
                            if ( in_array ( $response_block->status_code, array ( 'e312', 's203', 'e204', 'e002', 'e003' ) ) )
                                {
                                    $licence_data   =   $this->reset_licence_data( $licence_data );
                                }
                                else
                                {
                                    $licence_data['licence_status']         = isset( $response_block->licence_status ) ?    $response_block->licence_status :   ''  ;
                                    $licence_data['licence_expire']         = isset( $response_block->licence_expire ) ?    $response_block->licence_expire :   ''  ;   
                                    $licence_data['_sl_new_version']        = isset( $response_block->_sl_new_version ) ?   $response_block->_sl_new_version :   ''  ;   
                                }
                                
                            if($response_block->status == 'error')
                                {
                                    $licence_data   =   $this->reset_licence_data( $licence_data );
                                } 
                        }
                    
                    $licence_data['last_check']   = time();    
                    $this->update_licence_data( $licence_data );
                    $this->release_lock( 'WOOGC__API_status-check' );
                    
                }
                
            
            
        }
            

        
    
?>