<?php
/**
 * NaukriPatra — GeneratePress Child Theme
 * =======================================
 * Shining blue job portal with automatic state-wise list views.
 *
 * NOTE: Is theme me KOI tracking / phone-home code NAHI hai.
 * Aapki site 100% aapke control me hai.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Power features: SEO schema, ticker, trending, related posts, security */
require_once get_stylesheet_directory() . '/inc/features.php';

/* =========================================================
 * 0. EASY SETTINGS  (yahan edit kar sakte ho)
 * ======================================================= */

/** Social links — apne asli link yahan daalo */
function np_social_links() {
	return array(
		'whatsapp'  => 'https://whatsapp.com/channel/0029Vb86J5W0QearPaRwGA1E',
		'telegram'  => 'https://t.me/naukripatra',
		'facebook'  => '#',
		'youtube'   => '#',
		'playstore' => 'https://play.google.com/store/apps/details?id=in.naukripatra.in',
	);
}

/** Homepage ke 6 main sections (naam => category slug) */
function np_main_sections() {
	return array(
		'Latest Jobs' => 'latest-jobs',
		'Admit Card'  => 'admit-card',
		'Result'      => 'result',
		'Answer Key'  => 'answer-key',
		'Syllabus'    => 'syllabus',
		'Admission'   => 'admission',
	);
}

/** All India + 28 States + 8 Union Territories */
function np_locations() {
	return array(
		'all-india' => 'All India',
		// 28 States
		'andhra-pradesh' => 'Andhra Pradesh', 'arunachal-pradesh' => 'Arunachal Pradesh',
		'assam' => 'Assam', 'bihar' => 'Bihar', 'chhattisgarh' => 'Chhattisgarh',
		'goa' => 'Goa', 'gujarat' => 'Gujarat', 'haryana' => 'Haryana',
		'himachal-pradesh' => 'Himachal Pradesh', 'jharkhand' => 'Jharkhand',
		'karnataka' => 'Karnataka', 'kerala' => 'Kerala', 'madhya-pradesh' => 'Madhya Pradesh',
		'maharashtra' => 'Maharashtra', 'manipur' => 'Manipur', 'meghalaya' => 'Meghalaya',
		'mizoram' => 'Mizoram', 'nagaland' => 'Nagaland', 'odisha' => 'Odisha',
		'punjab' => 'Punjab', 'rajasthan' => 'Rajasthan', 'sikkim' => 'Sikkim',
		'tamil-nadu' => 'Tamil Nadu', 'telangana' => 'Telangana', 'tripura' => 'Tripura',
		'uttar-pradesh' => 'Uttar Pradesh', 'uttarakhand' => 'Uttarakhand',
		'west-bengal' => 'West Bengal',
		// 8 Union Territories
		'andaman-nicobar' => 'Andaman & Nicobar', 'chandigarh' => 'Chandigarh',
		'dadra-nagar-haveli-daman-diu' => 'Dadra & Nagar Haveli and Daman & Diu',
		'delhi' => 'Delhi', 'jammu-kashmir' => 'Jammu & Kashmir', 'ladakh' => 'Ladakh',
		'lakshadweep' => 'Lakshadweep', 'puducherry' => 'Puducherry',
	);
}

/* =========================================================
 * 1. STYLES & FONTS
 * ======================================================= */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'np-fonts',
		'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap',
		array(), null );
	wp_enqueue_style( 'np-child',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'generate-style' ), wp_get_theme()->get( 'Version' ) );
	wp_enqueue_script( 'np-main',
		get_stylesheet_directory_uri() . '/js/np-main.js',
		array(), wp_get_theme()->get( 'Version' ), true );
}, 20 );

// Core Web Vitals: JS ko defer karo (render-blocking na ho)
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( 'np-main' === $handle && false === strpos( $tag, 'defer' ) ) {
		$tag = str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}, 10, 2 );

/**
 * Core Web Vitals (technical SEO audit fix): Google Fonts ka
 * stylesheet by default RENDER-BLOCKING hota hai — browser pehle
 * poora font CSS download karta hai, tabhi page paint hota hai
 * (FCP/LCP slow). Fix: "preload + swap on load" pattern (web.dev
 * ka official recommended tarika) — font CSS background me load
 * hota hai, load hote hi stylesheet ban jaata hai. Visual me KOI
 * farak nahi padta, sirf first paint fast hoti hai. <noscript>
 * fallback JS-disabled browsers ke liye.
 */
