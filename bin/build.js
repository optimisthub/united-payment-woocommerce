'use strict';

const { execSync } = require( 'child_process' );
const archiver = require( 'archiver' );
const fs = require( 'fs' );
const path = require( 'path' );

const pluginSlug = 'optimisthub-united-payment-for-woocommerce';
const buildFolder = 'build';
const targetFolder = path.join( buildFolder, pluginSlug );

const filesToCopy = [
	'assets',
	'src',
	'languages',
	'vendor',
	'composer.json',
	'optimisthub-united-payment-for-woocommerce.php',
	'readme.txt',
	'LICENSE',
	'uninstall.php',
];

/**
 * Execute shell command
 */
function exec( command ) {
	try {
		execSync( command, { stdio: 'inherit' } );
	} catch ( error ) {
		console.error( `Error executing: ${command}` );
		process.exit( 1 );
	}
}

/**
 * Copy files recursively
 */
function copyFiles() {
	console.log( 'Copying plugin files...' );
	filesToCopy.forEach( ( file ) => {
		const src = file;
		const dest = path.join( targetFolder, file );

		if ( ! fs.existsSync( src ) ) {
			console.warn( `Warning: ${src} not found, skipping...` );
			return;
		}

		// Create parent directory if needed
		const destDir = path.dirname( dest );
		if ( ! fs.existsSync( destDir ) ) {
			fs.mkdirSync( destDir, { recursive: true } );
		}

		// Copy file or directory
		exec( `cp -Rf ${src} ${dest}` );
	} );
	console.log( 'Done: Files copied successfully' );
}

/**
 * Create ZIP archive
 */
function createZip() {
	return new Promise( ( resolve, reject ) => {
		console.log( 'Creating ZIP archive...' );

		const output = fs.createWriteStream(
			path.join( buildFolder, `${pluginSlug}.zip` )
		);
		const archive = archiver( 'zip', { zlib: { level: 9 } } );

		output.on( 'close', () => {
			const sizeInMB = ( archive.pointer() / 1024 / 1024 ).toFixed( 2 );
			console.log( `Done: ZIP created successfully (${sizeInMB} MB)` );
			resolve();
		} );

		archive.on( 'error', ( err ) => {
			console.error( 'Error: Failed to create ZIP:', err.message );
			console.error( `You can manually zip the ${targetFolder} folder.` );
			reject( err );
		} );

		archive.pipe( output );
		archive.directory( targetFolder, pluginSlug );
		archive.finalize();
	} );
}

/**
 * Main build process
 */
async function build() {
	console.log( '\nStarting plugin build...\n' );

	// Generate translation files (requires wp-cli from dev dependencies)
	console.log( 'Generating translation files...' );
	try {
		exec( 'npm run build:i18n' );
		console.log( 'Done: Translation files generated' );
	} catch ( error ) {
		console.warn( 'Warning: Translation generation failed, continuing...' );
	}

	// Install production dependencies only (removes wp-cli and other dev tools)
	console.log( 'Installing production dependencies...' );
	try {
		exec( 'composer install --no-dev --optimize-autoloader' );
		console.log( 'Done: Production dependencies installed' );
	} catch ( error ) {
		console.warn( 'Warning: Composer install failed, continuing...' );
	}

	// Clean build folder
	console.log( 'Cleaning build folder...' );
	if ( fs.existsSync( buildFolder ) ) {
		exec( `rm -rf ${buildFolder}` );
	}
	fs.mkdirSync( buildFolder, { recursive: true } );
	fs.mkdirSync( targetFolder, { recursive: true } );
	console.log( 'Done: Build folder cleaned' );

	// Copy files
	copyFiles();

	// Create ZIP
	await createZip();

	// Restore development dependencies
	console.log( 'Restoring development dependencies...' );
	try {
		exec( 'composer install' );
		console.log( 'Done: Development dependencies restored' );
	} catch ( error ) {
		console.warn( 'Warning: Composer restore failed, continuing...' );
	}

	console.log( `\nBuild complete! Output: ${buildFolder}/${pluginSlug}.zip\n` );
}

// Run build
build().catch( ( error ) => {
	console.error( '\nBuild failed:', error.message );
	process.exit( 1 );
} );
