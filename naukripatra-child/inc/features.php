<?php
/**
 * NaukriPatra — Power Features Module
 * SEO Schema, Ticker, Views/Trending, Related Posts, Breadcrumbs,
 * Dashboard Widget, Security Hardening.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================
 * A. POST VIEW COUNTER + TRENDING
 * ======================================================= */
add_action( 'wp_head', function () {
	if ( ! is_single() || is_user_logged_in() || is_preview() ) return;
	$id    = get_the_ID();
	$views = (int) get_post_meta( $id, '_np_views', true );
	update_post_meta( $id, '_np_views', $views + 1 );
} );

function np_get_views( $id ) {
	return (int) get_post_meta( $id, '_np_views', true );
}

/** Trending posts query (last 30 days, most viewed) */
function np_trending_query( $count = 6 ) {
	return new WP_Query( array(
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'meta_key'            => '_np_views',
		'orderby'             => 'meta_value_num',
		'order'               => 'DESC',
		'date_query'          => array( array( 'after' => '30 days ago' ) ),
	) );
}

/* =========================================================
 * B. BREAKING NEWS TICKER (homepage)
 * ======================================================= */
function np_render_ticker() {
	$q = new WP_Query( array(
		'posts_per_page'      => 8,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	if ( ! $q->have_posts() ) return;
	echo '<div class="np-ticker"><span class="np-ticker-label">🔴 LIVE</span><div class="np-ticker-track"><div class="np-ticker-move">';
	while ( $q->have_posts() ) { $q->the_post();
		echo '<a href="' . esc_url( get_permalink() ) . '">⚡ ' . esc_html( get_the_title() ) . '</a>';
	}
	wp_reset_postdata();
	echo '</div></div></div>';
}

/**
 * LIVE RESULTS TICKER — homepage ka naya section (additive, existing
 * kuch bhi nahi chheda gaya).
 *
 * [naukripatra_results_ticker] shortcode kisi plugin se aata hai, is
 * theme me register nahi hota — isliye shortcode_exists() se check
 * karke hi call kiya jaata hai (agar plugin kabhi inactive ho jaaye,
 * to yahan silently kuch nahi todega).
 *
 * FIX: Shortcode ka apna khud ka "🔴 LIVE RESULTS" label already
 * built-in hai (plugin ke andar) — pehle iske UPAR se bhi ek "🔴 LIVE
 * RESULTS" label wrap kiya jaata tha, jisse label DO BAAR dikhta tha
 * (ek box ke andar text repeat). Ab jab asli shortcode render hota
 * hai, humara apna label print NAHI hota — sirf shortcode ka khud ka
 * output dikhta hai. Humara label sirf "Coming Soon" placeholder ke
 * saath dikhta hai (jahan koi plugin output hi nahi hai, label ki
 * zaroorat hai).
 *
 * "Result" category me post count check karke decide karta hai ki
 * asli shortcode dikhana hai ya "Coming Soon" — shortcode ke apne
 * empty-state par depend nahi karta.
 */
function np_render_results_ticker() {
	$term        = get_term_by( 'slug', 'result', 'category' );
	$has_results = $term && ! is_wp_error( $term ) && (int) $term->count > 0;

	if ( $has_results && shortcode_exists( 'naukripatra_results_ticker' ) ) {
		// Shortcode khud self-contained hai (apna label + apna box styling
		// already deta hai) — isliye sirf spacing wrapper, koi extra box/
		// label nahi (warna do box ya do label ek saath dikhte).
		echo '<div class="np-results-ticker-live">';
		echo do_shortcode( '[naukripatra_results_ticker]' );
		echo '</div>';
	} else {
		echo '<div class="np-results-ticker">';
		echo '<span class="np-results-label">🔴 LIVE RESULTS</span>';
		echo '<div class="np-results-track"><span class="np-results-soon">Coming Soon</span></div>';
		echo '</div>';
	}
}

/* =========================================================
 * C. BREADCRUMBS
 * ======================================================= */
function np_breadcrumbs() {
	if ( is_front_page() ) return;
	echo '<nav class="np-crumbs" aria-label="Breadcrumb"><a href="' . esc_url( home_url( '/' ) ) . '">🏠 Home</a>';
	if ( is_single() ) {
		$cats = get_the_category();
		if ( $cats ) {
			$main = $cats[0];
			foreach ( $cats as $c ) {
				if ( ! isset( np_locations()[ $c->slug ] ) ) { $main = $c; break; }
			}
			echo '<span>›</span><a href="' . esc_url( get_category_link( $main ) ) . '">' . esc_html( $main->name ) . '</a>';
		}
		echo '<span>›</span><span class="np-crumb-cur">' . esc_html( wp_trim_words( get_the_title(), 8 ) ) . '</span>';
	} elseif ( is_archive() ) {
		echo '<span>›</span><span class="np-crumb-cur">' . esc_html( single_term_title( '', false ) ?: get_the_archive_title() ) . '</span>';
	} elseif ( is_search() ) {
		echo '<span>›</span><span class="np-crumb-cur">Search</span>';
	}
	echo '</nav>';
}
add_action( 'generate_before_main_content', 'np_breadcrumbs' );

/* =========================================================
 * D. RELATED POSTS (same location/category) on single
 * ======================================================= */
add_action( 'generate_after_content', function () {
	if ( ! is_single() ) return;
	$cats = wp_get_post_categories( get_the_ID() );
	if ( ! $cats ) return;
	$q = new WP_Query( array(
		'category__in'        => $cats,
		'post__not_in'        => array( get_the_ID() ),
		'posts_per_page'      => 6,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	if ( ! $q->have_posts() ) return;
	echo '<div class="np-related"><h3>🔥 Aapke Liye Aur Jobs</h3><div class="np-related-grid">';
	while ( $q->have_posts() ) { $q->the_post();
		$last = get_post_meta( get_the_ID(), '_np_last_date', true );
		echo '<a class="np-rel-item" href="' . esc_url( get_permalink() ) . '">';
		echo '<span class="np-rel-title">' . esc_html( get_the_title() ) . '</span>';
		echo '<span class="np-rel-meta">📅 ' . esc_html( get_the_date( 'd M Y' ) );
		if ( $last ) echo ' &nbsp;|&nbsp; <b class="np-red">Last: ' . esc_html( $last ) . '</b>';
		echo '</span></a>';
	}
	wp_reset_postdata();
	echo '</div></div>';
}, 8 );

/* =========================================================
 * E0. GOOGLE JOBPOSTING SCHEMA — HELPER FUNCTIONS
 * =========================================================
 * Yeh functions Search Console ke JobPosting errors fix karte hain:
 *   - Missing field "jobLocation" (Critical)
 *   - Missing field "baseSalary" / "validThrough" (Warning)
 *   - Missing addressLocality / streetAddress / postalCode (Warning)
 *   - Invalid enum value in "educationRequirements" (Warning)
 * Sab kuch dynamic hai — custom job meta fields se aata hai.
 * Koi value fabricate/hardcode nahi ki jaati; agar data available
 * nahi hai to woh field schema se poori tarah OMIT ho jaata hai
 * (Google guideline: galat/andaza data dene se behtar hai field na do).
 * ======================================================= */

/**
 * "Last Date" field (free-text, admin ke haathon se likha) ko
 * reliably ek Unix timestamp me convert karta hai.
 *
 * PEHLE: sirf strtotime() use hota tha — agar admin ne format thoda
 * alag likha (25/08/2026, 25-08-2026, "Last Date: 25 Aug 2026", ya
 * "25th Aug 2026") to parsing fail ho jaati thi aur validThrough
 * field CHUP-CHAAP schema se gayab ho jaata tha. Yehi Search
 * Console ka "Missing field validThrough" warning tha.
 *
 * @param string $str Raw last-date text.
 * @return int|false Unix timestamp, ya false agar parse na ho paaye.
 */
function np_schema_parse_date( $str ) {
	$str = trim( (string) $str );
	if ( '' === $str ) return false;

	// "Last Date: 25 Aug 2026" jaisा prefix hata do
	$str = preg_replace( '/^[^\d]*/', '', $str );
	// Ordinal suffix hata do: "25th Aug 2026" -> "25 Aug 2026"
	$str = preg_replace( '/\b(\d{1,2})(st|nd|rd|th)\b/i', '$1', (string) $str );
	$str = trim( preg_replace( '/\s+/', ' ', (string) $str ) );
	if ( '' === $str ) return false;

	$formats = array( 'd M Y', 'd F Y', 'd-m-Y', 'd/m/Y', 'd.m.Y', 'Y-m-d', 'M d, Y', 'M d Y' );
	foreach ( $formats as $fmt ) {
		// '!' prefix = format me na diye gaye fields (H:i:s) Unix epoch
		// par reset ho jate hain, current time bleed nahi karta.
		$dt = DateTime::createFromFormat( '!' . $fmt, $str );
		if ( $dt instanceof DateTime ) {
			$errors = DateTime::getLastErrors();
			$clean  = ( false === $errors )
				|| ( 0 === (int) $errors['warning_count'] && 0 === (int) $errors['error_count'] );
			if ( $clean ) return $dt->getTimestamp();
		}
	}

	// Aakhri sahara: PHP ka built-in permissive parser
	$ts = strtotime( $str );
	return $ts ?: false;
}

/**
 * Free-text Qualification ko Google ke JobPosting schema ke
 * ALLOWED "credentialCategory" enum me map karta hai:
 * high school | associate degree | bachelor degree |
 * postgraduate degree | professional certificate | no requirement
 * (https://developers.google.com/search/docs/appearance/structured-data/job-posting)
 *
 * PEHLE: raw free text (jaise "B.Tech or B.E./M.Tech or M.E/Phd")
 * seedha educationRequirements me daal diya jaata tha — jo Google
 * ke allowed enum values me se koi nahi hai. Yehi Search Console
 * ka "Invalid enum value in educationRequirements" warning tha.
 *
 * AGAR text me EK se zyada alag-alag qualification level milte hain
 * (jaise "10th Pass / Graduate" — dono valid), to koi bhi ek enum
 * choose karna GALAT/misleading hoga, isliye is case me function
 * `false` return karta hai aur educationRequirements schema se
 * OMIT ho jaata hai (qualifications free-text field me poori
 * jankari waise hi maujood rehti hai).
 *
 * @param string $qual Raw qualification text.
 * @return string|false Google ka valid enum string, ya false.
 */
function np_schema_map_education( $qual ) {
	$q = strtolower( trim( (string) $qual ) );
	if ( '' === $q ) return false;

	// Punctuation (. - /) ko space me badlo taaki "B.Tech", "B-Tech",
	// "B/Tech" sab ek jaisa match ho jayein, phir space-padding se
	// whole-word matching karo (regex \b ki jhanjhat ke bina) —
	// isse "iti" jaisa short keyword "authority" ke andar galti se
	// match nahi karta.
	$norm = preg_replace( '/[.\-\/]+/', ' ', $q );
	$norm = preg_replace( '/\s+/', ' ', (string) $norm );
	$norm = ' ' . trim( (string) $norm ) . ' ';

	$map = array(
		'postgraduate degree'      => array( ' post graduate ', ' postgraduate ', ' master ', ' m tech ', ' me ', ' m e ', ' msc ', ' m sc ', ' ma ', ' m a ', ' m com ', ' mcom ', ' mba ', ' phd ', ' ph d ', ' doctorate ' ),
		'bachelor degree'          => array( ' graduate ', ' graduation ', ' bachelor ', ' b tech ', ' btech ', ' be ', ' b e ', ' bsc ', ' b sc ', ' ba ', ' b a ', ' b com ', ' bcom ', ' llb ', ' b ed ', ' bed ' ),
		'associate degree'         => array( ' 12th ', ' intermediate ', ' diploma ', ' higher secondary ', ' hsc ', ' senior secondary ', ' plus two ', ' +2 ' ),
		'high school'              => array( ' 10th ', ' matric ', ' secondary school ', ' ssc pass ', ' class x ' ),
		'professional certificate' => array( ' certificate ', ' certification ', ' iti ' ),
		'no requirement'           => array( ' no qualification ', ' any qualification ', ' not required ', ' no minimum ', ' no formal education ' ),
	);

	$matched = array();
	foreach ( $map as $enum => $keywords ) {
		foreach ( $keywords as $kw ) {
			if ( false !== strpos( $norm, $kw ) ) { $matched[ $enum ] = true; break; }
		}
	}

	// Sirf TABHI enum do jab exactly EK confident category match ho.
	return ( 1 === count( $matched ) ) ? array_keys( $matched )[0] : false;
}

/**
 * Free-text Salary/Pay-Scale field ko Google ke JobPosting
 * "baseSalary" (MonetaryAmount → QuantitativeValue) structure me
 * convert karta hai.
 *
 * PEHLE: koi salary field hi nahi tha, isliye baseSalary hamesha
 * missing rehta tha (Search Console warning). Ab field available
 * hai, lekin agar admin ne "As per Govt norms" jaisa non-numeric
 * text likha, to schema me GALAT number dalne ki bajaye field
 * poori tarah omit ho jaata hai.
 *
 * @param string $str Raw salary text, e.g. "Rs 35,400 - 1,12,400 per month".
 * @return array|false Schema.org MonetaryAmount array, ya false.
 */
function np_schema_parse_salary( $str ) {
	$str = trim( (string) $str );
	if ( '' === $str ) return false;

	// Indian comma-grouping (1,12,400) samajh kar sabhi numbers nikaalo
	preg_match_all( '/\d[\d,]*(?:\.\d+)?/', $str, $m );
	$nums = array();
	foreach ( $m[0] as $raw ) {
		$clean = (float) str_replace( ',', '', $raw );
		if ( $clean > 0 ) $nums[] = $clean;
	}
	if ( empty( $nums ) ) return false; // "As per rules" jaisa text — kuch fabricate mat karo

	$unit = 'MONTH';
	if ( preg_match( '/per\s*day|daily|\/\s*day/i', $str ) )        $unit = 'DAY';
	elseif ( preg_match( '/per\s*year|annual|p\.?a\.?|\/\s*year/i', $str ) ) $unit = 'YEAR';
	elseif ( preg_match( '/per\s*hour|hourly|\/\s*hour/i', $str ) ) $unit = 'HOUR';

	sort( $nums );
	$value = array( '@type' => 'QuantitativeValue', 'unitText' => $unit );
	if ( count( $nums ) >= 2 ) {
		$value['minValue'] = $nums[0];
		$value['maxValue'] = end( $nums );
	} else {
		$value['value'] = $nums[0];
	}

	return array(
		'@type'    => 'MonetaryAmount',
		'currency' => 'INR',
		'value'    => $value,
	);
}

/**
 * Employment Type meta value ko Google ke allowed enum ke against
 * validate karta hai. Admin panel me dropdown hone ki wajah se
 * value pehle se hi Google ke exact enum strings me se ek hoti hai
 * — yeh sirf ek defense-in-depth safety-net hai (tampered/purani
 * data ke liye), FULL_TIME safe default ke saath.
 *
 * @param string $val Stored employment type meta value.
 * @return string Google ka ek valid employmentType enum.
 */
function np_schema_valid_employment_type( $val ) {
	$allowed = array( 'FULL_TIME', 'PART_TIME', 'CONTRACTOR', 'TEMPORARY', 'INTERN', 'VOLUNTEER', 'PER_DIEM', 'OTHER' );
	return in_array( $val, $allowed, true ) ? $val : 'FULL_TIME';
}

/**
 * jobLocation array banata hai — Google JobPosting schema me yeh
 * field REQUIRED hai (iske bina Search Console "Missing field
 * jobLocation" CRITICAL error deta hai).
 *
 * PEHLE: agar post par koi state/UT category select hi nahi thi
 * (sirf "Result"/"Admit Card" jaisi job-type category thi), to
 * $locs empty aata tha aur jobLocation poori tarah OMIT ho jaata
 * tha — yehi critical error tha.
 *
 * AB: jobLocation hamesha kam se kam ek Place ke saath present
 * rehta hai:
 *   - Agar specific states/UTs select hain → unka istemal (jaisa
 *     pehle tha), plus locality/street/postalCode agar admin ne
 *     diye hon.
 *   - "All India" ek real geographic region NAHI hai, isliye usko
 *     addressRegion me daalna galat/misleading structured data hai
 *     — is case me sirf country-level Place (addressCountry: IN)
 *     diya jaata hai.
 *   - Agar bilkul koi location category select nahi hai, tab bhi
 *     kam se kam country-level Place diya jaata hai (fabricate kuch
 *     nahi kiya jaata — sirf jo pakka pata hai: India).
 *
 * @param int   $post_id Post ID.
 * @param array $locs    np_get_job_locations() ka result.
 * @return array Schema.org Place objects, kabhi khali nahi.
 */
function np_schema_build_job_locations( $post_id, $locs ) {
	$locality = trim( (string) get_post_meta( $post_id, '_np_locality', true ) );
	$street   = trim( (string) get_post_meta( $post_id, '_np_street', true ) );
	$postal   = trim( (string) get_post_meta( $post_id, '_np_postal_code', true ) );

	$build_address = function ( $region = '' ) use ( $locality, $street, $postal ) {
		$addr = array( '@type' => 'PostalAddress', 'addressCountry' => 'IN' );
		if ( $region )   $addr['addressRegion']   = $region;
		if ( $locality ) $addr['addressLocality'] = $locality;
		if ( $street )   $addr['streetAddress']   = $street;
		if ( $postal )   $addr['postalCode']      = $postal;
		return $addr;
	};

	$places = array();
	$seen   = array();
	foreach ( $locs as $l ) {
		$region = ( 'All India' === $l['name'] ) ? '' : $l['name'];
		$key    = $region ?: '__country__';
		if ( isset( $seen[ $key ] ) ) continue; // duplicate region na ho
		$seen[ $key ] = true;
		$places[] = array( '@type' => 'Place', 'address' => $build_address( $region ) );
	}

	if ( empty( $places ) ) {
		$places[] = array( '@type' => 'Place', 'address' => $build_address() );
	}

	return $places;
}

/* =========================================================
 * E. SEO — JobPosting Schema (Google Jobs ready)
 * =========================================================
 * NOTE (Technical SEO audit fix): Is site par Yoast SEO active hai
 * aur woh khud already ek complete @graph deta hai jisme WebSite,
 * Organization, BreadcrumbList, Person (author E-E-A-T bio), Article
 * aur WebPage schema shaamil hain — live site check karke confirm
 * kiya gaya. Pehle yahan theme ka apna ALAG WebSite schema bhi tha
 * (different "name" value ke saath — "NaukriPatra" vs Yoast ka
 * "naukripatra.in") jo Google ko DUPLICATE/conflicting structured
 * data deta tha. Woh hata diya gaya hai — Yoast ka WebSite node hi
 * authoritative hai (usme publisher/Organization link bhi hai, jo
 * yahan nahi tha). JobPosting Yoast nahi deta, isliye woh yahi
 * rehta hai — koi duplication nahi.
 * ======================================================= */
add_action( 'wp_head', function () {
	// JobPosting (single post)
	if ( is_single() ) {
		$id       = get_the_ID();
		$qual     = get_post_meta( $id, '_np_qualification', true );
		$last     = get_post_meta( $id, '_np_last_date', true );
		$total    = get_post_meta( $id, '_np_posts_count', true );
		$salary   = get_post_meta( $id, '_np_salary', true );
		$org      = get_post_meta( $id, '_np_organization', true );
		$emp_type = get_post_meta( $id, '_np_employment_type', true );
		$locs     = np_get_job_locations( $id );

		$description = wp_strip_all_tags( get_the_excerpt( $id ) );
		if ( '' === trim( $description ) ) $description = get_the_title( $id ); // Google: description required, kabhi empty na ho

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'JobPosting',
			'title'       => get_the_title( $id ),
			'description' => $description,
			'datePosted'  => get_the_date( 'c', $id ),
			'url'         => get_permalink( $id ),
			'hiringOrganization' => array(
				'@type' => 'Organization',
				// PEHLE: yahan job ka TITLE daala jaata tha (galat/misleading —
				// organization ka naam kabhi post title jaisa nahi hota).
				// Ab: admin ke diye Recruiting Organisation field se, ya
				// available na hone par site ka naam (job title kabhi nahi).
				'name'  => ( '' !== trim( (string) $org ) ) ? $org : get_bloginfo( 'name' ),
			),
			'employmentType' => np_schema_valid_employment_type( $emp_type ),
			// CRITICAL FIX: jobLocation ab hamesha present rehta hai.
			'jobLocation' => np_schema_build_job_locations( $id, $locs ),
		);

		// validThrough — robust multi-format date parsing; parse fail
		// hone par field silently skip (Google: galat date se behtar
		// hai field hi na do).
		$ts = np_schema_parse_date( $last );
		if ( $ts ) {
			$ts += DAY_IN_SECONDS - 1; // deadline wale din ke end (23:59:59) tak valid
			$schema['validThrough'] = gmdate( 'c', $ts );
		}

		if ( $qual ) {
			// qualifications (free-text) — Google isko enum ke against
			// validate nahi karta, isliye hamesha safe hai.
			$schema['qualifications'] = $qual;

			// educationRequirements — SIRF confident single-match par
			// structured valid-enum object bhejo (invalid-enum fix).
			$edu_enum = np_schema_map_education( $qual );
			if ( $edu_enum ) {
				$schema['educationRequirements'] = array(
					'@type'              => 'EducationalOccupationalCredential',
					'credentialCategory' => $edu_enum,
				);
			}
		}

		// totalJobOpenings — Google Integer expect karta hai, Text nahi
		if ( $total && preg_match( '/\d+/', (string) $total, $m ) ) {
			$schema['totalJobOpenings'] = (int) $m[0];
		}

		// baseSalary — sirf tab jab admin ne asli number diya ho
		$base_salary = np_schema_parse_salary( $salary );
		if ( $base_salary ) {
			$schema['baseSalary'] = $base_salary;
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
	}
}, 5 );

/* =========================================================
 * E2. SOCIAL/META FALLBACK GUARD — Open Graph, Twitter Card,
 *     meta description (technical SEO audit fix)
 * =========================================================
 * PROBLEM FOUND: Live homepage check karne par pata chala ki us
 * page par (jo ek khaali static "Home Page" hai, sirf front-page.php
 * template trigger karne ke liye) na meta description hai, na
 * og:image, na og:description, na twitter:description — kyunki
 * Yoast SEO in sabko underlying post/page ke excerpt/featured-image
 * se generate karta hai, aur wahan kuch nahi bhara hua tha. Isse:
 *   - Google search snippet me generic/khaali text dikhta hai
 *   - Social share (WhatsApp/Facebook/Twitter) par koi preview
 *     image nahi dikhti — click-through rate girta hai
 *   - Google Discover ke liye bhi ek strong image zaroori hoti hai
 *
 * FIX (duplicate-safe): Poora wp_head output buffer karke check
 * karte hain ki yeh tags GENUINELY gayab hain ya nahi — sirf tabhi
 * fallback add karte hain. Isse kabhi bhi duplicate meta tag nahi
 * banega, chahe Yoast/kisi aur plugin ka version/config kuch bhi ho,
 * aur agar future me asli content/image add ho jaye to yeh code
 * khud-ba-khud silent ho jaayega.
 * ======================================================= */
add_action( 'wp_head', function () { ob_start(); }, 0 );
add_action( 'wp_head', function () {
	$head = ob_get_clean();

	$logo_id  = get_theme_mod( 'custom_logo' );
	$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
	if ( ! $logo_url ) $logo_url = get_site_icon_url( 512 );

	$fallback = '';

	if ( $logo_url && false === stripos( $head, 'property="og:image"' ) ) {
		$fallback .= '<meta property="og:image" content="' . esc_url( $logo_url ) . '">' . "\n";
	}
	if ( $logo_url && false === stripos( $head, 'name="twitter:image"' ) ) {
		$fallback .= '<meta name="twitter:image" content="' . esc_url( $logo_url ) . '">' . "\n";
	}

	if ( false === stripos( $head, 'name="description"' ) ) {
		$desc = wp_strip_all_tags( get_bloginfo( 'description' ) );
		if ( '' === $desc ) {
			$desc = 'Latest Sarkari Naukri, Government Jobs, Admit Card, Result, Answer Key, Syllabus aur Admission updates — sabhi States aur Union Territories ke liye, roz updated.';
		}
		$fallback .= '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		if ( false === stripos( $head, 'property="og:description"' ) ) {
			$fallback .= '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		if ( false === stripos( $head, 'name="twitter:description"' ) ) {
			$fallback .= '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
	}

	echo $head . $fallback;
}, PHP_INT_MAX );

/* =========================================================
 * F. FLOATING JOIN BUTTON + BACK TO TOP + PROGRESS BAR
 * ======================================================= */
add_action( 'wp_footer', function () {
	$s = np_social_links();
	// Floating join (sirf tab dikhega jab link set ho)
	if ( ! empty( $s['whatsapp'] ) && '#' !== $s['whatsapp'] ) {
		echo '<a class="np-float np-float-wa" href="' . esc_url( $s['whatsapp'] ) . '" target="_blank" rel="noopener" aria-label="Join WhatsApp">💬<span>Join</span></a>';
	} elseif ( ! empty( $s['telegram'] ) && '#' !== $s['telegram'] ) {
		echo '<a class="np-float np-float-tg" href="' . esc_url( $s['telegram'] ) . '" target="_blank" rel="noopener" aria-label="Join Telegram">✈️<span>Join</span></a>';
	}
	echo '<button class="np-top" id="npTop" aria-label="Back to top">↑</button>';
	if ( is_single() ) echo '<div class="np-progress" id="npProgress"></div>';
} );

/* =========================================================
 * G. ADMIN DASHBOARD WIDGET (site stats)
 * ======================================================= */
add_action( 'wp_dashboard_setup', function () {
	wp_add_dashboard_widget( 'np_dashboard', '💙 NaukriPatra Stats', function () {
		$total = wp_count_posts()->publish;
		echo '<p style="font-size:15px"><b>Total Published Jobs:</b> ' . (int) $total . '</p>';
		echo '<table style="width:100%;border-collapse:collapse">';
		foreach ( np_main_sections() as $name => $slug ) {
			$t = get_term_by( 'slug', $slug, 'category' );
			$c = $t ? (int) $t->count : 0;
			echo '<tr style="border-bottom:1px solid #eee"><td style="padding:6px 0">' . esc_html( $name ) . '</td><td style="text-align:right;font-weight:700;color:#0b3fd8">' . $c . '</td></tr>';
		}
		echo '</table>';
		$trend = np_trending_query( 3 );
		if ( $trend->have_posts() ) {
			echo '<p style="margin-top:12px"><b>🔥 Trending (30 din):</b></p><ol style="margin:4px 0 0 18px">';
			while ( $trend->have_posts() ) { $trend->the_post();
				echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a> — ' . np_get_views( get_the_ID() ) . ' views</li>';
			}
			wp_reset_postdata();
			echo '</ol>';
		}
	} );
} );

/* =========================================================
 * H. SECURITY + PERFORMANCE HARDENING
 * ======================================================= */
// WP version chhupao (security)
remove_action( 'wp_head', 'wp_generator' );
// XML-RPC band (brute-force attacks rokta hai)
add_filter( 'xmlrpc_enabled', '__return_false' );
// Login errors generic (username guess na ho)
add_filter( 'login_errors', function () { return 'Galat details. Dobara try karo.'; } );
// Emoji scripts hatao (speed)
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
// Lazy-load images by default (WP core supports, ensure on)
add_filter( 'wp_lazy_loading_enabled', '__return_true' );

/* =========================================================
 * I. ROBOTS.TXT — internal search results ko crawl/index se bahar rakho
 * =========================================================
 * PROBLEM: WordPress ka built-in search (?s=...) URLs par thin/
 * duplicate-content pages banata hai (job listing hi dikhate hain,
 * jo already category pages par index ho chuki hain). Agar Google
 * inhe crawl+index kare, to crawl budget waste hota hai aur "duplicate
 * without user-selected canonical" jaisi Search Console warnings
 * aa sakti hain.
 * FIX: robots.txt me Disallow: /?s= add karo — mojooda robots.txt
 * (Sitemap line samet) ko bilkul preserve karte hue, sirf additive.
 * Duplicate-safe: pehle check karta hai ki rule already to nahi hai.
 * ======================================================= */
add_filter( 'robots_txt', function ( $output, $public ) {
	if ( '1' !== (string) $public ) return $output; // site "discourage search engines" par hai to kuch mat chhedo
	if ( false !== strpos( $output, 'Disallow: /?s=' ) ) return $output; // already present — duplicate mat karo

	$extra = "Disallow: /?s=\nDisallow: /*?*replytocom=\n";
	if ( false !== strpos( $output, 'Sitemap:' ) ) {
		$output = preg_replace( '/\nSitemap:/', "\n" . $extra . "\nSitemap:", $output, 1 );
	} else {
		$output = rtrim( $output ) . "\n" . $extra;
	}
	return $output;
}, 20, 2 );
