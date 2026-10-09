<?php
/**
 * One consistent inline SVG icon set (24px grid, 2px rounded strokes).
 * Use with: <svg class="pkc-i"><use href="#i-name"></use></svg>
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

$pkc_icons = array(
	'home'      => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
	'plus'      => '<path d="M12 5v14M5 12h14"/>',
	'folder'    => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
	'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 3.5 6"/>',
	'printer'   => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/>',
	'palette'   => '<path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.5-.8 1.5-1.6 0-1.2-1-1.6-1-2.7 0-.9.7-1.7 1.7-1.7H16a5 5 0 0 0 5-5C21 6.4 17 3 12 3z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="15" cy="7" r="1"/>',
	'building'  => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10"/><path d="M2 21h20M8 7h4M8 11h4M8 15h4"/>',
	'card'      => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19M6 15h4"/>',
	'idcard'    => '<rect x="5" y="2.5" width="14" height="19" rx="2"/><path d="M10 2.5v2h4v-2"/><circle cx="12" cy="10" r="2.5"/><path d="M8 17a4 4 0 0 1 8 0"/>',
	'help'      => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5V14"/><path d="M12 17.5v.01"/>',
	'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
	'bell'      => '<path d="M6 16V11a6 6 0 1 1 12 0v5l2 2H4z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
	'logout'    => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l-5-5 5-5M5 12h11"/>',
	'menu'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
	'x'         => '<path d="M6 6l12 12M18 6 6 18"/>',
	'check'     => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
	'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/>',
	'alert'     => '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17v.01"/>',
	'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/>',
	'lock'      => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
	'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
	'upload'    => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
	'download'  => '<path d="M12 4v12M7 11l5 5 5-5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
	'trash'     => '<path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14"/>',
	'edit'      => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m14 6 4 4"/>',
	'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
	'chevron-left'  => '<path d="m15 6-6 6 6 6"/>',
	'chevron-down'  => '<path d="m6 9 6 6 6-6"/>',
	'eye'       => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
	'eye-off'   => '<path d="M3 3l18 18"/><path d="M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4M6.6 6.6C3.8 8.3 2 12 2 12s3.5 7 10 7c1.9 0 3.5-.6 4.9-1.4"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
	'image'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/>',
	'tools'     => '<path d="M14.5 6.5a4 4 0 0 0 5 5L21 13l-8 8-3-3 8-8"/><path d="M3 21l6-6"/><path d="M7 3 3 7l4 4 4-4z"/>',
	'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
	'phone'     => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
	'shield'    => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
	'school'    => '<path d="M2 9 12 4l10 5-10 5z"/><path d="M6 11v5c3 2.5 9 2.5 12 0v-5"/><path d="M22 9v6"/>',
	'college'   => '<path d="M3 10 12 4l9 6"/><path d="M5 10v9M9 10v9M15 10v9M19 10v9M3 21h18"/>',
	'book'      => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 19V5M8 7h7"/>',
	'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
	'health'    => '<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M12 8v8M8 12h8"/>',
	'heart'     => '<path d="M12 20s-8-4.6-8-10.5A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 8 2.5C20 15.4 12 20 12 20z"/>',
	'ticket'    => '<path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6v12" stroke-dasharray="2 2"/>',
	'mic'       => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/>',
	'sparkles'  => '<path d="M12 3l1.8 4.7L18.5 9.5 13.8 11.3 12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>',
	'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
	'refresh'   => '<path d="M20 11a8 8 0 0 0-14.6-4.5L4 8"/><path d="M4 4v4h4M4 13a8 8 0 0 0 14.6 4.5L20 16"/><path d="M20 20v-4h-4"/>',
	'receipt'   => '<path d="M5 3h14v18l-2.5-1.5L14 21l-2-1.5L10 21l-2.5-1.5L5 21z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
	'external'  => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
	'star'      => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
	'rupee'     => '<path d="M6 4h12M6 9h12M6 4h3a5 5 0 0 1 0 10H6l8 7"/>',
	'chart'     => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
	'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
);
?>
<svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true">
	<?php foreach ( $pkc_icons as $pkc_icon_name => $pkc_icon_path ) : ?>
	<symbol id="i-<?php echo esc_attr( $pkc_icon_name ); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $pkc_icon_path; // phpcs:ignore WordPress.Security.EscapeOutput -- Static SVG paths defined above. ?></symbol>
	<?php endforeach; ?>
</svg>