add_filter( 'style_loader_tag', function ( $html, $handle, $href ) {
	if ( 'np-fonts' !== $handle ) return $html;
	$url = esc_url( $href );
	return '<link rel="preload" as="style" href="' . $url . '" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n"
		. '<noscript><link rel="stylesheet" href="' . $url . '"></noscript>' . "\n";
}, 10, 3 );

/* =========================================================
 * 1B. WEB APP FEEL — PWA meta tags (mobile browser app-jaisa)
 * =========================================================
 * NOTE (technical SEO audit fix): Yahan pehle ek viewport meta tag
 * aur do Google Fonts preconnect <link> tags bhi the. Live site
 * check karne par pata chala ki GeneratePress (parent theme) khud
 * apna viewport tag aur fonts.googleapis.com/fonts.gstatic.com
 * preconnect resource-hints already print karta hai — isliye woh
 * teeno duplicate the (do viewport tags = invalid HTML). Hata diye
 * gaye hain; PWA-specific tags (jo sirf yeh theme deta hai, kahin
 * aur se nahi aate) waise hi rakhe gaye hain.
 * ======================================================= */
add_action( 'wp_head', function () {
	echo '<meta name="theme-color" content="#0b3fd8">' . "\n";
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
	echo '<meta name="apple-mobile-web-app-title" content="NaukriPatra">' . "\n";
}, 1 );

/* =========================================================
 * 2. AUTO-CREATE CATEGORIES (activation par)
 * ======================================================= */
function np_create_categories() {
	// Job sections
	foreach ( np_main_sections() as $name => $slug ) {
		if ( ! term_exists( $slug, 'category' ) ) {
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}
	// Locations
	foreach ( np_locations() as $slug => $name ) {
		if ( ! term_exists( $slug, 'category' ) ) {
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}
	update_option( 'np_categories_done', 1 );
}
add_action( 'after_switch_theme', 'np_create_categories' );
// Fallback: agar activation hook miss ho jaye
add_action( 'admin_init', function () {
	if ( ! get_option( 'np_categories_done' ) ) np_create_categories();
} );

/* =========================================================
 * 3. JOB DETAILS META BOX (Qualification, Last Date, No. of Posts)
 * ======================================================= */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'np_job_details', '📋 Job Details (List me automatic dikhega)',
		'np_job_details_box', 'post', 'normal', 'high' );
} );

/**
 * Google JobPosting schema ke liye allowed employmentType enum.
 * (https://developers.google.com/search/docs/appearance/structured-data/job-posting)
 * Key = post meta me save hone wali value (Google ka exact enum),
 * Value = admin ko dropdown me dikhne wala label.
 */
function np_employment_types() {
	return array(
		'FULL_TIME'  => 'Full-Time',
		'PART_TIME'  => 'Part-Time',
		'CONTRACTOR' => 'Contract',
		'TEMPORARY'  => 'Temporary',
		'INTERN'     => 'Internship',
		'VOLUNTEER'  => 'Volunteer',
		'PER_DIEM'   => 'Per Diem',
		'OTHER'      => 'Other',
	);
}

