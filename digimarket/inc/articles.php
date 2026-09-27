<?php
/**
 * Articles: WordPress posts served under /articles/, list view page,
 * table of contents, reading time, related products and inline shortcodes.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

function dm_articles_url( $args = array() ) {
	$url = dm_pretty_permalinks() ? dm_pretty_url( 'articles/' ) : add_query_arg( 'dm_route', 'articles', home_url( '/' ) );
	return $args ? add_query_arg( $args, $url ) : $url;
}

add_action( 'init', function () {
	$p = dm_rule_prefix();
	add_rewrite_rule( $p . 'articles/?$', 'index.php?dm_route=articles', 'top' );
	add_rewrite_rule( $p . 'articles/page/([0-9]+)/?$', 'index.php?dm_route=articles&paged=$matches[1]', 'top' );
	add_rewrite_rule( $p . 'articles/([^/]+)/?$', 'index.php?name=$matches[1]', 'top' );
}, 22 );

add_filter( 'post_link', function ( $url, $post ) {
	if ( 'post' !== $post->post_type || ! dm_pretty_permalinks() || ! $post->post_name || in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ), true ) ) {
		return $url;
	}
	return dm_pretty_url( 'articles/' . $post->post_name . '/' );
}, 10, 2 );

add_filter( 'template_include', function ( $template ) {
	return 'articles' === dm_route() ? DM_DIR . '/templates/articles.php' : $template;
}, 100 );

/* One URL per article; blog index/category archives fold into /articles/. */
add_action( 'template_redirect', function () {
	if ( is_preview() || ! dm_pretty_permalinks() ) {
		return;
	}
	if ( is_singular( 'post' ) ) {
		$want = wp_parse_url( get_permalink(), PHP_URL_PATH );
		$have = wp_parse_url( dm_current_url(), PHP_URL_PATH );
		if ( $want && $have && untrailingslashit( $want ) !== untrailingslashit( $have ) ) {
			wp_safe_redirect( get_permalink(), 301 );
			exit;
		}
	} elseif ( is_home() && ! is_front_page() ) {
		wp_safe_redirect( dm_articles_url(), 301 );
		exit;
	} elseif ( is_category() ) {
		wp_safe_redirect( dm_articles_url( array( 'topic' => get_queried_object()->slug ) ), 301 );
		exit;
	}
}, 3 );

function dm_reading_minutes( $content ) {
	return max( 1, (int) ceil( str_word_count( wp_strip_all_tags( $content ) ) / 200 ) );
}

/**
 * Add ids to h2/h3 and return [toc items, content].
 */
function dm_article_toc( $content ) {
	$items = array();
	$used  = array();
	$html  = preg_replace_callback(
		'#<h([23])([^>]*)>(.*?)</h\1>#is',
		function ( $m ) use ( &$items, &$used ) {
			$text = trim( wp_strip_all_tags( $m[3] ) );
			if ( '' === $text ) {
				return $m[0];
			}
			if ( preg_match( '/\sid=["\']([^"\']+)["\']/', $m[2], $idm ) ) {
				$id = $idm[1];
				$tag = $m[0];
			} else {
				$id = sanitize_title( $text );
				$id = $id ? $id : 'section';
				$base = $id;
				$n    = 2;
				while ( isset( $used[ $id ] ) ) {
					$id = $base . '-' . $n++;
				}
				$tag = '<h' . $m[1] . $m[2] . ' id="' . esc_attr( $id ) . '">' . $m[3] . '</h' . $m[1] . '>';
			}
			$used[ $id ] = 1;
			$items[]     = array( (int) $m[1], $text, $id );
			return $tag;
		},
		$content
	);
	return array( $items, $html );
}

/**
 * Products linked from an article (explicit picks + product links in the text).
 */
function dm_article_products( $post_id, $content ) {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, '_dm_article_products', true ) ) ) );
	if ( preg_match_all( '#href=["\']([^"\']*/product/[^"\']+)["\']#i', $content, $m ) ) {
		foreach ( array_unique( $m[1] ) as $href ) {
			$id = url_to_postid( $href );
			if ( $id && 'dm_product' === get_post_type( $id ) ) {
				$ids[] = $id;
			}
		}
	}
	$ids = array_values( array_unique( $ids ) );
	return array_filter(
		$ids,
		function ( $id ) {
			return 'publish' === get_post_status( $id ) && 'dm_product' === get_post_type( $id );
		}
	);
}

