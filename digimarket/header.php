<?php
/**
 * Site header: announcement bar, logo, search with suggestions, account, cart,
 * category links and the mobile drawer.
 *
 * @package DigiMarket
 */

$dm_uid   = get_current_user_id();
$dm_cats  = dm_categories( array( 'hide_empty' => false, 'parent' => 0 ) );
$dm_count = dm_cart_count();
$dm_links = array();
foreach ( dm_lines( dm_store_opt( 'header_links' ) ) as $dm_line ) {
	$dm_p = array_map( 'trim', explode( '|', $dm_line, 2 ) );
	if ( 2 === count( $dm_p ) && $dm_p[0] && $dm_p[1] ) {
		$dm_links[] = $dm_p;
	}
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="dm-skip" href="#dm-main"><?php esc_html_e( 'Skip to content', 'digimarket' ); ?></a>
<?php dm_render_announcement(); ?>
<header class="dm-hd" id="dm-header">
	<div class="dm-container dm-hd-row">
		<button class="dm-icon-btn dm-menu-toggle" aria-controls="dm-drawer" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open menu', 'digimarket' ); ?>"><?php echo dm_icon( 'menu', 24 ); // phpcs:ignore ?></button>
		<div class="dm-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="dm-logo" rel="home"><img class="dm-logo-light" src="<?php echo esc_url( DM_URI . '/assets/img/pikacart-logo.svg' ); ?>" width="148" height="32" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"><img class="dm-logo-dark" src="<?php echo esc_url( DM_URI . '/assets/img/pikacart-logo-white.svg' ); ?>" width="148" height="32" alt="" aria-hidden="true" loading="lazy"></a>
			<?php endif; ?>
		</div>

		<form class="dm-srch" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-suggest>
			<input type="hidden" name="post_type" value="dm_product">
			<label class="screen-reader-text" for="dm-s"><?php esc_html_e( 'Search products', 'digimarket' ); ?></label>
			<span class="dm-srch-ico"><?php echo dm_icon( 'search', 20 ); // phpcs:ignore ?></span>
			<input id="dm-s" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search notes, templates, websites…', 'digimarket' ); ?>" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="dm-suggest" aria-expanded="false">
			<button class="dm-srch-btn" type="submit" aria-label="<?php esc_attr_e( 'Search', 'digimarket' ); ?>"><?php echo dm_icon( 'search', 18 ); // phpcs:ignore ?></button>
			<div class="dm-suggest" id="dm-suggest" role="listbox" hidden></div>
		</form>

		<nav class="dm-hd-actions" aria-label="<?php esc_attr_e( 'Account', 'digimarket' ); ?>">
			<?php if ( dm_store_opt( 'articles_show' ) ) : ?><a class="dm-hd-textlink dm-hide-sm" href="<?php echo esc_url( dm_articles_url() ); ?>"><?php echo esc_html( dm_store_opt( 'articles_label' ) ); ?></a><?php endif; ?>
			<button class="dm-icon-btn dm-mode-toggle dm-hide-sm" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'digimarket' ); ?>"><span class="dm-sun"><?php echo dm_icon( 'sun', 20 ); // phpcs:ignore ?></span><span class="dm-moon"><?php echo dm_icon( 'moon', 20 ); // phpcs:ignore ?></span></button>
			<?php if ( $dm_uid ) : ?>
				<?php $dm_unread = dm_unread_notifications( $dm_uid ); ?>
				<a class="dm-icon-btn dm-hide-sm" href="<?php echo esc_url( dm_url( 'account', 'wishlist' ) ); ?>" aria-label="<?php esc_attr_e( 'Wishlist', 'digimarket' ); ?>"><?php echo dm_icon( 'heart', 21 ); // phpcs:ignore ?></a>
				<a class="dm-icon-btn" href="<?php echo esc_url( dm_url( 'account', 'notifications' ) ); ?>" aria-label="<?php esc_attr_e( 'Notifications', 'digimarket' ); ?>"><?php echo dm_icon( 'bell', 21 ); // phpcs:ignore ?><?php if ( $dm_unread ) : ?><span class="dm-dot"><?php echo (int) min( 99, $dm_unread ); ?></span><?php endif; ?></a>
			<?php endif; ?>
			<a class="dm-icon-btn dm-cart-link" href="<?php echo esc_url( dm_url( 'cart' ) ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'digimarket' ); ?>">
				<?php echo dm_icon( 'cart', 22 ); // phpcs:ignore ?><span class="dm-hd-label"><?php esc_html_e( 'Cart', 'digimarket' ); ?></span>
				<span class="dm-dot dm-cart-count"<?php echo $dm_count ? '' : ' hidden'; ?>><?php echo (int) $dm_count; ?></span>
			</a>
			<?php if ( $dm_uid ) : ?>
				<div class="dm-user-menu">
					<button class="dm-avatar-btn" aria-haspopup="true" aria-expanded="false" aria-label="<?php esc_attr_e( 'Account menu', 'digimarket' ); ?>"><?php echo get_avatar( $dm_uid, 32 ); ?></button>
					<div class="dm-dropdown dm-dropdown-right" role="menu">
						<div class="dm-dropdown-head"><?php echo esc_html( wp_get_current_user()->display_name ); ?></div>
						<?php if ( current_user_can( 'dm_view_marketplace' ) ) : ?>
							<a role="menuitem" href="<?php echo esc_url( admin_url( 'admin.php?page=dm-marketplace' ) ); ?>"><?php esc_html_e( 'Admin dashboard', 'digimarket' ); ?></a>
						<?php endif; ?>
						<?php if ( dm_is_seller() && ! dm_single_seller_mode() ) : ?>
							<a role="menuitem" href="<?php echo esc_url( dm_url( 'dashboard' ) ); ?>"><?php esc_html_e( 'Seller dashboard', 'digimarket' ); ?></a>
						<?php elseif ( ! dm_single_seller_mode() ) : ?>
							<a role="menuitem" href="<?php echo esc_url( dm_url( 'sell' ) ); ?>"><?php esc_html_e( 'Start selling', 'digimarket' ); ?></a>
						<?php endif; ?>
						<a role="menuitem" href="<?php echo esc_url( dm_url( 'account', 'purchases' ) ); ?>"><?php esc_html_e( 'My purchases', 'digimarket' ); ?></a>
						<a role="menuitem" href="<?php echo esc_url( dm_url( 'account', 'orders' ) ); ?>"><?php esc_html_e( 'Order history', 'digimarket' ); ?></a>
						<a role="menuitem" href="<?php echo esc_url( dm_url( 'account', 'wishlist' ) ); ?>"><?php esc_html_e( 'Wishlist', 'digimarket' ); ?></a>
						<a role="menuitem" href="<?php echo esc_url( dm_url( 'account', 'profile' ) ); ?>"><?php esc_html_e( 'Profile', 'digimarket' ); ?></a>
						<form method="post" action="<?php echo esc_url( home_url( '/' ) ); ?>"><?php dm_nonce_field( 'logout' ); ?><button type="submit" role="menuitem"><?php esc_html_e( 'Log out', 'digimarket' ); ?></button></form>
					</div>
				</div>
			<?php else : ?>
				<a class="dm-btn dm-btn-primary dm-hd-login" href="<?php echo esc_url( dm_url( 'login' ) ); ?>" aria-label="<?php esc_attr_e( 'Login', 'digimarket' ); ?>"><?php echo dm_icon( 'user', 18 ); // phpcs:ignore ?><span><?php esc_html_e( 'Login', 'digimarket' ); ?></span></a>
			<?php endif; ?>
		</nav>
	</div>
	<?php if ( ! is_front_page() ) : ?>
	<nav class="dm-hd-nav" aria-label="<?php esc_attr_e( 'Categories', 'digimarket' ); ?>">
		<div class="dm-container dm-hd-nav-track">
			<?php foreach ( array_slice( $dm_cats, 0, 9 ) as $dm_cat ) : ?>
				<a href="<?php echo esc_url( get_term_link( $dm_cat ) ); ?>"<?php echo ( is_tax( 'dm_category', $dm_cat->term_id ) ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $dm_cat->name ); ?></a>
			<?php endforeach; ?>
			<?php if ( dm_sale_product_ids( 1 ) ) : ?><a class="is-sale" href="<?php echo esc_url( dm_url( 'sale' ) ); ?>"><?php esc_html_e( 'Sale', 'digimarket' ); ?></a><?php endif; ?>
			<?php foreach ( $dm_links as $dm_l ) : ?><a href="<?php echo esc_url( $dm_l[1] ); ?>"><?php echo esc_html( $dm_l[0] ); ?></a><?php endforeach; ?>
		</div>
	</nav>
	<?php endif; ?>
