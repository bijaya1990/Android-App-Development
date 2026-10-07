<?php
/**
 * Category / state / section / tag list page: H1, intro, filter chips or state dropdown, table, pagination.
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
global $wp_query;
$term   = get_queried_object();
$is_cat = $term instanceof WP_Term && 'category' === $term->taxonomy;
$is_sec = $is_cat && in_array( $term->slug, np_section_slugs(), true );
$active = np_active_filter();
$per    = max( 5, (int) np_opt( 'rows_per_page' ) );
$offset = max( 0, (int) get_query_var( 'paged' ) - 1 ) * $per;
$base   = $is_cat ? get_category_link( $term ) : '';
?>
<div class="np-wrap np-layout">
	<div class="np-col">
		<?php np_breadcrumb_html(); ?>
		<header class="np-card">
			<h1><?php echo esc_html( $is_cat ? np_list_heading() : get_the_archive_title() ); ?></h1>
			<?php $intro = $is_cat ? np_term_meta( 'np_intro' ) : ''; ?>
			<?php if ( $intro ) : ?><div class="np-prose"><?php echo wp_kses_post( wpautop( $intro ) ); ?></div>
			<?php elseif ( $is_cat ) : ?><p><?php printf( /* translators: 1: heading, 2: site */ esc_html__( 'Find the latest %1$s with last date, number of posts, advertisement number and direct apply links, updated daily on %2$s.', 'naukripatra' ), esc_html( np_list_heading() ), esc_html( np_opt( 'brand_text' ) ) ); ?></p><?php endif; ?>
			<?php if ( $is_cat && ! $is_sec ) : // State page: section chips. ?>
				<nav class="np-chips" aria-label="<?php esc_attr_e( 'Filter by section', 'naukripatra' ); ?>">
					<a class="np-chip <?php echo $active ? '' : 'is-on'; ?>" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'All', 'naukripatra' ); ?></a>
					<?php foreach ( array( 'latest-jobs', 'admit-card', 'result', 'answer-key', 'syllabus', 'admission' ) as $s ) : $t = get_term_by( 'slug', $s, 'category' ); if ( $t ) : ?>
						<a class="np-chip <?php echo $active === $s ? 'is-on' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'np_section', $s, $base ) ); ?>"><?php echo esc_html( $t->name ); ?></a>
					<?php endif; endforeach; ?>
				</nav>
			<?php elseif ( $is_sec ) : // Section page: state dropdown. ?>
				<form class="np-statefilter" method="get" action="<?php echo esc_url( $base ); ?>">
					<label for="np-state-f"><?php esc_html_e( 'State:', 'naukripatra' ); ?></label>
					<select id="np-state-f" name="np_state" class="np-select" data-np-submit>
						<option value=""><?php esc_html_e( 'All states', 'naukripatra' ); ?></option>
						<?php foreach ( np_locations() as $l ) : ?><option value="<?php echo esc_attr( $l[0] ); ?>" <?php selected( $active, $l[0] ); ?>><?php echo esc_html( $l[1] ); ?></option><?php endforeach; ?>
					</select>
					<noscript><button class="np-btn np-btn--sm" type="submit"><?php esc_html_e( 'Filter', 'naukripatra' ); ?></button></noscript>
				</form>
			<?php endif; ?>
		</header>
		<?php if ( have_posts() ) : ?>
			<section class="np-card">
				<?php if ( np_opt( 'live_filter' ) ) : ?>
					<label class="screen-reader-text" for="np-live"><?php esc_html_e( 'Filter this list', 'naukripatra' ); ?></label>
					<input type="search" id="np-live" class="np-input" placeholder="<?php esc_attr_e( 'Type to filter this page...', 'naukripatra' ); ?>" data-np-live>
				<?php endif; ?>
				<?php np_list_table( $wp_query, $offset ); ?>
				<?php np_pagination(); ?>
			</section>
		<?php else : ?>
			<section class="np-card"><p><?php esc_html_e( 'No posts found here yet. Please check again soon or browse another section.', 'naukripatra' ); ?></p></section>
		<?php endif; ?>
		<?php $seo = $is_cat ? np_term_meta( 'np_seo_text' ) : ''; if ( $seo ) : ?><section class="np-card np-prose"><?php echo wp_kses_post( wpautop( $seo ) ); ?></section><?php endif; ?>
	</div>
	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
