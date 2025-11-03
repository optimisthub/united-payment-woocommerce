<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'woocommerce_united_payment_settings' );

if ( defined( 'WC_LOG_DIR' ) ) {
	$log_files = glob( WC_LOG_DIR . 'optimisthub-united-payment-for-woocommerce-*' );

	if ( is_array( $log_files ) ) {
		foreach ( $log_files as $log_file ) {
			if ( is_file( $log_file ) ) {
				wp_delete_file( $log_file );
			}
		}
	}
}
