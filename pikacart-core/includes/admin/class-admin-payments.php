<?php
/**
 * Payments: every Razorpay transaction with filters, date range and export.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Payments {

	const PER_PAGE = 30;

	public static function init() {
		add_action( 'admin_post_pkc_export_payments', array( __CLASS__, 'export' ) );
	}

	private static function filters() {
		// phpcs:disable WordPress.Security.NonceVerification
		$q      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$from   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$to     = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
		// phpcs:enable
		global $wpdb;
		$where = array( "p.status <> 'created'" );
		$args  = array();
		if ( '' !== $q ) {
			$like    = '%' . $wpdb->esc_like( $q ) . '%';
			$where[] = '(o.name LIKE %s OR o.email LIKE %s OR p.rzp_payment_id LIKE %s OR p.invoice_no LIKE %s)';
			array_push( $args, $like, $like, $like, $like );
		}
		if ( in_array( $status, array( 'captured', 'failed', 'refunded' ), true ) ) {
			$where[] = 'p.status = %s';
			$args[]  = $status;
		}
		$utc = new DateTimeZone( 'UTC' );
		if ( $from && ( $d = DateTime::createFromFormat( 'Y-m-d H:i:s', $from . ' 00:00:00', wp_timezone() ) ) ) { // phpcs:ignore
			$where[] = 'p.created_at >= %s';
			$args[]  = $d->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
		}
		if ( $to && ( $d = DateTime::createFromFormat( 'Y-m-d H:i:s', $to . ' 23:59:59', wp_timezone() ) ) ) { // phpcs:ignore
			$where[] = 'p.created_at <= %s';
			$args[]  = $d->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
		}
		return array( implode( ' AND ', $where ), $args, compact( 'q', 'status', 'from', 'to' ) );
	}

	private static function from_sql() {
		return ' FROM ' . pkc_table( 'payments' ) . ' p LEFT JOIN ' . pkc_table( 'organisations' ) . ' o ON o.id = p.org_id ';
	}

	public static function render() {
		global $wpdb;
		list( $where, $args, $f ) = self::filters();
		$paged = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification

		$count_sql = 'SELECT COUNT(*), COALESCE(SUM(CASE WHEN p.status = \'captured\' THEN p.amount_paise ELSE 0 END),0) AS total' . self::from_sql() . "WHERE $where";
		$totals    = $args ? $wpdb->get_row( $wpdb->prepare( $count_sql, $args ), ARRAY_N ) : $wpdb->get_row( $count_sql, ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL
		$total     = (int) $totals[0];
		$sum       = (int) $totals[1];

		$sql  = 'SELECT p.*, o.name AS org_name, o.email AS org_email' . self::from_sql() . "WHERE $where ORDER BY p.id DESC LIMIT %d OFFSET %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $args, array( self::PER_PAGE, ( $paged - 1 ) * self::PER_PAGE ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL

		$export = wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'pkc_export_payments' ), $f ), admin_url( 'admin-post.php' ) ), 'pkc_export_payments' );

		PKC_Admin::header(
			__( 'Payments', 'pikacart' ),
			/* translators: 1: number of payments, 2: amount */
			sprintf( __( '%1$s transactions · %2$s collected', 'pikacart' ), number_format_i18n( $total ), pkc_money( $sum ) ),
			'<a class="pkc-btn" href="' . esc_url( $export ) . '">' . esc_html__( 'Export to Excel (CSV)', 'pikacart' ) . '</a>'
		);
		?>
		<form class="pkc-filters" method="get">
			<input type="hidden" name="page" value="pikacart-payments">
			<input type="search" name="s" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="<?php esc_attr_e( 'Account, payment ID or invoice', 'pikacart' ); ?>">
			<select name="status">
				<option value=""><?php esc_html_e( 'All statuses', 'pikacart' ); ?></option>
				<option value="captured" <?php selected( $f['status'], 'captured' ); ?>><?php esc_html_e( 'Paid', 'pikacart' ); ?></option>
				<option value="failed" <?php selected( $f['status'], 'failed' ); ?>><?php esc_html_e( 'Failed', 'pikacart' ); ?></option>
				<option value="refunded" <?php selected( $f['status'], 'refunded' ); ?>><?php esc_html_e( 'Refunded', 'pikacart' ); ?></option>
			</select>
			<label class="pkc-date"><?php esc_html_e( 'From', 'pikacart' ); ?> <input type="date" name="from" value="<?php echo esc_attr( $f['from'] ); ?>"></label>
			<label class="pkc-date"><?php esc_html_e( 'To', 'pikacart' ); ?> <input type="date" name="to" value="<?php echo esc_attr( $f['to'] ); ?>"></label>
			<button class="pkc-btn pkc-btn-primary" type="submit"><?php esc_html_e( 'Filter', 'pikacart' ); ?></button>
		</form>

		<div class="pkc-panel pkc-panel-flush">
		<?php if ( $rows ) : ?>
			<div class="pkc-table-wrap">
			<table class="pkc-table pkc-table-lg">
				<thead><tr>
					<th><?php esc_html_e( 'Date', 'pikacart' ); ?></th>
					<th><?php esc_html_e( 'Account', 'pikacart' ); ?></th>
					<th class="num"><?php esc_html_e( 'Amount', 'pikacart' ); ?></th>
					<th><?php esc_html_e( 'Type', 'pikacart' ); ?></th>
					<th><?php esc_html_e( 'Method', 'pikacart' ); ?></th>
					<th><?php esc_html_e( 'Status', 'pikacart' ); ?></th>
					<th><?php esc_html_e( 'Invoice', 'pikacart' ); ?></th>
					<th><?php esc_html_e( 'Payment ID', 'pikacart' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $rows as $p ) : ?>
					<tr>
						<td class="pkc-muted"><?php echo esc_html( pkc_date( $p->created_at, 'j M Y, g:i a' ) ); ?></td>
						<td>
							<?php if ( $p->org_name ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-accounts&org=' . (int) $p->org_id ) ); ?>"><strong><?php echo esc_html( $p->org_name ); ?></strong></a><br><small><?php echo esc_html( $p->org_email ); ?></small>
							<?php else : ?>
								<?php esc_html_e( '(deleted account)', 'pikacart' ); ?>
							<?php endif; ?>
						</td>
						<td class="num"><strong><?php echo esc_html( pkc_money( $p->amount_paise ) ); ?></strong></td>
						<td><?php echo esc_html( 'subscription' === $p->kind ? __( 'Autopay', 'pikacart' ) : __( 'One-time', 'pikacart' ) ); ?><?php echo 'test' === $p->mode ? ' <span class="pkc-badge pkc-badge-trial">' . esc_html__( 'Test', 'pikacart' ) . '</span>' : ''; ?></td>
						<td><?php echo esc_html( PKC_Billing::method_label( $p->method ) ); ?></td>
						<td><?php echo PKC_Admin::pay_badge( $p->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $p->error ? '<br><small class="pkc-muted">' . esc_html( $p->error ) . '</small>' : ''; ?></td>
						<td><?php echo esc_html( $p->invoice_no ? $p->invoice_no : '—' ); ?></td>
						<td><code><?php echo esc_html( (string) $p->rzp_payment_id ); ?></code></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			<?php PKC_Admin::pagination( $total, self::PER_PAGE, $paged, array_merge( array( 'page' => 'pikacart-payments' ), $f ) ); ?>
		<?php else : ?>
			<p class="pkc-empty"><?php esc_html_e( 'No payments match these filters.', 'pikacart' ); ?></p>
		<?php endif; ?>
		</div>
		<?php
		PKC_Admin::footer();
	}

	public static function export() {
		global $wpdb;
		PKC_Admin::guard( 'pkc_export_payments' );
		list( $where, $args ) = self::filters();
		$sql  = 'SELECT p.*, o.name AS org_name, o.email AS org_email' . self::from_sql() . "WHERE $where ORDER BY p.id DESC";
		$rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) : $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL
		$out  = array();
		foreach ( $rows as $p ) {
			$out[] = array( pkc_date( $p->created_at, 'Y-m-d H:i' ), $p->org_name, $p->org_email, number_format( $p->amount_paise / 100, 2, '.', '' ), $p->kind, PKC_Billing::method_label( $p->method ), $p->status, $p->invoice_no, $p->rzp_payment_id, $p->mode );
		}
		PKC_Admin::send_csv(
			'pikacart-payments-' . wp_date( 'Y-m-d' ) . '.csv',
			array( 'Date', 'Account', 'Email', 'Amount (INR)', 'Type', 'Method', 'Status', 'Invoice', 'Payment ID', 'Mode' ),
			$out
		);
	}
}
