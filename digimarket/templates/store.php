<?php
/**
 * Public shop page: /store/{slug}
 *
 * @package DigiMarket
 */

$dm_slug = sanitize_title( get_query_var( 'dm_store' ) );
$dm_sid  = dm_get_seller_by_slug( $dm_slug );

if ( ! $dm_sid ) {
	// Old slug? Redirect permanently to the new shop URL.
	$dm_old = get_users( array( 'meta_key' => 'dm_old_slugs', 'meta_value' => '"' . $dm_slug . '"', 'meta_compare' => 'LIKE', 'number' => 1, 'fields' => 'ID' ) );
	if ( $dm_old ) {
		wp_safe_redirect( dm_store_url( (int) $dm_old[0] ), 301 );
		exit;
	}
}

$dm_visible = $dm_sid && ( 'active' === dm_seller_status( $dm_sid ) || get_current_user_id() === $dm_sid || current_user_can( 'dm_view_marketplace' ) );
if ( ! $dm_visible ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_header();
	echo '<div class="dm-container dm-page">';
	dm_empty_state( __( 'Shop not found', 'digimarket' ), __( 'This shop does not exist or is not available right now.', 'digimarket' ), dm_url( 'shops' ), __( 'Browse all shops', 'digimarket' ) );
	echo '</div>';
	get_footer();
	return;
}

