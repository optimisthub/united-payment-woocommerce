<?php
/**
 * Sandbox Payments API for United Payment Georgia Client
 *
 * Simulates the payments API functionality for testing purposes in sandbox mode.
 *
 * @package UnitedPayment\WooCommerce
 */

namespace UnitedPayment\WooCommerce\Sandbox;

use UnitedPayment\WooCommerce\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payments Class
 *
 * Simulates the payments API of the real United Payment Georgia client for testing.
 */
class Payments {

	/**
	 * Dealer Code
	 *
	 * @var string
	 */
	protected $dealer_code;

	/**
	 * Username
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * Password
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Logger Instance
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Constructor
	 *
	 * @param string $dealer_code Dealer Code.
	 * @param string $username Username.
	 * @param string $password Password.
	 */
	public function __construct( $dealer_code, $username, $password ) {
		$this->dealer_code = $dealer_code;
		$this->username    = $username;
		$this->password    = $password;
		$this->logger      = new Logger();
	}

	/**
	 * Create a sandbox payment
	 *
	 * @param array $payment_data Payment data.
	 * @return Response Sandbox payment response.
	 */
	public function create( $payment_data ) {
		// Log API request.
		$this->logger->info(
			'[Sandbox API Request] POST /payments/create',
			[
				'dealer_code' => $this->dealer_code,
				'endpoint'    => '/payments/create',
				'method'      => 'POST',
				'request'     => $payment_data,
			]
		);

		// Validate required fields.
		if ( empty( $payment_data['Amount'] ) || empty( $payment_data['Currency'] ) ) {
			$error_response = new Response(
				null,
				'PaymentDealer.DoDirectPayment3dRequest.InvalidRequest',
				'Amount and Currency are required',
				null
			);

			// Log error response.
			$this->logger->error(
				'[Sandbox API Response] POST /payments/create - Error',
				[
					'status_code'    => 200,
					'result_code'    => 'PaymentDealer.DoDirectPayment3dRequest.InvalidRequest',
					'result_message' => 'Amount and Currency are required',
				]
			);

			return $error_response;
		}

		if ( empty( $payment_data['RedirectUrl'] ) ) {
			$error_response = new Response(
				null,
				'PaymentDealer.DoDirectPayment3dRequest.RedirectUrlRequired',
				'RedirectURL is required',
				null
			);

			// Log error response.
			$this->logger->error(
				'[Sandbox API Response] POST /payments/create - Error',
				[
					'status_code'    => 200,
					'result_code'    => 'PaymentDealer.DoDirectPayment3dRequest.RedirectUrlRequired',
					'result_message' => 'RedirectURL is required',
				]
			);

			return $error_response;
		}

		// Generate sandbox CodeForHash (GUID format).
		$code_for_hash = strtoupper( wp_generate_uuid4() );

		// Generate sandbox threeDTrxCode.
		$three_d_trx_code = wp_generate_uuid4();

		// Generate sandbox payment URL (points to our sandbox payment page).
		$payment_url = add_query_arg(
			[
				'threeDTrxCode' => $three_d_trx_code,
				'RedirectType'  => 0,
			],
			home_url( '/wc-api/united_payment_sandbox_payment' )
		);

		// Store payment data in transient for sandbox payment page to retrieve.
		set_transient(
			'united_payment_sandbox_payment_' . $three_d_trx_code,
			[
				'amount'         => $payment_data['Amount'],
				'currency'       => $payment_data['Currency'],
				'other_trx_code' => $payment_data['OtherTrxCode'],
				'redirect_url'   => $payment_data['RedirectUrl'],
				'dealer_code'    => $this->dealer_code,
				'code_for_hash'  => $code_for_hash,
			],
			HOUR_IN_SECONDS
		);

		$response_data = [
			'Url'         => $payment_url,
			'CodeForHash' => $code_for_hash,
		];

		$response = new Response(
			$response_data,
			'Success',
			'',
			null
		);

		// Log successful API response.
		$this->logger->info(
			'[Sandbox API Response] POST /payments/create - Success',
			[
				'status_code'    => 200,
				'result_code'    => 'Success',
				'result_message' => '',
				'response_data'  => $response_data,
			]
		);

		return $response;
	}
}
