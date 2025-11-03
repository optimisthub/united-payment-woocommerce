<?php
/**
 * WooCommerce Blocks Integration
 *
 * @package UnitedPayment\WooCommerce
 */

namespace UnitedPayment\WooCommerce;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlocksSupport Class
 *
 * @extends AbstractPaymentMethodType
 */
final class BlocksSupport extends AbstractPaymentMethodType {
	/**
	 * Payment method name defined by the payment method.
	 *
	 * @var string
	 */
	protected $name = 'united_payment';

	/**
	 * Gateway instance.
	 *
	 * @var Gateway
	 */
	private $gateway;

	/**
	 * Initialize the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_united_payment_settings', [] );

		// Ensure WooCommerce and payment gateways are available.
		if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->payment_gateways() ) {
			return;
		}

		$gateways      = WC()->payment_gateways()->payment_gateways();
		$this->gateway = $gateways[ $this->name ] ?? null;
	}

	/**
	 * Returns if this payment method should be active.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return ! empty( $this->gateway ) && $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_asset_path = UNITED_PAYMENT_PLUGIN_DIR . 'assets/js/blocks/united-payment-blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => UNITED_PAYMENT_VERSION,
			];

		$script_url = UNITED_PAYMENT_PLUGIN_URL . 'assets/js/blocks/united-payment-blocks.js';

		wp_register_script(
			'united-payment-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// Set script translations.
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'united-payment-blocks',
				'optimisthub-united-payment-for-woocommerce',
				UNITED_PAYMENT_PLUGIN_DIR . 'languages'
			);
		}

		return [ 'united-payment-blocks' ];
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles_for_admin() {
		return $this->get_payment_method_script_handles();
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$data = [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => $this->get_supported_features(),
		];

		// Add gateway-specific data if gateway is available.
		if ( ! empty( $this->gateway ) ) {
			$data['icon']              = ! empty( $this->gateway->icon ) ? $this->gateway->icon : '';
			$data['order_button_text'] = ! empty( $this->gateway->order_button_text ) ? $this->gateway->order_button_text : '';
		}

		return $data;
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		if ( empty( $this->gateway ) ) {
			return [ 'products' ];
		}

		return $this->gateway->supports;
	}
}
