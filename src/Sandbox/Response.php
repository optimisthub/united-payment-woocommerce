<?php
/**
 * Sandbox Response Object for United Payment Georgia Client
 *
 * Simulates the response object returned by the official United Payment Georgia client in test mode.
 *
 * @package UnitedPayment\WooCommerce
 */

namespace UnitedPayment\WooCommerce\Sandbox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Response Class
 *
 * This class intentionally keeps camelCase style in method names to mimic the
 * real external API client response structure exactly for compatibility.
 *
 * Simulates the response object returned by the real United Payment Georgia client in test mode.
 */
class Response {

	/**
	 * Response data
	 *
	 * @var array|null
	 */
	protected $data;

	/**
	 * Result code
	 *
	 * @var string
	 */
	protected $result_code;

	/**
	 * Result message
	 *
	 * @var string
	 */
	protected $result_message;

	/**
	 * Exception
	 *
	 * @var mixed
	 */
	protected $exception;

	/**
	 * Constructor
	 *
	 * @param array|null $data Response data.
	 * @param string     $result_code Result code.
	 * @param string     $result_message Result message.
	 * @param mixed      $exception Exception.
	 */
	public function __construct( $data, $result_code, $result_message, $exception ) {
		$this->data           = $data;
		$this->result_code    = $result_code;
		$this->result_message = $result_message;
		$this->exception      = $exception;
	}

	/**
	 * Get status code
	 *
	 * @return int
	 */
	public function getStatusCode() {
		return 200;
	}

	/**
	 * Get result code
	 *
	 * @return string
	 */
	public function getResultCode() {
		return $this->result_code;
	}

	/**
	 * Get result message
	 *
	 * @return string
	 */
	public function getResultMessage() {
		return $this->result_message;
	}

	/**
	 * Get data
	 *
	 * @return array|null
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Get body
	 *
	 * @return array
	 */
	public function getBody() {
		return [
			'Data'          => $this->data,
			'ResultCode'    => $this->result_code,
			'ResultMessage' => $this->result_message,
			'Exception'     => $this->exception,
		];
	}

	/**
	 * Get headers
	 *
	 * @return array
	 */
	public function getHeaders() {
		return [];
	}

	/**
	 * Get exception
	 *
	 * @return mixed
	 */
	public function getException() {
		return $this->exception;
	}

	/**
	 * Check if request was successful
	 *
	 * @return bool
	 */
	public function isSuccessful() {
		return 'Success' === $this->result_code;
	}
}