$dm_paged  = max( 1, (int) get_query_var( 'paged' ) );
$dm_q      = new WP_Query(
	array(
		'post_type'      => 'dm_product',
		'post_status'    => 'publish',
		'author'         => $dm_sid,
		'posts_per_page' => 12,
		'paged'          => $dm_paged,
		's'              => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
	)
);
if ( ! empty( $_GET['sort'] ) ) {
	dm_apply_catalogue_args( $dm_q );
	$dm_q->set( 'author', $dm_sid );
	$dm_q->get_posts();
}
$dm_banner = dm_shop_banner_url( $dm_sid );
$dm_rating = dm_shop_rating( $dm_sid );
$dm_social = array_filter(
	array(
		'Website'   => get_user_meta( $dm_sid, 'dm_social_website', true ),
		'Instagram' => get_user_meta( $dm_sid, 'dm_social_instagram', true ),
		'YouTube'   => get_user_meta( $dm_sid, 'dm_social_youtube', true ),
		'X'         => get_user_meta( $dm_sid, 'dm_social_twitter', true ),
	)
);
get_header();
?>
<div class="dm-store" style="--dm-accent:<?php echo esc_attr( dm_shop_accent( $dm_sid ) ); ?>">
	<div class="dm-store-banner"<?php echo $dm_banner ? ' style="background-image:url(' . esc_url( $dm_banner ) . ')"' : ''; ?>></div>
	<div class="dm-container">
		<?php if ( 'active' !== dm_seller_status( $dm_sid ) ) : ?>
			<div class="dm-notice dm-notice-warning"><?php esc_html_e( 'Preview — this shop is not public yet.', 'digimarket' ); ?></div>
		<?php endif; ?>
		<div class="dm-store-head">
			<?php echo dm_shop_logo( $dm_sid, 'dm-avatar dm-avatar-xl' ); // phpcs:ignore ?>
			<div class="dm-store-id">
				<h1><?php echo esc_html( dm_shop_name( $dm_sid ) ); ?></h1>
				<div class="dm-store-stats">
					<?php if ( $dm_rating['count'] ) : ?><span><?php echo dm_stars( $dm_rating['avg'], $dm_rating['count'] ); // phpcs:ignore ?></span><?php endif; ?>
					<span><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d product', '%d products', dm_seller_product_count( $dm_sid ), 'digimarket' ), dm_seller_product_count( $dm_sid ) ) ); ?></span>
					<span><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d follower', '%d followers', dm_follower_count( $dm_sid ), 'digimarket' ), dm_follower_count( $dm_sid ) ) ); ?></span>
				</div>
			</div>
			<div class="dm-store-actions">
				<?php if ( get_current_user_id() === $dm_sid ) : ?>
					<a class="dm-btn dm-btn-primary" href="<?php echo esc_url( dm_url( 'dashboard', 'settings' ) ); ?>"><?php esc_html_e( 'Edit shop', 'digimarket' ); ?></a>
				<?php elseif ( is_user_logged_in() ) : ?>
					<form method="post"><?php dm_nonce_field( 'follow' ); ?><input type="hidden" name="seller_id" value="<?php echo (int) $dm_sid; ?>"><button class="dm-btn <?php echo dm_is_following( $dm_sid ) ? 'dm-btn-ghost' : 'dm-btn-primary'; ?>"><?php echo dm_is_following( $dm_sid ) ? esc_html__( 'Following ✓', 'digimarket' ) : esc_html__( '+ Follow shop', 'digimarket' ); ?></button></form>
				<?php else : ?>
					<a class="dm-btn dm-btn-primary" href="<?php echo esc_url( dm_url( 'login', '', '', array( 'redirect_to' => rawurlencode( dm_store_url( $dm_sid ) ) ) ) ); ?>"><?php esc_html_e( '+ Follow shop', 'digimarket' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php $dm_bio = get_user_meta( $dm_sid, 'dm_shop_bio', true ); ?>
		<?php if ( $dm_bio || $dm_social ) : ?>
			<div class="dm-store-bio">
				<?php if ( $dm_bio ) : ?><p><?php echo nl2br( esc_html( $dm_bio ) ); ?></p><?php endif; ?>
				<?php if ( $dm_social ) : ?>
					<div class="dm-social"><?php foreach ( $dm_social as $dm_net => $dm_link ) : ?><a href="<?php echo esc_url( $dm_link ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( $dm_net ); ?> ↗</a><?php endforeach; ?></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="dm-results-bar">
			<form method="get" class="dm-inline-search">
				<input type="search" name="q" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ) ); ?>" placeholder="<?php esc_attr_e( 'Search this shop…', 'digimarket' ); ?>" aria-label="<?php esc_attr_e( 'Search this shop', 'digimarket' ); ?>">
				<select name="sort" data-autosubmit aria-label="<?php esc_attr_e( 'Sort', 'digimarket' ); ?>">
					<?php foreach ( array( 'newest' => __( 'Newest', 'digimarket' ), 'bestselling' => __( 'Best selling', 'digimarket' ), 'price_asc' => __( 'Price ↑', 'digimarket' ), 'price_desc' => __( 'Price ↓', 'digimarket' ), 'rating' => __( 'Top rated', 'digimarket' ) ) as $dm_k => $dm_l ) : ?>
						<option value="<?php echo esc_attr( $dm_k ); ?>" <?php selected( $_GET['sort'] ?? 'newest', $dm_k ); ?>><?php echo esc_html( $dm_l ); ?></option>
					<?php endforeach; ?>
				</select>
			</form>
		</div>

		<?php if ( $dm_q->have_posts() ) : ?>
			<div class="dm-grid">
				<?php while ( $dm_q->have_posts() ) : $dm_q->the_post(); get_template_part( 'template-parts/product-card' ); endwhile; wp_reset_postdata(); ?>
			</div>
			<?php
			echo '<div class="dm-paginate">' . paginate_links( // phpcs:ignore
				array(
					'total'   => $dm_q->max_num_pages,
					'current' => $dm_paged,
					'base'    => trailingslashit( dm_store_url( $dm_sid ) ) . '%_%',
					'format'  => dm_pretty_permalinks() ? 'page/%#%/' : '&paged=%#%',
				)
			) . '</div>';
			?>
		<?php else : ?>
			<?php dm_empty_state( __( 'No products yet', 'digimarket' ), __( 'This shop hasn’t published any products yet. Follow it to get notified.', 'digimarket' ) ); ?>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
