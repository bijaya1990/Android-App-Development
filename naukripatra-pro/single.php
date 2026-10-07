<?php
/**
 * Single job post reader.
 * Order: overview, ad, important links, article, FAQ (in article), related, prev/next, share.
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) :
	the_post();
	$id     = get_the_ID();
	$g      = function ( $k ) use ( $id ) {
		return nppro_meta( $id, $k );
	};
	$apply  = $g( 'apply_link' );
	$notif  = $g( 'notification_link' );
	$site   = $g( 'official_website' );
	$first  = function ( $v ) {
		return preg_match( '#https?://[^\s,]+#i', $v, $m ) ? $m[0] : '';
	};
	$rows   = nppro_overview_rows( $id );
	$states = nppro_states_of( $id );
	$sec    = nppro_section_of( $id );
	$title  = get_the_title();
	$url    = get_permalink();
	?>
<div class="np-wrap np-layout np-layout--single">
	<article class="np-col np-article" data-np-view>
		<?php nppro_breadcrumb_html(); ?>
		<header class="np-card np-hero">
			<?php if ( $sec ) : ?><a class="np-kicker" href="<?php echo esc_url( get_category_link( $sec ) ); ?>"><?php echo esc_html( $sec->name ); ?></a><?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<p class="np-meta">
				<span><?php esc_html_e( 'Published', 'naukripatra' ); ?>: <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span>
				<span><?php esc_html_e( 'Updated', 'naukripatra' ); ?>: <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date() ); ?></time></span>
				<span><?php /* translators: %d minutes */ printf( esc_html__( '%d min read', 'naukripatra' ), (int) nppro_reading_time( $id ) ); ?></span>
			</p>
			<?php if ( $states ) : ?>
				<p class="np-chips"><?php foreach ( $states as $s ) : ?><a class="np-chip" href="<?php echo esc_url( get_category_link( $s ) ); ?>"><?php echo esc_html( $s->name ); ?></a><?php endforeach; ?></p>
			<?php endif; ?>
			<?php
			$ds    = nppro_date_status( $g( 'last_date' ) );
			$facts = array();
			if ( $g( 'last_date' ) ) {
				$facts[] = array( __( 'Last Date', 'naukripatra' ), $ds['text'] . ( $ds['label'] ? ' (' . $ds['label'] . ')' : '' ), 'np-date--' . $ds['class'] );
			}
			if ( '' !== $g( 'vacancy' ) ) {
				$facts[] = array( __( 'Total Posts', 'naukripatra' ), nppro_posts_count( $id ), '' );
			}
			if ( $g( 'application_mode' ) ) {
				$facts[] = array( __( 'Apply Mode', 'naukripatra' ), $g( 'application_mode' ), '' );
			}
			if ( $g( 'job_type' ) ) {
				$facts[] = array( __( 'Job Type', 'naukripatra' ), $g( 'job_type' ), '' );
			}
			if ( $facts ) :
				?>
				<dl class="np-facts">
					<?php foreach ( $facts as $f ) : ?><div class="np-fact"><dt><?php echo esc_html( $f[0] ); ?></dt><dd class="<?php echo esc_attr( $f[2] ); ?>"><?php echo esc_html( $f[1] ); ?></dd></div><?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<?php if ( $first( $apply ) || $first( $notif ) || $first( $site ) ) : ?>
				<p class="np-actions">
					<?php if ( $first( $apply ) ) : ?><a class="np-btn np-btn--green" rel="nofollow noopener" target="_blank" href="<?php echo esc_url( $first( $apply ) ); ?>"><?php esc_html_e( 'Apply Online', 'naukripatra' ); ?></a><?php endif; ?>
					<?php if ( $first( $notif ) ) : ?><a class="np-btn" rel="noopener" target="_blank" href="<?php echo esc_url( $first( $notif ) ); ?>"><?php esc_html_e( 'Download Notification', 'naukripatra' ); ?></a><?php endif; ?>
					<?php if ( $first( $site ) ) : ?><a class="np-btn np-btn--accent" rel="nofollow noopener" target="_blank" href="<?php echo esc_url( $first( $site ) ); ?>"><?php esc_html_e( 'Official Website', 'naukripatra' ); ?></a><?php endif; ?>
				</p>
			<?php endif; ?>
			<div class="np-reader-tools" role="toolbar" aria-label="<?php esc_attr_e( 'Reading tools', 'naukripatra' ); ?>">
				<span class="np-reader-tools__grp">
					<button type="button" class="np-btn np-btn--ghost np-btn--sm" data-np-font="-1" aria-label="<?php esc_attr_e( 'Smaller text', 'naukripatra' ); ?>">A-</button>
					<button type="button" class="np-btn np-btn--ghost np-btn--sm" data-np-font="1" aria-label="<?php esc_attr_e( 'Larger text', 'naukripatra' ); ?>">A+</button>
					<button type="button" class="np-btn np-btn--ghost np-btn--sm" data-np-print><?php esc_html_e( 'Print', 'naukripatra' ); ?></button>
				</span>
				<span class="np-reader-tools__grp">
					<span class="np-reader-tools__lbl"><?php esc_html_e( 'Share', 'naukripatra' ); ?>:</span>
					<?php foreach ( nppro_share_links( $url, $title ) as $name => $link ) : ?>
						<a class="np-btn np-btn--ghost np-btn--sm" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $name ); ?></a>
					<?php endforeach; ?>
					<button type="button" class="np-btn np-btn--ghost np-btn--sm" data-np-copy="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Copy link', 'naukripatra' ); ?></button>
				</span>
			</div>
		</header>

		<?php if ( $rows ) : ?>
			<section class="np-card" aria-labelledby="np-ov"><h2 id="np-ov" class="np-h2"><?php esc_html_e( 'Job Overview', 'naukripatra' ); ?></h2>
				<div class="np-kv">
					<?php foreach ( $rows as $label => $val ) : ?><div class="np-kv__row"><div class="np-kv__k"><?php echo esc_html( $label ); ?></div><div class="np-kv__v"><?php echo esc_html( $val ); ?></div></div><?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
		<?php nppro_ad( 'infeed' ); ?>

		<div class="np-card np-prose np-reader" id="np-article"><?php the_content(); ?></div>
		<?php nppro_ad( 'after' ); ?>

		<?php
		// Related: same state AND same section; fall back to same section.
		$ids = array();
		foreach ( $states as $s ) {
			$ids[] = $s->term_id;
		}
		$args = array( 'post__not_in' => array( $id ) );
		if ( $sec && $ids ) {
			$args['tax_query'] = array( 'relation' => 'AND', array( 'taxonomy' => 'category', 'terms' => $sec->term_id ), array( 'taxonomy' => 'category', 'terms' => $ids ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		} elseif ( $sec ) {
			$args['cat'] = $sec->term_id;
		}
		$rq = nppro_query( '', 6, $args );
		if ( $rq->have_posts() ) :
			?>
			<section class="np-card"><h2><?php esc_html_e( 'Related Jobs', 'naukripatra' ); ?></h2><?php nppro_list_table( $rq, 0, false ); wp_reset_postdata(); ?></section>
		<?php endif; ?>
		<?php if ( $states ) : ?>
			<section class="np-card"><h2><?php esc_html_e( 'More from this state', 'naukripatra' ); ?></h2><p class="np-chips">
				<?php foreach ( $states as $s ) : ?><a class="np-chip" href="<?php echo esc_url( get_category_link( $s ) ); ?>"><?php /* translators: %s: state */ printf( esc_html__( '%s Jobs', 'naukripatra' ), esc_html( $s->name ) ); ?></a><?php endforeach; ?>
			</p></section>
		<?php endif; ?>
		<nav class="np-card np-prevnext" aria-label="<?php esc_attr_e( 'Previous and next post', 'naukripatra' ); ?>">
			<span><?php previous_post_link( '%link', '&larr; %title', true ); ?></span><span><?php next_post_link( '%link', '%title &rarr;', true ); ?></span>
		</nav>
	</article>
	<?php get_sidebar(); ?>
</div>
<?php endwhile; ?>
<?php
get_footer();
