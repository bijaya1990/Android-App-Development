<?php
// Demo storefront data for visual QA. Run with wp eval-file.
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$demo = DM_DIR . '/assets/demo/';
function seed_img( $file, $alt = '' ) {
	$tmp = wp_tempnam( basename( $file ) );
	copy( $file, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $file ), 'tmp_name' => $tmp ), 0 );
	if ( is_wp_error( $id ) ) { echo $id->get_error_message(); return 0; }
	if ( $alt ) update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return $id;
}
$admin = get_user_by( 'login', 'admin' )->ID;
wp_set_current_user( $admin );
$s = get_option( 'dm_settings' ); $s['single_seller_mode'] = 1; $s['whatsapp_number'] = '919776144085'; update_option( 'dm_settings', $s );
update_option( 'blogname', 'PikaCart' ); update_option( 'blogdescription', 'Study notes, templates & websites' );
$st = get_option( 'dm_store', array() ); $st['announce_on'] = 1; $st['announce_text'] = 'Dussehra Sale: Flat 30% off with code DUSSEHRA30'; $st['announce_link'] = home_url( '/sale/' ); $st['announce_end'] = wp_date( 'Y-m-d\TH:i', time() + 3 * DAY_IN_SECONDS ); $st['trust_projects'] = '25+'; $st['owner_name'] = 'Bijaya Nanda'; $st['owner_bio'] = 'WordPress developer from Odisha. I build websites for schools, puja committees and small businesses, and create study and career resources.'; update_option( 'dm_store', $st );
$cat = function ( $name ) { $t = get_term_by( 'name', $name, 'dm_category' ); if ( ! $t ) { $r = wp_insert_term( $name, 'dm_category' ); return $r['term_id']; } return $t->term_id; };
$imgs = array();
foreach ( array( 'tile-notes', 'tile-resume', 'tile-kids', 'tile-canva-packs', 'tile-committee-website', 'tile-school-website' ) as $f ) { $imgs[ $f ] = seed_img( $demo . $f . '.webp' ); }
$desc = '<p>Everything you need in one clean, well-organised download. Made for Indian students, job seekers and small organisations who want quality without paying a fortune.</p><h2>What is inside</h2><ul><li>Neatly formatted, easy to print</li><li>Works on phone, tablet and computer</li><li>Free updates when we improve it</li></ul><h2>Who is it for?</h2><p>Anyone who wants to save time and get a professional result quickly. Buy once, download instantly and keep it forever in your account.</p>';
$products = array(
	array( 'CBSE Class 10 Physics Notes PDF – Chapter-wise', 'Study Notes', 99, 49, 'tile-notes', "PDF, 86 pages\nAll 13 chapters\nSolved numericals\nLifetime updates", '', 34, 4.6, 0 ),
	array( 'Class 12 Chemistry Notes + Previous Year Papers', 'Study Notes', 149, 99, 'tile-notes', "PDF, 120 pages\n10 years of solved papers\nFormula sheet", '', 21, 4.4, 2 ),
	array( 'ATS Resume Template Pack (Word + PDF)', 'Resume & Career', 99, 49, 'tile-resume', "8 resume designs\nEditable Word files\nATS-friendly layout\nCover letter included", '', 58, 4.7, 0 ),
	array( 'Fresher Cover Letter Templates', 'Resume & Career', 49, '', 'tile-resume', "12 letters\nWord + Google Docs", '', 12, 4.2, 0 ),
	array( 'Kids ABC Tracing Worksheets', 'Kids Zone', 49, 29, 'tile-kids', "40 printable pages\nTracing + colouring\nA4 PDF", '3-5', 40, 4.8, 0 ),
	array( 'Number Fun Maths Worksheets – Class 1', 'Kids Zone', 39, '', 'tile-kids', "30 worksheets\nAnswer key", '5-7', 8, 4.5, 0 ),
	array( 'Instagram Post Pack – 60 Canva Templates', 'Design Templates', 149, 79, 'tile-canva-packs', "60 Canva templates\nFestival + sale posts\nCommercial use", '', 26, 4.3, 0 ),
	array( 'Puja Committee Chanda-Kharch Tracker', 'Business & Committee Tools', 299, 199, 'tile-committee-website', "Excel + Google Sheets\nQR donation receipts\nWhatsApp share format", '', 17, 4.9, 1 ),
	array( 'School Website WordPress Theme', 'WordPress Templates', 2499, 1499, 'tile-school-website', "WordPress theme\nAdmission form\nNotice board\nSetup guide", '', 6, 4.6, 0 ),
	array( 'Invoice & Quotation Template Kit', 'Business & Committee Tools', 99, '', 'tile-committee-website', "20 templates\nGST-ready fields", '', 3, 0, 0 ),
);
$pids = array();
foreach ( $products as $i => $p ) {
	$id = wp_insert_post( array( 'post_type' => 'dm_product', 'post_status' => 'publish', 'post_title' => $p[0], 'post_excerpt' => 'Instant download · ' . strtolower( $p[1] ) . ' made simple and ready to use.', 'post_content' => $desc, 'post_author' => $admin, 'post_date' => wp_date( 'Y-m-d H:i:s', time() - ( $i * 3 + 1 ) * DAY_IN_SECONDS ) ) );
	wp_set_object_terms( $id, array( $cat( $p[1] ) ), 'dm_category' );
	set_post_thumbnail( $id, $imgs[ $p[4] ] );
	update_post_meta( $id, '_dm_price', $p[2] ); update_post_meta( $id, '_dm_sale_price', $p[3] ); update_post_meta( $id, '_dm_delivery', 'file' );
	update_post_meta( $id, '_dm_file', 'x.bin' ); update_post_meta( $id, '_dm_file_name', 'notes.pdf' ); update_post_meta( $id, '_dm_file_size', 2400000 );
	update_post_meta( $id, '_dm_what_you_get', $p[5] ); update_post_meta( $id, '_dm_age_band', $p[6] );
	update_post_meta( $id, '_dm_sales', $p[7] ); update_post_meta( $id, '_dm_views', $p[7] * 12 );
	if ( $p[9] ) { update_post_meta( $id, '_dm_sale_to', wp_date( 'Y-m-d\TH:i', time() + $p[9] * DAY_IN_SECONDS + 5 * HOUR_IN_SECONDS ) ); }
	update_post_meta( $id, '_dm_focus_kw', strtolower( strtok( $p[0], '–(' ) ) );
	global $wpdb;
	if ( $p[8] ) {
		foreach ( array( array( 'Ankit S.', 5, "Very helpful, saved me hours of work." ), array( 'Priya M.', 4, "Good quality and easy to use." ), array( 'Rahul K.', 5, "Exactly what I needed. Recommended!" ) ) as $r ) {
			$wpdb->insert( dm_table( 'reviews' ), array( 'product_id' => $id, 'seller_id' => $admin, 'buyer_id' => 0, 'reviewer_name' => $r[0], 'reviewer_type' => 'buyer', 'rating' => $r[1], 'comment' => $r[2], 'status' => 'approved', 'created_at' => dm_now() ) );
		}
		dm_refresh_product_rating( $id );
	}
	dm_sync_effective_price( $id );
	dm_seo_autofill( $id );
	$pids[] = $id;
}
$svc_content = '<p>A fast, mobile-friendly website that makes your organisation look professional and easy to find on Google. We handle design, setup and training — you just share your details.</p><h2>Why choose us</h2><ul><li>Clear starting price, no hidden charges</li><li>You approve the design before we build</li><li>Free training so you can update notices and photos yourself</li><li>Full refund if we cannot deliver what we agreed</li></ul><h2>What we need from you</h2><p>Your logo, a few photos, contact details and the pages you want. Not ready? We will guide you on WhatsApp.</p>';
$services = array(
	array( 'School & College Website Development', 5000, 'hero-2-school-website', "Up to 6 pages\nAdmission enquiry form\nNotice board\nMobile-friendly\n3 months free support", '5–7 working days after we confirm requirements' ),
	array( 'Puja Committee Website', 3000, 'hero-5-puja-committee', "Donation & expense tracker page\nQR receipts on WhatsApp\nEvent photo gallery\nCommittee members page", '4–6 working days' ),
	array( 'Shop, Café & Restaurant Website', 4000, 'tile-school-website', "Menu / catalogue page\nGoogle Maps location\nWhatsApp order button\nMobile-friendly", '5–7 working days' ),
	array( 'Website Maintenance (AMC)', 999, 'tile-committee-website', "Monthly backups\nContent updates\nSecurity checks\nPriority WhatsApp support", 'Starts the same week' ),
);
$svc_ids = array();
foreach ( $services as $i => $p ) {
	$img = isset( $imgs[ $p[2] ] ) ? $imgs[ $p[2] ] : seed_img( $demo . $p[2] . '.webp' );
	$id  = wp_insert_post( array( 'post_type' => 'dm_product', 'post_status' => 'publish', 'post_title' => $p[0], 'post_excerpt' => 'Professional website with clear pricing. Discuss on WhatsApp — pay only after we agree.', 'post_content' => $svc_content, 'post_author' => $admin, 'menu_order' => $i ) );
	wp_set_object_terms( $id, array( $cat( 'Website Services' ) ), 'dm_category' );
	set_post_thumbnail( $id, $img );
	update_post_meta( $id, '_dm_price', $p[1] ); update_post_meta( $id, '_dm_service_mode', 1 );
	update_post_meta( $id, '_dm_included', $p[3] ); update_post_meta( $id, '_dm_turnaround', $p[4] ); update_post_meta( $id, '_dm_area_served', 'Odisha · All India (remote)' );
	update_post_meta( $id, '_dm_show_partners', 1 );
	update_post_meta( $id, '_dm_packages', array(
		array( 'name' => 'Basic', 'price' => $p[1], 'features' => array( '5 pages', 'Mobile-friendly', 'Contact form', '-Custom domain email' ), 'popular' => 0 ),
		array( 'name' => 'Standard', 'price' => $p[1] * 1.6, 'features' => array( '8 pages', 'Admission / enquiry form', 'Photo gallery', 'Google Maps' ), 'popular' => 1 ),
		array( 'name' => 'Premium', 'price' => $p[1] * 2.4, 'features' => array( 'Unlimited pages', 'Online payments', 'Blog / notices', '1 year support' ), 'popular' => 0 ),
	) );
	dm_seo_autofill( $id );
	$svc_ids[] = $id;
}
global $wpdb;
$wpdb->insert( dm_table( 'reviews' ), array( 'product_id' => $svc_ids[0], 'seller_id' => $admin, 'buyer_id' => 0, 'reviewer_name' => 'Sunrise Public School', 'reviewer_type' => 'client', 'rating' => 5, 'comment' => "Excellent work\nOur admission enquiries doubled after the new website. Very responsive on WhatsApp.", 'status' => 'approved', 'created_at' => dm_now() ) );
dm_refresh_product_rating( $svc_ids[0] );
foreach ( array( array( 'Hostinger Premium Web Hosting', 69, 'hostinger' ), array( 'MilesWeb WordPress Hosting', 99, 'milesweb' ) ) as $i => $a ) {
	$id = wp_insert_post( array( 'post_type' => 'dm_product', 'post_status' => 'publish', 'post_title' => $a[0], 'post_excerpt' => 'Fast, affordable hosting we use for school and small business websites.', 'post_content' => '<p>Reliable hosting with free SSL and easy WordPress setup. A great choice for your first website.</p>', 'post_author' => $admin, 'menu_order' => $i ) );
	wp_set_object_terms( $id, array( $cat( 'Hosting & Tools' ) ), 'dm_category' );
	set_post_thumbnail( $id, $imgs['tile-school-website'] );
	update_post_meta( $id, '_dm_price', $a[1] ); update_post_meta( $id, '_dm_affiliate', 1 ); update_post_meta( $id, '_dm_aff_url', 'https://example.com/partner/' . $a[2] . '?ref=pikacart' );
	update_post_meta( $id, '_dm_aff_slug', $a[2] ); update_post_meta( $id, '_dm_aff_suffix', '/month' ); update_post_meta( $id, '_dm_aff_best_for', 'School & small business websites' );
	update_post_meta( $id, '_dm_aff_pros', "Free SSL & domain\nEasy WordPress install\nIndian payment options" ); update_post_meta( $id, '_dm_aff_cons', "Renewal price is higher" );
	dm_seo_autofill( $id );
}
// Portfolio + testimonials.
foreach ( array( array( 'Sunrise Public School, Bhubaneswar', 'School', 'hero-2-school-website', 0 ), array( 'Durga Puja Samiti, Cuttack', 'Puja committee', 'hero-5-puja-committee', 1 ), array( 'Chai Adda Café', 'Café', 'tile-school-website', 2 ) ) as $pf ) {
	$id = wp_insert_post( array( 'post_type' => 'dm_portfolio', 'post_status' => 'publish', 'post_title' => $pf[0], 'post_content' => '<p>A clean, fast website built on WordPress.</p>' ) );
	$img = isset( $imgs[ $pf[2] ] ) ? $imgs[ $pf[2] ] : seed_img( $demo . $pf[2] . '.webp' );
	set_post_thumbnail( $id, $img );
	update_post_meta( $id, '_dm_pf_url', 'https://example.com/demo' ); update_post_meta( $id, '_dm_pf_kind', 2 === $pf[3] ? 'demo' : 'client' ); update_post_meta( $id, '_dm_pf_client', $pf[1] ); update_post_meta( $id, '_dm_pf_service', $svc_ids[ min( $pf[3], 2 ) ] );
	update_post_meta( $id, '_dm_pf_problem', 'Parents could not find admission details and notices were shared only on paper.' ); update_post_meta( $id, '_dm_pf_built', 'A 7-page website with an admission enquiry form, notice board and photo gallery.' ); update_post_meta( $id, '_dm_pf_result', 'Enquiries now arrive on WhatsApp every day and the office saves hours each week.' );
}
foreach ( array( array( 'Ramesh Mohanty', 'Principal', 'Sunrise Public School', 'Bhubaneswar', 'The website was ready in a week and looks very professional. Parents now find everything online.' ), array( 'Sanjay Das', 'Secretary', 'Durga Puja Samiti', 'Cuttack', 'Chanda hisaab is now fully transparent. Every donor gets a receipt on WhatsApp — no more disputes!' ), array( 'Neha Patnaik', 'Owner', 'Chai Adda Café', 'Puri', 'Our menu and location are on Google now. Orders on WhatsApp have gone up a lot.' ) ) as $t ) {
	$id = wp_insert_post( array( 'post_type' => 'dm_testimonial', 'post_status' => 'publish', 'post_title' => $t[0], 'post_content' => $t[4] ) );
	update_post_meta( $id, '_dm_t_org', $t[1] . ', ' . $t[2] ); update_post_meta( $id, '_dm_t_city', $t[3] ); update_post_meta( $id, '_dm_t_rating', 5 );
}
// Articles.
$topic = wp_insert_term( 'Website Tips', 'category' ); $topic2 = wp_insert_term( 'Study & Career', 'category' );
$art = '<p>Getting this right saves time, money and a lot of stress. Here is a simple, practical guide.</p><h2>Why it matters</h2><p>Parents, members and customers search online first. A clear website builds trust before they even call you.</p><h3>Common mistakes</h3><p>Outdated notices, no phone number, and pages that do not open on mobile.</p><h2>Step-by-step plan</h2><p>Start with the essentials, then add more as you grow. See our <a href="' . get_permalink( $svc_ids[0] ) . '">school website package</a> for a ready plan.</p><h3>What it costs</h3><table><tr><th>Package</th><th>Starting price</th></tr><tr><td>Basic</td><td>₹5,000</td></tr><tr><td>Standard</td><td>₹8,000</td></tr></table><h2>Conclusion</h2><p>Keep it simple, keep it updated, and make it easy to contact you on WhatsApp.</p>[pikacart_cta service="' . $svc_ids[0] . '"]';
$heroes = array( 'hero-2-school-website', 'hero-5-puja-committee', 'hero-3-resume-templates', 'hero-4-study-notes' );
foreach ( array( array( 'How Much Does a School Website Cost in India?', $topic ), array( 'How to Track Puja Chanda Transparently (With QR Receipts)', $topic ), array( 'Best Resume Format for Freshers in 2026', $topic2 ), array( 'Class 10 Board Exam: A 30-Day Revision Plan', $topic2 ) ) as $i => $a ) {
	$id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $a[0], 'post_content' => $art, 'post_excerpt' => 'A simple, practical guide with real prices, examples and a step-by-step plan you can follow today.', 'post_date' => wp_date( 'Y-m-d H:i:s', time() - ( $i + 1 ) * 2 * DAY_IN_SECONDS ) ) );
	wp_set_post_categories( $id, array( $a[1]['term_id'] ) );
	set_post_thumbnail( $id, seed_img( $demo . $heroes[ $i ] . '.webp' ) );
	update_post_meta( $id, '_dm_article_products', $pids[0] . ',' . $pids[2] );
}
update_post_meta( $svc_ids[0], '_dm_related_posts', '' );
flush_rewrite_rules();
echo "seeded\n";
