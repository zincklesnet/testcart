<?php
/**
 * PayPal Reference Transaction API Class
 *
 * Performs reference transaction related transactions requests via the PayPal Express Checkout API,
 * including the creation of a billing agreement and processing renewal payments using that billing
 * agremeent's ID in a reference tranasction.
 *
 * Also hijacks checkout when PayPal Standard is chosen as the payment method, but Reference Transactions
 * are enabled on the store's PayPal account, to go via Express Checkout approval flow instead of the
 * PayPal Standard checkout flow.
 *
 * Heavily inspired by the WC_Paypal_Express_API class developed by the masterful SkyVerge team
 *
 * @package		WooCommerce Subscriptions
 * @subpackage	Gateways/PayPal
 * @category	Class
 * @since		2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooGC_WCS_PayPal_Reference_Transaction_API extends WCS_PayPal_Reference_Transaction_API {

	

	/**
	 * Builds and returns a new API request object
	 *
	 * @see \WCS_SV_API_Base::get_new_request()
	 * @param array $args
	 * @return WC_PayPal_Reference_Transaction_API_Request API request object
	 * @since 2.0
	 */
	protected function get_new_request( $args = array() ) {
		return new WooGC_WCS_PayPal_Reference_Transaction_API_Request( $this->api_username, $this->api_password, $this->api_signature, self::VERSION );
	}

}
