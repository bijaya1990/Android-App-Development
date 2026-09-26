<?php
/**
 * Site header.
 *
 * @package DigiMarket
 */

$dm_uid   = get_current_user_id();
$dm_cats  = dm_categories( array( 'hide_empty' => false, 'parent' => 0 ) );
$dm_count = dm_cart_count();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="dm-skip" href="#dm-main"><?php esc_html_e( 'Skip to content', 'digimarket' ); ?></a>
<header class="dm-header" id="dm-header">
	<div class="dm-container dm-header-inner">
		<button class="dm-icon-btn dm-menu-toggle" aria-controls="dm-mobile-nav" aria-expanded="false" aria-label="<?php esc_attr_e( 'Menu', 'digimarket' ); ?>">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
		</button>
		<div class="dm-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="dm-logo-text" rel="home"><span class="dm-logo-mark" aria-hidden="true">◆</span><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="dm-cat-menu">
			<button class="dm-btn dm-btn-ghost dm-cat-toggle" aria-haspopup="true" aria-expanded="false"><?php esc_html_e( 'Categories', 'digimarket' ); ?> <span aria-hidden="true">▾</span></button>
			<div class="dm-dropdown" role="menu">
				<?php foreach ( $dm_cats as $dm_cat ) : ?>
					<a role="menuitem" href="<?php echo esc_url( get_term_link( $dm_cat ) ); ?>"><?php echo esc_html( $dm_cat->name ); ?></a>
				<?php endforeach; ?>
				<a role="menuitem" class="dm-dropdown-all" href="<?php echo esc_url( dm_products_url() ); ?>"><?php esc_html_e( 'Browse all products →', 'digimarket' ); ?></a>
			</div>
		</div>

		<form class="dm-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<input type="hidden" name="post_type" value="dm_product">
			<label class="screen-reader-text" for="dm-s"><?php esc_html_e( 'Search products', 'digimarket' ); ?></label>
			<svg class="dm-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
			<input id="dm-s" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search products and shops…', 'digimarket' ); ?>">
		</form>

		<nav class="dm-header-actions" aria-label="<?php esc_attr_e( 'Account', 'digimarket' ); ?>">
			<button class="dm-icon-btn dm-mode-toggle" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'digimarket' ); ?>">
				<svg class="dm-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
				<svg class="dm-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
			</button>
			<?php if ( $dm_uid ) : ?>
				<?php $dm_unread = dm_unread_notifications( $dm_uid ); ?>
				<a class="dm-icon-btn" href="<?php echo esc_url( dm_url( 'account', 'notifications' ) ); ?>" aria-label="<?php esc_attr_e( 'Notifications', 'digimarket' ); ?>">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>
					<?php if ( $dm_unread ) : ?><span class="dm-dot"><?php echo (int) min( 99, $dm_unread ); ?></span><?php endif; ?>
				</a>
			<?php endif; ?>
			<a class="dm-icon-btn dm-cart-link" href="<?php echo esc_url( dm_url( 'cart' ) ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'digimarket' ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
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
						<?php if ( dm_is_seller() ) : ?>
							<a role="menuitem" href="<?php echo esc_url( dm_url( 'dashboard' ) ); ?>"><?php esc_html_e( 'Seller dashboard', 'digimarket' ); ?></a>
						<?php else : ?>
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
				<a class="dm-btn dm-btn-ghost dm-hide-sm" href="<?php echo esc_url( dm_url( 'login' ) ); ?>"><?php esc_html_e( 'Log in', 'digimarket' ); ?></a>
				<a class="dm-btn dm-btn-primary dm-hide-sm" href="<?php echo esc_url( dm_url( 'register' ) ); ?>"><?php esc_html_e( 'Sign up', 'digimarket' ); ?></a>
			<?php endif; ?>
		</nav>
	</div>
	<?php if ( has_nav_menu( 'primary' ) ) : ?>
		<nav class="dm-primary-nav dm-container" aria-label="<?php esc_attr_e( 'Primary', 'digimarket' ); ?>">
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'depth' => 1 ) ); ?>
		</nav>
	<?php endif; ?>
	<div class="dm-mobile-nav" id="dm-mobile-nav" hidden>
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="dm-search dm-search-mobile">
			<input type="hidden" name="post_type" value="dm_product">
			<input type="search" name="s" placeholder="<?php esc_attr_e( 'Search…', 'digimarket' ); ?>" aria-label="<?php esc_attr_e( 'Search products', 'digimarket' ); ?>">
		</form>
		<a href="<?php echo esc_url( dm_products_url() ); ?>"><?php esc_html_e( 'All products', 'digimarket' ); ?></a>
		<a href="<?php echo esc_url( dm_url( 'shops' ) ); ?>"><?php esc_html_e( 'All shops', 'digimarket' ); ?></a>
		<?php foreach ( $dm_cats as $dm_cat ) : ?>
			<a href="<?php echo esc_url( get_term_link( $dm_cat ) ); ?>"><?php echo esc_html( $dm_cat->name ); ?></a>
		<?php endforeach; ?>
		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'depth' => 1 ) ); ?>
		<?php endif; ?>
		<?php if ( ! $dm_uid ) : ?>
			<a href="<?php echo esc_url( dm_url( 'login' ) ); ?>"><?php esc_html_e( 'Log in', 'digimarket' ); ?></a>
			<a href="<?php echo esc_url( dm_url( 'register' ) ); ?>"><?php esc_html_e( 'Sign up', 'digimarket' ); ?></a>
		<?php endif; ?>
		<a href="<?php echo esc_url( dm_url( 'sell' ) ); ?>" class="dm-btn dm-btn-primary"><?php esc_html_e( 'Become a seller', 'digimarket' ); ?></a>
	</div>
</header>
<main id="dm-main" class="dm-main">
	<div class="dm-container dm-flash-wrap"><?php dm_render_flash(); ?></div>
