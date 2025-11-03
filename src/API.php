<?php
/**
 * United Payment Georgia API Client Wrapper
 *
 * @package UnitedPayment\WooCommerce
 */

namespace UnitedPayment\WooCommerce;

use MokaUnitedGE\MokaUnitedClient;
use UnitedPayment\WooCommerce\Sandbox\Client as SandboxClient;
use Exception;
use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Class
 */
class API {

	/**
	 * United Payment Georgia SDK client instance
	 *
	 * @var MokaUnitedClient|SandboxClient
	 */
	protected $client;

	/**
	 * Logger Instance
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Dealer Code
	 *
	 * @var string
	 */
	protected $dealer_code;

	/**
	 * Test Mode
	 *
	 * @var bool
	 */
	protected $test_mode;

	/**
	 * Constructor
	 *
	 * @param string $dealer_code Dealer Code.
	 * @param string $username API Username.
	 * @param string $password API Password.
	 * @param bool   $test_mode Test Mode flag (uses sandbox client when enabled).
	 */
	public function __construct( $dealer_code, $username, $password, $test_mode = false ) {
		$this->dealer_code = $dealer_code;
		$this->test_mode   = $test_mode;
		$this->logger      = new Logger();

		try {
			// Use sandbox client if test mode is enabled.
			if ( $test_mode ) {
				$this->client = new SandboxClient(
					[
						'dealerCode' => $dealer_code,
						'username'   => $username,
						'password'   => $password,
					]
				);

				$this->logger->info(
					'Sandbox API Client initialized (Test Mode)',
					[
						'dealer_code' => $dealer_code,
						'test_mode'   => $test_mode,
					]
				);
			}

			if ( false === $test_mode ) {
				$this->client = new MokaUnitedClient(
					[
						'dealerCode' => $dealer_code,
						'username'   => $username,
						'password'   => $password,
					]
				);

				$this->logger->info(
					'API Client initialized',
					[
						'dealer_code' => $dealer_code,
						'test_mode'   => $test_mode,
					]
				);
			}
		} catch ( Exception $e ) {
			$this->logger->error(
				'Failed to initialize API Client',
				[
					'error' => $e->getMessage(),
				]
			);
			throw $e;
		}
	}