function np_job_details_box( $post ) {
	wp_nonce_field( 'np_job_details_save', 'np_job_details_nonce' );
	$qual     = get_post_meta( $post->ID, '_np_qualification', true );
	$last     = get_post_meta( $post->ID, '_np_last_date', true );
	$total    = get_post_meta( $post->ID, '_np_posts_count', true );
	$salary   = get_post_meta( $post->ID, '_np_salary', true );
	$org      = get_post_meta( $post->ID, '_np_organization', true );
	$emp_type = get_post_meta( $post->ID, '_np_employment_type', true );
	$locality = get_post_meta( $post->ID, '_np_locality', true );
	$street   = get_post_meta( $post->ID, '_np_street', true );
	$postal   = get_post_meta( $post->ID, '_np_postal_code', true );
	if ( '' === $emp_type ) $emp_type = 'FULL_TIME';
	?>
	<style>
		.np-mb{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
		.np-mb label{font-weight:600;display:block;margin-bottom:4px}
		.np-mb input,.np-mb select{width:100%;padding:8px;border:2px solid #0b3fd8;border-radius:6px;font:inherit}
		.np-mb-sub{margin:18px 0 4px;font-weight:700;color:#0b3fd8;border-top:1px solid #dbe4ff;padding-top:14px}
		@media(max-width:782px){.np-mb{grid-template-columns:1fr}}
	</style>
	<div class="np-mb">
		<div>
			<label for="np_qualification">Qualification</label>
			<input type="text" id="np_qualification" name="np_qualification"
				value="<?php echo esc_attr( $qual ); ?>" placeholder="e.g. 10th Pass / Graduate">
		</div>
		<div>
			<label for="np_last_date">Last Date</label>
			<input type="text" id="np_last_date" name="np_last_date"
				value="<?php echo esc_attr( $last ); ?>" placeholder="e.g. 25 Aug 2026">
		</div>
		<div>
			<label for="np_posts_count">No. of Posts</label>
			<input type="text" id="np_posts_count" name="np_posts_count"
				value="<?php echo esc_attr( $total ); ?>" placeholder="e.g. 1250">
		</div>
	</div>

	<p class="np-mb-sub">🔍 Google Jobs Schema Fields (sabhi optional — jo bharoge wahi schema me jayega)</p>
	<div class="np-mb">
		<div>
			<label for="np_organization">Recruiting Organisation</label>
			<input type="text" id="np_organization" name="np_organization"
				value="<?php echo esc_attr( $org ); ?>" placeholder="e.g. Staff Selection Commission">
		</div>
		<div>
			<label for="np_salary">Salary / Pay Scale</label>
			<input type="text" id="np_salary" name="np_salary"
				value="<?php echo esc_attr( $salary ); ?>" placeholder="e.g. Rs 35,400 - 1,12,400 per month">
		</div>
		<div>
			<label for="np_employment_type">Employment Type</label>
			<select id="np_employment_type" name="np_employment_type">
				<?php foreach ( np_employment_types() as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $emp_type, $val ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label for="np_locality">City / Locality</label>
			<input type="text" id="np_locality" name="np_locality"
				value="<?php echo esc_attr( $locality ); ?>" placeholder="e.g. Delhi (optional)">
		</div>
		<div>
			<label for="np_street">Street Address</label>
			<input type="text" id="np_street" name="np_street"
				value="<?php echo esc_attr( $street ); ?>" placeholder="Optional — sirf tab bharo jab office ka pata dena ho">
		</div>
		<div>
			<label for="np_postal_code">PIN / Postal Code</label>
			<input type="text" id="np_postal_code" name="np_postal_code"
				value="<?php echo esc_attr( $postal ); ?>" placeholder="e.g. 110001 (optional)">
		</div>
	</div>
	<p style="margin-bottom:0;color:#666">💡 Location alag se likhne ki zaroorat nahi — jo state categories select karoge, wahi list me automatic aayenge. Yeh naye fields sirf zyada complete Google Jobs schema banane ke liye hain.</p>
	<?php
}

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['np_job_details_nonce'] ) ||
		 ! wp_verify_nonce( $_POST['np_job_details_nonce'], 'np_job_details_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	foreach ( array(
		'np_qualification' => '_np_qualification',
		'np_last_date'     => '_np_last_date',
		'np_posts_count'   => '_np_posts_count',
		'np_organization'  => '_np_organization',
		'np_salary'        => '_np_salary',
		'np_locality'      => '_np_locality',
		'np_street'        => '_np_street',
		'np_postal_code'   => '_np_postal_code',
	) as $field => $key ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
		}
	}

	// Employment type: whitelist ke against validate karo (tampering-safe),
	// invalid/blank value par safe default FULL_TIME.
	if ( isset( $_POST['np_employment_type'] ) ) {
		$emp_type = sanitize_text_field( wp_unslash( $_POST['np_employment_type'] ) );
		if ( ! array_key_exists( $emp_type, np_employment_types() ) ) {
			$emp_type = 'FULL_TIME';
		}
		update_post_meta( $post_id, '_np_employment_type', $emp_type );
	}
} );

/* =========================================================
 * 4. AUTO JOB LOCATION (selected categories se)
 * ======================================================= */
function np_get_job_locations( $post_id ) {
	$locations = np_locations();
	$cats = get_the_category( $post_id );
	$out  = array();
	if ( $cats ) {
		foreach ( $cats as $cat ) {
			if ( isset( $locations[ $cat->slug ] ) ) {
				$out[] = array( 'name' => $locations[ $cat->slug ], 'link' => get_category_link( $cat ) );
			}
		}
	}
	return $out;
}

