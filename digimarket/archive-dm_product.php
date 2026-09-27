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
$dm_sort   = isset( $_GET['sort'] ) ? sanitize_key( $_GET['sort'] ) : 'newest'; // phpcs:ignore
$dm_base   = $dm_term ? get_term_link( $dm_term ) : dm_products_url();
if ( $dm_term ) {
	$dm_title = $dm_term->name;
} elseif ( $dm_search ) {
	/* translators: %s search */
	$dm_title = sprintf( __( 'Results for “%s”', 'digimarket' ), $dm_search );
} else {
	$dm_title = __( 'All products', 'digimarket' );
}
$dm_intro = $dm_term ? (string) get_term_meta( $dm_term->term_id, 'dm_intro', true ) : '';
$dm_intro = $dm_intro ? $dm_intro : ( $dm_term ? $dm_term->description : '' );
$dm_faq   = $dm_term ? dm_parse_pairs( get_term_meta( $dm_term->term_id, 'dm_faq', true ) ) : array();
$dm_subs  = ( $dm_term && 'dm_category' === $dm_term->taxonomy ) ? dm_categories( array( 'parent' => $dm_term->term_id, 'hide_empty' => true ) ) : array();
$dm_keep  = array( 's', 'post_type', 'pcat', 'ptag', 'min_price', 'max_price', 'rating', 'type', 'age' );
$dm_qs    = array();
foreach ( $dm_keep as $dm_k ) {
	if ( isset( $_GET[ $dm_k ] ) && '' !== $_GET[ $dm_k ] ) { // phpcs:ignore
		$dm_qs[ $dm_k ] = sanitize_text_field( wp_unslash( $_GET[ $dm_k ] ) ); // phpcs:ignore
	}
}
$dm_has_age = (bool) get_posts( array( 'post_type' => 'dm_product', 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_dm_age_band', 'value' => '', 'compare' => '!=' ) ) ) );

// Matching shops for a search query (marketplace mode only).
$dm_shops = array();
if ( $dm_search && ! dm_single_seller_mode() ) {
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
<div class="dm-container dm-page dm-cat-page">
	<?php dm_render_breadcrumbs(); ?>
	<?php
	if ( $dm_term && 'dm_category' === $dm_term->taxonomy && dm_paged() < 2 ) {
		echo '<div class="dm-cat-banner">';
		dm_render_wide_banner( 'category', $dm_term->term_id, false );
		echo '</div>';
	}
	?>
	<header class="dm-cat-head">
		<div>
			<h1><?php echo esc_html( $dm_title ); ?></h1>
			<p class="dm-muted"><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d item', '%d items', (int) $wp_query->found_posts, 'digimarket' ), (int) $wp_query->found_posts ) ); ?></p>
		</div>
		<?php if ( $dm_intro && dm_paged() < 2 ) : ?>
			<div class="dm-cat-intro" data-clamp><div class="dm-prose"><?php echo wp_kses_post( wpautop( $dm_intro ) ); ?></div><button type="button" class="dm-link dm-clamp-btn" hidden><?php esc_html_e( 'Read more', 'digimarket' ); ?></button></div>
		<?php endif; ?>
	</header>

	<?php if ( $dm_subs ) : ?>
		<nav class="dm-subcats" aria-label="<?php esc_attr_e( 'Sub-categories', 'digimarket' ); ?>">
			<?php foreach ( $dm_subs as $dm_i => $dm_sc ) : ?>
				<a href="<?php echo esc_url( get_term_link( $dm_sc ) ); ?>"><?php echo dm_category_icon_html( $dm_sc, $dm_i ); // phpcs:ignore ?><span><?php echo esc_html( $dm_sc->name ); ?></span></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( $dm_shops ) : ?>
		<section class="dm-subsection">
			<h2 class="dm-h3"><?php esc_html_e( 'Shops', 'digimarket' ); ?></h2>
			<div class="dm-carousel"><?php foreach ( $dm_shops as $dm_sid ) { get_template_part( 'template-parts/shop-card', null, array( 'seller_id' => $dm_sid ) ); } ?></div>
		</section>
	<?php endif; ?>

	<div class="dm-catalogue">
		<aside class="dm-filters" id="dm-filters" aria-label="<?php esc_attr_e( 'Filters', 'digimarket' ); ?>">
			<form method="get" action="<?php echo esc_url( $dm_search ? home_url( '/' ) : $dm_base ); ?>" class="dm-filter-form">
				<div class="dm-filter-top"><strong><?php esc_html_e( 'Filters', 'digimarket' ); ?></strong><a href="<?php echo esc_url( $dm_search ? add_query_arg( array( 's' => $dm_search, 'post_type' => 'dm_product' ), home_url( '/' ) ) : $dm_base ); ?>"><?php esc_html_e( 'Clear all', 'digimarket' ); ?></a></div>
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
							<label class="dm-radio"><input type="radio" name="pcat" value="<?php echo esc_attr( $dm_cat->slug ); ?>" <?php checked( ( $_GET['pcat'] ?? '' ), $dm_cat->slug ); // phpcs:ignore ?>> <?php echo esc_html( $dm_cat->name ); ?></label>
						<?php endforeach; ?>
					</fieldset>
				<?php endif; ?>
				<fieldset>
					<legend><?php esc_html_e( 'Type', 'digimarket' ); ?></legend>
					<?php foreach ( array( '' => __( 'Everything', 'digimarket' ), 'digital' => __( 'Instant download', 'digimarket' ), 'service' => __( 'Website services', 'digimarket' ), 'affiliate' => __( 'Partner deals', 'digimarket' ) ) as $dm_v => $dm_l ) : ?>
						<label class="dm-radio"><input type="radio" name="type" value="<?php echo esc_attr( $dm_v ); ?>" <?php checked( (string) ( $_GET['type'] ?? '' ), (string) $dm_v ); // phpcs:ignore ?>> <?php echo esc_html( $dm_l ); ?></label>
					<?php endforeach; ?>
				</fieldset>
				<fieldset>
					<legend><?php esc_html_e( 'Price', 'digimarket' ); ?> (<?php echo esc_html( dm_opt( 'currency_symbol' ) ); ?>)</legend>
					<div class="dm-price-range">
						<input type="number" min="0" step="1" name="min_price" placeholder="<?php esc_attr_e( 'Min', 'digimarket' ); ?>" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['min_price'] ?? '' ) ) ); // phpcs:ignore ?>" aria-label="<?php esc_attr_e( 'Minimum price', 'digimarket' ); ?>">
						<span>–</span>
						<input type="number" min="0" step="1" name="max_price" placeholder="<?php esc_attr_e( 'Max', 'digimarket' ); ?>" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['max_price'] ?? '' ) ) ); // phpcs:ignore ?>" aria-label="<?php esc_attr_e( 'Maximum price', 'digimarket' ); ?>">
					</div>
					<div class="dm-chips dm-price-chips">
						<?php foreach ( array( array( 0, 99 ), array( 100, 499 ), array( 500, 1999 ), array( 2000, '' ) ) as $dm_pr ) : ?>
							<a class="dm-chip-link" href="<?php echo esc_url( add_query_arg( array_merge( $dm_qs, array( 'min_price' => $dm_pr[0], 'max_price' => $dm_pr[1] ) ), $dm_search ? home_url( '/' ) : $dm_base ) ); ?>"><?php echo esc_html( '' === $dm_pr[1] ? dm_money_short( $dm_pr[0] ) . '+' : dm_money_short( $dm_pr[0] ) . '–' . dm_money_short( $dm_pr[1] ) ); ?></a>
						<?php endforeach; ?>
					</div>
				</fieldset>
				<fieldset>
					<legend><?php esc_html_e( 'Customer rating', 'digimarket' ); ?></legend>
					<?php foreach ( array( '' => __( 'Any', 'digimarket' ), '4' => __( '4★ & above', 'digimarket' ), '3' => __( '3★ & above', 'digimarket' ) ) as $dm_v => $dm_l ) : ?>
						<label class="dm-radio"><input type="radio" name="rating" value="<?php echo esc_attr( $dm_v ); ?>" <?php checked( (string) ( $_GET['rating'] ?? '' ), (string) $dm_v ); // phpcs:ignore ?>> <?php echo esc_html( $dm_l ); ?></label>
					<?php endforeach; ?>
				</fieldset>
				<?php if ( $dm_has_age ) : ?>
					<fieldset>
						<legend><?php esc_html_e( 'Age', 'digimarket' ); ?></legend>
						<select name="age">
							<?php foreach ( dm_age_bands() as $dm_v => $dm_l ) : ?>
								<option value="<?php echo esc_attr( $dm_v ); ?>" <?php selected( (string) ( $_GET['age'] ?? '' ), (string) $dm_v ); // phpcs:ignore ?>><?php echo esc_html( '' === $dm_v ? __( 'Any age', 'digimarket' ) : $dm_l ); ?></option>
							<?php endforeach; ?>
						</select>
					</fieldset>
				<?php endif; ?>
				<?php
				$dm_tags = get_terms( array( 'taxonomy' => 'dm_tag', 'number' => 15, 'orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true ) );
				if ( $dm_tags && ! is_wp_error( $dm_tags ) && ( ! $dm_term || 'dm_category' === $dm_term->taxonomy ) ) :
					?>
					<fieldset>
						<legend><?php esc_html_e( 'Tags', 'digimarket' ); ?></legend>
						<div class="dm-chips">
							<?php foreach ( $dm_tags as $dm_tag ) : ?>
								<label class="dm-chip"><input type="radio" name="ptag" value="<?php echo esc_attr( $dm_tag->slug ); ?>" <?php checked( ( $_GET['ptag'] ?? '' ), $dm_tag->slug ); // phpcs:ignore ?>><span><?php echo esc_html( $dm_tag->name ); ?></span></label>
							<?php endforeach; ?>
						</div>
					</fieldset>
				<?php endif; ?>
				<button class="dm-btn dm-btn-primary dm-btn-block" type="submit"><?php esc_html_e( 'Apply filters', 'digimarket' ); ?></button>
			</form>
		</aside>

		<section class="dm-results">
			<div class="dm-results-bar">
				<button class="dm-btn dm-btn-ghost dm-filter-toggle" aria-controls="dm-filters" aria-expanded="false"><?php echo dm_icon( 'filter', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Filters', 'digimarket' ); ?></button>
				<nav class="dm-sorttabs" aria-label="<?php esc_attr_e( 'Sort by', 'digimarket' ); ?>">
					<span class="dm-sorttabs-label"><?php esc_html_e( 'Sort by', 'digimarket' ); ?></span>
					<?php
					$dm_sorts = array(
						'popular'     => __( 'Popularity', 'digimarket' ),
						'bestselling' => __( 'Best selling', 'digimarket' ),
						'price_asc'   => __( 'Price — Low to High', 'digimarket' ),
						'price_desc'  => __( 'Price — High to Low', 'digimarket' ),
						'newest'      => __( 'Newest first', 'digimarket' ),
						'rating'      => __( 'Top rated', 'digimarket' ),
					);
					foreach ( $dm_sorts as $dm_k => $dm_l ) {
						echo '<a href="' . esc_url( add_query_arg( array_merge( $dm_qs, array( 'sort' => $dm_k ) ), $dm_search ? home_url( '/' ) : $dm_base ) ) . '"' . ( $dm_sort === $dm_k ? ' aria-current="true" class="is-active"' : '' ) . ' rel="nofollow">' . esc_html( $dm_l ) . '</a>';
					}
					?>
				</nav>
			</div>

			<?php if ( have_posts() ) : ?>
				<div class="dm-grid dm-grid-3">
					<?php
					$dm_n = 0;
					while ( have_posts() ) :
						the_post();
						dm_render_product_card( get_the_ID(), array( 'eager' => $dm_n++ < 4, 'h' => 'h2' ) );
					endwhile;
					?>
				</div>
				<div class="dm-paginate"><?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '‹', 'next_text' => '›' ) ); ?></div>
			<?php else : ?>
				<?php dm_empty_state( __( 'No products match your filters', 'digimarket' ), __( 'Try removing a filter or searching for something else.', 'digimarket' ), dm_products_url(), __( 'Clear filters', 'digimarket' ) ); ?>
				<?php if ( $dm_search && dm_opt( 'whatsapp_number' ) ) : ?>
					<p class="dm-center"><a class="dm-btn dm-btn-wa" href="<?php echo esc_url( dm_whatsapp_url( sprintf( /* translators: %s */ __( 'Hi, I was looking for "%s" on your site. Do you have it?', 'digimarket' ), $dm_search ) ) ); ?>" target="_blank" rel="noopener" data-track="whatsapp"><?php echo dm_icon_whatsapp( 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Ask us on WhatsApp', 'digimarket' ); ?></a></p>
				<?php endif; ?>
			<?php endif; ?>
		</section>
	</div>

	<?php if ( $dm_faq && dm_paged() < 2 ) : ?>
		<div class="dm-cat-faq"><?php dm_render_faq( $dm_faq, sprintf( /* translators: %s */ __( '%s — questions & answers', 'digimarket' ), $dm_title ) ); ?></div>
	<?php endif; ?>
</div>
<?php
get_footer();
