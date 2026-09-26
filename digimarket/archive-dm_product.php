<?php
/**
 * Product catalogue: archive, category/tag pages and product search.
 *
 * @package DigiMarket
 */

get_header();

global $wp_query;
$dm_term   = ( is_tax( 'dm_category' ) || is_tax( 'dm_tag' ) ) ? get_queried_object() : null;
$dm_search = get_search_query();
$dm_sort   = isset( $_GET['sort'] ) ? sanitize_key( $_GET['sort'] ) : 'newest';
$dm_base   = $dm_term ? get_term_link( $dm_term ) : dm_products_url();
if ( $dm_term ) {
	$dm_title = $dm_term->name;
} elseif ( $dm_search ) {
	/* translators: %s search */
	$dm_title = sprintf( __( 'Results for “%s”', 'digimarket' ), $dm_search );
} else {
	$dm_title = __( 'All products', 'digimarket' );
}

// Matching shops for a search query.
$dm_shops = array();
if ( $dm_search ) {
	$dm_shops = get_users(
		array(
			'number'     => 6,
			'fields'     => 'ID',
			'meta_query' => array(
				'relation' => 'AND',
				array( 'key' => 'dm_seller_status', 'value' => 'active' ),
				array( 'key' => 'dm_shop_name', 'value' => $dm_search, 'compare' => 'LIKE' ),
			),
		)
	);
}
?>
<div class="dm-container dm-page">
	<header class="dm-page-head">
		<nav class="dm-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'digimarket' ); ?>"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'digimarket' ); ?></a> / <a href="<?php echo esc_url( dm_products_url() ); ?>"><?php esc_html_e( 'Products', 'digimarket' ); ?></a><?php echo $dm_term ? ' / <span>' . esc_html( $dm_term->name ) . '</span>' : ''; ?></nav>
		<h1><?php echo esc_html( $dm_title ); ?></h1>
		<?php if ( $dm_term && $dm_term->description ) : ?><p class="dm-lead"><?php echo esc_html( $dm_term->description ); ?></p><?php endif; ?>
	</header>

	<?php if ( $dm_shops ) : ?>
		<section class="dm-subsection">
			<h2 class="dm-h3"><?php esc_html_e( 'Shops', 'digimarket' ); ?></h2>
			<div class="dm-carousel"><?php foreach ( $dm_shops as $dm_sid ) { get_template_part( 'template-parts/shop-card', null, array( 'seller_id' => $dm_sid ) ); } ?></div>
		</section>
	<?php endif; ?>

	<div class="dm-catalogue">
		<aside class="dm-filters" id="dm-filters">
			<form method="get" action="<?php echo esc_url( $dm_search ? home_url( '/' ) : $dm_base ); ?>" class="dm-filter-form">
				<?php if ( $dm_search ) : ?>
					<input type="hidden" name="s" value="<?php echo esc_attr( $dm_search ); ?>">
					<input type="hidden" name="post_type" value="dm_product">
				<?php endif; ?>
				<input type="hidden" name="sort" value="<?php echo esc_attr( $dm_sort ); ?>">
				<?php if ( ! $dm_term || 'dm_tag' === $dm_term->taxonomy ) : ?>
					<fieldset>
						<legend><?php esc_html_e( 'Category', 'digimarket' ); ?></legend>
						<label class="dm-radio"><input type="radio" name="pcat" value="" <?php checked( empty( $_GET['pcat'] ) ); ?>> <?php esc_html_e( 'All', 'digimarket' ); ?></label>
						<?php foreach ( dm_categories( array( 'parent' => 0 ) ) as $dm_cat ) : ?>
							<label class="dm-radio"><input type="radio" name="pcat" value="<?php echo esc_attr( $dm_cat->slug ); ?>" <?php checked( ( $_GET['pcat'] ?? '' ), $dm_cat->slug ); ?>> <?php echo esc_html( $dm_cat->name ); ?></label>
						<?php endforeach; ?>
					</fieldset>
				<?php endif; ?>
				<fieldset>
					<legend><?php esc_html_e( 'Price', 'digimarket' ); ?> (<?php echo esc_html( dm_opt( 'currency_symbol' ) ); ?>)</legend>
					<div class="dm-price-range">
						<input type="number" min="0" step="1" name="min_price" placeholder="<?php esc_attr_e( 'Min', 'digimarket' ); ?>" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['min_price'] ?? '' ) ) ); ?>" aria-label="<?php esc_attr_e( 'Minimum price', 'digimarket' ); ?>">
						<span>–</span>
						<input type="number" min="0" step="1" name="max_price" placeholder="<?php esc_attr_e( 'Max', 'digimarket' ); ?>" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['max_price'] ?? '' ) ) ); ?>" aria-label="<?php esc_attr_e( 'Maximum price', 'digimarket' ); ?>">
					</div>
				</fieldset>
				<fieldset>
					<legend><?php esc_html_e( 'Rating', 'digimarket' ); ?></legend>
					<?php foreach ( array( '' => __( 'Any', 'digimarket' ), '4' => '4★ & up', '3' => '3★ & up' ) as $dm_v => $dm_l ) : ?>
						<label class="dm-radio"><input type="radio" name="rating" value="<?php echo esc_attr( $dm_v ); ?>" <?php checked( (string) ( $_GET['rating'] ?? '' ), (string) $dm_v ); ?>> <?php echo esc_html( $dm_l ); ?></label>
					<?php endforeach; ?>
				</fieldset>
				<?php
				$dm_tags = get_terms( array( 'taxonomy' => 'dm_tag', 'number' => 15, 'orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true ) );
				if ( $dm_tags && ! is_wp_error( $dm_tags ) && ( ! $dm_term || 'dm_category' === $dm_term->taxonomy ) ) :
					?>
					<fieldset>
						<legend><?php esc_html_e( 'Tags', 'digimarket' ); ?></legend>
						<div class="dm-chips">
							<?php foreach ( $dm_tags as $dm_tag ) : ?>
								<label class="dm-chip"><input type="radio" name="ptag" value="<?php echo esc_attr( $dm_tag->slug ); ?>" <?php checked( ( $_GET['ptag'] ?? '' ), $dm_tag->slug ); ?>><span><?php echo esc_html( $dm_tag->name ); ?></span></label>
							<?php endforeach; ?>
						</div>
					</fieldset>
				<?php endif; ?>
				<button class="dm-btn dm-btn-primary dm-btn-block" type="submit"><?php esc_html_e( 'Apply filters', 'digimarket' ); ?></button>
				<a class="dm-btn dm-btn-ghost dm-btn-block" href="<?php echo esc_url( $dm_search ? add_query_arg( array( 's' => $dm_search, 'post_type' => 'dm_product' ), home_url( '/' ) ) : $dm_base ); ?>"><?php esc_html_e( 'Reset', 'digimarket' ); ?></a>
			</form>
		</aside>

		<section class="dm-results">
			<div class="dm-results-bar">
				<button class="dm-btn dm-btn-ghost dm-filter-toggle" aria-controls="dm-filters" aria-expanded="false"><?php esc_html_e( 'Filters', 'digimarket' ); ?></button>
				<span class="dm-muted"><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d product', '%d products', (int) $wp_query->found_posts, 'digimarket' ), (int) $wp_query->found_posts ) ); ?></span>
				<form method="get" class="dm-sort">
					<?php
					foreach ( array( 's', 'post_type', 'pcat', 'ptag', 'min_price', 'max_price', 'rating' ) as $dm_keep ) {
						if ( isset( $_GET[ $dm_keep ] ) && '' !== $_GET[ $dm_keep ] ) {
							echo '<input type="hidden" name="' . esc_attr( $dm_keep ) . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET[ $dm_keep ] ) ) ) . '">';
						}
					}
					?>
					<label for="dm-sort" class="screen-reader-text"><?php esc_html_e( 'Sort by', 'digimarket' ); ?></label>
					<select id="dm-sort" name="sort" data-autosubmit>
						<?php
						$dm_sorts = array(
							'newest'      => __( 'Newest', 'digimarket' ),
							'bestselling' => __( 'Best selling', 'digimarket' ),
							'price_asc'   => __( 'Price: low to high', 'digimarket' ),
							'price_desc'  => __( 'Price: high to low', 'digimarket' ),
							'rating'      => __( 'Top rated', 'digimarket' ),
						);
						foreach ( $dm_sorts as $dm_k => $dm_l ) {
							echo '<option value="' . esc_attr( $dm_k ) . '"' . selected( $dm_sort, $dm_k, false ) . '>' . esc_html( $dm_l ) . '</option>';
						}
						?>
					</select>
					<noscript><button class="dm-btn dm-btn-ghost"><?php esc_html_e( 'Sort', 'digimarket' ); ?></button></noscript>
				</form>
			</div>

			<?php if ( have_posts() ) : ?>
				<div class="dm-grid dm-grid-3">
					<?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/product-card' ); endwhile; ?>
				</div>
				<div class="dm-paginate"><?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?></div>
			<?php else : ?>
				<?php dm_empty_state( __( 'No products match your filters', 'digimarket' ), __( 'Try removing a filter or searching for something else.', 'digimarket' ), dm_products_url(), __( 'Clear filters', 'digimarket' ) ); ?>
			<?php endif; ?>
		</section>
	</div>
</div>
<?php
get_footer();
