<?php
/**
 * Gateway
 *
 * @package UnitedPayment\WooCommerce
 */

namespace UnitedPayment\WooCommerce;

use WC_Payment_Gateway;
use WC_Order;
use WC_Admin_Settings;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gateway Class
 *
 * @extends WC_Payment_Gateway
 */
class Gateway extends WC_Payment_Gateway {
	/**
	 * Dealer Code
	 *
	 * @var string
	 */
	protected $dealer_code;

	/**
	 * API Username
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * API Password
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Test Mode
	 *
	 * @var bool
	 */
	protected $test_mode;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'united_payment';
		$this->method_title       = __( 'United Payment', 'optimisthub-united-payment-for-woocommerce' );
		$this->method_description = __( 'Accept payments through United Payment redirect gateway.', 'optimisthub-united-payment-for-woocommerce' );
		$this->has_fields         = false;
		$this->supports           = [ 'products' ];
		$this->order_button_text  = __( 'Pay with United Payment', 'optimisthub-united-payment-for-woocommerce' );

		// Load settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );
		$this->dealer_code = $this->get_option( 'dealer_code' );
		$this->username    = $this->get_option( 'username' );
		$this->password    = $this->get_option( 'password' );
		$this->test_mode   = 'yes' === $this->get_option( 'test_mode', 'no' );

		// Set checkout icon - uses checkout-logo.png instead of the default icon
		$this->icon = UNITED_PAYMENT_PLUGIN_URL . 'assets/images/checkout-logo.png';

