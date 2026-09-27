<?php
/**
 * Inline SVG icon set (stroke icons, 24px grid) and category icon picker.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

function dm_icon_paths() {
	return array(
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'cart'      => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 12.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.5L21.5 7H6"/>',
		'heart'     => '<path d="M20.8 5.6a5.4 5.4 0 0 0-7.7 0L12 6.7l-1.1-1.1a5.4 5.4 0 0 0-7.7 7.7L12 22l8.8-8.7a5.4 5.4 0 0 0 0-7.7z"/>',
		'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'home'      => '<path d="M3 11 12 3l9 8"/><path d="M5 10v10h14V10"/>',
		'grid'      => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
		'bell'      => '<path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
		'menu'      => '<path d="M3 6h18M3 12h18M3 18h18"/>',
		'close'     => '<path d="M18 6 6 18M6 6l12 12"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'chev-l'    => '<path d="m15 18-6-6 6-6"/>',
		'chev-r'    => '<path d="m9 18 6-6-6-6"/>',
		'chev-d'    => '<path d="m6 9 6 6 6-6"/>',
		'star'      => '<path d="m12 2.8 2.9 5.9 6.5.9-4.7 4.6 1.1 6.4L12 17.6l-5.8 3 1.1-6.4L2.6 9.6l6.5-.9z"/>',
		'check'     => '<path d="M20 6 9 17l-5-5"/>',
		'shield'    => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
		'bolt'      => '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
		'refund'    => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'download'  => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
		'tag'       => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
		'percent'   => '<path d="M19 5 5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
		'copy'      => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
		'external'  => '<path d="M14 3h7v7"/><path d="M10 14 21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/>',
		'filter'    => '<path d="M3 5h18M6 12h12M10 19h4"/>',
		'share'     => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
		'chat'      => '<path d="M21 12a8 8 0 0 1-11.8 7L3 21l2-5.6A8 8 0 1 1 21 12z"/>',
		'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'moon'      => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
		'book'      => '<path d="M4 19.5V5a2 2 0 0 1 2-2h14v16H6.5A2.5 2.5 0 0 0 4 21.5v-2z"/><path d="M8 7h8M8 11h6"/>',
		'resume'    => '<rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="9" r="2.5"/><path d="M8 17a4 4 0 0 1 8 0"/>',
		'kids'      => '<circle cx="12" cy="12" r="9"/><path d="M8.5 14a4 4 0 0 0 7 0"/><path d="M9 9.5h.01M15 9.5h.01"/>',
		'palette'   => '<path d="M12 22a10 10 0 1 1 10-10c0 3-2 4-4 4h-2a2 2 0 0 0-1.5 3.3A2 2 0 0 1 12 22z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="15" cy="7" r="1"/>',
		'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M22 21a7 7 0 0 0-4-6.3"/>',
		'layout'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
		'globe'     => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
		'server'    => '<rect x="3" y="3" width="18" height="7" rx="2"/><rect x="3" y="14" width="18" height="7" rx="2"/><path d="M7 6.5h.01M7 17.5h.01"/>',
		'code'      => '<path d="m8 6-6 6 6 6M16 6l6 6-6 6"/>',
		'cap'       => '<path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
		'music'     => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
		'image'     => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/>',
		'sparkle'   => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
		'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
		'gift'      => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M5 12v9h14v-9"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
		'fire'      => '<path d="M12 22a7 7 0 0 0 7-7c0-4-3-6-4-9-1 2-2 3-4 3 0-2-1-4-2-5-1 4-4 6-4 11a7 7 0 0 0 7 7z"/>',
		'award'     => '<circle cx="12" cy="9" r="6"/><path d="m8.2 13.8-1.7 7.7L12 19l5.5 2.5-1.7-7.7"/>',
		'pin'       => '<path d="M12 22s7-6.3 7-12a7 7 0 0 0-14 0c0 5.7 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'phone'     => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
	);
}

/**
 * Inline stroke icon.
 */
function dm_icon( $name, $size = 20, $class = '' ) {
	$paths = dm_icon_paths();
	if ( ! isset( $paths[ $name ] ) ) {
		$name = 'tag';
	}
	$fill = 'star' === $name ? 'currentColor' : 'none';
	return '<svg class="dm-i' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="' . $fill . '" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * WhatsApp brand glyph (filled).
 */
function dm_icon_whatsapp( $size = 20 ) {
	return '<svg class="dm-i" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7a2.8 2.8 0 0 0 1.8-1.3 2.3 2.3 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3z"/></svg>';
}

/**
 * Best-guess icon for a category from its slug/name (used when no icon is set).
 */
function dm_guess_category_icon( $term ) {
	$hay = strtolower( $term->slug . ' ' . $term->name );
	$map = array(
		'hosting'   => 'server',
		'domain'    => 'server',
		'kid'       => 'kids',
		'child'     => 'kids',
		'resume'    => 'resume',
		'career'    => 'resume',
		'cv'        => 'resume',
		'note'      => 'book',
		'study'     => 'book',
		'ebook'     => 'book',
		'book'      => 'book',
		'exam'      => 'cap',
		'course'    => 'cap',
		'committee' => 'users',
		'puja'      => 'users',
		'business'  => 'briefcase',
		'wordpress' => 'layout',
		'theme'     => 'layout',
		'template'  => 'layout',
		'website'   => 'globe',
		'service'   => 'globe',
		'design'    => 'palette',
		'canva'     => 'palette',
		'graphic'   => 'palette',
		'software'  => 'code',
		'plugin'    => 'code',
		'code'      => 'code',
		'music'     => 'music',
		'audio'     => 'music',
		'photo'     => 'image',
		'preset'    => 'image',
		'font'      => 'sparkle',
		'tool'      => 'briefcase',
	);
	foreach ( $map as $needle => $icon ) {
		if ( false !== strpos( $hay, $needle ) ) {
			return $icon;
		}
	}
	return 'tag';
}

function dm_category_palette() {
	return array( '#5B4BFF', '#10B981', '#F59E0B', '#EC4899', '#0EA5E9', '#8B5CF6', '#16A34A', '#F97316', '#64748B', '#DB2777' );
}

/**
 * Icon markup for a category: uploaded image > emoji/text > guessed SVG icon.
 */
function dm_category_icon_html( $term, $index = 0 ) {
	$img   = (int) get_term_meta( $term->term_id, 'dm_icon_img', true );
	$emoji = (string) get_term_meta( $term->term_id, 'dm_icon', true );
	$color = dm_category_color( $term, $index );
	$style = ' style="--dm-cat:' . esc_attr( $color ) . '"';
	if ( $img ) {
		return '<span class="dm-cat-ico has-img"' . $style . '>' . wp_get_attachment_image( $img, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) ) . '</span>';
	}
	if ( '' !== $emoji ) {
		return '<span class="dm-cat-ico is-emoji"' . $style . '>' . esc_html( $emoji ) . '</span>';
	}
	return '<span class="dm-cat-ico"' . $style . '>' . dm_icon( dm_guess_category_icon( $term ), 26 ) . '</span>';
}

function dm_category_color( $term, $index = 0 ) {
	$c = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'dm_color', true ) );
	if ( $c ) {
		return $c;
	}
	$p = dm_category_palette();
	return $p[ abs( (int) $term->term_id + (int) $index ) % count( $p ) ];
}