/* =========================================================
 * 5. LAYOUT: list pages full-width, sidebar sirf single post me
 * ======================================================= */
add_filter( 'generate_sidebar_layout', function ( $layout ) {
	if ( is_single() ) return 'right-sidebar';
	return 'no-sidebar';
} );

// List pages par 20 post per page
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) return;
	if ( $q->is_archive() || $q->is_search() || $q->is_home() ) {
		$q->set( 'posts_per_page', 20 );
	}
} );

/* =========================================================
 * 6. THE SHINING BLUE LIST TABLE  (core of the site)
 * ======================================================= */
function np_render_job_table( $query = null ) {
	if ( null === $query ) { global $wp_query; $query = $wp_query; }

	$paged  = max( 1, (int) $query->get( 'paged' ) );
	$ppp    = (int) $query->get( 'posts_per_page' );
	if ( $ppp < 1 ) $ppp = (int) get_option( 'posts_per_page', 10 );
	$serial = ( $paged - 1 ) * $ppp;

	if ( ! $query->have_posts() ) {
		echo '<div class="np-empty">🔍 Abhi is section me koi post nahi hai. Jaldi hi nayi updates aayengi!</div>';
		return;
	}
	?>
	<div class="np-table-wrap">
	<table class="np-table">
		<thead>
			<tr>
				<th class="np-col-sl">Sl No</th>
				<th>Publish Date</th>
				<th class="np-col-name">Name of the Post</th>
				<th>Job Location</th>
				<th>Qualification</th>
				<th>Last Date</th>
				<th>No. of Posts</th>
				<th class="np-col-btn">Get Details</th>
			</tr>
		</thead>
		<tbody>
		<?php
		while ( $query->have_posts() ) {
			$query->the_post();
			$serial++;
			$id    = get_the_ID();
			$qual  = get_post_meta( $id, '_np_qualification', true );
			$last  = get_post_meta( $id, '_np_last_date', true );
			$total = get_post_meta( $id, '_np_posts_count', true );
			$locs  = np_get_job_locations( $id );
			$is_new = ( time() - get_post_time( 'U', true, $id ) ) < 3 * DAY_IN_SECONDS;
			?>
			<tr onclick="window.location='<?php echo esc_url( get_permalink() ); ?>'">
				<td data-label="Sl No"><span class="np-sl"><?php echo (int) $serial; ?></span></td>
				<td data-label="Publish Date"><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></td>
				<td data-label="Post Name" class="np-post-name">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					<?php if ( $is_new ) echo '<span class="np-new">NEW</span>'; ?>
				</td>
				<td data-label="Job Location">
					<?php
					if ( $locs ) {
						foreach ( $locs as $l ) {
							echo '<span class="np-loc">' . esc_html( $l['name'] ) . '</span> ';
						}
					} else { echo '—'; }
					?>
				</td>
				<td data-label="Qualification"><?php echo $qual ? esc_html( $qual ) : '—'; ?></td>
				<td data-label="Last Date" class="np-lastdate"><?php echo $last ? esc_html( $last ) : '—'; ?></td>
				<td data-label="No. of Posts"><?php echo $total ? esc_html( $total ) : '—'; ?></td>
				<td data-label="" class="np-col-btn">
					<a class="np-btn" href="<?php the_permalink(); ?>">Get Details</a>
				</td>
			</tr>
			<?php
		}
		wp_reset_postdata();
		?>
		</tbody>
	</table>
	</div>
	<div class="np-pagination">
		<?php
		echo paginate_links( array(
			'total'     => $query->max_num_pages,
			'current'   => $paged,
			'prev_text' => '« Prev',
			'next_text' => 'Next »',
		) );
		?>
	</div>
	<?php
}

/* =========================================================
 * 7. HEADER — NaukriPatra text logo + tagline
 * ======================================================= */
add_filter( 'generate_site_title_output', function () {
	return '<div class="np-brand"><a href="' . esc_url( home_url( '/' ) ) . '" rel="home">'
		. '<span class="np-logo">Naukri<span>Patra</span></span>'
		. '<span class="np-tagline">Latest Jobs Update</span>'
		. '</a></div>';
} );
// GP ki default tagline hata do (hamari custom hai)
add_filter( 'generate_site_description_output', '__return_empty_string' );

