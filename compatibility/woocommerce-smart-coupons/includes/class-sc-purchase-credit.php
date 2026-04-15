<?php
/**
 * Purchase Credit Features
 *
 * @author 		StoreApps
 * @since 		3.3.0
 * @version 	1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	/**
	 * Class for handling Purchase credit feature
	 */
	class WooGC_WC_SC_Purchase_Credit extends WC_SC_Purchase_Credit {
        
		/**
		 * Constructor
		 */
		public function __construct() 
        {

			if ( is_plugin_active( 'woocommerce-gateway-paypal-express/woocommerce-gateway-paypal-express.php' ) ) {
				add_action( 'woocommerce_ppe_checkout_order_review', array( $this, 'gift_certificate_receiver_detail_form' ) );
			}

			add_action( 'woocommerce_checkout_after_customer_details', array( $this, 'gift_certificate_receiver_detail_form' ) );

		}
           
		/**
		 * Function to display form for entering details of the gift certificate's receiver
		 */
		public function gift_certificate_receiver_detail_form() 
            {
			    global $woocommerce, $total_coupon_amount;

			    $form_started = false;

			    $all_discount_types = wc_get_coupon_types();

			    foreach ( WC()->cart->cart_contents as $product ) 
                    {

                        switch_to_blog( $product['blog_id'] );
                        
				        $coupon_titles = get_post_meta( $product['product_id'], '_coupon_title', true );

				        $_product = wc_get_product( $product['product_id'] );

				        $price = $_product->get_price();

				        if ( $coupon_titles ) {

					        foreach ( $coupon_titles as $coupon_title ) {

						        $coupon = new WC_Coupon( $coupon_title );
						        if ( $this->is_wc_gte_30() ) {
							        $coupon_id     = $coupon->get_id();
							        $discount_type = $coupon->get_discount_type();
							        $coupon_amount = $coupon->get_amount();
						        } else {
							        $coupon_id     = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
							        $discount_type = ( ! empty( $coupon->discount_type ) ) ? $coupon->discount_type : '';
							        $coupon_amount = ( ! empty( $coupon->amount ) ) ? $coupon->amount : 0;
						        }

						        $pick_price_of_prod = get_post_meta( $coupon_id, 'is_pick_price_of_product', true );
						        $smart_coupon_gift_certificate_form_page_text  = get_option( 'smart_coupon_gift_certificate_form_page_text' );
						        $smart_coupon_gift_certificate_form_page_text  = ( ! empty( $smart_coupon_gift_certificate_form_page_text ) ) ? $smart_coupon_gift_certificate_form_page_text : __( 'Coupon Receiver Details', WC_SC_TEXT_DOMAIN );
						        $smart_coupon_gift_certificate_form_details_text  = get_option( 'smart_coupon_gift_certificate_form_details_text' );
						        $smart_coupon_gift_certificate_form_details_text  = ( ! empty( $smart_coupon_gift_certificate_form_details_text ) ) ? $smart_coupon_gift_certificate_form_details_text : '';     // Enter email address and optional message for Gift Card receiver

						        // MADE CHANGES IN THE CONDITION TO SHOW FORM
						        if ( array_key_exists( $discount_type, $all_discount_types ) || ( $pick_price_of_prod == 'yes' && $price == '' ) || ( $pick_price_of_prod == 'yes' &&  $price != '' && $coupon_amount > 0)  ) {

							        if ( ! $form_started ) {

								        $js = "
											        var is_multi_form = function() {
												        var creditCount = jQuery('div#gift-certificate-receiver-form-multi div.form_table').length;

												        if ( creditCount <= 1 ) {
													        return false;
												        } else {
													        return true;
												        }
											        };

											        jQuery('input#show_form').on('click', function(){
												        if ( is_multi_form() ) {
													        jQuery('ul.single_multi_list').slideDown();
												        }
												        jQuery('div.gift-certificate-receiver-detail-form').slideDown();
											        });
											        jQuery('input#hide_form').on('click', function(){
												        if ( is_multi_form() ) {
													        jQuery('ul.single_multi_list').slideUp();
												        }
												        jQuery('div.gift-certificate-receiver-detail-form').slideUp();
											        });
											        jQuery('input[name=sc_send_to]').on('change', function(){
												        jQuery('div#gift-certificate-receiver-form-single').slideToggle(1);
												        jQuery('div#gift-certificate-receiver-form-multi').slideToggle(1);
											        });
										        ";

								        wc_enqueue_js( $js );

								        ?>

								        <div class="gift-certificate sc_info_box">
									        <h3><?php _e( stripslashes( $smart_coupon_gift_certificate_form_page_text ) ); ?></h3>
										        <?php if ( ! empty( $smart_coupon_gift_certificate_form_details_text ) ) { ?>
										        <p><?php _e( stripslashes( $smart_coupon_gift_certificate_form_details_text ) , WC_SC_TEXT_DOMAIN ); ?></p>
										        <?php } ?>
										        <div class="gift-certificate-show-form">
											        <p><?php _e( 'Your order contains coupons. What would you like to do?', WC_SC_TEXT_DOMAIN ); ?></p>
											        <ul class="show_hide_list" style="list-style-type: none;">
												        <li><input type="radio" id="hide_form" name="is_gift" value="no" checked="checked" /> <label for="hide_form"><?php _e( 'Send coupons to me', WC_SC_TEXT_DOMAIN ); ?></label></li>
												        <li>
												        <input type="radio" id="show_form" name="is_gift" value="yes" /> <label for="show_form"><?php _e( 'Gift coupons to someone else', WC_SC_TEXT_DOMAIN ); ?></label>
												        <ul class="single_multi_list" style="list-style-type: none;">
												        <li><input type="radio" id="send_to_one" name="sc_send_to" value="one" checked="checked" /> <label for="send_to_one"><?php _e( 'Send to one person', WC_SC_TEXT_DOMAIN ); ?></label>
												        <input type="radio" id="send_to_many" name="sc_send_to" value="many" /> <label for="send_to_many"><?php _e( 'Send to different people', WC_SC_TEXT_DOMAIN ); ?></label></li>
												        </ul>
												        </li>
											        </ul>
										        </div>
								        <div class="gift-certificate-receiver-detail-form">
								        <div class="clear"></div>
								        <div id="gift-certificate-receiver-form-multi">
								        <?php

								        $form_started = true;

							        }

							        WC_SC_Purchase_Credit::add_text_field_for_email( $coupon, $product );

						        }
					        }
				        }
                        
                        restore_current_blog();
			        }

			    if ( $form_started ) 
                    {
				        ?>
				        </div>
				        <div id="gift-certificate-receiver-form-single">
					        <div class="form_table">
						        <div class="email_amount">
							        <div class="amount"></div>
							        <div class="email"><input class="gift_receiver_email" type="text" placeholder="<?php _e( 'Email address', WC_SC_TEXT_DOMAIN ); ?>..." name="gift_receiver_email[0][0]" value="" /></div>
						        </div>
						        <div class="message_row">
							        <div class="message"><textarea placeholder="<?php _e( 'Message', WC_SC_TEXT_DOMAIN ); ?>..." class="gift_receiver_message" name="gift_receiver_message[0][0]" cols="50" rows="5"></textarea></div>
						        </div>
					        </div>
				        </div>
				        </div></div>
				        <?php
			        }

		    }
       

	}
