<?php
/**
 * Articles list (/articles/) — list view with thumbnails and a View button.
 * Reached from the menu only; never shown on the homepage.
 *
 * @package DigiMarket
 */

get_header();

$dm_topic = isset( $_GET['topic'] ) ? sanitize_title( wp_unslash( $_GET['topic'] ) ) : ''; // phpcs:ignore
$dm_q     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore
$dm_page  = dm_paged();
$dm_args  = array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 10,
	'paged'          => $dm_page,
	'ignore_sticky_posts' => true,
);
if ( $dm_topic ) {
	$dm_args['category_name'] = $dm_topic;
}
if ( $dm_q ) {
	$dm_args['s'] = $dm_q;
}
$dm_list   = new WP_Query( $dm_args );
$dm_topics = get_categories( array( 'hide_empty' => true, 'exclude' => array( (int) get_option( 'default_category' ) ) ) );
$dm_label  = dm_store_opt( 'articles_label' );
?>
<div class="dm-container dm-page dm-articles">
	<?php dm_render_breadcrumbs(); ?>
	<?php
	if ( $dm_page < 2 && ! $dm_q && ! $dm_topic ) {
		echo '<div class="dm-cat-banner">';
		dm_render_wide_banner( 'articles', 0, false );
		echo '</div>';
	}
	?>
	<header class="dm-articles-head">
		<div>
			<h1><?php echo esc_html( $dm_label ); ?></h1>
			<p class="dm-muted"><?php esc_html_e( 'Guides and tips on websites, studies, careers and more.', 'digimarket' ); ?></p>
		</div>
		<form class="dm-articles-search" method="get" action="<?php echo esc_url( dm_articles_url() ); ?>" role="search">
			<?php if ( ! dm_pretty_permalinks() ) : ?><input type="hidden" name="dm_route" value="articles"><?php endif; ?>
			<label class="screen-reader-text" for="dm-aq"><?php esc_html_e( 'Search articles', 'digimarket' ); ?></label>
			<?php echo dm_icon( 'search', 18 ); // phpcs:ignore ?>
			<input id="dm-aq" type="search" name="q" value="<?php echo esc_attr( $dm_q ); ?>" placeholder="<?php esc_attr_e( 'Search articles…', 'digimarket' ); ?>">
		</form>
	</header>

	<?php if ( $dm_topics ) : ?>
		<nav class="dm-topic-chips" aria-label="<?php esc_attr_e( 'Topics', 'digimarket' ); ?>">
			<a class="dm-chip-link<?php echo $dm_topic ? '' : ' is-active'; ?>" href="<?php echo esc_url( dm_articles_url() ); ?>"><?php esc_html_e( 'All', 'digimarket' ); ?></a>
			<?php foreach ( $dm_topics as $dm_t ) : ?>
				<a class="dm-chip-link<?php echo $dm_topic === $dm_t->slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( dm_articles_url( array( 'topic' => $dm_t->slug ) ) ); ?>"><?php echo esc_html( $dm_t->name ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( $dm_list->have_posts() ) : ?>
		<div class="dm-alist">
			<?php
			$dm_i = 0;
			while ( $dm_list->have_posts() ) {
				$dm_list->the_post();
				dm_article_row( get_post(), $dm_i++ < 2 );
			}
			wp_reset_postdata();
			?>
		</div>
		<?php if ( $dm_list->max_num_pages > 1 ) : ?>
			<nav class="dm-pagination" aria-label="<?php esc_attr_e( 'Pages', 'digimarket' ); ?>">
				<?php
				for ( $dm_n = 1; $dm_n <= $dm_list->max_num_pages; $dm_n++ ) {
					$dm_url = 1 === $dm_n ? dm_articles_url() : ( dm_pretty_permalinks() ? dm_pretty_url( 'articles/page/' . $dm_n . '/' ) : add_query_arg( 'paged', $dm_n, dm_articles_url() ) );
					if ( $dm_topic ) {
						$dm_url = add_query_arg( 'topic', $dm_topic, $dm_url );
					}
					if ( $dm_q ) {
						$dm_url = add_query_arg( 'q', $dm_q, $dm_url );
					}
					echo '<a class="dm-pagenum' . ( $dm_n === $dm_page ? ' current' : '' ) . '" href="' . esc_url( $dm_url ) . '"' . ( $dm_n === $dm_page ? ' aria-current="page"' : '' ) . '>' . (int) $dm_n . '</a>';
				}
				?>
			</nav>
		<?php endif; ?>
	<?php else : ?>
		<div class="dm-empty">
			<div class="dm-empty-icon"><?php echo dm_icon( 'book', 26 ); // phpcs:ignore ?></div>
			<h2><?php esc_html_e( 'No articles yet', 'digimarket' ); ?></h2>
			<p><?php echo $dm_q || $dm_topic ? esc_html__( 'Nothing matches that search. Try another word or topic.', 'digimarket' ) : esc_html__( 'New guides are on the way — check back soon.', 'digimarket' ); ?></p>
			<?php if ( $dm_q || $dm_topic ) : ?><a class="dm-btn dm-btn-primary" href="<?php echo esc_url( dm_articles_url() ); ?>"><?php esc_html_e( 'See all articles', 'digimarket' ); ?></a><?php endif; ?>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