/* =========================================================
 * 8. SINGLE POST: thumbnail sabse upar + share buttons
 * ======================================================= */
// GP ka default featured image band (duplicate na ho)
add_filter( 'generate_show_post_image', function ( $show ) {
	return is_single() ? false : $show;
} );

add_action( 'generate_before_content', function () {
	if ( is_single() && has_post_thumbnail() ) {
		/**
		 * Core Web Vitals — LCP fix (technical SEO audit): live site
		 * check karne par pata chala ki yeh featured image (single
		 * post ka sabse pehla/sabse bada element — Largest Contentful
		 * Paint candidate) caching plugin ke lazy-load system se bhi
		 * lazy-load ho rahi thi. LCP image ko KABHI lazy-load nahi
		 * karna chahiye — isse LCP time badh jaata hai (Core Web
		 * Vitals me seedha nuksan). Fix: eager loading + high fetch
		 * priority + LiteSpeed Cache ka apna lazy-load-skip attribute.
		 */
		$attrs = array(
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'data-no-lazy'  => '1', // LiteSpeed Cache: is image ko lazy-load na kare
		);
		echo '<div class="np-featured">' . get_the_post_thumbnail( null, 'large', $attrs ) . '</div>';
	}
}, 5 );

// Post meta strip (date + locations) title ke neeche
add_action( 'generate_after_entry_title', function () {
	if ( ! is_single() ) return;
	$locs = np_get_job_locations( get_the_ID() );
	echo '<div class="np-meta-strip">';
	echo '<span class="np-meta-date">📅 ' . esc_html( get_the_date( 'd M Y' ) ) . '</span>';
	foreach ( $locs as $l ) {
		echo '<a class="np-loc" href="' . esc_url( $l['link'] ) . '">📍 ' . esc_html( $l['name'] ) . '</a>';
	}
	echo '</div>';
} );

// Job Details highlight box article ke shuru me + share buttons end me
add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) return $content;

	$id    = get_the_ID();
	$qual  = get_post_meta( $id, '_np_qualification', true );
	$last  = get_post_meta( $id, '_np_last_date', true );
	$total = get_post_meta( $id, '_np_posts_count', true );

	$box = '';
	if ( $qual || $last || $total ) {
		$box .= '<div class="np-detail-box"><h3>⚡ Quick Details</h3><div class="np-detail-grid">';
		if ( $qual )  $box .= '<div><span>Qualification</span><strong>' . esc_html( $qual ) . '</strong></div>';
		if ( $last )  $box .= '<div><span>Last Date</span><strong class="np-red">' . esc_html( $last ) . '</strong></div>';
		if ( $total ) $box .= '<div><span>No. of Posts</span><strong>' . esc_html( $total ) . '</strong></div>';
		$box .= '</div></div>';
	}

	// In-article ad (2nd paragraph ke baad)
	$ad = np_get_ad( 'in_article' );
	if ( $ad ) {
		$parts = explode( '</p>', $content );
		if ( count( $parts ) > 2 ) {
			$parts[1] .= '</p><div class="np-ad np-ad-inarticle">' . $ad . '</div>';
			array_splice( $parts, 2, 0, '' );
			$content = implode( '</p>', array_filter( $parts, function( $v, $k ){ return $v !== '' || $k === 0; }, ARRAY_FILTER_USE_BOTH ) );
		} else {
			$content .= '<div class="np-ad np-ad-inarticle">' . $ad . '</div>';
		}
	}

	return $box . $content . np_share_buttons_html();
} );

/** Optimized social share buttons */
function np_share_buttons_html() {
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( get_the_title() );
	$wa = "https://api.whatsapp.com/send?text={$title}%20-%20{$url}";
	$tg = "https://t.me/share/url?url={$url}&text={$title}";
	$fb = "https://www.facebook.com/sharer/sharer.php?u={$url}";
	$tw = "https://twitter.com/intent/tweet?url={$url}&text={$title}";
	ob_start();
	?>
	<div class="np-share">
		<span class="np-share-label">🔗 Share karo:</span>
		<a class="np-sh np-sh-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener nofollow" aria-label="Share on WhatsApp">WhatsApp</a>
		<a class="np-sh np-sh-tg" href="<?php echo esc_url( $tg ); ?>" target="_blank" rel="noopener nofollow" aria-label="Share on Telegram">Telegram</a>
		<a class="np-sh np-sh-fb" href="<?php echo esc_url( $fb ); ?>" target="_blank" rel="noopener nofollow" aria-label="Share on Facebook">Facebook</a>
		<a class="np-sh np-sh-tw" href="<?php echo esc_url( $tw ); ?>" target="_blank" rel="noopener nofollow" aria-label="Share on X">X</a>
		<button class="np-sh np-sh-copy" type="button"
			onclick="navigator.clipboard.writeText('<?php echo esc_js( get_permalink() ); ?>');this.textContent='Copied ✓';">Copy Link</button>
	</div>
	<?php
	return ob_get_clean();
}

