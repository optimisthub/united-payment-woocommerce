<?php
/**
 * Plugin Name: OptimistHub Payment Gateway with United Payment for WooCommerce
 * Plugin URI: https://unitedpayment.ge
 * Description: United Payment integration for WooCommerce, supporting payments in Georgia.
 * Version: 1.0.0
 * Author: optimisthub
 * Author URI: https://www.optimisthub.com
 * Text Domain: optimisthub-united-payment-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'UNITED_PAYMENT_VERSION', '1.0.0' );
define( 'UNITED_PAYMENT_PLUGIN_FILE', __FILE__ );
define( 'UNITED_PAYMENT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UNITED_PAYMENT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Require Composer autoloader.
if ( file_exists( UNITED_PAYMENT_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once UNITED_PAYMENT_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Check if WooCommerce is active
 *
 * Supports both single-site and multisite (network-activated) installations.
 *
 * @return bool
 */
function united_payment_is_woocommerce_active() {
	/**
	 * Filters the active plugins list.
	 *
	 * @since 1.0.0
	 *
	 * @hook active_plugins
	 */
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
		return true;
	}

	// Check multisite network activation.
	if ( is_multisite() ) {
		$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins' );
		if ( isset( $active_sitewide_plugins['woocommerce/woocommerce.php'] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Initialize the gateway
 */
function united_payment_init() {
	if ( ! united_payment_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'united_payment_woocommerce_missing_notice' );
		return;
	}

	// Add the gateway to WooCommerce.
	add_filter( 'woocommerce_payment_gateways', 'united_payment_add_gateway' );

	// Initialize sandbox payment page handler.
	\UnitedPayment\WooCommerce\Sandbox\PaymentPage::init();

	// Register WooCommerce Blocks support.
	// If Blocks already loaded, call the function directly.
	// Otherwise, register it for when Blocks loads.
	if ( did_action( 'woocommerce_blocks_loaded' ) ) {
		united_payment_register_blocks_support();
	} else {
		add_action( 'woocommerce_blocks_loaded', 'united_payment_register_blocks_support' );
	}
}
add_action( 'plugins_loaded', 'united_payment_init', 11 );

/**
 * Add the gateway to WooCommerce
 *
 * @param array $gateways WooCommerce payment gateways.
 * @return array
 */
function united_payment_add_gateway( $gateways ) {
	$gateways[] = \UnitedPayment\WooCommerce\Gateway::class;
	return $gateways;
}

/**
 * Admin notice for missing WooCommerce
 */
function united_payment_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: WooCommerce plugin link */
					__( '<strong>United Payment Gateway for WooCommerce</strong> requires WooCommerce to be installed and active. You can download %s here.', 'optimisthub-united-payment-for-woocommerce' ),
					'<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>'
				)
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Add plugin action links
 *
 * @param array $links Plugin action links.
 * @return array
 */
function united_payment_plugin_action_links( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=united_payment' ) . '">' . __( 'Settings', 'optimisthub-united-payment-for-woocommerce' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'united_payment_plugin_action_links' );

/**
 * Declare HPOS compatibility
 */
function united_payment_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'united_payment_declare_hpos_compatibility' );

/**
 * Register WooCommerce Blocks integration
 */
function united_payment_register_blocks_support() {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	// Register the payment method with Blocks.
	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
			$payment_method_registry->register( new \UnitedPayment\WooCommerce\BlocksSupport() );
		}
	);
}

/**
 * Declare Cart and Checkout Blocks compatibility
 */
function united_payment_declare_blocks_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'united_payment_declare_blocks_compatibility' );
