#!/usr/bin/env php
<?php
/**
 * Update POT file references.
 *
 * This script updates file references in the POT file to use relative paths
 * from the plugin root directory, similar to how WooCommerce Stripe does it.
 *
 * Usage: php bin/update-pot-file-references.php languages/optimisthub-united-payment-for-woocommerce.pot
 *
 */

if ( ! isset( $argv[1] ) || ! file_exists( $argv[1] ) ) {
	echo "Usage: php bin/update-pot-file-references.php <pot-file-path>\n";
	exit( 1 );
}

$pot_file = $argv[1];
$content  = file_get_contents( $pot_file );

if ( false === $content ) {
	echo "Error: Could not read POT file.\n";
	exit( 1 );
}

// Get the plugin root directory.
$plugin_root = dirname( __DIR__ );

// Replace absolute paths with relative paths.
$content = str_replace( $plugin_root . DIRECTORY_SEPARATOR, '', $content );

// Normalize path separators to forward slashes without touching escape sequences.
$normalized = preg_replace( '/\\\\(?![abfnrtv0"\'\\\\])/', '/', $content );

if ( null === $normalized ) {
	echo "Error: Failed to normalize path separators.\n";
	exit( 1 );
}

$content = $normalized;

// Write back to the file.
$result = file_put_contents( $pot_file, $content );

if ( false === $result ) {
	echo "Error: Could not write to POT file.\n";
	exit( 1 );
}

echo "Successfully updated file references in {$pot_file}\n";
