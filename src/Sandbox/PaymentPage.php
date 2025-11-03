<?php
/**
 * Sandbox Payment Page Handler
 *
 * Simulates the payment gateway's hosted payment page for testing.
 *
 * @package UnitedPayment\WooCommerce
 */

namespace UnitedPayment\WooCommerce\Sandbox;

use UnitedPayment\WooCommerce\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PaymentPage Class
 */
class PaymentPage {

	/**
	 * Initialize hooks
	 */
	public static function init() {
		add_action( 'woocommerce_api_united_payment_sandbox_payment', [ __CLASS__, 'display_payment_page' ] );
	}

	/**
	 * Display sandbox payment page
	 */
	public static function display_payment_page() {
		// Sanitize and validate parameters.
		$three_d_trx_code = isset( $_GET['threeDTrxCode'] ) ? sanitize_text_field( wp_unslash( $_GET['threeDTrxCode'] ) ) : '';

		// Validate required parameters.
		if ( empty( $three_d_trx_code ) ) {
			wp_die( esc_html__( 'Invalid payment parameters.', 'optimisthub-united-payment-for-woocommerce' ) );
		}

		// Retrieve payment data from transient.
		$payment_data = get_transient( 'united_payment_sandbox_payment_' . $three_d_trx_code );

		if ( ! $payment_data ) {
			wp_die( esc_html__( 'Payment session expired.', 'optimisthub-united-payment-for-woocommerce' ) );
		}

		// Log the payment page display.
		$logger = new Logger();
		$logger->info(
			'Sandbox payment page displayed',
			[
				'three_d_trx_code' => $three_d_trx_code,
				'amount'           => $payment_data['amount'],
				'currency'         => $payment_data['currency'],
				'other_trx_code'   => $payment_data['other_trx_code'],
			]
		);

		// Handle form submission.
		if ( isset( $_POST['payment_action'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'sandbox_payment_action' ) ) {
			self::process_payment_action(
				sanitize_text_field( wp_unslash( $_POST['payment_action'] ) ),
				$payment_data,
				$three_d_trx_code
			);
			return;
		}

		// Display payment page.
		self::render_payment_page( $three_d_trx_code, $payment_data );
	}

	/**
	 * Process payment action (success/failed)
	 *
	 * @param string $action Payment action (success, failed).
	 * @param array  $payment_data Payment data from transient.
	 * @param string $three_d_trx_code 3D transaction code.
	 */
	protected static function process_payment_action( $action, $payment_data, $three_d_trx_code ) {
		$logger = new Logger();

		$logger->info(
			'Sandbox payment action processed',
			[
				'action'           => $action,
				'three_d_trx_code' => $three_d_trx_code,
			]
		);

		// Get CodeForHash from payment data.
		$code_for_hash = strtoupper( $payment_data['code_for_hash'] );

		// Generate sandbox trxCode (ORDER-XXXXXX format).
		$trx_code = 'ORDER-' . strtoupper( substr( md5( $three_d_trx_code ), 0, 14 ) );

		// Build callback parameters based on action.
		$callback_params = [
			'sandbox_payment' => '1',
			'trxCode'         => $trx_code,
			'OtherTrxCode'    => $payment_data['other_trx_code'],
		];

		// Calculate hash based on success or failure.
		if ( 'success' === $action ) {
			// Success: SHA256(CodeForHash + "T").
			$callback_params['hashValue']     = hash( 'sha256', $code_for_hash . 'T' );
			$callback_params['resultCode']    = 'Success';
			$callback_params['resultMessage'] = 'Payment successful';
		} else {
			// Failed: SHA256(CodeForHash + "F").
			$callback_params['hashValue']     = hash( 'sha256', $code_for_hash . 'F' );
			$callback_params['resultCode']    = 'Failed';
			$callback_params['resultMessage'] = 'Payment declined by bank';
		}

		$logger->debug(
			'Callback parameters generated',
			[
				'code_for_hash' => $code_for_hash,
				'hash_value'    => $callback_params['hashValue'],
				'result_code'   => $callback_params['resultCode'],
				'trx_code'      => $trx_code,
			]
		);

		// Delete transient (payment session consumed).
		delete_transient( 'united_payment_sandbox_payment_' . $three_d_trx_code );

		// Build final callback URL.
		$redirect_url       = $payment_data['redirect_url'];
		$final_callback_url = add_query_arg( $callback_params, $redirect_url );

		// Redirect to callback URL.
		wp_safe_redirect( $final_callback_url );
		exit;
	}

	/**
	 * Render payment page HTML
	 *
	 * @param string $three_d_trx_code 3D transaction code.
	 * @param array  $payment_data Payment data.
	 */
	protected static function render_payment_page( $three_d_trx_code, $payment_data ) {
		$amount         = $payment_data['amount'];
		$currency       = $payment_data['currency'];
		$other_trx_code = $payment_data['other_trx_code'];
		$dealer_code    = $payment_data['dealer_code'];
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e( 'Sandbox Payment Gateway', 'optimisthub-united-payment-for-woocommerce' ); ?></title>
			<?php wp_head(); ?>
			<style>
				body {
					background: #f7f7f7;
					margin: 0;
					padding: 20px;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
				}
			</style>
		</head>
		<body>
			<div style="max-width: 500px; margin: 40px auto; padding: 0; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden;">
				<div style="background: #fff; text-align: center; padding: 20px; border-bottom: 1px solid #e2e8f0;">
					<div style="margin-bottom: 10px; display: inline-block;">
						<img src="<?php echo esc_url( UNITED_PAYMENT_PLUGIN_URL . 'assets/images/united-payment.png' ); ?>" alt="<?php echo esc_attr__( 'United Payment', 'optimisthub-united-payment-for-woocommerce' ); ?>" style="max-width: 80px; height: auto; display: block;">
					</div>
					<h1 style="color: #1e293b; margin: 0 0 8px 0; font-size: 24px; font-weight: 700;"><?php esc_html_e( 'United Payment', 'optimisthub-united-payment-for-woocommerce' ); ?></h1>
					<p style="color: #64748b; margin: 0; font-size: 14px;"><?php esc_html_e( 'Test Mode', 'optimisthub-united-payment-for-woocommerce' ); ?></p>
				</div>

				<div style="padding: 30px;">
					<div style="background: #f0f9ff; color: #1e293b; padding: 16px 20px; border-radius: 8px; margin-bottom: 30px; font-size: 14px; line-height: 1.6; border: 1px solid #e0f2fe;">
						<strong style="display: block; font-size: 15px; margin-bottom: 6px; color: #0f172a;"><?php esc_html_e( 'Test Environment', 'optimisthub-united-payment-for-woocommerce' ); ?></strong>
						<?php esc_html_e( 'This is a test transaction. No real payment will be processed. Choose a payment result to continue.', 'optimisthub-united-payment-for-woocommerce' ); ?>
					</div>

					<div style="background: #f8fafc; padding: 24px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #e2e8f0;">
						<h3 style="margin: 0 0 20px 0; color: #1e293b; font-size: 16px; font-weight: 600;"><?php esc_html_e( 'Payment Details', 'optimisthub-united-payment-for-woocommerce' ); ?></h3>
						<table style="width: 100%; border-collapse: collapse;">
						<tr>
							<td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; font-weight: 500; color: #64748b; width: 45%;"><?php esc_html_e( '3D Transaction Code:', 'optimisthub-united-payment-for-woocommerce' ); ?></td>
							<td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #1e293b; font-weight: 500; text-align: right;"><?php echo esc_html( substr( $three_d_trx_code, 0, 20 ) . '...' ); ?></td>
						</tr>
						<tr>
							<td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; font-weight: 500; color: #64748b; width: 45%;"><?php esc_html_e( 'Transaction Reference:', 'optimisthub-united-payment-for-woocommerce' ); ?></td>
							<td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #1e293b; font-weight: 500; text-align: right;"><?php echo esc_html( $other_trx_code ); ?></td>
						</tr>
						<tr>
							<td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; font-weight: 500; color: #64748b; width: 45%;"><?php esc_html_e( 'Amount:', 'optimisthub-united-payment-for-woocommerce' ); ?></td>
							<td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #1e293b; font-weight: 500; text-align: right;"><strong><?php echo esc_html( number_format( $amount, 2 ) . ' ' . $currency ); ?></strong></td>
						</tr>
						<tr>
							<td style="padding: 12px 0; font-size: 14px; font-weight: 500; color: #64748b; width: 45%;"><?php esc_html_e( 'Dealer Code:', 'optimisthub-united-payment-for-woocommerce' ); ?></td>
							<td style="padding: 12px 0; font-size: 14px; color: #1e293b; font-weight: 500; text-align: right;"><?php echo esc_html( $dealer_code ); ?></td>
						</tr>
					</table>
					</div>

					<form method="post" action="">
						<?php wp_nonce_field( 'sandbox_payment_action' ); ?>

						<div style="display: flex; gap: 12px; margin-top: 30px; flex-direction: column;">
							<button type="submit" name="payment_action" value="success" style="padding: 16px 24px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 8px; background: #2bfb97; color: #000;">
								<span>✓</span> <?php esc_html_e( 'Complete Payment', 'optimisthub-united-payment-for-woocommerce' ); ?>
							</button>

							<button type="submit" name="payment_action" value="failed" style="padding: 16px 24px; border: 2px solid #cbd5e1; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 8px; background: #e2e8f0; color: #64748b;">
								<span>✗</span> <?php esc_html_e( 'Decline Payment', 'optimisthub-united-payment-for-woocommerce' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
		exit;
	}
}