</header>

<div class="dm-drawer" id="dm-drawer" hidden>
	<div class="dm-drawer-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'digimarket' ); ?>">
		<div class="dm-drawer-head">
			<?php if ( $dm_uid ) : ?>
				<span class="dm-drawer-user"><?php echo get_avatar( $dm_uid, 36 ); ?> <?php echo esc_html( wp_get_current_user()->display_name ); ?></span>
			<?php else : ?>
				<a class="dm-btn dm-btn-light" href="<?php echo esc_url( dm_url( 'login' ) ); ?>"><?php echo dm_icon( 'user', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Login / Sign up', 'digimarket' ); ?></a>
			<?php endif; ?>
			<button class="dm-icon-btn dm-drawer-close" aria-label="<?php esc_attr_e( 'Close menu', 'digimarket' ); ?>"><?php echo dm_icon( 'close', 22 ); // phpcs:ignore ?></button>
		</div>
		<nav class="dm-drawer-nav">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo dm_icon( 'home', 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Home', 'digimarket' ); ?></a>
			<a href="<?php echo esc_url( dm_products_url() ); ?>"><?php echo dm_icon( 'grid', 20 ); // phpcs:ignore ?> <?php esc_html_e( 'All products', 'digimarket' ); ?></a>
			<?php if ( dm_sale_product_ids( 1 ) ) : ?><a href="<?php echo esc_url( dm_url( 'sale' ) ); ?>"><?php echo dm_icon( 'percent', 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Sale', 'digimarket' ); ?></a><?php endif; ?>
			<div class="dm-drawer-label"><?php esc_html_e( 'Categories', 'digimarket' ); ?></div>
			<?php foreach ( $dm_cats as $dm_i => $dm_cat ) : ?>
				<a href="<?php echo esc_url( get_term_link( $dm_cat ) ); ?>"><?php echo dm_category_icon_html( $dm_cat, $dm_i ); // phpcs:ignore ?> <?php echo esc_html( $dm_cat->name ); ?></a>
			<?php endforeach; ?>
			<div class="dm-drawer-label"><?php esc_html_e( 'More', 'digimarket' ); ?></div>
			<?php if ( dm_store_opt( 'articles_show' ) ) : ?><a href="<?php echo esc_url( dm_articles_url() ); ?>"><?php echo dm_icon( 'book', 20 ); // phpcs:ignore ?> <?php echo esc_html( dm_store_opt( 'articles_label' ) ); ?></a><?php endif; ?>
			<?php if ( post_type_exists( 'dm_portfolio' ) && wp_count_posts( 'dm_portfolio' )->publish ) : ?><a href="<?php echo esc_url( get_post_type_archive_link( 'dm_portfolio' ) ); ?>"><?php echo dm_icon( 'layout', 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Our work', 'digimarket' ); ?></a><?php endif; ?>
			<?php foreach ( $dm_links as $dm_l ) : ?><a href="<?php echo esc_url( $dm_l[1] ); ?>"><?php echo dm_icon( 'arrow', 20 ); // phpcs:ignore ?> <?php echo esc_html( $dm_l[0] ); ?></a><?php endforeach; ?>
			<?php if ( $dm_uid ) : ?>
				<a href="<?php echo esc_url( dm_url( 'account', 'purchases' ) ); ?>"><?php echo dm_icon( 'download', 20 ); // phpcs:ignore ?> <?php esc_html_e( 'My purchases', 'digimarket' ); ?></a>
				<a href="<?php echo esc_url( dm_url( 'account', 'wishlist' ) ); ?>"><?php echo dm_icon( 'heart', 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Wishlist', 'digimarket' ); ?></a>
			<?php endif; ?>
			<?php if ( has_nav_menu( 'primary' ) ) : ?>
				<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'depth' => 1 ) ); ?>
			<?php endif; ?>
			<?php if ( ! dm_single_seller_mode() ) : ?><a href="<?php echo esc_url( dm_url( 'sell' ) ); ?>"><?php echo dm_icon( 'briefcase', 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Become a seller', 'digimarket' ); ?></a><?php endif; ?>
			<button class="dm-drawer-mode dm-mode-toggle" type="button"><span class="dm-sun"><?php echo dm_icon( 'sun', 20 ); // phpcs:ignore ?></span><span class="dm-moon"><?php echo dm_icon( 'moon', 20 ); // phpcs:ignore ?></span> <?php esc_html_e( 'Dark mode', 'digimarket' ); ?></button>
		</nav>
	</div>
</div>
<main id="dm-main" class="dm-main">
	<div class="dm-container dm-flash-wrap"><?php dm_render_flash(); ?></div>
