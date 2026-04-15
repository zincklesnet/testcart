        
                
        jQuery( document ).ready(function() {
             
            jQuery( '#cart_checkout_type select' ).on( 'change', function () {
                
                var remove_classes = jQuery('#form_data.options').attr("class").split(' ');
                jQuery.each( remove_classes, function( i, val ) { 
                    if ( val.match(/checkout_type_(\w+)/) )
                        jQuery('#form_data.options').removeClass( val );
                })
                
                if ( this.value  == 'single_checkout')
                    jQuery('#form_data.options').addClass('checkout_type_single_checkout');
                if ( this.value  == 'each_store')
                    jQuery('#form_data.options').addClass('checkout_type_each_store');
                        
            });
            
        });