<?php
/**
 * Header: brand, tagline, theme toggle, search, Post Job button, menu bar.
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="np-skip" href="#main"><?php esc_html_e( 'Skip to content', 'naukripatra' ); ?></a>

<header class="np-header">
	<div class="np-wrap np-header__row">
		<div class="np-brand">
			<?php $tag = is_front_page() ? 'h1' : 'p'; ?>
			<<?php echo $tag; ?> class="np-brand__name"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo np_brand_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></<?php echo $tag; ?>>
			<?php if ( np_opt( 'tagline' ) ) : ?>
				<p class="np-brand__tag"><?php echo esc_html( np_opt( 'tagline' ) ); ?></p>
			<?php endif; ?>
		</div>
		<div class="np-header__actions">
			<button type="button" class="np-iconbtn" id="np-theme-toggle" aria-label="<?php esc_attr_e( 'Switch dark or light mode', 'naukripatra' ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
			</button>
			<a class="np-iconbtn" href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" aria-label="<?php esc_attr_e( 'Search', 'naukripatra' ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
			</a>
			<?php if ( np_can_post() ) : ?>
				<a class="np-btn np-btn--accent" href="<?php echo esc_url( home_url( '/post-job/' ) ); ?>"><?php esc_html_e( 'Post Job', 'naukripatra' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</header>

<nav class="np-nav" aria-label="<?php esc_attr_e( 'Primary', 'naukripatra' ); ?>">
	<div class="np-wrap">
		<button type="button" class="np-nav__toggle" aria-expanded="false" aria-controls="np-menu">
			<span class="np-nav__bars" aria-hidden="true"></span>
			<span><?php esc_html_e( 'Menu', 'naukripatra' ); ?></span>
		</button>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'menu_id'        => 'np-menu',
				'menu_class'     => 'np-menu',
				'container'      => false,
				'fallback_cb'    => false,
			)
		);
		?>
	</div>
</nav>

<main id="main" class="np-main">
