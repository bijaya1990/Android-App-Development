<?php
/**
 * Launch-ready legal pages (Terms, Privacy, Refund, Shipping & Delivery,
 * Contact, About, Affiliate, Content & IP) written for an Indian online store
 * that sells digital products and website services.
 *
 * Business details come from Marketplace → Storefront & SEO through the
 * [pikacart_business] shortcode, so editing the settings updates every page.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bump when the page texts change; older untouched pages are refreshed on upgrade.
 */
define( 'DM_LEGAL_VERSION', 4 );

function dm_business() {
	$email = dm_store_opt( 'business_email' ) ? dm_store_opt( 'business_email' ) : dm_opt( 'support_email' );
	$phone = dm_store_opt( 'business_phone' ) ? dm_store_opt( 'business_phone' ) : dm_opt( 'whatsapp_number' );
	$phone = $phone ? '+' . ltrim( preg_replace( '/[^0-9]/', '', $phone ), '+' ) : '';
	$wa    = dm_opt( 'whatsapp_number' ) ? '+' . preg_replace( '/\D/', '', dm_opt( 'whatsapp_number' ) ) : '';
	return array(
		'name'      => dm_site_name(),
		'legal'     => dm_store_opt( 'business_legal_name' ) ? dm_store_opt( 'business_legal_name' ) : dm_site_name(),
		'owner'     => dm_store_opt( 'owner_name' ),
		'address'   => dm_store_opt( 'business_address' ),
		'email'     => $email,
		'phone'     => $phone,
		'whatsapp'  => $wa,
		'hours'     => dm_store_opt( 'lp_hours' ),
		'grievance' => dm_store_opt( 'grievance_name' ) ? dm_store_opt( 'grievance_name' ) : dm_store_opt( 'owner_name' ),
		'website'   => preg_replace( '#^https?://#', '', untrailingslashit( home_url() ) ),
		'refund'    => (int) dm_opt( 'refund_window_days', 7 ),
	);
}

/**
 * [pikacart_business field="email"] — one business detail (email/phone links are clickable).
 */
add_shortcode( 'pikacart_business', function ( $atts ) {
	$atts = shortcode_atts( array( 'field' => 'name' ), $atts );
	$b    = dm_business();
	$f    = $atts['field'];
	if ( 'updated' === $f ) {
		return esc_html( get_the_modified_date( get_option( 'date_format' ) ) );
	}
	$v = isset( $b[ $f ] ) ? (string) $b[ $f ] : '';
	if ( '' === $v ) {
		if ( 'grievance' === $f ) {
			return esc_html__( 'Grievance Officer', 'digimarket' );
		}
		return 'address' === $f ? esc_html__( 'India', 'digimarket' ) : '';
	}
	if ( 'email' === $f ) {
		return '<a href="mailto:' . esc_attr( $v ) . '">' . esc_html( $v ) . '</a>';
	}
	if ( in_array( $f, array( 'phone', 'whatsapp' ), true ) ) {
		return '<a href="' . esc_url( ( 'whatsapp' === $f ? 'https://wa.me/' : 'tel:' ) . ltrim( $v, '+' ) ) . '">' . esc_html( $v ) . '</a>';
	}
	return esc_html( $v );
} );

/**
 * [pikacart_contact_card] — the full business details block.
 */
add_shortcode( 'pikacart_contact_card', function () {
	$b    = dm_business();
	$rows = array(
		__( 'Business name', 'digimarket' )      => esc_html( $b['legal'] ) . ( $b['legal'] !== $b['name'] ? ' (' . esc_html( $b['name'] ) . ')' : '' ),
		__( 'Owner', 'digimarket' )              => esc_html( $b['owner'] ),
		__( 'Registered address', 'digimarket' ) => esc_html( $b['address'] ),
		__( 'Email', 'digimarket' )              => $b['email'] ? '<a href="mailto:' . esc_attr( $b['email'] ) . '">' . esc_html( $b['email'] ) . '</a>' : '',
		__( 'Phone', 'digimarket' )              => $b['phone'] ? '<a href="tel:' . esc_attr( ltrim( $b['phone'], '+' ) ) . '">' . esc_html( $b['phone'] ) . '</a>' : '',
		__( 'WhatsApp', 'digimarket' )           => $b['whatsapp'] ? '<a href="https://wa.me/' . esc_attr( ltrim( $b['whatsapp'], '+' ) ) . '">' . esc_html( $b['whatsapp'] ) . '</a>' : '',
		__( 'Working hours', 'digimarket' )      => esc_html( $b['hours'] ),
		__( 'Website', 'digimarket' )            => '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( $b['website'] ) . '</a>',
	);
	$out = '<table class="dm-bizcard"><tbody>';
	foreach ( $rows as $k => $v ) {
		if ( '' !== $v ) {
			$out .= '<tr><th scope="row">' . esc_html( $k ) . '</th><td>' . $v . '</td></tr>';
		}
	}
	return $out . '</tbody></table>';
} );