/* =========================================================
 * 9. ADSENSE-READY AD SLOTS  (Settings → NaukriPatra Ads)
 * ======================================================= */
function np_get_ad( $slot ) {
	$ads = get_option( 'np_ads', array() );
	return isset( $ads[ $slot ] ) ? trim( $ads[ $slot ] ) : '';
}
function np_ad_slot( $slot, $class = '' ) {
	$code = np_get_ad( $slot );
	if ( $code ) echo '<div class="np-ad ' . esc_attr( $class ) . '">' . $code . '</div>';
}

add_action( 'admin_menu', function () {
	add_options_page( 'NaukriPatra Ads', 'NaukriPatra Ads', 'manage_options', 'np-ads', 'np_ads_page' );
} );

function np_ads_page() {
	if ( isset( $_POST['np_ads_nonce'] ) && wp_verify_nonce( $_POST['np_ads_nonce'], 'np_ads_save' )
		&& current_user_can( 'manage_options' ) ) {
		$slots = array( 'header', 'list_top', 'in_article', 'after_content', 'footer' );
		$ads = array();
		foreach ( $slots as $s ) {
			$ads[ $s ] = isset( $_POST[ 'np_ad_' . $s ] ) ? wp_unslash( $_POST[ 'np_ad_' . $s ] ) : '';
		}
		update_option( 'np_ads', $ads );
		echo '<div class="updated"><p>✅ Ads saved!</p></div>';
	}
	$ads = get_option( 'np_ads', array() );
	$fields = array(
		'header'        => 'Header ke neeche (sab pages)',
		'list_top'      => 'List/table ke upar (archive pages)',
		'in_article'    => 'Article ke andar (2nd paragraph ke baad)',
		'after_content' => 'Article ke end me (share buttons se pehle)',
		'footer'        => 'Footer ke upar',
	);
	?>
	<div class="wrap"><h1>💰 NaukriPatra Ad Slots</h1>
	<p>AdSense approve hone ke baad, ad code yahan paste karo — sahi jagah automatic dikhega. Khali chhodoge to kuch nahi dikhega (koi khali box nahi).</p>
	<form method="post"><?php wp_nonce_field( 'np_ads_save', 'np_ads_nonce' ); ?>
	<?php foreach ( $fields as $slot => $label ) : ?>
		<h3><?php echo esc_html( $label ); ?></h3>
		<textarea name="np_ad_<?php echo esc_attr( $slot ); ?>" rows="4" style="width:100%;max-width:700px"><?php
			echo esc_textarea( isset( $ads[ $slot ] ) ? $ads[ $slot ] : '' );
		?></textarea>
	<?php endforeach; ?>
	<p><button class="button button-primary">Save Ads</button></p>
	</form></div>
	<?php
}

// Header ad + after-content ad hooks
add_action( 'generate_after_header', function () { np_ad_slot( 'header', 'np-ad-header' ); } );
add_action( 'generate_after_content', function () {
	if ( is_single() ) np_ad_slot( 'after_content', 'np-ad-after' );
}, 5 );

/* =========================================================
 * 10. PROFESSIONAL FOOTER (4 columns, automatic links)
 * ======================================================= */
