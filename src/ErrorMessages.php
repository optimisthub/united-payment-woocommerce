<?php
/**
 * Maps API error codes to user-friendly messages
 *
 * @package UnitedPayment\WooCommerce
 */

namespace UnitedPayment\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ErrorMessages Class
 */
class ErrorMessages {

	/**
	 * Get user-friendly error message for a given error code
	 *
	 * @param string $error_code ResultCode from API.
	 * @param string $original_message Original error message from API (fallback).
	 * @return string User-friendly error message.
	 */
	public static function get_message( $error_code, $original_message = '' ) {
		$messages = self::get_error_messages();

		// Check if we have a user-friendly message for this code.
		if ( isset( $messages[ $error_code ] ) ) {
			return $messages[ $error_code ];
		}

		// Special handling for "EX" code (unexpected software errors).
		if ( 'EX' === $error_code ) {
			return __( 'A system error occurred. Please try again or contact support.', 'optimisthub-united-payment-for-woocommerce' );
		}

		// If no mapping found, return a generic message.
		if ( ! empty( $original_message ) ) {
			return sprintf(
				/* translators: %s: Original error message */
				__( 'Payment processing error: %s', 'optimisthub-united-payment-for-woocommerce' ),
				$original_message
			);
		}

		return __( 'An unexpected error occurred during payment processing. Please try again.', 'optimisthub-united-payment-for-woocommerce' );
	}

