/**
 * Draws an invoice PDF in the browser with jsPDF (bundled locally).
 * Uses "Rs." because the built-in PDF fonts have no rupee sign.
 */

const { __ } = window.wp.i18n;

function rs( text ) {
	return String( text || '' ).replace( /₹/g, 'Rs. ' );
}

function brandRGB() {
	const hex = getComputedStyle( document.documentElement ).getPropertyValue( '--pkc-brand' ).trim() || '#4F46E5';
	const m = hex.replace( '#', '' ).match( /^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i );
	return m ? [ parseInt( m[ 1 ], 16 ), parseInt( m[ 2 ], 16 ), parseInt( m[ 3 ], 16 ) ] : [ 79, 70, 229 ];
}

export function downloadInvoice( inv ) {
	const { jsPDF } = window.jspdf;
	const doc = new jsPDF( { unit: 'mm', format: 'a4' } );
	const brand = brandRGB();
	const W = 210;
	const L = 18;
	const R = W - 18;
	let y = 0;

	// Header band.
	doc.setFillColor( brand[ 0 ], brand[ 1 ], brand[ 2 ] );
	doc.rect( 0, 0, W, 34, 'F' );
	doc.setTextColor( 255, 255, 255 );
	doc.setFont( 'helvetica', 'bold' );
	doc.setFontSize( 20 );
	doc.text( inv.seller.name, L, 16 );
	doc.setFontSize( 11 );
	doc.setFont( 'helvetica', 'normal' );
	doc.text( inv.amounts.show_tax ? __( 'TAX INVOICE', 'pikacart' ) : __( 'INVOICE', 'pikacart' ), R, 16, { align: 'right' } );
	doc.text( inv.invoice.number, R, 23, { align: 'right' } );

	// Seller and buyer.
	doc.setTextColor( 30, 34, 50 );
	y = 46;
	doc.setFont( 'helvetica', 'bold' );
	doc.setFontSize( 10 );
	doc.text( __( 'From', 'pikacart' ), L, y );
	doc.text( __( 'Billed to', 'pikacart' ), 110, y );
	doc.setFont( 'helvetica', 'normal' );
	const seller = [ inv.seller.name, ...( inv.seller.address ? inv.seller.address.split( /\n/ ) : [] ), inv.seller.email, inv.seller.phone, inv.seller.gstin ? 'GSTIN: ' + inv.seller.gstin : '' ].filter( Boolean );
	const buyer = [ inv.buyer.name, inv.buyer.contact, ...( inv.buyer.address ? inv.buyer.address.split( /\n/ ) : [] ), inv.buyer.email, inv.buyer.mobile ].filter( Boolean );
	let ys = y + 6;
	seller.forEach( ( line ) => {
		doc.text( doc.splitTextToSize( line, 80 ), L, ys );
		ys += 5;
	} );
	let yb = y + 6;
	buyer.forEach( ( line ) => {
		doc.text( doc.splitTextToSize( line, 80 ), 110, yb );
		yb += 5;
	} );
	y = Math.max( ys, yb ) + 6;

	// Meta row.
	doc.setDrawColor( 225, 228, 236 );
	doc.line( L, y, R, y );
	y += 7;
	const meta = [
		[ __( 'Invoice date', 'pikacart' ), inv.invoice.date ],
		[ __( 'Payment ID', 'pikacart' ), inv.invoice.payment_id ],
		[ __( 'Method', 'pikacart' ), inv.invoice.method ],
	];
	meta.forEach( ( m, i ) => {
		const x = L + i * 58;
		doc.setFontSize( 8 );
		doc.setTextColor( 110, 116, 135 );
		doc.text( m[ 0 ].toUpperCase(), x, y );
		doc.setFontSize( 10 );
		doc.setTextColor( 30, 34, 50 );
		doc.text( String( m[ 1 ] || '-' ), x, y + 5 );
	} );
	y += 14;

	// Item table.
	doc.setFillColor( 245, 246, 251 );
	doc.rect( L, y, R - L, 9, 'F' );
	doc.setFont( 'helvetica', 'bold' );
	doc.setFontSize( 9 );
	doc.text( __( 'Description', 'pikacart' ), L + 3, y + 6 );
	doc.text( __( 'Amount', 'pikacart' ), R - 3, y + 6, { align: 'right' } );
	y += 15;
	doc.setFont( 'helvetica', 'normal' );
	doc.setFontSize( 10 );
	doc.text( inv.item.name, L + 3, y );
	doc.text( rs( inv.amounts.show_tax ? inv.amounts.taxable : inv.amounts.total ), R - 3, y, { align: 'right' } );
	if ( inv.item.period ) {
		doc.setFontSize( 8.5 );
		doc.setTextColor( 110, 116, 135 );
		doc.text( __( 'Service period:', 'pikacart' ) + ' ' + inv.item.period.replace( /–/g, '-' ), L + 3, y + 5 );
		doc.setTextColor( 30, 34, 50 );
		doc.setFontSize( 10 );
	}
	y += 12;
	doc.line( L, y, R, y );
	y += 8;

	// Totals.
	const totals = [];
	if ( inv.amounts.show_tax ) {
		totals.push( [ __( 'Taxable value', 'pikacart' ), rs( inv.amounts.taxable ) ] );
		totals.push( [ `GST @ ${ inv.amounts.tax_rate }%`, rs( inv.amounts.tax ) ] );
	}
	totals.forEach( ( t ) => {
		doc.text( t[ 0 ], 120, y );
		doc.text( t[ 1 ], R - 3, y, { align: 'right' } );
		y += 7;
	} );
	doc.setFont( 'helvetica', 'bold' );
	doc.setFontSize( 12 );
	doc.text( __( 'Total paid', 'pikacart' ), 120, y + 2 );
	doc.text( rs( inv.amounts.total ), R - 3, y + 2, { align: 'right' } );
	y += 20;

	doc.setFont( 'helvetica', 'normal' );
	doc.setFontSize( 9 );
	doc.setTextColor( 110, 116, 135 );
	doc.text( inv.amounts.show_tax ? __( 'Price is inclusive of GST.', 'pikacart' ) : __( 'Thank you for your payment.', 'pikacart' ), L, y );
	doc.text( __( 'This is a computer-generated invoice and needs no signature.', 'pikacart' ), L, y + 5 );
	doc.text( inv.seller.email, L, 285 );

	doc.save( `${ inv.invoice.number || 'invoice' }.pdf` );
}
