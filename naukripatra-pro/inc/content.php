<?php
/**
 * Article cleanup: strip imported inline styling, wrap tables, add TOC, alt text and in-article ads.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'the_content', 'np_clean_content', 20 );
function np_clean_content( $html ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() || ( ! is_singular( 'post' ) && ! is_page() ) ) {
		return $html;
	}
	// Remove presentational attributes on text/table tags (never on img/iframe).
	$html = preg_replace_callback(
		'#<(table|thead|tbody|tfoot|tr|td|th|p|div|span|h[1-6]|ul|ol|li|a|strong|b|em|i|u|center|section|article)\b([^>]*)>#i',
		function ( $m ) {
			$attrs = preg_replace( '#\s+(?:style|bgcolor|color|face|size|border|cellpadding|cellspacing|width|height|align|valign|class)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $m[2] );
			return '<' . $m[1] . $attrs . '>';
		},
		$html
	);
	$html = preg_replace( '#</?(?:font|center)\b[^>]*>#i', '', $html );
	$html = preg_replace( '#<(p|div|span)[^>]*>(?:\s|&nbsp;|<br\s*/?>)*</\1>#i', '', $html );

	// Alt text from the title when missing.
	$title = esc_attr( get_the_title() );
	$html  = preg_replace_callback( '#<img\b([^>]*)>#i', function ( $m ) use ( $title ) {
		$a = $m[1];
		if ( ! preg_match( '#\balt\s*=\s*"[^"]+"#i', $a ) ) {
			$a = preg_replace( '#\balt\s*=\s*(?:""|\'\')#i', '', $a ) . ' alt="' . $title . '"';
		}
		return '<img' . $a . '>';
	}, $html );

	// Mobile-safe tables.
	$html = preg_replace( '#(<table\b.*?</table>)#is', '<div class="np-table-wrap">$1</div>', $html );

	if ( ! is_singular( 'post' ) ) {
		return $html;
	}
	$html = np_add_toc( $html );
	return np_inject_ads( $html );
}

/** Table of contents for long posts (4+ h2/h3 and 600+ words). */
function np_add_toc( $html ) {
	if ( str_word_count( wp_strip_all_tags( $html ) ) < 600 ) {
		return $html;
	}
	$items = array();
	$n     = 0;
	$html  = preg_replace_callback( '#<h([23])([^>]*)>(.*?)</h\1>#is', function ( $m ) use ( &$items, &$n ) {
		$text = trim( wp_strip_all_tags( $m[3] ) );
		if ( '' === $text ) {
			return $m[0];
		}
		$id = 'np-s' . ( ++$n ) . '-' . sanitize_title( $text );
		$items[] = array( $id, $text, $m[1] );
		return '<h' . $m[1] . ' id="' . esc_attr( $id ) . '"' . $m[2] . '>' . $m[3] . '</h' . $m[1] . '>';
	}, $html );
	if ( count( $items ) < 4 ) {
		return $html;
	}
	$toc = '<nav class="np-toc" aria-label="' . esc_attr__( 'Table of contents', 'naukripatra' ) . '"><strong>' . esc_html__( 'In this article', 'naukripatra' ) . '</strong><ol>';
	foreach ( $items as $it ) {
		$toc .= '<li class="np-toc__l' . (int) $it[2] . '"><a href="#' . esc_attr( $it[0] ) . '">' . esc_html( $it[1] ) . '</a></li>';
	}
	return $toc . '</ol></nav>' . $html;
}

/** Ads after paragraph 2, then every 7 paragraphs (max 3), only outside tables and lists. */
function np_inject_ads( $html ) {
	$ad = np_ad_html( 'article', 'np-ad--inline' );
	if ( ! $ad ) {
		return $html;
	}
	$parts = preg_split( '#(</p>)#i', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	$out   = '';
	$paras = 0;
	$ads   = 0;
	$next  = 2;
	$depth = 0;
	for ( $i = 0; $i < count( $parts ); $i += 2 ) {
		$chunk = $parts[ $i ];
		$out  .= $chunk;
		$depth += preg_match_all( '#<(?:table|ul|ol)\b#i', $chunk ) - preg_match_all( '#</(?:table|ul|ol)>#i', $chunk );
		if ( isset( $parts[ $i + 1 ] ) ) {
			$out .= $parts[ $i + 1 ];
			++$paras;
			if ( $paras >= $next && $depth <= 0 && $ads < 3 ) {
				$out .= ( 0 === $ads ? $ad : np_ad_html( 'article', 'np-ad--inline' ) );
				++$ads;
				$next = $paras + 7;
			}
		}
	}
	return $out;
}