	/**
	 * Create Payment
	 *
	 * @param WC_Order $order WooCommerce Order.
	 * @return array Payment response with redirect URL.
	 * @throws Exception When payment creation fails.
	 */
	public function create_payment( $order ) {
		$order_id = $order->get_id();

		$this->logger->info(
			'Creating payment',
			[
				'order_id' => $order_id,
				'amount'   => $order->get_total(),
				'currency' => $order->get_currency(),
			]
		);

		try {
			// Prepare payment data.
			$payment_data = $this->prepare_payment_data( $order );

			$this->logger->debug(
				'Payment data prepared',
				[
					'order_id'     => $order_id,
					'payment_data' => $payment_data,
				]
			);

			// Make API request.
			$response = $this->client->payments()->create( $payment_data );

			// Check if response is successful.
			if ( ! $response->isSuccessful() ) {
				$result_code    = $response->getResultCode();
				$result_message = $response->getResultMessage();

				// Get user-friendly error message.
				$user_message = ErrorMessages::get_message( $result_code, $result_message );

				$this->logger->error(
					'Payment creation failed',
					[
						'order_id'       => $order_id,
						'result_code'    => $result_code,
						'result_message' => $result_message,
						'user_message'   => $user_message,
					]
				);

				throw new Exception( $user_message );
			}

			$response_data = $response->getData();

			$this->logger->info(
				'Payment created successfully',
				[
					'order_id' => $order_id,
					'response' => $response_data,
				]
			);

			// Save transaction data to order meta.
			$this->save_transaction_data( $order, $response );

			return $response_data;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Payment creation failed',
				[
					'order_id' => $order_id,
					'error'    => $e->getMessage(),
					'trace'    => $e->getTraceAsString(),
				]
			);
			throw $e;
		}
	}

	/**
	 * Prepare payment data from order
	 *
	 * @param WC_Order $order WooCommerce Order.
	 * @return array Payment data array.
	 */
	protected function prepare_payment_data( $order ) {
		$order_id = $order->get_id();

		// Generate unique transaction reference.
		$transaction_ref = $this->generate_transaction_reference( $order );

		// Prepare callback URL.
		$callback_url = $this->get_callback_url( $order );

		// Prepare payment data.
		$client_ip     = \WC_Geolocation::get_ip_address();
		$buyer_address = trim( $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() );
		$customer_code = $order->get_customer_id() ? (string) $order->get_customer_id() : 'guest-' . $order_id;

		$payment_data = [
			'Amount'              => (float) $order->get_total(),
			'Currency'            => $order->get_currency(),
			'BankCode'            => 1,
			'InstallmentNumber'   => 1,
			'ClientIP'            => $client_ip,
			'OtherTrxCode'        => $transaction_ref,
			'SubMerchantName'     => '',
			'IsPoolPayment'       => 0,
			'IsPreAuth'           => 0,
			'IsTokenized'         => 0,
			'IntegratorId'        => 0,
			'Software'            => 'WooCommerce',
			'Description'         => '',
			'ReturnHash'          => 1,
			'RedirectUrl'         => $callback_url,
			'RedirectType'        => 0,
			'BuyerInformation'    => [
				'BuyerFullName' => $order->get_formatted_billing_full_name(),
				'BuyerEmail'    => $order->get_billing_email(),
				'BuyerAddress'  => $buyer_address,
			],
			'CustomerInformation' => [
				'DealerCustomerId' => '',
				'CustomerCode'     => $customer_code,
				'FirstName'        => $order->get_billing_first_name(),
				'LastName'         => $order->get_billing_last_name(),
				'Email'            => $order->get_billing_email(),
			],
		];

		/**
		 * Filter payment data before sending to API
		 *
		 * @since 1.0.0
		 *
		 * @param array    $payment_data Payment data array.
		 * @param WC_Order $order WooCommerce Order.
		 *
		 * @hook united_payment_payment_data
		 */
		return apply_filters( 'united_payment_payment_data', $payment_data, $order );
	}

	/**
	 * Generate unique transaction reference
	 *
	 * @param WC_Order $order WooCommerce Order.
	 * @return string Transaction reference.
	 */
	protected function generate_transaction_reference( $order ) {
		$order_id = $order->get_id();
		$date     = gmdate( 'Ymd' );
		$time     = gmdate( 'His' );

		return sprintf( 'ORDER-%s-%s-%d', $date, $time, $order_id );
	}

	/**
	 * Get callback URL for payment
	 *
	 * @param WC_Order $order WooCommerce Order.
	 * @return string Callback URL.
	 */
	protected function get_callback_url( $order ) {
		$order_id = $order->get_id();

		// Generate order key for security.
		$order_key = $order->get_order_key();

		// Build callback URL with order ID and key.
		$callback_url = add_query_arg(
			[
				'order_id'  => $order_id,
				'order_key' => $order_key,
			],
			WC()->api_request_url( 'united_payment' )
		);

		return $callback_url;
	}

	/**
	 * Save transaction data to order meta
	 *
	 * @param WC_Order $order WooCommerce Order.
	 * @param object   $response API response object.
	 */
	protected function save_transaction_data( $order, $response ) {
		$response_data = $response->getData();

		// Save CodeForHash for callback validation.
		if ( ! empty( $response_data['CodeForHash'] ) ) {
			$order->update_meta_data( '_united_payment_code_for_hash', sanitize_text_field( $response_data['CodeForHash'] ) );
		}

		// Save payment URL.
		if ( ! empty( $response_data['Url'] ) ) {
			$order->update_meta_data( '_united_payment_payment_url', esc_url_raw( $response_data['Url'] ) );
		}

		// Save full response for debugging.
		$order->update_meta_data( '_united_payment_payment_response', wp_json_encode( $response->getBody() ) );

		// Save timestamp.
		$order->update_meta_data( '_united_payment_payment_created_at', time() );

		$order->save();

		$this->logger->debug(
			'Transaction data saved to order meta',
			[
				'order_id'      => $order->get_id(),
				'code_for_hash' => $response_data['CodeForHash'] ?? 'N/A',
			]
		);
	}

	/**
	 * Validate callback hash
	 *
	 * @param array    $callback_data Callback data from request.
	 * @param WC_Order $order WooCommerce Order.
	 * @return bool True if hash is valid.
	 */
	public function validate_callback_hash( $callback_data, $order ) {
		$this->logger->info(
			'Validating callback hash',
			[
				'callback_data' => $callback_data,
			]
		);

		// In test mode, always validate successfully if sandbox_payment flag is present.
		if ( $this->test_mode && ! empty( $callback_data['sandbox_payment'] ) ) {
			$this->logger->info( 'Test mode: Hash validation bypassed for sandbox payment' );
			return true;
		}

		/**
		 * Allow developers to implement custom hash validation.
		 *
		 * @since 1.0.0
		 *
		 * @param null|bool $custom_validation Custom validation result (null to use default).
		 * @param array     $callback_data Callback data from request.
		 * @param string    $dealer_code Dealer code.
		 * @param WC_Order  $order WooCommerce Order.
		 *
		 * @hook united_payment_validate_hash
		 */
		$custom_validation = apply_filters( 'united_payment_validate_hash', null, $callback_data, $this->dealer_code, $order );
		if ( null !== $custom_validation ) {
			return (bool) $custom_validation;
		}

		// Check if hash is provided.
		if ( empty( $callback_data['hashValue'] ) ) {
			$this->logger->warning( 'No hashValue provided in callback' );
			return false;
		}

		$provided_hash = sanitize_text_field( $callback_data['hashValue'] );

		// Get CodeForHash from order meta (saved during payment creation).
		$code_for_hash = $order->get_meta( '_united_payment_code_for_hash' );

		if ( empty( $code_for_hash ) ) {
			$this->logger->error( 'CodeForHash not found in order meta' );
			return false;
		}

		// Calculate hash for success scenario: SHA256(CodeForHash + "T").
		$hash_success = hash( 'sha256', strtoupper( $code_for_hash ) . 'T' );

		// Calculate hash for failure scenario: SHA256(CodeForHash + "F").
		$hash_failure = hash( 'sha256', strtoupper( $code_for_hash ) . 'F' );

		$this->logger->debug(
			'Hash calculation',
			[
				'code_for_hash' => $code_for_hash,
				'hash_success'  => $hash_success,
				'hash_failure'  => $hash_failure,
				'provided_hash' => $provided_hash,
			]
		);

		// Compare hashes (timing-safe comparison).
		$is_valid = hash_equals( $hash_success, $provided_hash ) || hash_equals( $hash_failure, $provided_hash );

		if ( $is_valid ) {
			$this->logger->info( 'Hash validation successful' );
		} else {
			$this->logger->error(
				'Hash validation failed',
				[
					'provided_hash' => $provided_hash,
					'hash_success'  => $hash_success,
					'hash_failure'  => $hash_failure,
				]
			);
		}

		return $is_valid;
	}

	/**
	 * Get payment status from callback
	 *
	 * @param array    $callback_data Callback data from request.
	 * @param WC_Order $order WooCommerce Order.
	 * @return string Payment status (success, failed).
	 */
	public function get_payment_status( $callback_data, $order ) {
		$this->logger->info(
			'Determining payment status',
			[
				'callback_data' => $callback_data,
			]
		);

		// Get CodeForHash from order meta.
		$code_for_hash = $order->get_meta( '_united_payment_code_for_hash' );

		if ( empty( $code_for_hash ) ) {
			$this->logger->error( 'CodeForHash not found in order meta' );
			return 'failed';
		}

		// Get provided hash.
		$provided_hash = ! empty( $callback_data['hashValue'] ) ? sanitize_text_field( $callback_data['hashValue'] ) : '';

		if ( empty( $provided_hash ) ) {
			$this->logger->error( 'No hashValue in callback' );
			return 'failed';
		}

		// Calculate success hash: SHA256(CodeForHash + "T").
		$hash_success = hash( 'sha256', strtoupper( $code_for_hash ) . 'T' );

		// Calculate failure hash: SHA256(CodeForHash + "F").
		$hash_failure = hash( 'sha256', strtoupper( $code_for_hash ) . 'F' );

		$this->logger->debug(
			'Payment status hash comparison',
			[
				'code_for_hash' => $code_for_hash,
				'hash_success'  => $hash_success,
				'hash_failure'  => $hash_failure,
				'provided_hash' => $provided_hash,
			]
		);

		// If hash matches success hash, payment is successful.
		if ( hash_equals( $hash_success, $provided_hash ) ) {
			$this->logger->info( 'Payment status: success (hash matched success pattern)' );

			// Save trxCode (transaction code from gateway) if available.
			if ( ! empty( $callback_data['trxCode'] ) ) {
				$order->update_meta_data( '_united_payment_trx_code', sanitize_text_field( $callback_data['trxCode'] ) );
				$order->save();
			}

			return 'success';
		}

		// If hash matches failure hash, payment explicitly failed.
		if ( hash_equals( $hash_failure, $provided_hash ) ) {
			$this->logger->info( 'Payment status: failed (hash matched failure pattern)' );
			return 'failed';
		}

		// Hash doesn't match either pattern - invalid.
		$this->logger->error(
			'Payment status: invalid (hash matched neither success nor failure pattern)',
			[
				'provided_hash' => $provided_hash,
			]
		);
		return 'failed';
	}
}
