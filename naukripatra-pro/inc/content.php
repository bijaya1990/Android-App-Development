<?php
/**
 * Article cleanup: strip imported inline styling, wrap tables, add TOC, alt text and in-article ads.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'the_content', 'nppro_clean_content', 20 );

/**
 * Strip inline colours, backgrounds, sizes and classes from every tag except media, and drop <font>/<center>.
 * Shared by the website reader and the REST output so imported HTML is readable in light AND dark.
 */
function nppro_strip_presentation( $html ) {
	$html = preg_replace_callback(
		'#<(?!(?:img|iframe|video|audio|source|svg|path|script|style)\b)([a-z][a-z0-9]*)\b([^>]*)>#i',
		function ( $m ) {
			$attrs = preg_replace( '#\s+(?:style|bgcolor|color|face|size|border|bordercolor|cellpadding|cellspacing|width|height|align|valign|background|class)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $m[2] );
			return '<' . $m[1] . $attrs . '>';
		},
		$html
	);
	return preg_replace( '#</?(?:font|center)\b[^>]*>#i', '', $html );
}
function nppro_clean_content( $html ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		// App / REST output: same JSON keys, but readable HTML (no inline colours). No ads, TOC or wrappers.
		return nppro_opt( 'rest_clean' ) ? nppro_tables_to_cards( nppro_strip_presentation( $html ), true ) : $html;
	}
	if ( is_admin() || ! in_the_loop() || ! is_main_query() || ( ! is_singular( 'post' ) && ! is_page() ) ) {
		return $html;
	}
	$html = nppro_strip_presentation( $html );
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

	$html = nppro_tables_to_cards( $html, false );

	// Mobile-safe tables (the ones that stay tables).
	$html = preg_replace( '#(<table\b.*?</table>)#is', '<div class="np-table-wrap">$1</div>', $html );

	if ( ! is_singular( 'post' ) ) {
		return $html;
	}
	$html = nppro_add_toc( $html );
	return nppro_inject_ads( $html );
}

/** Table of contents for long posts (4+ h2/h3 and 600+ words). */
function nppro_add_toc( $html ) {
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
	$toc = '<nav class="np-toc" aria-label="' . esc_attr__( 'Table of contents', 'naukripatra' ) . '"><strong>' . esc_html__( 'Quick Links', 'naukripatra' ) . '</strong><ol>';
	foreach ( $items as $it ) {
		$toc .= '<li class="np-toc__l' . (int) $it[2] . '"><a href="#' . esc_attr( $it[0] ) . '">' . esc_html( $it[1] ) . '</a></li>';
	}
	$toc .= '</ol></nav>';
	if ( 'top' === nppro_opt( 'toc_mode' ) ) {
		return $toc . $html;
	}
	return 'off' === nppro_opt( 'toc_mode' ) ? $html : $html . $toc; // Default: Quick Links at the END of the article (anchors stay in the headings).
}

/** Ads after paragraph 2, then every 7 paragraphs (max 3), only outside tables and lists. */
function nppro_inject_ads( $html ) {
	$ad = nppro_ad_html( 'article', 'np-ad--inline' );
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
				$out .= ( 0 === $ads ? $ad : nppro_ad_html( 'article', 'np-ad--inline' ) );
				++$ads;
				$next = $paras + 7;
			}
		}
	}
	return $out;
}


/**
 * The theme already prints a clean Overview table from the post data. If the article carries its own
 * overview-style table (3+ label rows), remove that first table so the overview is not shown twice.
 */
function nppro_strip_overview_table( $html, $post_id ) {
	if ( count( nppro_overview_rows( $post_id ) ) < 3 ) {
		return $html;
	}
	$syn  = nppro_synonyms();
	$done = false;
	return preg_replace_callback( '#<table\b.*?</table>#is', function ( $m ) use ( $syn, &$done ) {
		if ( $done || ! preg_match_all( '#<tr[^>]*>(.*?)</tr>#is', $m[0], $rows ) ) {
			return $m[0];
		}
		$hits = 0;
		foreach ( $rows[1] as $row ) {
			if ( preg_match_all( '#<t[dh][^>]*>(.*?)</t[dh]>#is', $row, $c ) && count( $c[1] ) >= 2 ) {
				$label = nppro_norm_label( $c[1][0] );
				foreach ( $syn as $labels ) {
					if ( in_array( $label, $labels, true ) ) {
						++$hits;
						break;
					}
				}
			}
		}
		if ( $hits >= 3 ) {
			$done = true;
			return '';
		}
		return $m[0];
	}, $html );
}