/**
 * [pikacart_gst field="note|threshold"] — live GST wording that follows the GST switch.
 */
add_shortcode( 'pikacart_gst', function ( $atts ) {
	$atts = shortcode_atts( array( 'field' => 'note' ), $atts );
	if ( 'threshold' === $atts['field'] ) {
		return esc_html( dm_inr( dm_gst_opt( 'threshold' ) ) );
	}
	if ( dm_gst_enabled() && dm_valid_gstin( dm_store_gstin() ) ) {
		/* translators: 1 rate 2 gstin */
		return esc_html( sprintf( __( 'All prices include GST at %1$s%%. Our GSTIN is %2$s, and every order gets a GST tax invoice.', 'digimarket' ), (float) dm_gst_opt( 'rate' ), dm_store_gstin() ) );
	}
	return esc_html__( 'We are not registered under GST at present, so no GST is charged and receipts state “GST not applicable”. If we register, prices and receipts will show GST from that date.', 'digimarket' );
} );

/**
 * [pikacart_legal_link page="refund"]Refund policy[/pikacart_legal_link]
 */
add_shortcode( 'pikacart_legal_link', function ( $atts, $content = '' ) {
	$atts = shortcode_atts( array( 'page' => 'terms' ), $atts );
	return '<a href="' . esc_url( dm_legal_url( sanitize_key( $atts['page'] ) ) ) . '">' . wp_kses_post( $content ) . '</a>';
} );

/**
 * Page titles and bodies. Business details stay live through shortcodes.
 */
