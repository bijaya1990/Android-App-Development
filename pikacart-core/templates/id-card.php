<?php
/**
 * Public design gallery: /id-card/ (all categories) and
 * /id-card/{category}/{sub-type}/ (50 styles with filters and live preview).
 * A theme can override this file with pikacart-id-card.php.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

$pkc_ctx = PKC_Public::context();
$pkc_cat = $pkc_ctx['category'];
$pkc_sub = $pkc_ctx['subtype'];
$pkc_seo = $pkc_cat ? $pkc_ctx['seo'] : null;

get_header();
?>
<main id="main" class="pg">
	<div class="pg-wrap">
		<nav class="pg-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'pikacart' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'pikacart' ); ?></a>
			<span aria-hidden="true">›</span>
			<?php if ( $pkc_cat ) : ?>
				<a href="<?php echo esc_url( PKC_Public::url() ); ?>"><?php esc_html_e( 'ID card designs', 'pikacart' ); ?></a>
				<span aria-hidden="true">›</span>
				<span aria-current="page"><?php echo esc_html( $pkc_cat['name'] ); ?></span>
			<?php else : ?>
				<span aria-current="page"><?php esc_html_e( 'ID card designs', 'pikacart' ); ?></span>
			<?php endif; ?>
		</nav>

		<?php if ( ! $pkc_cat ) : ?>
			<header class="pg-head">
				<h1><?php esc_html_e( 'ID card designs for every organisation', 'pikacart' ); ?></h1>
				<p><?php esc_html_e( 'Choose your category to see every design with realistic sample details. Recolour any design live and start with it in one click.', 'pikacart' ); ?></p>
			</header>
			<div class="pg-cats">
				<?php foreach ( $pkc_ctx['tree'] as $pkc_c ) : ?>
					<?php if ( ! $pkc_c['subtypes'] ) { continue; } ?>
					<article class="pg-cat">
						<a class="pg-cat-art" href="<?php echo esc_url( PKC_Public::url( $pkc_c['slug'] ) ); ?>" aria-label="<?php echo esc_attr( $pkc_c['name'] ); ?>">
							<canvas data-cat-card="<?php echo esc_attr( $pkc_c['slug'] ); ?>" aria-hidden="true"></canvas>
						</a>
						<div class="pg-cat-body">
							<h2><a href="<?php echo esc_url( PKC_Public::url( $pkc_c['slug'] ) ); ?>"><?php echo esc_html( $pkc_c['name'] ); ?></a></h2>
							<p><?php echo esc_html( $pkc_c['description'] ); ?></p>
							<ul>
								<?php foreach ( $pkc_c['subtypes'] as $pkc_s ) : ?>
									<li><a href="<?php echo esc_url( PKC_Public::url( $pkc_c['slug'], $pkc_s['slug'] ) ); ?>"><?php echo esc_html( $pkc_s['name'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<header class="pg-head">
				<h1><?php echo esc_html( $pkc_seo['h1'] ); ?></h1>
				<p><?php echo esc_html( $pkc_cat['description'] ); ?></p>
				<p class="pg-facts">
					<span><?php echo esc_html( sprintf( /* translators: %d: number */ _n( '%d design', '%d designs', count( $pkc_ctx['templates'] ), 'pikacart' ), count( $pkc_ctx['templates'] ) ) ); ?></span>
					<span><?php esc_html_e( 'Portrait and landscape', 'pikacart' ); ?></span>
					<span><?php esc_html_e( 'Live colour preview', 'pikacart' ); ?></span>
					<span><?php esc_html_e( 'Free to start', 'pikacart' ); ?></span>
				</p>
			</header>

			<nav class="pg-tabs" aria-label="<?php esc_attr_e( 'Card types', 'pikacart' ); ?>">
				<?php foreach ( $pkc_cat['subtypes'] as $pkc_s ) : ?>
					<a href="<?php echo esc_url( PKC_Public::url( $pkc_cat['slug'], $pkc_s['slug'] ) ); ?>" class="<?php echo (int) $pkc_s['id'] === (int) $pkc_sub['id'] ? 'is-on' : ''; ?>" <?php echo (int) $pkc_s['id'] === (int) $pkc_sub['id'] ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $pkc_s['name'] ); ?></a>
				<?php endforeach; ?>
			</nav>

			<div class="pg-filters" data-filters></div>
			<div class="pg-grid" data-grid>
				<noscript>
					<ul class="pg-names">
						<?php foreach ( $pkc_ctx['templates'] as $pkc_t ) : ?>
							<li><?php echo esc_html( $pkc_t['name'] ); ?></li>
						<?php endforeach; ?>
					</ul>
				</noscript>
			</div>

			<section class="pg-cta">
				<div>
					<h2><?php esc_html_e( 'Make these cards with your own logo and people', 'pikacart' ); ?></h2>
					<p><?php esc_html_e( 'Import from Excel, match photos by ID number, verify with QR codes and print on any card size.', 'pikacart' ); ?></p>
				</div>
				<a class="pkc-btn pkc-btn-primary" href="<?php echo esc_url( pkc_url( 'register' ) ); ?>"><?php esc_html_e( 'Start free', 'pikacart' ); ?></a>
			</section>

			<?php if ( ! empty( $pkc_seo['text'] ) ) : ?>
				<section class="seo-text pg-seo"><?php echo wpautop( wp_kses_post( $pkc_seo['text'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></section>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</main>
<div id="pkc-modal-root"></div>
<?php
get_footer();
