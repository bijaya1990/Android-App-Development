<?php
/**
 * Reusable renderers: list table, tickers, grids, sidebar, share, related.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Posts of a category slug (or all) as WP_Query. */
function np_query( $slug, $count, $extra = array() ) {
	$args = array( 'post_type' => 'post', 'posts_per_page' => (int) $count, 'ignore_sticky_posts' => true, 'no_found_rows' => true );
	if ( $slug ) {
		$args['category_name'] = $slug;
	}
	return new WP_Query( array_merge( $args, $extra ) );
}

/**
 * Jobs table (cards on mobile). Must run inside a loop of $q.
 *
 * @param WP_Query $q      Query.
 * @param int      $offset Sl No offset.
 * @param bool     $ads    Insert list ad after every 5th row.
 */
function np_list_table( $q, $offset = 0, $ads = true ) {
	$advt  = (int) np_opt( 'col_advt' );
	$posts = (int) np_opt( 'col_posts' );
	$cols  = 4 + $advt + $posts;
	?>
	<div class="np-tablebox">
	<table class="np-table np-list">
		<thead><tr>
			<th scope="col"><?php esc_html_e( 'Sl', 'naukripatra' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Name of the Job', 'naukripatra' ); ?></th>
			<?php if ( $advt ) : ?><th scope="col"><?php esc_html_e( 'Adv No', 'naukripatra' ); ?></th><?php endif; ?>
			<th scope="col"><?php esc_html_e( 'Last Date', 'naukripatra' ); ?></th>
			<?php if ( $posts ) : ?><th scope="col"><?php esc_html_e( 'Posts', 'naukripatra' ); ?></th><?php endif; ?>
			<th scope="col"><?php esc_html_e( 'Get Details', 'naukripatra' ); ?></th>
		</tr></thead>
		<tbody>
		<?php
		$i = 0;
		while ( $q->have_posts() ) :
			$q->the_post();
			++$i;
			$id = get_the_ID();
			$st = np_date_status( np_meta( $id, 'last_date' ) );
			$ad = np_meta( $id, 'advt_no' );
			?>
			<tr>
				<td data-label="<?php esc_attr_e( 'Sl', 'naukripatra' ); ?>"><?php echo (int) ( $offset + $i ); ?></td>
				<td class="np-list__name" data-label="<?php esc_attr_e( 'Job', 'naukripatra' ); ?>"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php if ( np_is_new( $id ) ) : ?> <span class="np-badge"><?php esc_html_e( 'NEW', 'naukripatra' ); ?></span><?php endif; ?></td>
				<?php if ( $advt ) : ?><td data-label="<?php esc_attr_e( 'Adv No', 'naukripatra' ); ?>"><?php echo esc_html( $ad ? $ad : '—' ); ?></td><?php endif; ?>
				<td data-label="<?php esc_attr_e( 'Last Date', 'naukripatra' ); ?>" class="np-date np-date--<?php echo esc_attr( $st['class'] ); ?>"><?php echo esc_html( $st['text'] ); ?><?php if ( $st['label'] ) : ?> <span class="np-expired"><?php echo esc_html( $st['label'] ); ?></span><?php endif; ?></td>
				<?php if ( $posts ) : ?><td data-label="<?php esc_attr_e( 'Posts', 'naukripatra' ); ?>"><?php echo esc_html( np_posts_count( $id ) ); ?></td><?php endif; ?>
				<td data-label=""><a class="np-btn np-btn--sm" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Get Details', 'naukripatra' ); ?></a></td>
			</tr>
			<?php if ( $ads && 0 === $i % 5 && $q->current_post + 1 < $q->post_count ) : ?>
				<?php $adh = np_ad_html( 'list' ); ?>
				<?php if ( $adh ) : ?><tr class="np-ad-row"><td colspan="<?php echo (int) $cols; ?>"><?php echo $adh; // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr><?php endif; ?>
			<?php endif; ?>
		<?php endwhile; ?>
		</tbody>
	</table>
	</div>
	<?php
}

/** Compact link list used in boxes and related sections. */
function np_link_list( $q ) {
	echo '<ul class="np-links">';
	while ( $q->have_posts() ) {
		$q->the_post();
		echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>' . ( np_is_new( get_the_ID() ) ? ' <span class="np-badge">' . esc_html__( 'NEW', 'naukripatra' ) . '</span>' : '' ) . '</li>';
	}
	echo '</ul>';
}

/** Marquee. $dir = ltr|rtl (text moves left-to-right / right-to-left). */
function np_ticker( $label, $q, $dir ) {
	if ( ! $q->have_posts() ) {
		return;
	}
	$items = '';
	while ( $q->have_posts() ) {
		$q->the_post();
		$items .= '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
	}
	wp_reset_postdata();
	?>
	<div class="np-wrap"><div class="np-ticker np-ticker--<?php echo esc_attr( $dir ); ?>" style="--np-tspeed:<?php echo (int) np_opt( 'ticker_speed' ); ?>s">
		<span class="np-ticker__label"><span class="np-dot" aria-hidden="true"></span><?php echo esc_html( $label ); ?></span>
		<div class="np-ticker__track" tabindex="0" role="region" aria-label="<?php echo esc_attr( $label ); ?>">
			<div class="np-ticker__inner"><div class="np-ticker__set"><?php echo $items; // phpcs:ignore WordPress.Security.EscapeOutput ?></div><div class="np-ticker__set" aria-hidden="true"><?php echo $items; // phpcs:ignore WordPress.Security.EscapeOutput ?></div></div>
		</div>
	</div></div>
	<?php
}

/** Resolve tool/location link: relative paths become site URLs. */
function np_url( $u ) {
	return preg_match( '#^https?://#i', $u ) ? esc_url( $u ) : esc_url( home_url( '/' . ltrim( $u, '/' ) ) );
}

/** States + UTs honouring hidden/renamed settings. Returns array of array(slug,name,url,is_ut). */
function np_locations() {
	$hidden = array_filter( array_map( 'trim', explode( ',', strtolower( (string) np_opt( 'hidden_locations' ) ) ) ) );
	$rename = array();
	foreach ( preg_split( '/\r?\n/', (string) np_opt( 'location_names' ) ) as $line ) {
		if ( false !== strpos( $line, '=' ) ) {
			list( $k, $v ) = array_map( 'trim', explode( '=', $line, 2 ) );
			$rename[ $k ] = $v;
		}
	}
	$out = array();
	foreach ( np_location_slugs() as $slug ) {
		$t = get_term_by( 'slug', $slug, 'category' );
		if ( ! $t || in_array( $slug, $hidden, true ) ) {
			continue;
		}
		$name  = isset( $rename[ $slug ] ) ? $rename[ $slug ] : np_short_name( $slug, $t->name );
		$out[] = array( $slug, $name, get_category_link( $t ), in_array( $slug, np_ut_slugs(), true ) );
	}
	return $out;
}

/** Sidebar: Trending, join card, ads. $sticky_ad false for mobile-stacked contexts. */
function np_sidebar( $context = 'home' ) {
	?>
	<aside class="np-sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'naukripatra' ); ?>">
		<?php np_ad( 'sidebar1' ); ?>
		<section class="np-card">
			<h2><?php esc_html_e( 'Trending Jobs', 'naukripatra' ); ?></h2>
			<?php
			$q = np_query( 'latest-jobs', (int) np_opt( 'trending_count' ), array( 'meta_key' => '_np_views', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'date_query' => array( array( 'after' => '60 days ago' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			if ( ! $q->have_posts() ) {
				$q = np_query( 'latest-jobs', (int) np_opt( 'trending_count' ) );
			}
			np_link_list( $q );
			wp_reset_postdata();
			?>
		</section>
		<?php if ( 'single' === $context ) : ?>
			<section class="np-card"><h2><?php esc_html_e( 'Expiring Soon', 'naukripatra' ); ?></h2>
				<?php
				$q = np_query( 'latest-jobs', 40 );
				$soon = array();
				while ( $q->have_posts() ) {
					$q->the_post();
					$d = np_parse_date( np_meta( get_the_ID(), 'last_date' ) );
					if ( $d && $d['ts'] > time() && $d['ts'] < time() + 7 * DAY_IN_SECONDS ) {
						$soon[ get_the_ID() ] = $d['ts'];
					}
				}
				asort( $soon );
				echo '<ul class="np-links">';
				foreach ( array_slice( array_keys( $soon ), 0, 6 ) as $pid ) {
					echo '<li><a href="' . esc_url( get_permalink( $pid ) ) . '">' . esc_html( get_the_title( $pid ) ) . '</a></li>';
				}
				echo '</ul>';
				wp_reset_postdata();
				?>
			</section>
			<section class="np-card"><h2><?php esc_html_e( 'Latest Results', 'naukripatra' ); ?></h2><?php np_link_list( np_query( 'result', 6 ) ); wp_reset_postdata(); ?></section>
		<?php endif; ?>
		<?php
		$soc = (array) np_opt( 'social' );
		if ( ! empty( $soc['telegram'] ) || ! empty( $soc['whatsapp'] ) ) :
			?>
			<section class="np-card np-join">
				<h2><?php esc_html_e( 'Join Us', 'naukripatra' ); ?></h2>
				<p><?php echo esc_html( np_opt( 'join_text' ) ); ?></p>
				<?php if ( ! empty( $soc['telegram'] ) ) : ?><a class="np-btn" href="<?php echo esc_url( $soc['telegram'] ); ?>" target="_blank" rel="noopener">Telegram</a><?php endif; ?>
				<?php if ( ! empty( $soc['whatsapp'] ) ) : ?><a class="np-btn np-btn--green" href="<?php echo esc_url( $soc['whatsapp'] ); ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
			</section>
		<?php endif; ?>
		<?php np_ad( 'sidebar2', 'np-ad--sticky-side' ); ?>
	</aside>
	<?php
}

function np_share_links( $url, $title ) {
	$u = rawurlencode( $url );
	$t = rawurlencode( $title );
	return array(
		'WhatsApp' => 'https://wa.me/?text=' . $t . '%20' . $u,
		'Telegram' => 'https://t.me/share/url?url=' . $u . '&text=' . $t,
		'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $u,
		'X'        => 'https://twitter.com/intent/tweet?url=' . $u . '&text=' . $t,
	);
}

function np_pagination() {
	$links = paginate_links( array( 'type' => 'array', 'prev_text' => '&laquo;', 'next_text' => '&raquo;', 'mid_size' => 1 ) );
	if ( $links ) {
		echo '<nav class="np-pager" aria-label="' . esc_attr__( 'Pagination', 'naukripatra' ) . '">' . implode( '', $links ) . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

function np_breadcrumb_html() {
	$c = np_breadcrumbs();
	echo '<nav class="np-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'naukripatra' ) . '"><ol>';
	foreach ( $c as $i => $x ) {
		echo '<li>' . ( $i < count( $c ) - 1 ? '<a href="' . esc_url( $x[1] ) . '">' . esc_html( $x[0] ) . '</a>' : '<span aria-current="page">' . esc_html( $x[0] ) . '</span>' ) . '</li>';
	}
	echo '</ol></nav>';
}