function dm_legal_page_defs() {
	$site = get_bloginfo( 'name' );
	$e    = '[pikacart_business field="email"]';
	$gro  = '<p><strong>[pikacart_business field="grievance"]</strong><br>[pikacart_business field="legal"], [pikacart_business field="address"]<br>' . __( 'Email:', 'digimarket' ) . ' ' . $e . '</p>';
	$upd  = '<p><em>' . __( 'Last updated:', 'digimarket' ) . ' [pikacart_business field="updated"]</em></p>';
	$days = (int) dm_opt( 'refund_window_days', 7 );

	return array(
		'terms'     => array(
			__( 'Terms & Conditions', 'digimarket' ),
			$upd . '<p>' . sprintf( __( 'These Terms & Conditions govern your use of %1$s ([pikacart_business field="website"]) and every purchase you make on it. The website is owned and operated by [pikacart_business field="legal"], [pikacart_business field="address"] (“we”, “us”, “our”). By using the website or placing an order you agree to these terms.', 'digimarket' ), $site ) . '</p>'
			. '<h2>1. What we offer</h2><ul><li><strong>Digital products</strong> — study notes, resume and design templates, kids worksheets, WordPress themes and similar downloadable files, delivered online right after payment.</li><li><strong>Website services</strong> — design and development of websites. Scope, price and timeline are agreed with you in writing (WhatsApp or email) before any payment.</li><li><strong>Partner offers</strong> — links to third-party products such as web hosting. Those purchases are made on the partner’s website under the partner’s terms.</li></ul>'
			. '<h2>2. Eligibility and accounts</h2><p>You must be 18 or older, or use the website with the consent of a parent or guardian. Keep your login details confidential; you are responsible for activity on your account. Please give accurate information so we can deliver your order and contact you.</p>'
			. '<h2>3. Prices and payment</h2><p>All prices are in Indian Rupees (₹). [pikacart_gst field="note"] Payments are processed securely by Razorpay using UPI, debit/credit cards, net banking and wallets. We never see or store your full card details. Coupon codes have their own validity, limits and conditions shown at checkout, cannot be exchanged for cash and may be withdrawn at any time. If a price is shown wrongly because of an obvious error, we may cancel the order and refund you in full.</p>'
			. '<h2>4. Delivery</h2><p>Digital products are delivered electronically — nothing is shipped. See our [pikacart_legal_link page="delivery"]Shipping &amp; Delivery Policy[/pikacart_legal_link].</p>'
			. '<h2>5. Refunds and cancellations</h2><p>See our [pikacart_legal_link page="refund"]Refund &amp; Cancellation Policy[/pikacart_legal_link].</p>'
			. '<h2>6. Licence for digital products</h2><p>When you buy a digital product you receive a personal, non-exclusive, non-transferable licence to use it for yourself or your own organisation. Unless the product page says otherwise: a WordPress theme or website template may be used on one website per purchase; you may edit files for your own use; you may not resell, share, upload to other websites or claim the product as your own. Download links are personal and time-limited.</p>'
			. '<h2>7. Website services</h2><ul><li>The work, price, payment milestones and timeline are the ones we agree in writing. Timelines start after we receive your content (text, photos, logo) and any agreed advance.</li><li>You confirm that the content you give us is yours or that you have permission to use it.</li><li>Domain names and hosting are third-party services, billed and governed by their providers.</li><li>Ownership of the finished website passes to you once the agreed amount is paid in full.</li><li>If we cannot deliver what was agreed, you get a full refund of any amount already paid.</li></ul>'
			. '<h2>8. Acceptable use</h2><p>Do not misuse the website: no fraud, false chargebacks, attempts to break security, scraping, or uploading unlawful content. We may suspend accounts or cancel orders involved in such activity.</p>'
			. '<h2>9. Intellectual property</h2><p>The website design, logo, text and products belong to us or our licensors and are protected by Indian copyright and trademark law. Reviews you post may be shown on the website.</p>'
			. '<h2>10. Third-party links and affiliate links</h2><p>Some links take you to other websites, and some are affiliate links for which we may earn a commission. We are not responsible for third-party websites, products or services. See our [pikacart_legal_link page="affiliate"]Affiliate Disclosure[/pikacart_legal_link].</p>'
			. '<h2>11. Disclaimer and limitation of liability</h2><p>We take care to make our products accurate and useful, but study materials are for learning support only and do not guarantee exam or job results. To the extent allowed by law, our total liability for any claim is limited to the amount you paid for the product or service concerned, and we are not liable for indirect or consequential losses.</p>'
			. '<h2>12. Governing law</h2><p>These terms are governed by the laws of India. Courts at Bargarh, Odisha have jurisdiction, subject to your rights under the Consumer Protection Act, 2019.</p>'
			. '<h2>13. Grievance redressal</h2><p>In line with the Consumer Protection (E-Commerce) Rules, 2020 and the Information Technology Rules, 2021, you can contact our Grievance Officer:</p>' . $gro . '<p>We acknowledge every complaint within 48 hours and aim to resolve it within 30 days.</p>'
			. '<h2>14. Changes</h2><p>We may update these terms. The version published on this page at the time of your order applies to that order.</p>'
			. '<h2>15. Contact</h2><p>Questions about these terms? Write to ' . $e . '.</p>',
		),
		'privacy'   => array(
			__( 'Privacy Policy', 'digimarket' ),
			$upd . '<p>' . sprintf( __( 'This Privacy Policy explains how %s ([pikacart_business field="legal"], [pikacart_business field="address"]) collects, uses and protects your personal data. We follow the Digital Personal Data Protection Act, 2023 and the Information Technology Act, 2000 and its rules.', 'digimarket' ), $site ) . '</p>'
			. '<h2>1. Data we collect</h2><ul><li><strong>Account and order data:</strong> name, email, phone/WhatsApp number, order history, downloads and invoices.</li><li><strong>Enquiries:</strong> details you send through our forms, WhatsApp or email about a website project.</li><li><strong>Payment data:</strong> handled by Razorpay. We receive the payment status and reference, never your full card, UPI PIN or bank login details.</li><li><strong>Technical data:</strong> IP address, browser and device type and pages visited, used for security and to improve the website.</li><li><strong>Reviews:</strong> ratings and comments you choose to publish.</li></ul>'
			. '<h2>2. Why we use it</h2><p>To create your account, process payments, deliver downloads and services, send order emails and receipts, answer your questions, prevent fraud, keep accounting and tax records, and improve our products. We send promotional messages only if you agree, and you can opt out at any time.</p>'
			. '<h2>3. Consent</h2><p>By creating an account, placing an order or sending an enquiry you consent to this use of your data. You may withdraw consent by writing to us; this does not affect processing already done or data we must keep by law.</p>'
			. '<h2>4. Who we share it with</h2><ul><li>Razorpay, to process payments and refunds.</li><li>Our web hosting and email service providers, who store data on our behalf.</li><li>WhatsApp (Meta), when you choose to chat with us there.</li><li>Government or law-enforcement authorities, when required by law.</li></ul><p>We do not sell your personal data.</p>'
			. '<h2>5. Cookies</h2><p>We use essential cookies to keep you logged in and to remember your cart, and simple, anonymous counters to understand which pages are useful. You can clear or block cookies in your browser, but login and checkout may then not work.</p>'
			. '<h2>6. How long we keep data</h2><p>Account data is kept while your account is active. Order and invoice records are kept for up to 8 years as required by Indian tax and accounting laws. Enquiries that do not become orders are deleted within 2 years.</p>'
			. '<h2>7. Security</h2><p>The website uses HTTPS encryption, passwords are stored hashed, download links expire, and access to data is limited to people who need it. No system is perfectly secure, so please use a strong password.</p>'
			. '<h2>8. Your rights</h2><p>You can ask to access, correct or erase your personal data, withdraw consent, nominate a person to act for you, and raise a grievance. Write to ' . $e . ' from your registered email and we will respond within 30 days.</p>'
			. '<h2>9. Children</h2><p>Our kids worksheets are meant to be bought by parents and teachers. We do not knowingly collect data from children under 18 without a parent’s or guardian’s consent.</p>'
			. '<h2>10. Grievance Officer</h2>' . $gro . '<p>We acknowledge complaints within 48 hours and resolve them within 30 days. If you are not satisfied, you may approach the Data Protection Board of India.</p>'
			. '<h2>11. Changes</h2><p>We will post any change to this policy on this page with a new “Last updated” date.</p>',
		),
		'refund'    => array(
			__( 'Refund & Cancellation Policy', 'digimarket' ),
			$upd . '<p>' . __( 'We want you to be happy with every purchase. Because digital files can be copied as soon as they are downloaded, refunds work as follows.', 'digimarket' ) . '</p>'
			. '<h2>1. Digital products (notes, templates, themes, worksheets)</h2><p>You are eligible for a <strong>full refund</strong> if:</p><ul><li>you request it within <strong>' . $days . ' days</strong> of purchase and have <strong>not downloaded or opened</strong> the product; or</li><li>the file is damaged, incomplete or materially different from its description, and we cannot fix or replace it within 3 working days; or</li><li>you were charged twice for the same order.</li></ul><p>Refunds are not given for a change of mind after the product has been downloaded, or for not reading the product description, compatibility or system requirements.</p>'
			. '<h2>2. How to request a refund</h2><p>Email ' . $e . ' or open a support ticket from <em>My Account → Support</em> with your order number and the reason. We reply within 48 hours.</p>'
			. '<h2>3. How refunds are paid</h2><p>Approved refunds are made to the <strong>original payment method</strong> through Razorpay. The amount usually reaches you within <strong>5–7 working days</strong> after approval, depending on your bank. Access to the refunded product is removed.</p>'
			. '<h2>4. Cancellations</h2><p>Digital orders are completed the moment payment succeeds, so they cannot be cancelled afterwards — please use the refund process above. If money was debited but the order failed, the payment is automatically returned by Razorpay/your bank within 5–7 working days; write to us if it does not arrive.</p>'
			. '<h2>5. Website services</h2><ul><li>Cancel before we start work: full refund of any advance.</li><li>Cancel after work has started: we refund the advance minus the value of work already completed and approved, which we will explain in writing.</li><li>If we cannot deliver what was agreed: full refund of everything you paid.</li><li>Domain and hosting fees paid to third parties follow their own refund rules.</li></ul>'
			. '<h2>6. Partner offers</h2><p>Products bought on a partner’s website through our links (for example web hosting) are refunded by that partner under its own policy.</p>'
			. '<h2>7. Contact</h2><p>For any refund question write to ' . $e . '.</p>',
		),
		'delivery'  => array(
			__( 'Shipping & Delivery Policy', 'digimarket' ),
			$upd . '<h2>1. No physical shipping</h2><p>' . sprintf( __( '%s sells digital products and online services only. Nothing is shipped by courier, so there are no shipping charges and no delivery address is needed.', 'digimarket' ), $site ) . '</p>'
			. '<h2>2. Digital products</h2><p>Delivery is <strong>instant</strong> after successful payment:</p><ul><li>the <strong>Download</strong> button appears on the order confirmation page;</li><li>a confirmation email with the download link is sent to your email address;</li><li>your purchases are always available in <em>My Account → My Purchases</em>, where you can generate a fresh link any time.</li></ul><p>Download links expire after a short time for security. If you have not received access within 2 hours of payment, write to ' . $e . ' with your order number and we will deliver it manually within 24 hours.</p>'
			. '<h2>3. Website services</h2><p>Website services are delivered online (a live website, admin login and training). The delivery timeline shown on each service — usually 5–10 working days — starts after we confirm your requirements and receive your content and any agreed advance. We share progress on WhatsApp or email.</p>'
			. '<h2>4. Where we deliver</h2><p>Our digital products and services are available to customers across India and, where payment methods allow, worldwide.</p>'
			. '<h2>5. Contact</h2><p>Delivery questions: ' . $e . '.</p>',
		),
		'contact'   => array(
			__( 'Contact Us', 'digimarket' ),
			'<p>' . __( 'We are happy to help with orders, downloads, refunds and website projects. The fastest way to reach us is WhatsApp; email replies are sent within one working day.', 'digimarket' ) . '</p>'
			. '[pikacart_contact_card]'
			. '<h2>' . __( 'Order help', 'digimarket' ) . '</h2><p>' . __( 'For a problem with an order, open a support ticket from My Account → Support or email us with your order number so we can see the details.', 'digimarket' ) . '</p>'
			. '<h2>' . __( 'Grievance Officer', 'digimarket' ) . '</h2>' . $gro . '<p>' . __( 'Complaints are acknowledged within 48 hours and resolved within 30 days.', 'digimarket' ) . '</p>',
		),
		'about'     => array(
			__( 'About Us', 'digimarket' ),
			'<p>' . sprintf( __( '%s is an Indian online store based in [pikacart_business field="address"]. We build professional websites for schools, colleges, puja committees, shops and small businesses, and sell ready-to-use digital products — study notes, resume templates, design packs, kids worksheets and WordPress themes.', 'digimarket' ), $site ) . '</p>'
			. '<h2>What we do</h2><ul><li><strong>Websites:</strong> custom WordPress websites with clear starting prices. We discuss your needs on WhatsApp first — you pay only after we agree.</li><li><strong>Digital products:</strong> instant download after a secure UPI, card or net-banking payment through Razorpay.</li></ul>'
			. '<h2>Our promise</h2><ul><li>Clear prices in Indian Rupees, no hidden charges.</li><li>A written refund policy for every product and service.</li><li>Reviews only from verified buyers and clients.</li><li>Real people on WhatsApp and email to help you.</li></ul>'
			. '<h2>Contact</h2>[pikacart_contact_card]',
		),
		'affiliate' => array(
			__( 'Affiliate Disclosure', 'digimarket' ),
			$upd . '<p>' . __( 'Some links on this website are affiliate links — for example to web hosting and domain companies. If you buy through them we may earn a commission at no extra cost to you. This helps us keep our guides and tools free.', 'digimarket' ) . '</p><p>' . __( 'We only recommend services we use or have tested, and our reviews list honest pros and cons. Partner purchases are made on the partner’s website, under its own prices, terms and refund policy.', 'digimarket' ) . '</p><p>' . __( 'Questions? Write to', 'digimarket' ) . ' ' . $e . '.</p>',
		),
		'content'   => array(
			__( 'Content & IP Policy', 'digimarket' ),
			$upd . '<p>' . __( 'We respect intellectual property. Everything we sell is created by us or used under a proper licence.', 'digimarket' ) . '</p><h2>Report an infringement</h2><p>' . __( 'If you believe any content on this website infringes your copyright or trademark, email', 'digimarket' ) . ' ' . $e . ' ' . __( 'with: your name and contact details, the link to the content, proof that you own the rights, and a statement that the information is accurate. We acknowledge notices within 48 hours and remove infringing content within 36 hours of a valid notice, as required by the Information Technology Rules, 2021.', 'digimarket' ) . '</p><h2>Using our products</h2><p>' . __( 'Our products are licensed for personal or single-organisation use as described in our Terms & Conditions. Please do not share, resell or upload them elsewhere.', 'digimarket' ) . '</p>',
		),
		'seller'    => array(
			__( 'Seller Agreement', 'digimarket' ),
			$upd . '<p>' . sprintf( __( 'This Seller Agreement is between you (the “Seller”) and [pikacart_business field="legal"], [pikacart_business field="address"], which operates %s (the “Platform”). It applies when you open a shop and sell on the Platform, together with our [pikacart_legal_link page="terms"]Terms &amp; Conditions[/pikacart_legal_link] and [pikacart_legal_link page="content"]Content &amp; IP Policy[/pikacart_legal_link]. By ticking “I agree” you accept it.', 'digimarket' ), $site ) . '</p>'
			. '<h2>1. Eligibility</h2><p>You must be 18 or older, able to enter a contract under Indian law, and give true identity, PAN and bank/UPI details for payout verification (KYC). One person or business may hold one shop unless we agree otherwise.</p>'
			. '<h2>2. What you may sell</h2><p>Only digital products you created or hold the right to sell. Each listing must have an accurate title, description, preview, price and file. Pirated, stolen, illegal, adult, hateful, misleading or malicious content (including viruses or keyloggers), and products that infringe anyone’s copyright or trademark, are prohibited.</p>'
			. '<h2>3. Prices and commission</h2><p>You set your prices in Indian Rupees. The Platform deducts a commission from each sale at the rate shown in your dashboard at the time of the sale. Rate changes are announced in advance and never affect past orders. Launch offers may set the commission to 0% for a limited time. While the Platform is registered under GST, GST (currently 18%) is charged on the commission and deducted together with it; when GST is not applicable, only the commission is deducted.</p>'
			. '<h2>4. Payments and payouts</h2><p>Buyers pay through Razorpay. Your share of each sale is transferred automatically to your Razorpay linked account and settled to your bank on Razorpay’s settlement cycle. Payouts may be held while an order is under dispute, refund or fraud review.</p>'
			. '<h2>5. GST and taxes</h2><ul><li><strong>GST registration is compulsory once your aggregate turnover crosses [pikacart_gst field="threshold"] (twenty lakh rupees) in a financial year</strong> (1 April – 31 March), or any lower limit the law applies to you. You must then add your valid 15-character GSTIN in Shop settings.</li><li>Below that limit GST registration is optional. You may add a GSTIN at any time and choose to show it on your receipts.</li><li><strong>You alone are responsible for your own tax compliance</strong> — GST registration, tax invoices, returns and payments, and income tax. The Platform is not liable for any tax, interest or penalty arising from your sales.</li><li>When your sales on the Platform reach the limit, we may mark your shop “GST required”. Until you add a valid GSTIN, your products are paused for new purchases; existing buyers keep access.</li><li>The Platform may collect and deposit tax at source (such as GST TCS or income-tax TDS) where the law requires it and will share the details with you.</li></ul>'
			. '<h2>6. Delivery, support and refunds</h2><p>Your files must download correctly and match the description. Respond to buyer questions within 2 working days. Refunds follow our [pikacart_legal_link page="refund"]Refund &amp; Cancellation Policy[/pikacart_legal_link]; when a refund or chargeback is approved, your share and the commission for that order are reversed.</p>'
			. '<h2>7. Licence to the Platform</h2><p>You keep ownership of your products. You give the Platform a non-exclusive licence to host, display, promote (including previews and ads) and deliver them to buyers for as long as they are listed and for existing buyers afterwards.</p>'
			. '<h2>8. Reviews and conduct</h2><p>Do not post fake reviews, ask buyers to change reviews for rewards, or take buyers off the Platform to avoid commission. Treat buyers respectfully.</p>'
			. '<h2>9. Indemnity</h2><p>You will compensate the Platform for claims, losses or penalties caused by your products, your breach of this agreement, or infringement of someone else’s rights.</p>'
			. '<h2>10. Suspension and termination</h2><p>You may close your shop at any time from your dashboard; completed orders remain available to buyers. We may hide listings or suspend or close a shop for breach of this agreement, fraud, repeated complaints or legal reasons, and will tell you why where the law allows.</p>'
			. '<h2>11. Liability</h2><p>The Platform is provided “as is”. To the extent allowed by law, our total liability to you is limited to the commission we earned from your sales in the 3 months before the claim.</p>'
			. '<h2>12. Governing law and disputes</h2><p>This agreement is governed by the laws of India. We will first try to resolve any dispute by discussion; failing that, courts at Bargarh, Odisha have jurisdiction.</p>'
			. '<h2>13. Changes</h2><p>We may update this agreement with at least 7 days’ notice by email or in your dashboard. Continuing to sell after that means you accept the change.</p>'
			. '<h2>14. Contact and grievances</h2>' . $gro,
		),
	);
}