	/**
	 * Get all error messages mapping
	 *
	 * @return array Error code => User-friendly message mapping.
	 */
	protected static function get_error_messages() {
		return [
			// Authentication & Configuration Errors.
			'PaymentDealer.CheckPaymentDealerAuthentication.InvalidRequest' => __( 'Payment configuration error. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.CheckPaymentDealerAuthentication.InvalidAccount' => __( 'Merchant account not found. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.CheckPaymentDealerAuthentication.VirtualPosNotFound' => __( 'Payment terminal not configured. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),

			// Limit Errors.
			'PaymentDealer.CheckDealerPaymentLimits.DailyDealerLimitExceeded' => __( 'Merchant daily transaction limit exceeded. Please try again tomorrow or contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.CheckDealerPaymentLimits.DailyCardLimitExceeded' => __( 'Your card has reached its daily transaction limit. Please try a different card or wait until tomorrow.', 'optimisthub-united-payment-for-woocommerce' ),

			// Card Information Errors.
			'PaymentDealer.CheckCardInfo.InvalidCardInfo' => __( 'Card information is invalid. Please check your card details and try again.', 'optimisthub-united-payment-for-woocommerce' ),

			// Payment Request Errors.
			'PaymentDealer.DoDirectPayment3dRequest.InvalidRequest' => __( 'Payment request is invalid. Please try again.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.RedirectUrlRequired' => __( 'Payment configuration error. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.InvalidCurrencyCode' => __( 'Invalid currency. Only GEL is supported.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.InvalidInstallmentNumber' => __( 'Invalid installment number. Please select between 1 and 12 installments.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.InstallmentNotAvailableForForeignCurrencyTransaction' => __( 'Installments are not available for foreign currency transactions.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.ForeignCurrencyNotAvailableForThisDealer' => __( 'Foreign currency payments are not available. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.PaymentMustBeAuthorization' => __( 'This payment requires authorization. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.AuthorizationForbiddenForThisDealer' => __( 'Pre-authorization is not enabled for this merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.PoolPaymentNotAvailableForDealer' => __( 'Pooled payments are not available for this merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.PoolPaymentRequiredForDealer' => __( 'This merchant requires pooled payments. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.TokenizationNotAvailableForDealer' => __( 'Saved cards are not available for this merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.CardTokenCannotUseWithSaveCard' => __( 'Cannot use saved card token while saving a card. Please contact support.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.CardTokenNotFound' => __( 'Saved card not found. Please enter your card details again.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.OnlyCardTokenOrCardNumber' => __( 'Cannot provide both card number and saved card token. Please try again.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.ChannelPermissionNotAvailable' => __( 'This payment channel is not available for the merchant. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.IpAddressNotAllowed' => __( 'Payment request from your location is not allowed. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.VirtualPosNotAvailable' => __( 'No suitable payment terminal found for your card. Please try a different card.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.ThisInstallmentNumberNotAvailableForVirtualPos' => __( 'This installment option is not available for your card. Please select a different installment plan.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.ThisInstallmentNumberNotAvailableForDealer' => __( 'This installment option is not available. Please select a different installment plan.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.DealerCommissionRateNotFound' => __( 'Merchant commission rate not configured for this installment plan. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.DealerGroupCommissionRateNotFound' => __( 'Merchant group commission rate not configured. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.InvalidSubMerchantName' => __( 'Invalid merchant configuration. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.InvalidUnitPrice' => __( 'Invalid product price. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.InvalidQuantityValue' => __( 'Invalid product quantity. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.BasketAmountIsNotEqualPaymentAmount' => __( 'Cart amount does not match payment amount. Please try again.', 'optimisthub-united-payment-for-woocommerce' ),
			'PaymentDealer.DoDirectPayment3dRequest.BasketProductNotFoundInYourProductList' => __( 'Product not found in merchant catalog. Please contact the merchant.', 'optimisthub-united-payment-for-woocommerce' ),
		];
	}

	/**
	 * Check if error code indicates a merchant configuration issue
	 *
	 * @param string $error_code ResultCode from API.
	 * @return bool True if it's a merchant config issue.
	 */
	public static function is_merchant_configuration_error( $error_code ) {
		$merchant_errors = [
			'PaymentDealer.CheckPaymentDealerAuthentication.InvalidRequest',
			'PaymentDealer.CheckPaymentDealerAuthentication.InvalidAccount',
			'PaymentDealer.CheckPaymentDealerAuthentication.VirtualPosNotFound',
			'PaymentDealer.DoDirectPayment3dRequest.RedirectUrlRequired',
			'PaymentDealer.DoDirectPayment3dRequest.ForeignCurrencyNotAvailableForThisDealer',
			'PaymentDealer.DoDirectPayment3dRequest.AuthorizationForbiddenForThisDealer',
			'PaymentDealer.DoDirectPayment3dRequest.PoolPaymentNotAvailableForDealer',
			'PaymentDealer.DoDirectPayment3dRequest.PoolPaymentRequiredForDealer',
			'PaymentDealer.DoDirectPayment3dRequest.TokenizationNotAvailableForDealer',
			'PaymentDealer.DoDirectPayment3dRequest.ChannelPermissionNotAvailable',
			'PaymentDealer.DoDirectPayment3dRequest.IpAddressNotAllowed',
			'PaymentDealer.DoDirectPayment3dRequest.DealerCommissionRateNotFound',
			'PaymentDealer.DoDirectPayment3dRequest.DealerGroupCommissionRateNotFound',
			'PaymentDealer.DoDirectPayment3dRequest.InvalidSubMerchantName',
		];

		return in_array( $error_code, $merchant_errors, true );
	}

	/**
	 * Check if error code indicates a customer card issue
	 *
	 * @param string $error_code ResultCode from API.
	 * @return bool True if it's a customer card issue.
	 */
	public static function is_customer_card_error( $error_code ) {
		$customer_errors = [
			'PaymentDealer.CheckCardInfo.InvalidCardInfo',
			'PaymentDealer.CheckDealerPaymentLimits.DailyCardLimitExceeded',
			'PaymentDealer.DoDirectPayment3dRequest.CardTokenNotFound',
			'PaymentDealer.DoDirectPayment3dRequest.VirtualPosNotAvailable',
		];

		return in_array( $error_code, $customer_errors, true );
	}
}
