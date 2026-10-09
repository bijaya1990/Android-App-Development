<?php
/**
 * Site header: logo, tagline, menu, Login and Start Free Trial.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'pkc-site' ); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'pikacart' ); ?></a>
<header class="site-header">
	<div class="wrap header-inner">
		<div class="brand">
			<?php pkc_theme_logo(); ?>
			<span class="brand-tag"><?php echo esc_html( pkc_theme_setting( 'tagline', get_bloginfo( 'description' ) ) ); ?></span>
		</div>
		<button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-nav" aria-label="<?php esc_attr_e( 'Open menu', 'pikacart' ); ?>"><?php echo pkc_theme_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<nav class="site-nav" id="site-nav" aria-label="<?php esc_attr_e( 'Main menu', 'pikacart' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'menu',
						'depth'          => 1,
					)
				);
			} else {
				$pkc_home = home_url( '/' );
				echo '<ul class="menu">';
				echo '<li><a href="' . esc_url( $pkc_home . '#categories' ) . '">' . esc_html__( 'Categories', 'pikacart' ) . '</a></li>';
				echo '<li><a href="' . esc_url( class_exists( 'PKC_Public' ) ? PKC_Public::url() : $pkc_home . '#designs' ) . '">' . esc_html__( 'Designs', 'pikacart' ) . '</a></li>';
				echo '<li><a href="' . esc_url( pkc_theme_page( 'pricing' ) ? pkc_theme_page( 'pricing' ) : $pkc_home . '#pricing' ) . '">' . esc_html__( 'Pricing', 'pikacart' ) . '</a></li>';
				echo '<li><a href="' . esc_url( $pkc_home . '#how' ) . '">' . esc_html__( 'How it works', 'pikacart' ) . '</a></li>';
				echo '<li><a href="' . esc_url( pkc_theme_page( 'contact' ) ? pkc_theme_page( 'contact' ) : $pkc_home ) . '">' . esc_html__( 'Contact', 'pikacart' ) . '</a></li>';
				echo '</ul>';
			}
			?>
			<div class="nav-cta">
				<?php if ( is_user_logged_in() ) : ?>
					<a class="t-btn t-btn-primary" href="<?php echo esc_url( pkc_theme_url( 'app' ) ); ?>"><?php esc_html_e( 'My dashboard', 'pikacart' ); ?></a>
				<?php else : ?>
					<a class="t-btn t-btn-ghost" href="<?php echo esc_url( pkc_theme_url( 'login' ) ); ?>"><?php esc_html_e( 'Login', 'pikacart' ); ?></a>
					<a class="t-btn t-btn-primary" href="<?php echo esc_url( pkc_theme_url( 'register' ) ); ?>"><?php esc_html_e( 'Start Free', 'pikacart' ); ?></a>
				<?php endif; ?>
			</div>
		</nav>
	</div>
</header>
<main id="content" class="site-main">
