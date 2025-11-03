<?php
/**
 * Logger Wrapper
 *
 * @package UnitedPayment\WooCommerce
 */

namespace UnitedPayment\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger Class
 */
class Logger {

	/**
	 * WooCommerce Logger Instance
	 *
	 * @var \WC_Logger
	 */
	protected $logger;

	/**
	 * Logger Context
	 *
	 * @var array
	 */
	protected $context;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->logger  = wc_get_logger();
		$this->context = [ 'source' => 'optimisthub-united-payment-for-woocommerce' ];
	}

	/**
	 * Log debug message
	 *
	 * @param string $message Log message.
	 * @param array  $data Additional data to log.
	 */
	public function debug( $message, $data = [] ) {
		$this->log( 'debug', $message, $data );
	}

	/**
	 * Log info message
	 *
	 * @param string $message Log message.
	 * @param array  $data Additional data to log.
	 */
	public function info( $message, $data = [] ) {
		$this->log( 'info', $message, $data );
	}

	/**
	 * Log warning message
	 *
	 * @param string $message Log message.
	 * @param array  $data Additional data to log.
	 */
	public function warning( $message, $data = [] ) {
		$this->log( 'warning', $message, $data );
	}

	/**
	 * Log error message
	 *
	 * @param string $message Log message.
	 * @param array  $data Additional data to log.
	 */
	public function error( $message, $data = [] ) {
		$this->log( 'error', $message, $data );
	}

	/**
	 * Log message with level
	 *
	 * @param string $level Log level (debug, info, warning, error).
	 * @param string $message Log message.
	 * @param array  $data Additional data to log.
	 */
	protected function log( $level, $message, $data = [] ) {
		// Format message with data if provided.
		if ( ! empty( $data ) ) {
			$message .= ' | Data: ' . wp_json_encode( $data );
		}

		// Log based on level.
		switch ( $level ) {
			case 'debug':
				$this->logger->debug( $message, $this->context );
				break;
			case 'info':
				$this->logger->info( $message, $this->context );
				break;
			case 'warning':
				$this->logger->warning( $message, $this->context );
				break;
			case 'error':
				$this->logger->error( $message, $this->context );
				break;
			default:
				$this->logger->info( $message, $this->context );
		}
	}

	/**
	 * Get log file path for manual inspection
	 *
	 * @return string Log file path.
	 */
	public function get_log_file_path() {
		return WC_LOG_DIR . 'optimisthub-united-payment-for-woocommerce-' . wp_hash( 'optimisthub-united-payment-for-woocommerce' ) . '.log';
	}
}