add_action( 'add_meta_boxes_post', function () {
	add_meta_box( 'dm_article_extra', __( 'PikaCart: products & SEO', 'digimarket' ), function ( $post ) {
		wp_nonce_field( 'dm_article_save', 'dm_article_nonce' );
		$sel  = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_dm_article_products', true ) ) ) );
		$prod = get_posts( array( 'post_type' => 'dm_product', 'post_status' => 'publish', 'numberposts' => 300, 'orderby' => 'title', 'order' => 'ASC' ) );
		echo '<div class="dm-seo" data-seo-box data-post="' . (int) $post->ID . '" data-type="post">';
		echo '<div class="dm-serp"><div class="dm-serp-site">' . esc_html( get_bloginfo( 'name' ) ) . ' <span>' . esc_html( untrailingslashit( home_url() ) ) . ' › articles</span></div><div class="dm-serp-title" data-serp-title></div><div class="dm-serp-desc" data-serp-desc></div></div>';
		echo '<p><label>' . esc_html__( 'Focus keyword', 'digimarket' ) . '<br><input type="text" class="large-text" id="dmf_focus_kw" name="dma[focus_kw]" value="' . esc_attr( get_post_meta( $post->ID, '_dm_focus_kw', true ) ) . '"></label></p>';
		echo '<p><label>' . esc_html__( 'SEO title', 'digimarket' ) . '<br><input type="text" class="large-text" id="dm_meta_title" name="dma[meta_title]" data-seo-title maxlength="80" value="' . esc_attr( get_post_meta( $post->ID, '_dm_meta_title', true ) ) . '"></label><br><small data-count-for="dm_meta_title"></small></p>';
		echo '<p><label>' . esc_html__( 'Meta description', 'digimarket' ) . '<br><textarea class="large-text" rows="3" id="dm_meta_desc" name="dma[meta_desc]" data-seo-desc maxlength="300">' . esc_textarea( get_post_meta( $post->ID, '_dm_meta_desc', true ) ) . '</textarea></label><br><small data-count-for="dm_meta_desc"></small></p>';
		echo '<ul class="dm-seo-checks" data-seo-checks></ul><div class="dm-seo-dupes" data-seo-dupes></div></div>';
		echo '<p><strong>' . esc_html__( 'Products mentioned', 'digimarket' ) . '</strong><br><small>' . esc_html__( 'Shown as cards in the article. Product links in the text are added automatically.', 'digimarket' ) . '</small></p><select name="dma[products][]" multiple size="8" style="width:100%">';
		foreach ( $prod as $p ) {
			echo '<option value="' . (int) $p->ID . '"' . selected( in_array( $p->ID, $sel, true ), true, false ) . '>' . esc_html( $p->post_title ) . '</option>';
		}
		echo '</select><p class="description">' . esc_html__( 'Shortcodes: [pikacart_product id="123"] shows a product card, [pikacart_cta service="45"] shows a WhatsApp enquiry box.', 'digimarket' ) . '</p>';
	}, 'post', 'normal', 'high' );
} );

add_action( 'save_post_post', function ( $pid ) {
	if ( ! isset( $_POST['dm_article_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dm_article_nonce'] ) ), 'dm_article_save' ) || ! current_user_can( 'edit_post', $pid ) ) {
		return;
	}
	$d = wp_unslash( (array) ( $_POST['dma'] ?? array() ) ); // phpcs:ignore
	update_post_meta( $pid, '_dm_focus_kw', sanitize_text_field( $d['focus_kw'] ?? '' ) );
	update_post_meta( $pid, '_dm_meta_title', sanitize_text_field( $d['meta_title'] ?? '' ) );
	update_post_meta( $pid, '_dm_meta_desc', sanitize_textarea_field( $d['meta_desc'] ?? '' ) );
	update_post_meta( $pid, '_dm_article_products', implode( ',', array_map( 'absint', (array) ( $d['products'] ?? array() ) ) ) );
} );

/* Inline shortcodes for articles. */
add_shortcode( 'pikacart_product', function ( $atts ) {
	$a   = shortcode_atts( array( 'id' => 0 ), $atts );
	$pid = absint( $a['id'] );
	if ( ! $pid || 'publish' !== get_post_status( $pid ) || 'dm_product' !== get_post_type( $pid ) ) {
		return '';
	}
	ob_start();
	echo '<div class="dm-inline-product">';
	dm_mini_product_row( $pid );
	echo '</div>';
	return ob_get_clean();
} );

add_shortcode( 'pikacart_cta', function ( $atts ) {
	$a   = shortcode_atts( array( 'service' => 0, 'text' => '' ), $atts );
	$pid = absint( $a['service'] );
	$wa  = $pid ? dm_service_whatsapp_url( $pid ) : dm_whatsapp_url( __( 'Hi, I read your article and want to discuss a website.', 'digimarket' ) );
	$txt = $a['text'] ? $a['text'] : ( $pid ? sprintf( /* translators: %s */ __( 'Need a %s? Let’s talk on WhatsApp — no payment until we agree.', 'digimarket' ), get_the_title( $pid ) ) : __( 'Need a website like this? Let’s talk on WhatsApp.', 'digimarket' ) );
	if ( ! $wa ) {
		return '';
	}
	return '<aside class="dm-inline-cta"><p>' . esc_html( $txt ) . '</p><a class="dm-btn dm-btn-wa" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener" data-track="whatsapp" data-ref="' . (int) $pid . '">' . dm_icon_whatsapp( 18 ) . ' ' . esc_html__( 'Chat on WhatsApp', 'digimarket' ) . '</a></aside>';
} );

/**
 * Compact horizontal product row (used in articles and search suggestions).
 */
function dm_mini_product_row( $pid ) {
	echo '<a class="dm-mini-row" href="' . esc_url( get_permalink( $pid ) ) . '"><span class="dm-mini-row-img">' . dm_product_thumb( $pid, 'thumbnail' ) . '</span><span class="dm-mini-row-body"><strong>' . esc_html( get_the_title( $pid ) ) . '</strong><span>' . wp_kses_post( dm_card_price_html( $pid ) ) . '</span></span>' . dm_icon( 'chev-r', 18 ) . '</a>'; // phpcs:ignore
}

/**
 * Wrap tables in articles so they scroll on phones; lazy YouTube embeds.
 */
add_filter( 'the_content', function ( $html ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() ) {
		return $html;
	}
	$html = preg_replace( '#<table(.*?)</table>#is', '<div class="dm-table-scroll"><table$1</table></div>', $html );
	return $html;
}, 20 );
