<?php
/**
 * Homepage. Block order and visibility come from NaukriPatra Control > Homepage.
 * No hero, no slider, no big banner.
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$blocks = (array) np_opt( 'blocks' );
uasort( $blocks, function ( $a, $b ) {
	return (int) $a['order'] - (int) $b['order'];
} );

foreach ( $blocks as $key => $cfg ) {
	if ( empty( $cfg['show'] ) ) {
		continue;
	}
	switch ( $key ) {
		case 'ticker_jobs':
			np_ticker( np_opt( 'ticker_jobs_label' ), np_query( 'latest-jobs', (int) np_opt( 'ticker_jobs_count' ) ), 'rtl' );
			break;
		case 'ticker_results':
			$slug = np_opt( 'ticker_results_answer' ) ? 'result,answer-key' : 'result';
			np_ticker( np_opt( 'ticker_results_label' ), np_query( $slug, (int) np_opt( 'ticker_results_count' ) ), 'ltr' );
			break;
		case 'tools':
			$tools = array_values( array_filter( (array) np_opt( 'tools' ), function ( $t ) {
				return ! empty( $t['show'] ) && ! empty( $t['label'] );
			} ) );
			if ( $tools ) {
				$n    = count( $tools );
				$cols = $n <= 3 ? $n : ( 0 === $n % 4 ? 4 : 3 );
				echo '<div class="np-wrap"><nav class="np-tools" style="--cols:' . (int) $cols . '" aria-label="' . esc_attr__( 'Free tools', 'naukripatra' ) . '">';
				foreach ( $tools as $t ) {
					echo '<a class="np-tool" style="--c:' . esc_attr( $t['color'] ) . '" href="' . np_url( $t['link'] ) . '">' . np_icon( $t['icon'] ) . '<span>' . esc_html( $t['label'] ) . '</span></a>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				echo '</nav></div>';
			}
			break;
		case 'cats':
			echo '<div class="np-wrap"><nav class="np-cats" aria-label="' . esc_attr__( 'Sections', 'naukripatra' ) . '">';
			foreach ( (array) np_opt( 'cats' ) as $slug => $c ) {
				$t = get_term_by( 'slug', $slug, 'category' );
				if ( $t ) {
					echo '<a class="np-gbtn" style="--c:' . esc_attr( $c['color'] ) . '" href="' . esc_url( get_category_link( $t ) ) . '">' . esc_html( $c['label'] ) . '</a>';
				}
			}
			echo '</nav></div>';
			break;
		case 'states':
			$locs = np_locations();
			$all  = get_term_by( 'slug', 'all-india', 'category' );
			?>
			<div class="np-wrap"><section class="np-card np-states" aria-labelledby="np-states-h">
				<div class="np-states__head">
					<h2 id="np-states-h"><?php echo esc_html( np_opt( 'states_title' ) ); ?></h2>
					<label class="screen-reader-text" for="np-state-select"><?php esc_html_e( 'Jump to a state', 'naukripatra' ); ?></label>
					<select id="np-state-select" class="np-select" data-np-jump>
						<option value=""><?php esc_html_e( 'Select state or UT', 'naukripatra' ); ?></option>
						<?php foreach ( $locs as $l ) : ?><option value="<?php echo esc_url( $l[2] ); ?>"><?php echo esc_html( $l[1] ); ?></option><?php endforeach; ?>
					</select>
				</div>
				<div class="np-states__top">
					<?php if ( $all ) : ?><a class="np-gbtn np-gbtn--wide" style="--c:#14284B" href="<?php echo esc_url( get_category_link( $all ) ); ?>"><?php esc_html_e( 'All India Jobs', 'naukripatra' ); ?></a><?php endif; ?>
					<?php
					foreach ( preg_split( '/\r?\n/', (string) np_opt( 'extra_locations' ) ) as $line ) {
						$parts = array_map( 'trim', explode( '|', $line, 2 ) );
						if ( '' === $parts[0] ) {
							continue;
						}
						$url = ! empty( $parts[1] ) ? np_url( $parts[1] ) : esc_url( home_url( '/?s=' . rawurlencode( $parts[0] ) ) );
						echo '<a class="np-gbtn np-gbtn--wide" style="--c:#BE185D" href="' . $url . '">' . esc_html( $parts[0] ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</div>
				<div class="np-states__grid">
					<?php foreach ( $locs as $i => $l ) : ?>
						<a class="np-gbtn np-g<?php echo (int) ( $i % 8 ); ?>" href="<?php echo esc_url( $l[2] ); ?>"><?php echo esc_html( $l[1] ); ?><?php if ( $l[3] ) : ?><small class="np-ut">UT</small><?php endif; ?></a>
					<?php endforeach; ?>
				</div>
			</section></div>
			<div class="np-wrap"><?php np_ad( 'below_states' ); ?></div>
			<?php
			break;
		case 'main':
			?>
			<div class="np-wrap np-layout">
				<div class="np-col">
					<section class="np-card">
						<h2><?php esc_html_e( 'Latest Jobs', 'naukripatra' ); ?></h2>
						<?php
						$q = np_query( 'latest-jobs', (int) np_opt( 'home_jobs_count' ) );
						if ( $q->have_posts() ) {
							np_list_table( $q, 0, false );
						} else {
							echo '<p>' . esc_html__( 'No jobs yet.', 'naukripatra' ) . '</p>';
						}
						wp_reset_postdata();
						$t = get_term_by( 'slug', 'latest-jobs', 'category' );
						if ( $t ) {
							echo '<p class="np-more"><a class="np-btn" href="' . esc_url( get_category_link( $t ) ) . '">' . esc_html__( 'View all jobs', 'naukripatra' ) . '</a></p>';
						}
						?>
					</section>
					<?php np_ad( 'infeed' ); ?>
					<div class="np-boxes">
						<?php
						foreach ( array( 'admit-card' => 'Admit Card', 'result' => 'Result', 'answer-key' => 'Answer Key', 'syllabus' => 'Syllabus', 'admission' => 'Admission', 'scholarship-schemes' => 'Scholarship & Schemes' ) as $slug => $label ) {
							$t = get_term_by( 'slug', $slug, 'category' );
							if ( ! $t ) {
								continue;
							}
							echo '<section class="np-card np-box"><h2>' . esc_html( $label ) . '</h2>';
							$q = np_query( $slug, (int) np_opt( 'home_box_count' ) );
							np_link_list( $q );
							wp_reset_postdata();
							echo '<p class="np-more"><a href="' . esc_url( get_category_link( $t ) ) . '">' . esc_html__( 'View all', 'naukripatra' ) . ' &rarr;</a></p></section>';
						}
						?>
					</div>
				</div>
				<?php get_sidebar(); ?>
			</div>
			<?php
			break;
		case 'seo_text':
			if ( trim( (string) np_opt( 'seo_text' ) ) ) {
				echo '<div class="np-wrap"><section class="np-card np-prose">' . wp_kses_post( wpautop( np_opt( 'seo_text' ) ) ) . '</section></div>';
			}
			break;
	}
}
get_footer();