/**
 * Turn article tables into cards that read well on phones, in light and dark mode.
 * - Two-column tables (Important Dates, key/value) -> one card with label/value rows.
 * - Header + rows tables (vacancy, category-wise posts) -> one card per row, labelled fields.
 * Very large tables (12+ rows, 7+ columns), nested tables and rowspan tables stay as plain tables.
 * $inline = true adds neutral inline styles for the app/REST (no theme CSS there; colours stay inherited).
 *
 * @param string $html   Article HTML.
 * @param bool   $inline Inline styles for REST output.
 * @return string
 */
function nppro_tables_to_cards( $html, $inline = false ) {
	return preg_replace_callback( '#<table\b.*?</table>#is', function ( $m ) use ( $inline ) {
		$tbl = $m[0];
		if ( substr_count( strtolower( $tbl ), '<table' ) > 1 || preg_match( '#rowspan\s*=#i', $tbl ) || ! preg_match_all( '#<tr\b[^>]*>(.*?)</tr>#is', $tbl, $r ) ) {
			return $tbl;
		}
		$rows = array();
		foreach ( $r[1] as $row ) {
			if ( ! preg_match_all( '#<(t[dh])\b([^>]*)>(.*?)</\1>#is', $row, $c, PREG_SET_ORDER ) ) {
				continue;
			}
			$cells = array();
			$th    = true;
			foreach ( $c as $x ) {
				$cells[] = trim( $x[3] );
				$th      = $th && 'th' === strtolower( $x[1] );
			}
			$rows[] = array( $cells, $th );
		}
		if ( ! $rows ) {
			return $tbl;
		}
		$title = '';
		if ( 1 === count( $rows[0][0] ) && count( $rows ) > 1 ) { // Full-width title row.
			$title = wp_strip_all_tags( $rows[0][0][0] );
			array_shift( $rows );
		}
		$cols = 0;
		foreach ( $rows as $row ) {
			$cols = max( $cols, count( $row[0] ) );
		}
		if ( $cols < 2 || $cols > 6 || count( $rows ) > 13 ) {
			return $tbl;
		}
		$st = function ( $k ) use ( $inline ) {
			$s = array(
				'box'   => 'border:1px solid rgba(128,128,128,.4);border-radius:10px;margin:14px 0;overflow:hidden',
				'title' => 'padding:9px 14px;font-weight:700;background:rgba(128,128,128,.16)',
				'row'   => 'padding:9px 14px;border-top:1px solid rgba(128,128,128,.28)',
				'k'     => 'display:block;font-size:.85em;font-weight:700;opacity:.72',
				'v'     => 'display:block',
				'grid'  => 'margin:14px 0',
				'card'  => 'border:1px solid rgba(128,128,128,.4);border-radius:10px;margin:0 0 10px;overflow:hidden',
				'ctit'  => 'padding:9px 14px;font-weight:700;background:rgba(128,128,128,.16)',
			);
			return $inline ? ' style="' . $s[ $k ] . '"' : '';
		};
		$cl = function ( $c ) use ( $inline ) {
			return $inline ? '' : ' class="' . $c . '"';
		};
		$val = function ( $v ) {
			$t = trim( wp_strip_all_tags( $v ) );
			return '' === $t ? '&mdash;' : $v;
		};
		$out = '';
		$has_head = $rows[0][1] || ( $cols >= 3 && count( $rows ) >= 3 );
		if ( 2 === $cols && ! ( $rows[0][1] && count( $rows ) < 2 ) ) { // Key / value card: label | value side by side.
			$head = '';
			if ( $rows[0][1] ) { // "Event | Date" style header row.
				$h    = array_shift( $rows );
				$head = '<div' . $cl( 'np-kv__row np-kv__head' ) . ( $inline ? ' style="display:flex;gap:12px;padding:9px 14px;font-weight:700;background:rgba(128,128,128,.16)"' : '' ) . '><div' . $cl( 'np-kv__k' ) . ( $inline ? ' style="flex:0 0 38%"' : '' ) . '>' . $val( $h[0][0] ) . '</div><div' . $cl( 'np-kv__v' ) . ( $inline ? ' style="flex:1"' : '' ) . '>' . $val( isset( $h[0][1] ) ? $h[0][1] : '' ) . '</div></div>';
			}
			$out = '<div' . $cl( 'np-kv' ) . $st( 'box' ) . '>' . ( $title ? '<div' . $cl( 'np-kv__title' ) . $st( 'title' ) . '>' . esc_html( $title ) . '</div>' : '' ) . $head;
			foreach ( $rows as $i => $row ) {
				$k    = isset( $row[0][0] ) ? $row[0][0] : '';
				$v    = isset( $row[0][1] ) ? $row[0][1] : '';
				$top  = ( $i || $title || $head ) ? 'border-top:1px solid rgba(128,128,128,.28);' : '';
				$out .= '<div' . $cl( 'np-kv__row' ) . ( $inline ? ' style="display:flex;gap:12px;padding:9px 14px;' . $top . '"' : '' ) . '><div' . $cl( 'np-kv__k' ) . ( $inline ? ' style="flex:0 0 38%;font-weight:700;opacity:.8"' : '' ) . '>' . $val( $k ) . '</div><div' . $cl( 'np-kv__v' ) . ( $inline ? ' style="flex:1"' : '' ) . '>' . $val( $v ) . '</div></div>';
			}
			return $out . '</div>';
		}
		if ( ! $has_head ) {
			return $tbl;
		}
		// Header + rows with 3+ columns (vacancy lists etc.): a clean, compact, full-width table.
		$head = array_shift( $rows );
		$ts   = $inline ? array(
			'wrap'  => ' style="overflow-x:auto;margin:14px 0"',
			'table' => ' style="width:100%;border-collapse:collapse;border:1px solid rgba(128,128,128,.4)"',
			'th'    => ' style="padding:8px 10px;text-align:left;font-weight:700;background:rgba(128,128,128,.18);border:1px solid rgba(128,128,128,.4)"',
			'td'    => 'padding:8px 10px;border:1px solid rgba(128,128,128,.35);vertical-align:top',
		) : array( 'wrap' => ' class="np-vtable-wrap"', 'table' => ' class="np-vtable"', 'th' => '', 'td' => '' );
		$out = '<div' . $ts['wrap'] . '>' . ( $title ? '<div' . ( $inline ? ' style="font-weight:700;margin:0 0 8px"' : ' class="np-cards__title"' ) . '>' . esc_html( $title ) . '</div>' : '' ) . '<table' . $ts['table'] . '><thead><tr>';
		foreach ( $head[0] as $h ) {
			$out .= '<th' . $ts['th'] . '>' . $val( $h ) . '</th>';
		}
		$out .= '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$total = (bool) preg_match( '/^\s*(grand\s+)?total/i', wp_strip_all_tags( $row[0][0] ) );
			$out  .= '<tr' . ( $total && ! $inline ? ' class="np-vtable__total"' : '' ) . '>';
			for ( $i = 0; $i < $cols; $i++ ) {
				$cv    = isset( $row[0][ $i ] ) ? $row[0][ $i ] : '';
				$plain = trim( wp_strip_all_tags( $cv ) );
				$num   = $i > 0 && '' !== $plain && preg_match( '/^[\d,.\s%\x{20B9}+\/-]+$/u', $plain );
				if ( $inline ) {
					$out .= '<td style="' . $ts['td'] . ( $num ? ';text-align:center' : '' ) . ( $total ? ';font-weight:700;background:rgba(128,128,128,.12)' : '' ) . '">' . $val( $cv ) . '</td>';
				} else {
					$out .= '<td' . ( $num ? ' class="np-num"' : '' ) . '>' . $val( $cv ) . '</td>';
				}
			}
			$out .= '</tr>';
		}
		return $out . '</tbody></table></div>';
	}, $html );
}


/** True when the article already contains its own overview-style table (3+ known label rows). */
function nppro_article_has_overview( $post_id ) {
	$content = (string) get_post_field( 'post_content', $post_id );
	if ( ! preg_match_all( '#<table\b.*?</table>#is', $content, $tables ) ) {
		return false;
	}
	$syn = nppro_synonyms();
	foreach ( $tables[0] as $tbl ) {
		$hits = 0;
		if ( preg_match_all( '#<tr[^>]*>(.*?)</tr>#is', $tbl, $rows ) ) {
			foreach ( $rows[1] as $row ) {
				if ( preg_match_all( '#<t[dh][^>]*>(.*?)</t[dh]>#is', $row, $c ) && count( $c[1] ) >= 2 ) {
					$label = nppro_norm_label( $c[1][0] );
					foreach ( $syn as $labels ) {
						foreach ( $labels as $l ) {
							if ( $label === $l ) {
								++$hits;
								break 2;
							}
						}
					}
				}
			}
		}
		if ( $hits >= 3 ) {
			return true;
		}
	}
	return false;
}
