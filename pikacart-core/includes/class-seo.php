<?php
/**
 * SEO without a plugin: titles, meta description and keywords, canonical,
 * Open Graph and Twitter tags, schema.org (Organization, SoftwareApplication,
 * FAQPage, BreadcrumbList), robots.txt rules, and an SEO box on every page.
 *
 * When Yoast SEO or Rank Math is active, the meta tags are left to them.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_SEO {

	const META = '_pkc_seo';

	public static function init() {
		add_filter( 'robots_txt', array( __CLASS__, 'robots_txt' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta' ), 10, 2 );
		add_filter( 'the_content', array( __CLASS__, 'content_text' ), 20 );
		if ( self::other_plugin() ) {
			return;
		}
		add_filter( 'pre_get_document_title', array( __CLASS__, 'title' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'head' ), 1 );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ) );
	}

	public static function other_plugin() {
		return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' );
	}

	/** SEO values for the current public page, or null. */
	public static function current() {
		$site = pkc_setting( 'site_name', 'Pikacart' );
		$gal  = PKC_Public::context();
		if ( $gal && ! empty( $gal['seo'] ) ) {
			return $gal['seo'];
		}
		if ( $gal ) {
			return array(
				/* translators: %s: business name */
				'title'       => sprintf( __( 'ID Card Designs for Every Organisation · %s', 'pikacart' ), $site ),
				'description' => __( 'Browse more than 1,500 free ID card designs for schools, colleges, coaching institutes, companies, hospitals, NGOs, events, security agencies, clubs and media houses.', 'pikacart' ),
				'keywords'    => 'id card design, id card templates, id card maker',
				'image'       => '',
				'canonical'   => PKC_Public::url(),
			);
		}
		if ( is_front_page() ) {
			return array(
				'title'       => (string) pkc_setting( 'home_seo_title', '' ),
				'description' => (string) pkc_setting( 'home_seo_description', '' ),
				'keywords'    => (string) pkc_setting( 'home_seo_keywords', '' ),
				'image'       => (string) pkc_setting( 'home_seo_image', '' ),
				'canonical'   => home_url( '/' ),
			);
		}
		if ( is_singular() ) {
			$post = get_queried_object();
			$own  = get_post_meta( $post->ID, self::META, true );
			$own  = is_array( $own ) ? $own : array();
			return array(
				'title'       => $own['title'] ?? '',
				'description' => ! empty( $own['description'] ) ? $own['description'] : wp_trim_words( wp_strip_all_tags( has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content ) ), 28, '' ),
				'keywords'    => $own['keywords'] ?? '',
				'image'       => ! empty( $own['image'] ) ? $own['image'] : (string) get_the_post_thumbnail_url( $post, 'large' ),
				'canonical'   => '',
			);
		}
		return null;
	}

	public static function title( $title ) {
		$seo = self::current();
		if ( $seo && ! empty( $seo['title'] ) ) {
			return $seo['title'];
		}
		if ( is_singular() ) {
			return strtr(
				(string) pkc_setting( 'seo_title_pattern', '%title% · %site%' ),
				array(
					'%title%' => single_post_title( '', false ),
					'%site%'  => pkc_setting( 'site_name', 'Pikacart' ),
				)
			);
		}
		return $title;
	}

	public static function robots( $robots ) {
		$gal = get_query_var( 'pkc_idcard' ) ? PKC_Public::context() : null;
		if ( $gal ) {
			$robots['index']             = true;
			$robots['follow']            = true;
			$robots['max-image-preview'] = 'large';
		}
		return $robots;
	}

	private static function tag( $attr, $key, $value ) {
		if ( '' !== trim( (string) $value ) ) {
			printf( '<meta %s="%s" content="%s">' . "\n", esc_attr( $attr ), esc_attr( $key ), esc_attr( wp_strip_all_tags( (string) $value ) ) );
		}
	}

	public static function head() {
		if ( is_admin() || PKC_Router::current() || is_404() ) {
			return;
		}
		$seo = self::current();
		if ( ! $seo ) {
			return;
		}
		$site  = pkc_setting( 'site_name', 'Pikacart' );
		$desc  = $seo['description'] ? $seo['description'] : (string) pkc_setting( 'seo_site_description', '' );
		$image = $seo['image'] ? $seo['image'] : (string) pkc_setting( 'seo_default_image', '' );
		$url   = $seo['canonical'] ? $seo['canonical'] : ( is_singular() ? get_permalink() : home_url( '/' ) );
		$title = wp_get_document_title();

		echo "\n<!-- Pikacart SEO -->\n";
		self::tag( 'name', 'description', wp_html_excerpt( $desc, 300 ) );
		self::tag( 'name', 'keywords', $seo['keywords'] );
		if ( $seo['canonical'] ) {
			remove_action( 'wp_head', 'rel_canonical' );
			echo '<link rel="canonical" href="' . esc_url( $seo['canonical'] ) . '">' . "\n";
		}
		self::tag( 'property', 'og:type', is_front_page() ? 'website' : ( is_singular( 'post' ) ? 'article' : 'website' ) );
		self::tag( 'property', 'og:site_name', $site );
		self::tag( 'property', 'og:title', $title );
		self::tag( 'property', 'og:description', wp_html_excerpt( $desc, 300 ) );
		self::tag( 'property', 'og:url', $url );
		self::tag( 'property', 'og:locale', str_replace( '-', '_', get_bloginfo( 'language' ) ) );
		if ( $image ) {
			self::tag( 'property', 'og:image', $image );
		}
		self::tag( 'name', 'twitter:card', $image ? 'summary_large_image' : 'summary' );
		self::tag( 'name', 'twitter:title', $title );
		self::tag( 'name', 'twitter:description', wp_html_excerpt( $desc, 200 ) );
		if ( $image ) {
			self::tag( 'name', 'twitter:image', $image );
		}

		foreach ( self::schema( $url, $desc ) as $graph ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
		}
		echo "<!-- / Pikacart SEO -->\n";
	}

	/**
	 * FAQ entries shown on the homepage (owner's list, or the built-in one).
	 */
	public static function faq() {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) pkc_setting( 'home_faq', '' ) ) as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( 2 === count( $parts ) && '' !== $parts[0] && '' !== $parts[1] ) {
				$out[] = $parts;
			}
		}
		if ( $out ) {
			return $out;
		}
		$forever = 'trial' !== pkc_setting( 'free_mode', 'forever' );
		$mins    = (int) pkc_setting( 'trial_minutes', 120 );
		return array(
			array( __( 'Do I need to install any software?', 'pikacart' ), __( 'No. Pikacart works in your web browser on computer, tablet and phone.', 'pikacart' ) ),
			$forever
				? array( __( 'Is it really free?', 'pikacart' ), __( 'Yes, free forever with every feature. Free cards carry a small "Made with www.pikacart.in" watermark. Pro removes it.', 'pikacart' ) )
				/* translators: %d: minutes */
				: array( __( 'How does the free trial work?', 'pikacart' ), sprintf( __( 'Register and use every feature free for %d minutes. Downloads during the trial carry a watermark.', 'pikacart' ), $mins ) ),
			array( __( 'Are the premium designs included?', 'pikacart' ), __( 'Yes. Every design, from simple to premium, can be used on every plan. Pro removes the watermark so the cards are ready to hand out.', 'pikacart' ) ),
			array( __( 'Can I pay without autopay?', 'pikacart' ), __( 'Yes. You can pay once for one month by UPI, card or net banking if your bank does not support autopay.', 'pikacart' ) ),
			array( __( 'Which printers and holders are supported?', 'pikacart' ), __( 'Files are exact millimetre size at 300 DPI, so they work with PVC card printers, inkjet and laser printers and all common holder sizes.', 'pikacart' ) ),
			array( __( 'Can I use my own card design?', 'pikacart' ), __( 'Yes. Upload the front and back of your design and place the fields yourself, or send it to our Design on Demand team and we set it up within hours.', 'pikacart' ) ),
			array( __( 'Is my students\' data safe?', 'pikacart' ), __( 'Each organisation\'s data is kept separate and private. You choose what the QR verification page shows, and you can delete your data any time.', 'pikacart' ) ),
		);
	}

	/** Real testimonials entered by the owner (Name | Organisation | Quote). */
	public static function testimonials() {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) pkc_setting( 'home_testimonials', '' ) ) as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 3 ) );
			if ( 3 === count( $parts ) && '' !== $parts[2] ) {
				$out[] = $parts;
			}
		}
		return $out;
	}

	private static function schema( $url, $desc ) {
		$site  = pkc_setting( 'site_name', 'Pikacart' );
		$logo  = (string) pkc_setting( 'logo_url', '' );
		$org   = array_filter(
			array(
				'@context'     => 'https://schema.org',
				'@type'        => 'Organization',
				'name'         => $site,
				'url'          => home_url( '/' ),
				'logo'         => $logo,
				'email'        => pkc_support_email(),
				'telephone'    => (string) pkc_setting( 'contact_phone', '' ),
				'slogan'       => (string) pkc_setting( 'tagline', '' ),
				'sameAs'       => array_values( array_filter( array( pkc_setting( 'social_facebook', '' ), pkc_setting( 'social_instagram', '' ), pkc_setting( 'social_youtube', '' ) ) ) ),
			)
		);
		$out   = array( $org );
		if ( is_front_page() ) {
			$out[] = array(
				'@context'            => 'https://schema.org',
				'@type'               => 'SoftwareApplication',
				'name'                => $site,
				'applicationCategory' => 'BusinessApplication',
				'operatingSystem'     => 'Web browser',
				'description'         => $desc,
				'url'                 => home_url( '/' ),
				'offers'              => array(
					'@type'         => 'Offer',
					'price'         => number_format( PKC_Billing::default_plan_price() / 100, 2, '.', '' ),
					'priceCurrency' => 'INR',
				),
			);
			$out[] = array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => array_map(
					function ( $q ) {
						return array(
							'@type'          => 'Question',
							'name'           => $q[0],
							'acceptedAnswer' => array(
								'@type' => 'Answer',
								'text'  => $q[1],
							),
						);
					},
					self::faq()
				),
			);
		}
		$gal = PKC_Public::context();
		if ( $gal ) {
			$items = array(
				array( __( 'Home', 'pikacart' ), home_url( '/' ) ),
				array( __( 'ID card designs', 'pikacart' ), PKC_Public::url() ),
			);
			if ( $gal['category'] ) {
				$items[] = array( $gal['category']['name'], PKC_Public::url( $gal['category']['slug'] ) );
				if ( get_query_var( 'pkc_sub' ) ) {
					$items[] = array( $gal['subtype']['name'], PKC_Public::url( $gal['category']['slug'], $gal['subtype']['slug'] ) );
				}
			}
			$out[] = array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => array_map(
					function ( $it, $i ) {
						return array(
							'@type'    => 'ListItem',
							'position' => $i + 1,
							'name'     => $it[0],
							'item'     => $it[1],
						);
					},
					$items,
					array_keys( $items )
				),
			);
		}
		return $out;
	}

	public static function robots_txt( $output, $public ) {
		if ( ! $public ) {
			return $output;
		}
		$path    = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$path    = $path ? rtrim( $path, '/' ) : '';
		$rules = '';
		foreach ( array( 'app/', 'verify/', 'fill/', 'login/', 'register/', 'forgot-password/', 'reset-password/', 'verify-email/' ) as $p ) {
			$rules .= 'Disallow: ' . $path . '/' . $p . "\n";
		}
		if ( preg_match( '/^User-agent:\s*\*\s*$/mi', $output ) ) {
			// Add the rules to the existing "User-agent: *" group.
			$output = preg_replace( '/^(User-agent:\s*\*\s*\R)/mi', '$1' . $rules, $output, 1 );
		} else {
			$output .= "\nUser-agent: *\n" . $rules;
		}
		if ( false === strpos( $output, 'Sitemap:' ) && function_exists( 'get_sitemap_url' ) && get_sitemap_url( 'index' ) ) {
			$output .= 'Sitemap: ' . esc_url_raw( get_sitemap_url( 'index' ) ) . "\n";
		}
		return $output;
	}

	/* ---------- SEO box on pages and posts ---------- */

	public static function meta_box() {
		foreach ( array( 'page', 'post' ) as $type ) {
			add_meta_box( 'pkc-seo', __( 'Pikacart SEO', 'pikacart' ), array( __CLASS__, 'meta_box_html' ), $type, 'normal', 'low' );
		}
	}

	public static function meta_box_html( $post ) {
		$v = get_post_meta( $post->ID, self::META, true );
		$v = is_array( $v ) ? $v : array();
		wp_nonce_field( 'pkc_seo_' . $post->ID, 'pkc_seo_nonce' );
		if ( self::other_plugin() ) {
			echo '<p>' . esc_html__( 'An SEO plugin is active, so it controls the meta tags. The SEO text below is still shown at the bottom of the page.', 'pikacart' ) . '</p>';
		}
		$fields = array(
			'title'       => array( __( 'SEO title', 'pikacart' ), 'text' ),
			'description' => array( __( 'Meta description', 'pikacart' ), 'textarea' ),
			'keywords'    => array( __( 'Focus keywords', 'pikacart' ), 'text' ),
			'image'       => array( __( 'Social share image URL', 'pikacart' ), 'url' ),
			'text'        => array( __( 'SEO text shown at the bottom of the page', 'pikacart' ), 'textarea' ),
		);
		foreach ( $fields as $k => $f ) {
			echo '<p><label style="display:block;font-weight:600;margin-bottom:4px" for="pkc-seo-' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label>';
			if ( 'textarea' === $f[1] ) {
				echo '<textarea class="widefat" rows="3" id="pkc-seo-' . esc_attr( $k ) . '" name="pkc_seo[' . esc_attr( $k ) . ']">' . esc_textarea( $v[ $k ] ?? '' ) . '</textarea>';
			} else {
				echo '<input class="widefat" type="' . esc_attr( $f[1] ) . '" id="pkc-seo-' . esc_attr( $k ) . '" name="pkc_seo[' . esc_attr( $k ) . ']" value="' . esc_attr( $v[ $k ] ?? '' ) . '">';
			}
			echo '</p>';
		}
	}

	public static function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['pkc_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pkc_seo_nonce'] ) ), 'pkc_seo_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$raw = isset( $_POST['pkc_seo'] ) && is_array( $_POST['pkc_seo'] ) ? wp_unslash( $_POST['pkc_seo'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitized by clean_seo().
		update_post_meta( $post_id, self::META, PKC_Admin_Catalog::clean_seo( $raw ) );
	}

	/** SEO text block at the bottom of a page. */
	public static function content_text( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$v = get_post_meta( get_the_ID(), self::META, true );
		if ( is_array( $v ) && ! empty( $v['text'] ) ) {
			$content .= '<section class="seo-text">' . wpautop( wp_kses_post( $v['text'] ) ) . '</section>';
		}
		return $content;
	}
}