/**
 * Was this page left as the theme created it (not edited by the owner)?
 */
function dm_legal_page_untouched( $post ) {
	$c = (string) $post->post_content;
	if ( false !== strpos( $c, 'starter template' ) || false !== strpos( $c, 'Edit this page' ) ) {
		return true;
	}
	$edited = get_post_meta( $post->ID, '_dm_legal_hash', true );
	if ( $edited ) {
		return md5( $c ) === $edited;
	}
	// Created by the theme and never saved since.
	return abs( strtotime( $post->post_modified_gmt ) - strtotime( $post->post_date_gmt ) ) < 120;
}

/**
 * Write the latest text into the legal pages. $force also overwrites pages the owner edited.
 */
function dm_refresh_legal_pages( $force = false ) {
	$pages = get_option( 'dm_legal_pages', array() );
	$done  = 0;
	foreach ( dm_legal_page_defs() as $key => $def ) {
		$post = ! empty( $pages[ $key ] ) ? get_post( $pages[ $key ] ) : null;
		if ( ! $post || 'trash' === $post->post_status ) {
			continue;
		}
		if ( ! $force && ! dm_legal_page_untouched( $post ) ) {
			continue;
		}
		wp_update_post( array( 'ID' => $post->ID, 'post_title' => $def[0], 'post_content' => $def[1], 'post_status' => 'publish' ) );
		update_post_meta( $post->ID, '_dm_legal_hash', md5( get_post_field( 'post_content', $post->ID ) ) );
		++$done;
	}
	if ( ! empty( $pages['privacy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', (int) $pages['privacy'] );
	}
	update_option( 'dm_legal_version', DM_LEGAL_VERSION );
	return $done;
}

/**
 * Upgrade: fill business details once and refresh untouched legal pages.
 */
function dm_legal_upgrade() {
	if ( (int) get_option( 'dm_legal_version' ) >= DM_LEGAL_VERSION ) {
		return;
	}
	$st = get_option( 'dm_store', array() );
	$st = is_array( $st ) ? $st : array();
	foreach ( dm_legal_defaults() as $k => $v ) {
		if ( empty( $st[ $k ] ) && '' !== $v ) {
			$st[ $k ] = $v;
		}
	}
	update_option( 'dm_store', $st );
	$s = get_option( 'dm_settings', array() );
	if ( is_array( $s ) && ( empty( $s['support_email'] ) || get_option( 'admin_email' ) === $s['support_email'] ) ) {
		$s['support_email'] = $st['business_email'];
		update_option( 'dm_settings', $s );
	}
	do_action( 'dm_store_saved' );
	dm_refresh_legal_pages( false );
}

function dm_legal_defaults() {
	return array(
		'business_legal_name' => '',
		'business_email'      => 'contact@pikacart.in',
		'business_address'    => 'Patharla, Bijepur, Bargarh, Odisha 768032, India',
		'grievance_name'      => 'Bijaya Nanda',
	);
}

/* Admin: re-apply the latest legal texts on demand. */
add_action( 'dm_admin_do_refresh_legal', function () {
	$n = dm_refresh_legal_pages( true );
	do_action( 'litespeed_purge_all' );
	/* translators: %d pages */
	dm_admin_back( sprintf( __( '%d legal pages updated with the latest text.', 'digimarket' ), $n ) );
} );

/* Remember the content of pages the theme writes, so later owner edits are respected. */
add_action( 'save_post_page', function ( $pid ) {
	if ( wp_is_post_revision( $pid ) || ! is_admin() || ! in_array( (int) $pid, array_map( 'intval', (array) get_option( 'dm_legal_pages', array() ) ), true ) ) {
		return;
	}
	delete_post_meta( $pid, '_dm_legal_hash' );
}, 20 );
