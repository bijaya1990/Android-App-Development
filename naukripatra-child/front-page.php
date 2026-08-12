<?php
/**
 * NaukriPatra — Homepage v2.2
 * Order (desktop + mobile, 100% responsive):
 * Banner → Live Ticker → Trending Jobs → Quick Menu (4 buttons) →
 * All India button → State-Wise buttons → 6 Main Sections.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>
<div class="np-home">

	<!-- ============ 1. BANNER / HERO ============ -->
	<section class="np-hero">
		<div class="np-hero-inner">
			<h1 class="np-hero-title">Naukri<span>Patra</span></h1>
			<p class="np-hero-sub">Latest Jobs Update 🇮🇳</p>
			<form role="search" method="get" class="np-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<input type="search" name="s" placeholder="Search jobs... (SSC, Railway, Bank)"
					value="<?php echo esc_attr( get_search_query() ); ?>" required>
				<button type="submit" aria-label="Search">🔍</button>
			</form>
		</div>
	</section>

	<!-- ============ 2. MOVING NOTICE PANEL (LIVE TICKER) ============ -->
	<?php np_render_ticker(); ?>

	<!-- ============ LIVE RESULTS TICKER ============ -->
	<?php np_render_results_ticker(); ?>

	<!-- ============ 3. TRENDING JOBS + 4. QUICK MENU ============ -->
	<section class="np-trend-zone">
		<div class="np-trending">
			<h2 class="np-zone-title">🔥 Trending Jobs</h2>
			<?php
			$trending = np_trending_query( 4 );
			if ( $trending->have_posts() ) : ?>
				<div class="np-trend-list">
					<?php while ( $trending->have_posts() ) : $trending->the_post();
						$last = get_post_meta( get_the_ID(), '_np_last_date', true ); ?>
						<a class="np-rel-item" href="<?php the_permalink(); ?>">
							<span class="np-rel-title"><?php the_title(); ?></span>
							<span class="np-rel-meta">👁 <?php echo (int) np_get_views( get_the_ID() ); ?> views
								<?php if ( $last ) : ?>&nbsp;|&nbsp; <b class="np-red">Last: <?php echo esc_html( $last ); ?></b><?php endif; ?>
							</span>
						</a>
					<?php endwhile; wp_reset_postdata(); ?>
				</div>
			<?php else : ?>
				<div class="np-card-empty">Jaldi hi trending jobs dikhengi ✨</div>
			<?php endif; ?>
		</div>

		<div class="np-quick">
			<h2 class="np-zone-title">⚡ Quick Menu</h2>
			<div class="np-quick-grid">
				<?php
				$icons = array( '💼', '🎫', '🏆', '🔑' );
				$i = 0;
				foreach ( array_slice( np_main_sections(), 0, 4, true ) as $name => $slug ) :
					$t = get_term_by( 'slug', $slug, 'category' );
					$l = $t ? get_category_link( $t ) : '#';
					$i++;
					?>
					<a class="np-quick-btn np-quick-<?php echo (int) $i; ?>" href="<?php echo esc_url( $l ); ?>">
						<span class="np-quick-ico"><?php echo $icons[ $i - 1 ]; ?></span>
						<span><?php echo esc_html( $name ); ?></span>
					</a>
				<?php endforeach; ?>

				<a class="np-quick-btn np-quick-5" href="https://naukripatra.in/resume-builder/">
					<span class="np-quick-ico">📄</span>
					<span>Resume Maker</span>
					<span class="np-quick-5-badge">NEW</span>
				</a>

				<a class="np-quick-btn np-quick-6" href="https://naukripatra.in/image-tools">
					<span class="np-quick-ico">🖼️</span>
					<span>Photo Resizer</span>
				</a>
			</div>
		</div>
	</section>

	<!-- ============ APP + CHANNELS PROMO BANNER ============ -->
	<?php $np_social = np_social_links(); ?>
	<section class="np-appbar">
		<div class="np-appbar-info">
			<span class="np-appbar-ico">📲</span>
			<div>
				<h2 class="np-appbar-title">NaukriPatra App &amp; Channels</h2>
				<p class="np-appbar-sub">Har nayi sarkari job ka alert sabse pehle paayein 🔔</p>
			</div>
		</div>
		<div class="np-appbar-btns">
			<a class="np-appbtn np-appbtn-play" href="<?php echo esc_url( $np_social['playstore'] ); ?>" target="_blank" rel="noopener">
				<span class="np-appbtn-ico">▶</span>
				<span class="np-appbtn-txt"><small>GET IT ON</small>Google Play</span>
			</a>
			<a class="np-appbtn np-appbtn-wa" href="<?php echo esc_url( $np_social['whatsapp'] ); ?>" target="_blank" rel="noopener">
				<span class="np-appbtn-ico">💬</span>
				<span class="np-appbtn-txt"><small>JOIN OUR</small>WhatsApp Channel</span>
			</a>
			<a class="np-appbtn np-appbtn-tg" href="<?php echo esc_url( $np_social['telegram'] ); ?>" target="_blank" rel="noopener">
				<span class="np-appbtn-ico">✈️</span>
				<span class="np-appbtn-txt"><small>JOIN OUR</small>Telegram Channel</span>
			</a>
		</div>
	</section>

	<?php np_ad_slot( 'list_top', 'np-ad-home' ); ?>

	<!-- ============ 5. ALL INDIA + 6. STATE-WISE JOBS ============ -->
	<section class="np-states" id="npStates">
		<div class="np-states-head">
			<h2 class="np-states-title">📍 State-Wise Jobs</h2>
			<?php
			$ai_term = get_term_by( 'slug', 'all-india', 'category' );
			$ai_link = $ai_term ? get_category_link( $ai_term ) : '#';

			// USA Jobs — All India ke bagal me, bilkul same class (np-allindia)
			// isliye colour / size / shine animation / hover sab automatic same.
			$us_term = get_term_by( 'slug', 'usa-jobs', 'category' );
			$us_link = $us_term ? get_category_link( $us_term ) : '#';
			?>
			<div class="np-states-cta">
				<a class="np-allindia" href="<?php echo esc_url( $ai_link ); ?>">🇮🇳 All India Jobs</a>
				<a class="np-allindia" href="<?php echo esc_url( $us_link ); ?>">🇺🇸 USA Jobs</a>
			</div>
		</div>
		<div class="np-state-grid">
			<?php
			$locations = np_locations();
			array_shift( $locations ); // All India upar button ban chuka
			foreach ( $locations as $slug => $label ) :
				$t = get_term_by( 'slug', $slug, 'category' );
				$l = $t ? get_category_link( $t ) : '#';
				?>
				<a class="np-state-btn" href="<?php echo esc_url( $l ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- ============ 7. LATEST JOBS + 5 MAIN SECTIONS ============ -->
	<section class="np-sections">
		<?php
		$i = 0;
		foreach ( np_main_sections() as $name => $slug ) :
			$i++;
			$term = get_term_by( 'slug', $slug, 'category' );
			$link = $term ? get_category_link( $term ) : '#';
			$q = new WP_Query( array(
				'category_name'       => $slug,
				'posts_per_page'      => 6,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			) );
			?>
			<div class="np-card np-card-<?php echo (int) $i; ?>">
				<div class="np-card-head">
					<h2><?php echo esc_html( $name ); ?></h2>
				</div>
				<ul class="np-card-list">
					<?php if ( $q->have_posts() ) :
						while ( $q->have_posts() ) : $q->the_post();
							$last = get_post_meta( get_the_ID(), '_np_last_date', true );
							$is_new = ( time() - get_post_time( 'U', true ) ) < 3 * DAY_IN_SECONDS;
							?>
							<li>
								<a href="<?php the_permalink(); ?>">
									<span class="np-card-title"><?php the_title(); ?>
										<?php if ( $is_new ) echo '<span class="np-new">NEW</span>'; ?>
									</span>
									<?php if ( $last ) : ?>
										<span class="np-card-last">Last Date: <?php echo esc_html( $last ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endwhile; wp_reset_postdata();
					else : ?>
						<li class="np-card-empty">Jaldi hi updates aayengi ✨</li>
					<?php endif; ?>
				</ul>
				<a class="np-card-more" href="<?php echo esc_url( $link ); ?>">View All →</a>
			</div>
		<?php endforeach; ?>
	</section>

</div>
<?php
get_footer();
