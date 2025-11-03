<?php
/**
 * Sandbox United Payment Georgia Client for Testing
 *
 * This class simulates the United Payment Georgia API for testing purposes
 * when test mode is enabled.
 *
 * @package UnitedPayment\WooCommerce
 */

namespace UnitedPayment\WooCommerce\Sandbox;

use UnitedPayment\WooCommerce\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Client Class
 *
 * Simulates the behavior of `MokaUnitedGE\MokaUnitedClient` for testing.
 */
class Client {

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
	 * @param array $config Configuration array with dealerCode, username, password.
	 */
	public function __construct( $config ) {
		$this->dealer_code = $config['dealerCode'] ?? '';
		$this->username    = $config['username'] ?? '';
		$this->password    = $config['password'] ?? '';
		$this->logger      = new Logger();

		// Log initialization without sensitive data.
		$this->logger->info(
			'Sandbox Client initialized (Test Mode)',
			[
				'dealer_code' => $this->dealer_code,
				'username'    => $this->username,
			]
		);
	}

	/**
	 * Get payments API instance
	 *
	 * @return Payments
	 */
	public function payments() {
		return new Payments( $this->dealer_code, $this->username, $this->password );
	}
}