add_action( 'generate_before_copyright', function () {
	np_ad_slot( 'footer', 'np-ad-footer' );
	$social = np_social_links();
	?>
	<div class="np-footer">
		<div class="np-footer-inner">
			<div class="np-fcol">
				<span class="np-logo np-logo-footer">Naukri<span>Patra</span></span>
				<p>NaukriPatra — Latest Jobs Update. Sarkari naukri, admit card, result, answer key aur syllabus ki sabse tez aur bharosemand jankari, har state ke liye.</p>
			</div>
			<div class="np-fcol">
				<h4>Job Categories</h4>
				<ul>
					<?php foreach ( np_main_sections() as $name => $slug ) :
						$t = get_term_by( 'slug', $slug, 'category' );
						$link = $t ? get_category_link( $t ) : '#'; ?>
						<li><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="np-fcol">
				<h4>Quick Links</h4>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">About Us</a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>">Contact Us</a></li>
					<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a></li>
					<li><a href="<?php echo esc_url( home_url( '/disclaimer/' ) ); ?>">Disclaimer</a></li>
				</ul>
			</div>
			<div class="np-fcol">
				<h4>Follow Us</h4>
				<div class="np-fsocial">
					<a class="np-sh-wa" href="<?php echo esc_url( $social['whatsapp'] ); ?>" target="_blank" rel="noopener">WhatsApp</a>
					<a class="np-sh-tg" href="<?php echo esc_url( $social['telegram'] ); ?>" target="_blank" rel="noopener">Telegram</a>
					<a class="np-sh-fb" href="<?php echo esc_url( $social['facebook'] ); ?>" target="_blank" rel="noopener">Facebook</a>
					<a class="np-sh-yt" href="<?php echo esc_url( $social['youtube'] ); ?>" target="_blank" rel="noopener">YouTube</a>
					<?php if ( ! empty( $social['playstore'] ) && '#' !== $social['playstore'] ) : ?>
						<a class="np-sh-play" href="<?php echo esc_url( $social['playstore'] ); ?>" target="_blank" rel="noopener">📲 Download App</a>
					<?php endif; ?>
				</div>
				<p class="np-fnote">Daily job alerts ke liye humein follow karo 🔔</p>
			</div>
		</div>
	</div>
	<?php
} );

/* =========================================================
 * 11B. REST API — JOB DETAILS FOR ANDROID APP 📱
 * =======================================================
 * Meta box ka data (Qualification, Last Date, No. of Posts)
 * standard WordPress REST API me expose karta hai, taaki
 * Android app ko job details milein. 100% ADDITIVE — koi
 * existing API field change ya remove NAHI hota.
 *
 * App in fields ko padh sakta hai (GET /wp-json/wp/v2/posts):
 *   post.qualification          "10th Pass / Graduate"
 *   post.last_date              "25 Aug 2026"
 *   post.posts_count            "1250"
 *   post.job_details            { sab kuch ek object me:
 *     qualification, last_date, posts_count, locations[],
 *     views, publish_date, is_new }
 *   post.meta._np_qualification (raw meta bhi available)
 * ======================================================= */

// Meta keys ko REST ke liye register karo (post.meta me aayenge)
add_action( 'init', function () {
	foreach ( array(
		'_np_qualification', '_np_last_date', '_np_posts_count',
		'_np_organization', '_np_salary', '_np_employment_type',
		'_np_locality', '_np_street', '_np_postal_code',
	) as $key ) {
		register_post_meta( 'post', $key, array(
			'type'          => 'string',
			'single'        => true,
			'default'       => '',
			'show_in_rest'  => true,
			'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
		) );
	}
} );

