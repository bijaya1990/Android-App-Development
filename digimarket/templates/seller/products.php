<?php
/**
 * Seller products list with bulk actions.
 *
 * @package DigiMarket
 */

$dm_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
$dm_q      = new WP_Query(
	array(
		'post_type'      => 'dm_product',
		'author'         => $dm_uid,
		'post_status'    => $dm_status && in_array( $dm_status, array( 'publish', 'draft', 'dm_unpublished' ), true ) ? $dm_status : array( 'publish', 'draft', 'dm_unpublished', 'pending' ),
		'posts_per_page' => 20,
		'paged'          => dm_get_page_num(),
		's'              => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
	)
);
?>
<div class="dm-toolbar">
	<nav class="dm-tabs">
		<?php foreach ( array( '' => __( 'All', 'digimarket' ), 'publish' => __( 'Published', 'digimarket' ), 'draft' => __( 'Drafts', 'digimarket' ), 'dm_unpublished' => __( 'Unpublished', 'digimarket' ) ) as $dm_k => $dm_l ) : ?>
			<a class="<?php echo $dm_k === $dm_status ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'status', $dm_k, dm_url( 'dashboard', 'products' ) ) ); ?>"><?php echo esc_html( $dm_l ); ?></a>
		<?php endforeach; ?>
	</nav>
	<form method="get" class="dm-inline-search"><?php if ( ! dm_pretty_permalinks() ) : ?><input type="hidden" name="dm_route" value="dashboard"><input type="hidden" name="dm_tab" value="products"><?php endif; ?><input type="search" name="q" placeholder="<?php esc_attr_e( 'Search your products', 'digimarket' ); ?>" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ) ); ?>" aria-label="<?php esc_attr_e( 'Search your products', 'digimarket' ); ?>"></form>
</div>
<?php if ( $dm_q->have_posts() ) : ?>
	<form method="post" class="dm-card">
		<?php dm_nonce_field( 'seller_product_action' ); ?>
		<div class="dm-bulk">
			<select name="do" aria-label="<?php esc_attr_e( 'Bulk action', 'digimarket' ); ?>">
				<option value=""><?php esc_html_e( 'Bulk actions', 'digimarket' ); ?></option>
				<option value="publish"><?php esc_html_e( 'Publish', 'digimarket' ); ?></option>
				<option value="unpublish"><?php esc_html_e( 'Unpublish', 'digimarket' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete', 'digimarket' ); ?></option>
			</select>
			<button class="dm-btn dm-btn-ghost dm-btn-sm" data-confirm="<?php esc_attr_e( 'Apply this action to the selected products?', 'digimarket' ); ?>"><?php esc_html_e( 'Apply', 'digimarket' ); ?></button>
		</div>
		<div class="dm-table-wrap">
			<table class="dm-table">
				<thead><tr><th><input type="checkbox" data-check-all aria-label="<?php esc_attr_e( 'Select all', 'digimarket' ); ?>"></th><th><?php esc_html_e( 'Product', 'digimarket' ); ?></th><th><?php esc_html_e( 'Price', 'digimarket' ); ?></th><th><?php esc_html_e( 'Status', 'digimarket' ); ?></th><th><?php esc_html_e( 'Sales', 'digimarket' ); ?></th><th><?php esc_html_e( 'Views', 'digimarket' ); ?></th><th><?php esc_html_e( 'Actions', 'digimarket' ); ?></th></tr></thead>
				<tbody>
				<?php while ( $dm_q->have_posts() ) : $dm_q->the_post(); $dm_pid = get_the_ID(); ?>
					<tr>
						<td><input type="checkbox" name="ids[]" value="<?php echo (int) $dm_pid; ?>" aria-label="<?php esc_attr_e( 'Select', 'digimarket' ); ?>"></td>
						<td class="dm-td-product"><span class="dm-mini-thumb"><?php echo dm_product_thumb( $dm_pid, 'thumbnail' ); // phpcs:ignore ?></span><span><a href="<?php echo esc_url( dm_url( 'dashboard', 'edit', $dm_pid ) ); ?>"><strong><?php the_title(); ?></strong></a><br><small class="dm-muted"><?php echo esc_html( dm_delivery_label( get_post_meta( $dm_pid, '_dm_delivery', true ) ) ); ?><?php echo 'license_key' === get_post_meta( $dm_pid, '_dm_delivery', true ) ? ' · ' . esc_html( sprintf( /* translators: %d */ __( '%d keys left', 'digimarket' ), dm_license_keys_available( $dm_pid ) ) ) : ''; ?></small></span></td>
						<td><?php echo wp_kses_post( dm_price_html( $dm_pid ) ); ?></td>
						<td><?php echo dm_status_badge( get_post_status() ); // phpcs:ignore ?><?php echo get_post_meta( $dm_pid, '_dm_forced', true ) ? ' <span class="dm-badge dm-badge-danger">' . esc_html__( 'Locked by admin', 'digimarket' ) . '</span>' : ''; ?></td>
						<td><?php echo (int) get_post_meta( $dm_pid, '_dm_sales', true ); ?></td>
						<td><?php echo (int) get_post_meta( $dm_pid, '_dm_views', true ); ?></td>
						<td class="dm-row-actions">
							<a href="<?php echo esc_url( dm_url( 'dashboard', 'edit', $dm_pid ) ); ?>"><?php esc_html_e( 'Edit', 'digimarket' ); ?></a>
							<a href="<?php the_permalink(); ?>" target="_blank"><?php esc_html_e( 'View', 'digimarket' ); ?></a>
							<button class="dm-link" name="row" value="<?php echo esc_attr( ( 'publish' === get_post_status() ? 'unpublish' : 'publish' ) . ':' . $dm_pid ); ?>"><?php echo 'publish' === get_post_status() ? esc_html__( 'Unpublish', 'digimarket' ) : esc_html__( 'Publish', 'digimarket' ); ?></button>
							<button class="dm-link" name="row" value="duplicate:<?php echo (int) $dm_pid; ?>"><?php esc_html_e( 'Duplicate', 'digimarket' ); ?></button>
							<button class="dm-link dm-danger" name="row" value="delete:<?php echo (int) $dm_pid; ?>" data-confirm="<?php esc_attr_e( 'Delete this product? Buyers keep access to what they already bought.', 'digimarket' ); ?>"><?php esc_html_e( 'Delete', 'digimarket' ); ?></button>
						</td>
					</tr>
				<?php endwhile; wp_reset_postdata(); ?>
				</tbody>
			</table>
		</div>
	</form>
	<?php echo dm_pagination( $dm_q->found_posts, 20, dm_get_page_num(), remove_query_arg( 'pg' ) ); // phpcs:ignore ?>
<?php else : ?>
	<?php dm_empty_state( __( 'You haven’t added any products yet', 'digimarket' ), __( 'Click below to add your first one — it only takes a minute.', 'digimarket' ), dm_url( 'dashboard', 'edit' ), __( 'Add product', 'digimarket' ) ); ?>
<?php endif; ?>
