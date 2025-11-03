/**
 * WooCommerce Blocks Integration
 */

( function() {
	const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
	const { createElement } = window.wp.element;
	const { __ } = window.wp.i18n;
	const { decodeEntities } = window.wp.htmlEntities;
	const { getSetting } = window.wc.wcSettings;

	// Get payment method settings from server-side data
	const settings = getSetting( 'united_payment_data', {} );

	// Get localized label text
	const defaultLabel = __( 'United Payment', 'optimisthub-united-payment-for-woocommerce' );
	const label = decodeEntities( settings.title ) || defaultLabel;

	/**
	 * Label component with optional icon
	 */
	const Label = () => {
		const icon = settings.icon || '';

		// Create wrapper span for text and icon
		const children = [ createElement( 'span', { key: 'text' }, label ) ];

		// Add icon if available
		if ( icon ) {
			children.push(
				createElement( 'img', {
					key: 'icon',
					src: icon,
					alt: label,
					style: {
						marginLeft: '8px',
						maxHeight: '24px',
						height: 'auto',
						display: 'inline-block',
						verticalAlign: 'middle',
					},
				} )
			);
		}

		return createElement(
			'span',
			{
				style: {
					display: 'inline-flex',
					alignItems: 'center',
					gap: '8px',
				},
			},
			children
		);
	};

	/**
	 * Content component for displaying payment method description
	 */
	const Content = () => {
		return decodeEntities( settings.description || '' );
	};

	/**
	 * United Payment payment method configuration
	 */
	const UnitedPaymentMethod = {
		name: 'united_payment',
		label: createElement( Label ),
		content: createElement( Content ),
		edit: createElement( Content ),
		canMakePayment: () => true,
		ariaLabel: label,
		supports: {
			features: settings.supports || [ 'products' ],
		},
	};

	// Register the payment method
	registerPaymentMethod( UnitedPaymentMethod );
} )();
