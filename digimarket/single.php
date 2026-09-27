<?php
/**
 * Single article: featured image, TOC, reading time, products mentioned,
 * author box, share buttons, related articles.
 *
 * @package DigiMarket
 */

get_header();

while ( have_posts() ) :
	the_post();
	$dm_pid     = get_the_ID();
	$dm_raw     = apply_filters( 'the_content', get_the_content() );
	list( $dm_toc, $dm_html ) = dm_article_toc( $dm_raw );
	$dm_min     = dm_reading_minutes( get_the_content() );
	$dm_cat     = dm_primary_term( $dm_pid, 'category' );
	$dm_author  = dm_store_opt( 'owner_name' ) ? dm_store_opt( 'owner_name' ) : get_the_author();
	$dm_photo   = (int) dm_store_opt( 'owner_photo' );
	$dm_prods   = dm_article_products( $dm_pid, $dm_raw );
	$dm_updated = get_the_modified_time( 'U' ) - get_the_time( 'U' ) > 7 * DAY_IN_SECONDS;
	$dm_share   = dm_whatsapp_share_url( get_permalink(), get_the_title() );
	?>
	<div class="dm-container dm-page dm-article">
		<?php dm_render_breadcrumbs(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'dm-art' ); ?>>
			<header class="dm-art-head">
				<?php if ( $dm_cat && 'uncategorized' !== $dm_cat->slug ) : ?><a class="dm-arow-cat" href="<?php echo esc_url( dm_articles_url( array( 'topic' => $dm_cat->slug ) ) ); ?>"><?php echo esc_html( $dm_cat->name ); ?></a><?php endif; ?>
				<h1><?php the_title(); ?></h1>
				<div class="dm-art-meta">
					<?php echo $dm_photo ? wp_get_attachment_image( $dm_photo, array( 40, 40 ), false, array( 'class' => 'dm-art-avatar' ) ) : get_avatar( get_the_author_meta( 'ID' ), 40, '', '', array( 'class' => 'dm-art-avatar' ) ); ?>
					<span><strong><?php echo esc_html( $dm_author ); ?></strong><small>
						<?php if ( $dm_updated ) : ?>
							<?php echo esc_html( sprintf( /* translators: %s date */ __( 'Updated %s', 'digimarket' ), get_the_modified_date() ) ); ?>
						<?php else : ?>
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<?php endif; ?>
						· <?php echo esc_html( sprintf( /* translators: %d */ __( '%d min read', 'digimarket' ), $dm_min ) ); ?>
					</small></span>
				</div>
			</header>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="dm-art-hero"><?php the_post_thumbnail( 'full', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 900px) 100vw, 900px' ) ); ?></figure>
			<?php endif; ?>

			<div class="dm-art-layout<?php echo count( $dm_toc ) > 2 ? ' has-toc' : ''; ?>">
				<?php if ( count( $dm_toc ) > 2 ) : ?>
					<aside class="dm-toc">
						<details open>
							<summary><?php esc_html_e( 'Table of contents', 'digimarket' ); ?> <?php echo dm_icon( 'chev-d', 16 ); // phpcs:ignore ?></summary>
							<ol>
								<?php foreach ( $dm_toc as $dm_t ) : ?>
									<li class="lvl-<?php echo (int) $dm_t[0]; ?>"><a href="#<?php echo esc_attr( $dm_t[2] ); ?>"><?php echo esc_html( $dm_t[1] ); ?></a></li>
								<?php endforeach; ?>
							</ol>
						</details>
					</aside>
				<?php endif; ?>
				<div class="dm-art-body">
					<div class="dm-prose dm-art-content"><?php echo $dm_html; // phpcs:ignore — filtered post content. ?></div>
					<?php wp_link_pages( array( 'before' => '<nav class="dm-page-links">', 'after' => '</nav>' ) ); ?>

					<?php if ( $dm_prods ) : ?>
						<section class="dm-art-products">
							<h2><?php esc_html_e( 'Products mentioned', 'digimarket' ); ?></h2>
							<div class="dm-grid dm-grid-3">
								<?php foreach ( array_slice( $dm_prods, 0, 6 ) as $dm_p ) { dm_render_product_card( $dm_p ); } ?>
							</div>
						</section>
					<?php endif; ?>

					<div class="dm-art-share">
						<span><?php esc_html_e( 'Share this article', 'digimarket' ); ?></span>
						<a class="dm-tool is-wa" href="<?php echo esc_url( $dm_share ); ?>" target="_blank" rel="noopener" data-track="share" data-ref="<?php echo (int) $dm_pid; ?>"><?php echo dm_icon_whatsapp( 18 ); // phpcs:ignore ?> WhatsApp</a>
						<a class="dm-tool" href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( get_permalink() ) ); ?>" target="_blank" rel="noopener">Facebook</a>
						<a class="dm-tool" href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . rawurlencode( get_permalink() ) . '&text=' . rawurlencode( get_the_title() ) ); ?>" target="_blank" rel="noopener">X</a>
						<button type="button" class="dm-tool dm-copy" data-copy="<?php echo esc_attr( get_permalink() ); ?>"><?php echo dm_icon( 'copy', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Copy link', 'digimarket' ); ?></button>
					</div>

					<div class="dm-author">
						<?php echo $dm_photo ? wp_get_attachment_image( $dm_photo, array( 72, 72 ), false, array( 'class' => 'dm-author-img' ) ) : get_avatar( get_the_author_meta( 'ID' ), 72, '', '', array( 'class' => 'dm-author-img' ) ); ?>
						<div>
							<span class="dm-kicker"><?php esc_html_e( 'Written by', 'digimarket' ); ?></span>
							<strong><?php echo esc_html( $dm_author ); ?></strong>
							<p><?php echo esc_html( dm_store_opt( 'owner_bio' ) ? dm_store_opt( 'owner_bio' ) : get_the_author_meta( 'description' ) ); ?></p>
							<a href="<?php echo esc_url( dm_legal_url( 'about' ) ); ?>"><?php esc_html_e( 'More about us', 'digimarket' ); ?> →</a>
						</div>
					</div>

					<nav class="dm-art-nav" aria-label="<?php esc_attr_e( 'More articles', 'digimarket' ); ?>">
						<?php
						$dm_prev = get_previous_post();
						$dm_next = get_next_post();
						if ( $dm_prev ) {
							echo '<a class="is-prev" href="' . esc_url( get_permalink( $dm_prev ) ) . '"><small>' . esc_html__( '← Previous', 'digimarket' ) . '</small>' . esc_html( get_the_title( $dm_prev ) ) . '</a>';
						}
						if ( $dm_next ) {
							echo '<a class="is-next" href="' . esc_url( get_permalink( $dm_next ) ) . '"><small>' . esc_html__( 'Next →', 'digimarket' ) . '</small>' . esc_html( get_the_title( $dm_next ) ) . '</a>';
						}
						?>
					</nav>
				</div>
			</div>
		</article>

		<?php
		$dm_rel = get_posts(
			array(
				'post_type'    => 'post',
				'numberposts'  => 3,
				'post__not_in' => array( $dm_pid ),
				'category__in' => $dm_cat ? array( $dm_cat->term_id ) : array(),
			)
		);
		if ( count( $dm_rel ) < 3 ) {
			$dm_rel = array_merge( $dm_rel, get_posts( array( 'post_type' => 'post', 'numberposts' => 3 - count( $dm_rel ), 'post__not_in' => array_merge( array( $dm_pid ), wp_list_pluck( $dm_rel, 'ID' ) ) ) ) );
		}
		if ( $dm_rel ) :
			?>
			<section class="dm-art-related">
				<h2><?php esc_html_e( 'Related articles', 'digimarket' ); ?></h2>
				<div class="dm-alist"><?php foreach ( $dm_rel as $dm_r ) { dm_article_row( $dm_r ); } ?></div>
			</section>
		<?php endif; ?>

		<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>
	</div>
	<?php
endwhile;

get_footer();