add_action( 'rest_api_init', function () {

	// Ek complete object — app ke liye sabse aasan
	register_rest_field( 'post', 'job_details', array(
		'get_callback' => function ( $post ) {
			$id   = $post['id'];
			$locs = np_get_job_locations( $id );
			$loc_names = array();
			foreach ( $locs as $l ) { $loc_names[] = $l['name']; }
			return array(
				'qualification'    => (string) get_post_meta( $id, '_np_qualification', true ),
				'last_date'        => (string) get_post_meta( $id, '_np_last_date', true ),
				'posts_count'      => (string) get_post_meta( $id, '_np_posts_count', true ),
				'organization'     => (string) get_post_meta( $id, '_np_organization', true ),
				'salary'           => (string) get_post_meta( $id, '_np_salary', true ),
				'employment_type'  => np_schema_valid_employment_type( get_post_meta( $id, '_np_employment_type', true ) ),
				'locality'         => (string) get_post_meta( $id, '_np_locality', true ),
				'street'           => (string) get_post_meta( $id, '_np_street', true ),
				'postal_code'      => (string) get_post_meta( $id, '_np_postal_code', true ),
				'locations'        => $loc_names,
				'views'            => (int) get_post_meta( $id, '_np_views', true ),
				'publish_date'     => get_the_date( 'd M Y', $id ),
				'is_new'           => ( time() - get_post_time( 'U', true, $id ) ) < 3 * DAY_IN_SECONDS,
			);
		},
		'schema' => array(
			'description' => 'NaukriPatra job details (meta box data)',
			'type'        => 'object',
			'context'     => array( 'view', 'edit', 'embed' ),
		),
	) );

	// Flat top-level fields — read + write (app se edit bhi ho sakta hai)
	foreach ( array(
		'qualification' => '_np_qualification',
		'last_date'     => '_np_last_date',
		'posts_count'   => '_np_posts_count',
		'organization'  => '_np_organization',
		'salary'        => '_np_salary',
		'locality'      => '_np_locality',
		'street'        => '_np_street',
		'postal_code'   => '_np_postal_code',
	) as $field => $meta_key ) {
		register_rest_field( 'post', $field, array(
			'get_callback'    => function ( $post ) use ( $meta_key ) {
				return (string) get_post_meta( $post['id'], $meta_key, true );
			},
			'update_callback' => function ( $value, $post ) use ( $meta_key ) {
				if ( ! current_user_can( 'edit_post', $post->ID ) ) {
					return new WP_Error( 'rest_forbidden', 'Not allowed', array( 'status' => 403 ) );
				}
				update_post_meta( $post->ID, $meta_key, sanitize_text_field( (string) $value ) );
				return true;
			},
			'schema' => array(
				'description' => 'NaukriPatra job field: ' . $field,
				'type'        => 'string',
				'context'     => array( 'view', 'edit', 'embed' ),
			),
		) );
	}

	// employment_type — whitelist-validated read/write (Google JobPosting
	// enum ke against). Generic loop se alag isliye rakha kyunki isko
	// har write par np_employment_types() ke against confirm karna hai.
	register_rest_field( 'post', 'employment_type', array(
		'get_callback'    => function ( $post ) {
			return np_schema_valid_employment_type( get_post_meta( $post['id'], '_np_employment_type', true ) );
		},
		'update_callback' => function ( $value, $post ) {
			if ( ! current_user_can( 'edit_post', $post->ID ) ) {
				return new WP_Error( 'rest_forbidden', 'Not allowed', array( 'status' => 403 ) );
			}
			$value = sanitize_text_field( (string) $value );
			if ( ! array_key_exists( $value, np_employment_types() ) ) {
				return new WP_Error( 'rest_invalid_param', 'employment_type must be one of: ' . implode( ', ', array_keys( np_employment_types() ) ), array( 'status' => 400 ) );
			}
			update_post_meta( $post->ID, '_np_employment_type', $value );
			return true;
		},
		'schema' => array(
			'description' => 'NaukriPatra job field: employment_type (Google JobPosting enum)',
			'type'        => 'string',
			'enum'        => array( 'FULL_TIME', 'PART_TIME', 'CONTRACTOR', 'TEMPORARY', 'INTERN', 'VOLUNTEER', 'PER_DIEM', 'OTHER' ),
			'context'     => array( 'view', 'edit', 'embed' ),
		),
	) );
} );

/* =========================================================
 * 11. STICKY MOBILE FOOTER MENU
 * ======================================================= */
add_action( 'wp_footer', function () {
	$items = array(
		array( 'Home', home_url( '/' ), '🏠' ),
		array( 'Jobs', 'latest-jobs', '💼' ),
		array( 'Admit Card', 'admit-card', '🎫' ),
		array( 'Result', 'result', '🏆' ),
	);
	echo '<nav class="np-sticky-menu" aria-label="Quick menu">';
	$current = home_url( add_query_arg( array() ) );
	foreach ( $items as $it ) {
		$url = $it[1];
		if ( strpos( $url, 'http' ) !== 0 ) {
			$t = get_term_by( 'slug', $url, 'category' );
			$url = $t ? get_category_link( $t ) : home_url( '/' );
		}
		$active = untrailingslashit( $url ) === untrailingslashit( $current ) ? ' np-sm-active' : '';
		echo '<a class="np-sm-link' . $active . '" href="' . esc_url( $url ) . '"><span class="np-sm-ico">' . $it[2] . '</span><span>' . esc_html( $it[0] ) . '</span></a>';
	}
	echo '</nav>';
} );
