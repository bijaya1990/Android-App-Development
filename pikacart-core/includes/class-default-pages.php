<?php
/**
 * Default text for the info and legal pages created on activation.
 * The owner edits these pages normally in Pages after activation.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Default_Pages {

	public static function all() {
		$email = 'contact@pikacart.in';
		$site  = 'Pikacart';

		return array(
			'about'    => array(
				'title'   => __( 'About', 'pikacart' ),
				'slug'    => 'about',
				'content' => self::p(
					array(
						'<h2>Expert in ID Card Industry</h2>',
						"$site is an online tool that does one job very well: ID cards. Schools, colleges, coaching institutes, companies, hospitals, NGOs, events and clubs use $site to design, manage and print professional ID cards for their students, staff and members.",
						'Choose a ready design, add your people by hand or from Excel, and download print-ready cards with real QR verification. No design skills and no software installation needed.',
						"Questions? Write to us at <a href=\"mailto:$email\">$email</a>.",
					)
				),
			),
			'contact'  => array(
				'title'   => __( 'Contact', 'pikacart' ),
				'slug'    => 'contact',
				'content' => self::p(
					array(
						"We are happy to help. Send us a message with the form below or email <a href=\"mailto:$email\">$email</a>. We usually reply within one working day.",
						'[pikacart_contact_details]',
						'[pikacart_contact_form]',
					)
				),
			),
			'pricing'  => array(
				'title'   => __( 'Pricing', 'pikacart' ),
				'slug'    => 'pricing',
				'content' => self::p(
					array(
						'Try every feature free, then subscribe to one simple plan.',
						'[pikacart_pricing]',
					)
				),
			),
			'privacy'  => array(
				'title'   => __( 'Privacy Policy', 'pikacart' ),
				'slug'    => 'privacy-policy',
				'content' => self::p(
					array(
						"This Privacy Policy explains how $site (\"we\", \"us\") collects and uses personal data. We follow the principles of India's Digital Personal Data Protection Act, 2023.",
						'<h2>1. Who is responsible</h2>',
						"Each organisation that uses $site (a school, company or other body) decides what data of its students, staff or members it enters, and is responsible for that data. $site processes that data on the organisation's behalf only to create and verify ID cards.",
						'<h2>2. Data we collect</h2>',
						'Account data: organisation name, contact person, email, mobile number and password (stored only as a secure hash). Card data entered by the organisation: names, photos, ID numbers, class or department and optional details such as date of birth or blood group. Payment data: payment status and IDs from Razorpay. We never see or store your card or UPI details.',
						'<h2>3. Why we use it</h2>',
						'To provide the service, verify cards through QR codes, process payments, send account and billing emails, give support and keep the service secure.',
						'<h2>4. Minors</h2>',
						'Card data may relate to students who are minors. Organisations confirm they have the authority and, where needed, the consent of parents or guardians to enter this data. We collect only what a card needs.',
						'<h2>5. Sharing</h2>',
						'We do not sell personal data. We share it only with service providers we need (hosting, email delivery, Razorpay for payments) or when the law requires it. The public card verification page shows only the fields the organisation has allowed.',
						'<h2>6. Retention and deletion</h2>',
						'Data is kept while the account exists. An organisation can export and permanently delete its data, and can ask us to delete its account. Payment records are kept as long as tax law requires.',
						'<h2>7. Security</h2>',
						'We use encrypted connections, hashed passwords, access checks on every request and separate storage for each organisation.',
						'<h2>8. Your rights and contact</h2>',
						"You can ask to access, correct or delete your data, or raise a grievance, by writing to <a href=\"mailto:$email\">$email</a>.",
					)
				),
			),
			'terms'    => array(
				'title'   => __( 'Terms and Conditions', 'pikacart' ),
				'slug'    => 'terms-and-conditions',
				'content' => self::p(
					array(
						"These Terms govern your use of $site. By registering you agree to them.",
						'<h2>1. The service</h2>',
						"$site provides online tools to design, manage, download and print identity cards that an organisation issues to its own students, staff, members or visitors.",
						'<h2>2. Your authority</h2>',
						'When you register you confirm that you are authorised to issue ID cards on behalf of the organisation you name, and that you have the right to use its name, logo, signatures and seal, and the personal data you enter.',
						'<h2>3. Prohibited use</h2>',
						"You must not use $site to create any document that imitates a government-issued identity document, including Aadhaar, PAN, Voter ID, driving licence, passport, ration card, or police, military or government department cards, or any other country's official ID, and you must not use national emblems or government logos. We may suspend accounts and cancel cards that break this rule and may report illegal use to the authorities. See our Acceptable Use Policy.",
						'<h2>4. Free trial and subscription</h2>',
						'A new account gets a free trial. Exports during the trial carry a watermark. After the trial, a paid subscription is needed to create, download and print cards. Prices are shown on the Pricing page in Indian Rupees. Subscriptions renew automatically until you cancel autopay; one-time payments give access for the period shown.',
						'<h2>5. Your content</h2>',
						"You own the data and artwork you upload. You give $site permission to store and process it only to provide the service.",
						'<h2>6. Availability</h2>',
						'We work to keep the service available and accurate, but it is provided "as is". Please check every card before printing.',
						'<h2>7. Liability</h2>',
						"To the extent the law allows, $site's total liability is limited to the amount you paid in the last three months.",
						'<h2>8. Suspension and termination</h2>',
						'We may suspend an account that breaks these Terms. You may stop using the service and ask for deletion at any time.',
						'<h2>9. Law</h2>',
						'These Terms are governed by the laws of India.',
						"Contact: <a href=\"mailto:$email\">$email</a>",
					)
				),
			),
			'refund'   => array(
				'title'   => __( 'Refund and Cancellation Policy', 'pikacart' ),
				'slug'    => 'refund-and-cancellation-policy',
				'content' => self::p(
					array(
						'<h2>Free trial first</h2>',
						"Every new account can try all features free before paying, so you can check that $site suits you.",
						'<h2>Cancelling</h2>',
						'You can cancel autopay at any time from Subscription in your dashboard. Your access continues until the end of the period you have already paid for, and you will not be charged again.',
						'<h2>Refunds</h2>',
						"Because the service is available immediately after payment, payments are generally not refundable. If you were charged twice, or charged after cancelling, write to <a href=\"mailto:$email\">$email</a> within 7 days and we will refund the extra charge to the original payment method within 7 working days of approval.",
						'<h2>Failed payments</h2>',
						'If money was deducted but your plan did not activate, it is usually reversed by your bank automatically within 5 to 7 working days. Contact us with your payment ID if it is not.',
					)
				),
			),
			'aup'      => array(
				'title'   => __( 'Acceptable Use Policy', 'pikacart' ),
				'slug'    => 'acceptable-use-policy',
				'content' => self::p(
					array(
						"$site is for ID cards that an organisation issues to its own people. It must never be used to make forged identity documents.",
						'<h2>Not allowed</h2>',
						'<ul><li>Cards that imitate Aadhaar, PAN, Voter ID, driving licence, passport, ration card or any other government-issued ID of any country.</li><li>Police, military, court or government department identity cards.</li><li>National emblems, government logos or seals you are not authorised to use.</li><li>Cards for an organisation you are not authorised to represent.</li><li>Uploading data of people without the right to do so.</li></ul>',
						'<h2>What happens</h2>',
						'We review reports and may suspend accounts and cancel cards, which makes their verification pages show "Cancelled". Serious misuse may be reported to the authorities.',
						"Report misuse to <a href=\"mailto:$email\">$email</a>.",
					)
				),
			),
		);
	}

	private static function p( $parts ) {
		$out = '';
		foreach ( $parts as $part ) {
			if ( 0 === strpos( $part, '<' ) || 0 === strpos( $part, '[' ) ) {
				$out .= $part . "\n\n";
			} else {
				$out .= '<p>' . $part . "</p>\n\n";
			}
		}
		return $out;
	}
}
