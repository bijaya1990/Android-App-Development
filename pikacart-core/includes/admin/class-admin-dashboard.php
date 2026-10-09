<?php
/**
 * Super Admin dashboard: money, accounts, conversion, charts, latest lists.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Dashboard {

	private static function stat( $label, $value, $icon, $tone = '', $hint = '' ) {
		?>
		<div class="pkc-stat <?php echo esc_attr( $tone ? 'tone-' . $tone : '' ); ?>">
			<span class="pkc-stat-icon"><?php echo esc_html( $icon ); ?></span>
			<div>
				<div class="pkc-stat-label"><?php echo esc_html( $label ); ?></div>
				<div class="pkc-stat-value"><?php echo esc_html( $value ); ?></div>
				<?php if ( $hint ) : ?>
					<div class="pkc-stat-hint"><?php echo esc_html( $hint ); ?></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public static function render() {
		global $wpdb;
		$s = PKC_Stats::summary();

		wp_add_inline_script(
			'pkc-admin',
			'window.PKC_CHARTS = ' . wp_json_encode(
				array(
					'daily'    => PKC_Stats::daily( 30 ),
					'monthly'  => PKC_Stats::monthly(),
					'category' => PKC_Stats::by_category(),
				)
			) . ';',
			'before'
		);

		$latest_orgs = $wpdb->get_results( 'SELECT id, name, email, status, created_at FROM ' . pkc_table( 'organisations' ) . ' ORDER BY id DESC LIMIT 8' );
		$latest_pays = $wpdb->get_results( 'SELECT p.*, o.name AS org_name FROM ' . pkc_table( 'payments' ) . ' p LEFT JOIN ' . pkc_table( 'organisations' ) . " o ON o.id = p.org_id WHERE p.status IN ('captured','failed') ORDER BY p.id DESC LIMIT 8" );

		PKC_Admin::header( __( 'Everything at a glance', 'pikacart' ), wp_date( 'l, j F Y' ) );
		?>
		<h2 class="pkc-section-title"><?php esc_html_e( 'Money collected', 'pikacart' ); ?></h2>
		<div class="pkc-stats">
			<?php
			self::stat( __( 'Today', 'pikacart' ), pkc_money( $s['money_today'] ), '₹', 'green' );
			self::stat( __( 'This month', 'pikacart' ), pkc_money( $s['money_month'] ), '₹', 'green' );
			self::stat( __( 'This year', 'pikacart' ), pkc_money( $s['money_year'] ), '₹', 'green' );
			self::stat( __( 'All time', 'pikacart' ), pkc_money( $s['money_all'] ), '₹', 'green' );
			?>
		</div>

		<h2 class="pkc-section-title"><?php esc_html_e( 'Accounts', 'pikacart' ); ?></h2>
		<div class="pkc-stats pkc-stats-wide">
			<?php
			self::stat( __( 'Total accounts', 'pikacart' ), number_format_i18n( $s['accounts_total'] ), '◎', 'brand' );
			self::stat( __( 'New today', 'pikacart' ), number_format_i18n( $s['new_today'] ), '+', 'brand' );
			self::stat( __( 'New this month', 'pikacart' ), number_format_i18n( $s['new_month'] ), '+', 'brand' );
			self::stat( __( 'Free plan', 'pikacart' ), number_format_i18n( $s['status']['free'] + $s['status']['trial'] ), '★', 'amber' );
			self::stat( __( 'Active', 'pikacart' ), number_format_i18n( $s['status']['active'] ), '✓', 'green' );
			self::stat( __( 'Expired', 'pikacart' ), number_format_i18n( $s['status']['expired'] ), '○', 'grey' );
			self::stat( __( 'Suspended', 'pikacart' ), number_format_i18n( $s['status']['suspended'] ), '⛔', 'red' );
			self::stat( __( 'Cancelled autopay', 'pikacart' ), number_format_i18n( $s['status']['cancelled'] ), '↺', 'grey' );
			?>
		</div>

		<h2 class="pkc-section-title"><?php esc_html_e( 'Business health', 'pikacart' ); ?></h2>
		<div class="pkc-stats pkc-stats-wide">
			<?php
			self::stat( __( 'Free to paid', 'pikacart' ), $s['conversion'] . '%', '↗', 'brand', __( 'Accounts that have paid at least once', 'pikacart' ) );
			self::stat( __( 'Monthly recurring revenue', 'pikacart' ), pkc_money( $s['mrr'] ), '₹', 'green' );
			self::stat( __( 'Failed payments this month', 'pikacart' ), number_format_i18n( $s['failed_month'] ), '!', 'red' );
			self::stat( __( 'Renewals due in 7 days', 'pikacart' ), number_format_i18n( $s['renewals_7d'] ), '⟳', 'amber' );
			self::stat( __( 'Cards created', 'pikacart' ), number_format_i18n( $s['cards'] ), '▭', 'brand' );
			self::stat( __( 'Downloads', 'pikacart' ), number_format_i18n( $s['downloads'] ), '↓', 'brand' );
			self::stat( __( 'Pending design requests', 'pikacart' ), number_format_i18n( $s['design_requests'] ), '✎', 'amber' );
			self::stat( __( 'Unread support messages', 'pikacart' ), number_format_i18n( $s['unread_tickets'] ), '✉', 'amber' );
			?>
		</div>

		<div class="pkc-charts">
			<div class="pkc-panel pkc-chart-wide">
				<div class="pkc-panel-head"><h3><?php esc_html_e( 'Revenue by day (last 30 days)', 'pikacart' ); ?></h3></div>
				<div class="pkc-chart-box"><canvas id="pkc-chart-revenue" aria-label="<?php esc_attr_e( 'Revenue by day', 'pikacart' ); ?>" role="img"></canvas></div>
			</div>
			<div class="pkc-panel">
				<div class="pkc-panel-head"><h3><?php esc_html_e( 'Accounts by category', 'pikacart' ); ?></h3></div>
				<div class="pkc-chart-box"><canvas id="pkc-chart-category" aria-label="<?php esc_attr_e( 'Accounts by category', 'pikacart' ); ?>" role="img"></canvas></div>
			</div>
			<div class="pkc-panel">
				<div class="pkc-panel-head"><h3><?php esc_html_e( 'Revenue by month', 'pikacart' ); ?></h3></div>
				<div class="pkc-chart-box"><canvas id="pkc-chart-monthly" aria-label="<?php esc_attr_e( 'Revenue by month', 'pikacart' ); ?>" role="img"></canvas></div>
			</div>
			<div class="pkc-panel pkc-chart-wide">
				<div class="pkc-panel-head"><h3><?php esc_html_e( 'Sign-ups by day (last 30 days)', 'pikacart' ); ?></h3></div>
				<div class="pkc-chart-box"><canvas id="pkc-chart-signups" aria-label="<?php esc_attr_e( 'Sign-ups by day', 'pikacart' ); ?>" role="img"></canvas></div>
			</div>
		</div>

		<div class="pkc-two">
			<div class="pkc-panel">
				<div class="pkc-panel-head"><h3><?php esc_html_e( 'Latest sign-ups', 'pikacart' ); ?></h3><a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-accounts' ) ); ?>"><?php esc_html_e( 'All accounts', 'pikacart' ); ?></a></div>
				<?php if ( $latest_orgs ) : ?>
				<table class="pkc-table">
					<tbody>
					<?php foreach ( $latest_orgs as $o ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-accounts&org=' . (int) $o->id ) ); ?>"><strong><?php echo esc_html( $o->name ); ?></strong></a><br><small><?php echo esc_html( $o->email ); ?></small></td>
							<td><?php echo PKC_Admin::status_badge( $o->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td class="pkc-muted"><?php echo esc_html( pkc_date( $o->created_at, 'j M, g:i a' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php else : ?>
					<p class="pkc-empty"><?php esc_html_e( 'No sign-ups yet. Share your website to get your first customer!', 'pikacart' ); ?></p>
				<?php endif; ?>
			</div>
			<div class="pkc-panel">
				<div class="pkc-panel-head"><h3><?php esc_html_e( 'Latest payments', 'pikacart' ); ?></h3><a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-payments' ) ); ?>"><?php esc_html_e( 'All payments', 'pikacart' ); ?></a></div>
				<?php if ( $latest_pays ) : ?>
				<table class="pkc-table">
					<tbody>
					<?php foreach ( $latest_pays as $p ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $p->org_name ? $p->org_name : __( '(deleted account)', 'pikacart' ) ); ?></strong><br><small><?php echo esc_html( PKC_Billing::method_label( $p->method ) ); ?></small></td>
							<td><strong><?php echo esc_html( pkc_money( $p->amount_paise ) ); ?></strong></td>
							<td><?php echo PKC_Admin::pay_badge( $p->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td class="pkc-muted"><?php echo esc_html( pkc_date( $p->created_at, 'j M, g:i a' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php else : ?>
					<p class="pkc-empty"><?php esc_html_e( 'No payments yet.', 'pikacart' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		PKC_Admin::footer();
	}
}