		// Hooks.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_api_united_payment', [ $this, 'handle_callback' ] );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled'     => [
				'title'   => __( 'Enable/Disable', 'optimisthub-united-payment-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable United Payment', 'optimisthub-united-payment-for-woocommerce' ),
				'default' => 'no',
			],
			'title'       => [
				'title'       => __( 'Title', 'optimisthub-united-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Payment method title shown to customers during checkout.', 'optimisthub-united-payment-for-woocommerce' ),
				'default'     => __( 'United Payment', 'optimisthub-united-payment-for-woocommerce' ),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __( 'Description', 'optimisthub-united-payment-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description shown to customers during checkout.', 'optimisthub-united-payment-for-woocommerce' ),
				'default'     => __( 'Pay securely with United Payment.', 'optimisthub-united-payment-for-woocommerce' ),
				'desc_tip'    => true,
			],
			'dealer_code' => [
				'title'       => __( 'Dealer Code', 'optimisthub-united-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Your United Payment dealer code.', 'optimisthub-united-payment-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			],
			'username'    => [
				'title'       => __( 'API Username', 'optimisthub-united-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Your United Payment API username.', 'optimisthub-united-payment-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			],
			'password'    => [
				'title'       => __( 'API Password', 'optimisthub-united-payment-for-woocommerce' ),
				'type'        => 'password',
				'description' => __( 'Your United Payment API password.', 'optimisthub-united-payment-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			],
			'test_mode'   => [
				'title'       => __( 'Test Mode', 'optimisthub-united-payment-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Test Mode', 'optimisthub-united-payment-for-woocommerce' ),
				'description' => __( 'Place the payment gateway in test mode using test API credentials.', 'optimisthub-united-payment-for-woocommerce' ),
				'default'     => 'yes',
				'desc_tip'    => true,
			],
		];
	}

	/**
	 * Validate settings before saving
	 *
	 * @return bool
	 */
	public function process_admin_options() {
		$saved = parent::process_admin_options();

		// Validate credentials if gateway is enabled.
		if ( 'yes' === $this->get_option( 'enabled' ) ) {
			$dealer_code = $this->get_option( 'dealer_code' );
			$username    = $this->get_option( 'username' );
			$password    = $this->get_option( 'password' );

			if ( empty( $dealer_code ) || empty( $username ) || empty( $password ) ) {
				WC_Admin_Settings::add_error(
					__( 'United Payment requires Dealer Code, Username and Password to be configured.', 'optimisthub-united-payment-for-woocommerce' )
				);
			}
		}

		return $saved;
	}

	/**
	 * Check if gateway is available
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = true;

		// Check if enabled.
		if ( 'yes' !== $this->enabled ) {
			$is_available = false;
		}

		// Check if credentials are configured.
		if ( empty( $this->dealer_code ) || empty( $this->username ) || empty( $this->password ) ) {
			$is_available = false;
		}

		// Check parent availability (currency, country restrictions etc).
		if ( ! parent::is_available() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Payment fields - shown on checkout page
	 * We don't need fields for redirect gateway, but we can show a description
	 */
	public function payment_fields() {
		// Get description - if empty, use translated default.
		$description = $this->description;

		if ( empty( $description ) || 'Pay securely with United Payment.' === $description || 'Pay securely with United Payment Georgia.' === $description ) {
			// Use translated default if description is empty or matches the default English text.
			$description = __( 'Pay securely with United Payment.', 'optimisthub-united-payment-for-woocommerce' );
		}

		if ( $description ) {
			echo wp_kses_post( wpautop( $description ) );
		}
	}

	/**
	 * Admin Panel Options
	 */
	public function admin_options() {
		?>
		<h3><?php esc_html_e( 'United Payment Settings', 'optimisthub-united-payment-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'Configure your United Payment credentials to accept payments.', 'optimisthub-united-payment-for-woocommerce' ); ?></p>

		<?php if ( $this->test_mode ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'Test Mode Active', 'optimisthub-united-payment-for-woocommerce' ); ?></strong>
					<?php esc_html_e( 'The payment gateway is currently in test mode. Transactions will not be processed as real payments.', 'optimisthub-united-payment-for-woocommerce' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
	}

	/**
	 * Process Payment
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wc_add_notice( __( 'Invalid order.', 'optimisthub-united-payment-for-woocommerce' ), 'error' );
			return [
				'result'   => 'failure',
				'redirect' => '',
			];
		}

		try {
			// Initialize API client.
			$api = new API(
				$this->dealer_code,
				$this->username,
				$this->password,
				$this->test_mode
			);

			// Set order to pending payment.
			$order->update_status(
				'pending',
				__( 'Awaiting United Payment payment.', 'optimisthub-united-payment-for-woocommerce' )
			);

			// Create payment and get redirect URL.
			$payment_response = $api->create_payment( $order );

			// Check if we got a valid payment URL.
			if ( empty( $payment_response['Url'] ) ) {
				throw new Exception( __( 'Payment URL not received from gateway.', 'optimisthub-united-payment-for-woocommerce' ) );
			}

			$redirect_url = $payment_response['Url'];

			// Add order note.
			$order->add_order_note(
				sprintf(
					/* translators: %s: CodeForHash value */
					__( 'United Payment payment initiated. CodeForHash: %s', 'optimisthub-united-payment-for-woocommerce' ),
					isset( $payment_response['CodeForHash'] ) ? sanitize_text_field( wp_unslash( $payment_response['CodeForHash'] ) ) : 'N/A'
				)
			);

			// Return success with redirect URL.
			return [
				'result'   => 'success',
				'redirect' => esc_url_raw( $redirect_url ),
			];

		} catch ( Exception $e ) {
			// Log error.
			$logger = new Logger();
			$logger->error(
				'Payment processing failed',
				[
					'order_id' => $order_id,
					'error'    => $e->getMessage(),
				]
			);

			// Add user-facing error notice.
			wc_add_notice(
				sprintf(
					/* translators: %s: Error message */
					__( 'Payment error: %s', 'optimisthub-united-payment-for-woocommerce' ),
					esc_html( $e->getMessage() )
				),
				'error'
			);

			// Add order note about failure.
			$order->add_order_note(
				sprintf(
					/* translators: %s: Error message */
					__( 'United Payment payment failed: %s', 'optimisthub-united-payment-for-woocommerce' ),
					esc_html( $e->getMessage() )
				)
			);

			return [
				'result'   => 'failure',
				'redirect' => '',
			];
		}
	}

	/**
	 * Handle payment callback
	 *
	 * This is an external callback from the payment gateway, not a user-submitted form.
	 * Nonce verification is not applicable here. Security is ensured through:
	 * 1. Order key validation
	 * 2. Hash signature verification (SHA256)
	 * 3. Payment gateway authentication
	 */
	public function handle_callback() {
		// Merge & sanitize callback parameters.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		$callback_data = array_merge(
			wc_clean( wp_unslash( $_GET ) ),
			wc_clean( wp_unslash( $_POST ) )
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

		// Initialize logger.
		$logger = new Logger();

		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

		$logger->info(
			'Callback received',
			[
				'callback_data'  => $callback_data,
				'request_method' => $request_method,
			]
		);

		// Validate required parameters.
		if ( empty( $callback_data['order_id'] ) || empty( $callback_data['order_key'] ) ) {
			$logger->error( 'Callback missing required parameters', [ 'callback_data' => $callback_data ] );
			wp_die( esc_html__( 'Invalid callback request.', 'optimisthub-united-payment-for-woocommerce' ), esc_html__( 'Payment Error', 'optimisthub-united-payment-for-woocommerce' ), [ 'response' => 400 ] );
		}

		// Get order.
		$order_id = absint( $callback_data['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			$logger->error( 'Order not found', [ 'order_id' => $order_id ] );
			wp_die( esc_html__( 'Order not found.', 'optimisthub-united-payment-for-woocommerce' ), esc_html__( 'Payment Error', 'optimisthub-united-payment-for-woocommerce' ), [ 'response' => 404 ] );
		}

		// Validate order key.
		$order_key = sanitize_text_field( wp_unslash( $callback_data['order_key'] ) );
		if ( $order->get_order_key() !== $order_key ) {
			$logger->error(
				'Invalid order key',
				[
					'order_id'     => $order_id,
					'provided_key' => $order_key,
					'expected_key' => $order->get_order_key(),
				]
			);
			wp_die( esc_html__( 'Invalid order key.', 'optimisthub-united-payment-for-woocommerce' ), esc_html__( 'Payment Error', 'optimisthub-united-payment-for-woocommerce' ), [ 'response' => 403 ] );
		}

		// Check if order is already processed.
		if ( $order->has_status( [ 'processing', 'completed' ] ) ) {
			$logger->info( 'Order already processed', [ 'order_id' => $order_id ] );
			wp_safe_redirect( $this->get_return_url( $order ) );
			exit;
		}

		// Initialize API client for hash validation.
		$api = new API(
			$this->dealer_code,
			$this->username,
			$this->password,
			$this->test_mode
		);

		// Validate callback hash.
		if ( ! $api->validate_callback_hash( $callback_data, $order ) ) {
			$logger->error( 'Hash validation failed', [ 'order_id' => $order_id ] );
			$order->add_order_note( __( 'United Payment: Hash validation failed.', 'optimisthub-united-payment-for-woocommerce' ) );
			wc_add_notice( __( 'Payment verification failed.', 'optimisthub-united-payment-for-woocommerce' ), 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}

		// Get payment status from callback.
		$payment_status = $api->get_payment_status( $callback_data, $order );

		$logger->info(
			'Payment status determined',
			[
				'order_id' => $order_id,
				'status'   => $payment_status,
			]
		);

		// Save callback data to order meta.
		$order->update_meta_data( '_united_payment_callback_data', wp_json_encode( $callback_data ) );
		$order->update_meta_data( '_united_payment_callback_received_at', time() );
		$order->update_meta_data( '_united_payment_payment_status', $payment_status );
		$order->save();

		// Process based on payment status.
		if ( 'success' === $payment_status ) {
			$this->process_successful_payment( $order, $callback_data, $logger );
		} else {
			$this->process_failed_payment( $order, $callback_data, $logger );
		}
	}

	/**
	 * Process successful payment
	 *
	 * @param WC_Order $order         Order object.
	 * @param array    $callback_data Callback data.
	 * @param Logger   $logger        Logger instance.
	 */
	protected function process_successful_payment( $order, $callback_data, $logger ) {
		// Get trxCode (transaction code from gateway).
		$trx_code = ! empty( $callback_data['trxCode'] ) ? sanitize_text_field( wp_unslash( $callback_data['trxCode'] ) ) : '';

		// Mark payment complete.
		$order->payment_complete( $trx_code );

		// Add order note.
		$order->add_order_note(
			sprintf(
				/* translators: %s: Transaction code */
				__( 'United Payment payment successful. Transaction Code: %s', 'optimisthub-united-payment-for-woocommerce' ),
				esc_html( $trx_code )
			)
		);

		$logger->info(
			'Payment successful',
			[
				'order_id' => $order->get_id(),
				'trx_code' => $trx_code,
			]
		);

		// Empty cart.
		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->empty_cart();
		}

		// Redirect to thank you page.
		wp_safe_redirect( $this->get_return_url( $order ) );
		exit;
	}

	/**
	 * Process failed payment
	 *
	 * @param WC_Order $order         Order object.
	 * @param array    $callback_data Callback data.
	 * @param Logger   $logger        Logger instance.
	 */
	protected function process_failed_payment( $order, $callback_data, $logger ) {
		$result_code     = ! empty( $callback_data['resultCode'] ) ? sanitize_text_field( wp_unslash( $callback_data['resultCode'] ) ) : '';
		$result_message  = ! empty( $callback_data['resultMessage'] ) ? sanitize_text_field( wp_unslash( $callback_data['resultMessage'] ) ) : '';
		$default_message = __( 'Payment declined by bank.', 'optimisthub-united-payment-for-woocommerce' );

		// Get user-friendly error message.
		$user_error_message = ErrorMessages::get_message( $result_code, $result_message );

		// If we still don't have a specific message, use the default.
		if ( empty( $user_error_message ) ) {
			$user_error_message = $default_message;
		}

		// Update order status to failed.
		$order->update_status(
			'failed',
			sprintf(
				/* translators: 1: ResultCode, 2: Error message */
				__( 'United Payment payment failed [%1$s]: %2$s', 'optimisthub-united-payment-for-woocommerce' ),
				esc_html( $result_code ),
				esc_html( $result_message )
			)
		);

		$logger->error(
			'Payment failed',
			[
				'order_id'           => $order->get_id(),
				'result_code'        => $result_code,
				'result_message'     => $result_message,
				'user_error_message' => $user_error_message,
			]
		);

		// Add notice for customer.
		wc_add_notice(
			sprintf(
				/* translators: %s: Error message */
				__( 'Payment failed: %s', 'optimisthub-united-payment-for-woocommerce' ),
				esc_html( $user_error_message )
			),
			'error'
		);

		// Redirect to checkout.
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}
}
