<?php
/**
 * Finance: expenses (outgoing), the monthly account statement with PDF and
 * Excel download, and coupons.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Finance {

	public static function init() {
		add_action( 'admin_post_pkc_finance', array( __CLASS__, 'action' ) );
	}

	private static function tabs() {
		return array(
			'statement' => __( 'Monthly statement', 'pikacart' ),
			'expenses'  => __( 'Expenses', 'pikacart' ),
			'coupons'   => __( 'Coupons', 'pikacart' ),
		);
	}

	private static function form_open( $do ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'pkc_finance' );
		echo '<input type="hidden" name="action" value="pkc_finance"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	}

	public static function render() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'statement'; // phpcs:ignore WordPress.Security.NonceVerification
		$tab = array_key_exists( $tab, self::tabs() ) ? $tab : 'statement';
		PKC_Admin::header( __( 'Finance', 'pikacart' ), __( 'Income, refunds, your own expenses, net profit and coupons.', 'pikacart' ) );
		echo '<nav class="pkc-tabs">';
		foreach ( self::tabs() as $key => $label ) {
			printf( '<a href="%s" class="%s">%s</a>', esc_url( admin_url( 'admin.php?page=pikacart-finance&tab=' . $key ) ), $key === $tab ? 'is-active' : '', esc_html( $label ) );
		}
		echo '</nav>';
		call_user_func( array( __CLASS__, 'tab_' . $tab ) );
		PKC_Admin::footer();
	}

	/* ---------- Statement ---------- */

	private static function tab_statement() {
		$ym = isset( $_GET['month'] ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : wp_date( 'Y-m' ); // phpcs:ignore WordPress.Security.NonceVerification
		$st = PKC_Finance::statement( $ym );
		$t  = $st['totals'];

		PKC_Assets::print_config(
			'pkc-admin-finance',
			array(
				'statement' => $st,
				'business'  => array(
					'name'    => pkc_setting( 'site_name', 'Pikacart' ),
					'address' => (string) pkc_setting( 'business_address', '' ),
					'gstin'   => (string) pkc_setting( 'gstin', '' ),
				),
			)
		);
		wp_enqueue_script( 'pkc-admin-finance' );
		?>
		<div class="pkc-panel pkc-filters">
			<form method="get" class="pkc-inline-form">
				<input type="hidden" name="page" value="pikacart-finance">
				<input type="hidden" name="tab" value="statement">
				<label><?php esc_html_e( 'Month', 'pikacart' ); ?><input type="month" name="month" value="<?php echo esc_attr( $st['month'] ); ?>"></label>
				<button type="submit" class="pkc-btn"><?php esc_html_e( 'Show', 'pikacart' ); ?></button>
			</form>
			<div class="pkc-ad-actions">
				<button type="button" class="pkc-btn" data-dl="xlsx"><?php esc_html_e( 'Download Excel', 'pikacart' ); ?></button>
				<button type="button" class="pkc-btn pkc-btn-primary" data-dl="pdf"><?php esc_html_e( 'Download PDF', 'pikacart' ); ?></button>
			</div>
		</div>
		<div class="pkc-stats">
			<?php
			$cards = array(
				array( __( 'Income', 'pikacart' ), pkc_money( $t['income'] ), sprintf( /* translators: %d: payments */ _n( '%d payment', '%d payments', $t['count'], 'pikacart' ), $t['count'] ) ),
				array( __( 'Refunds', 'pikacart' ), pkc_money( $t['refunds'] ), '' ),
				array( __( 'Expenses', 'pikacart' ), pkc_money( $t['expenses'] ), '' ),
				array( __( 'Net profit', 'pikacart' ), ( $t['net'] < 0 ? '−' : '' ) . pkc_money( abs( $t['net'] ) ), $st['label'] ),
			);
			foreach ( $cards as $c ) :
				?>
				<div class="pkc-stat"><span class="pkc-stat-label"><?php echo esc_html( $c[0] ); ?></span><strong class="pkc-stat-value"><?php echo esc_html( $c[1] ); ?></strong><?php if ( $c[2] ) : ?><span class="pkc-stat-hint"><?php echo esc_html( $c[2] ); ?></span><?php endif; ?></div>
			<?php endforeach; ?>
		</div>
		<div class="pkc-panel pkc-panel-flush">
			<div class="pkc-table-wrap"><table class="pkc-table">
				<thead><tr><th><?php esc_html_e( 'Date', 'pikacart' ); ?></th><th class="num"><?php esc_html_e( 'Payments', 'pikacart' ); ?></th><th class="num"><?php esc_html_e( 'Income', 'pikacart' ); ?></th><th class="num"><?php esc_html_e( 'Refunds', 'pikacart' ); ?></th><th class="num"><?php esc_html_e( 'Expenses', 'pikacart' ); ?></th><th class="num"><?php esc_html_e( 'Net', 'pikacart' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $st['days'] as $d ) : ?>
					<tr class="<?php echo ( $d['income'] || $d['expenses'] || $d['refunds'] ) ? '' : 'pkc-quiet'; ?>">
						<td><?php echo esc_html( wp_date( 'D, j M', strtotime( $d['date'] . ' 12:00:00' ) ) ); ?></td>
						<td class="num"><?php echo (int) $d['count']; ?></td>
						<td class="num"><?php echo esc_html( pkc_money( $d['income'] ) ); ?></td>
						<td class="num"><?php echo esc_html( pkc_money( $d['refunds'] ) ); ?></td>
						<td class="num"><?php echo esc_html( pkc_money( $d['expenses'] ) ); ?></td>
						<td class="num"><strong><?php echo esc_html( ( $d['net'] < 0 ? '−' : '' ) . pkc_money( abs( $d['net'] ) ) ); ?></strong></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
				<tfoot><tr><th><?php esc_html_e( 'Total', 'pikacart' ); ?></th><th class="num"><?php echo (int) $t['count']; ?></th><th class="num"><?php echo esc_html( pkc_money( $t['income'] ) ); ?></th><th class="num"><?php echo esc_html( pkc_money( $t['refunds'] ) ); ?></th><th class="num"><?php echo esc_html( pkc_money( $t['expenses'] ) ); ?></th><th class="num"><?php echo esc_html( ( $t['net'] < 0 ? '−' : '' ) . pkc_money( abs( $t['net'] ) ) ); ?></th></tr></tfoot>
			</table></div>
		</div>
		<?php
	}

	/* ---------- Expenses ---------- */

	private static function tab_expenses() {
		$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : wp_date( 'Y-m-01' ); // phpcs:ignore WordPress.Security.NonceVerification
		$to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : wp_date( 'Y-m-t' ); // phpcs:ignore WordPress.Security.NonceVerification
		$rows = PKC_Finance::expenses( $from, $to );
		$cats = PKC_Finance::expense_categories();
		$sum  = array_sum( array_map( function ( $r ) { return (int) $r->amount_paise; }, $rows ) );
		?>
		<div class="pkc-panel">
			<h2 class="pkc-section-title"><?php esc_html_e( 'Add an expense', 'pikacart' ); ?></h2>
			<?php self::form_open( 'add_expense' ); ?>
				<div class="pkc-inline-form">
					<label><?php esc_html_e( 'Date', 'pikacart' ); ?><input type="date" name="date" value="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>" required></label>
					<label><?php esc_html_e( 'Category', 'pikacart' ); ?><select name="category"><?php foreach ( $cats as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label>
					<label><?php esc_html_e( 'Amount (₹)', 'pikacart' ); ?><input class="pkc-num" type="number" name="amount" step="0.01" min="0.01" required></label>
					<label class="pkc-grow"><?php esc_html_e( 'Note', 'pikacart' ); ?><input type="text" name="note" maxlength="500"></label>
					<button type="submit" class="pkc-btn pkc-btn-primary"><?php esc_html_e( 'Add expense', 'pikacart' ); ?></button>
				</div>
			</form>
		</div>
		<div class="pkc-panel pkc-filters">
			<form method="get" class="pkc-inline-form">
				<input type="hidden" name="page" value="pikacart-finance"><input type="hidden" name="tab" value="expenses">
				<label><?php esc_html_e( 'From', 'pikacart' ); ?><input type="date" name="from" value="<?php echo esc_attr( $from ); ?>"></label>
				<label><?php esc_html_e( 'To', 'pikacart' ); ?><input type="date" name="to" value="<?php echo esc_attr( $to ); ?>"></label>
				<button type="submit" class="pkc-btn"><?php esc_html_e( 'Filter', 'pikacart' ); ?></button>
			</form>
			<strong><?php echo esc_html( sprintf( /* translators: %s: amount */ __( 'Total: %s', 'pikacart' ), pkc_money( $sum ) ) ); ?></strong>
		</div>
		<div class="pkc-panel pkc-panel-flush">
			<?php if ( ! $rows ) : ?>
				<div class="pkc-empty"><?php esc_html_e( 'No expenses in this period.', 'pikacart' ); ?></div>
			<?php else : ?>
				<div class="pkc-table-wrap"><table class="pkc-table">
					<thead><tr><th><?php esc_html_e( 'Date', 'pikacart' ); ?></th><th><?php esc_html_e( 'Category', 'pikacart' ); ?></th><th><?php esc_html_e( 'Note', 'pikacart' ); ?></th><th class="num"><?php esc_html_e( 'Amount', 'pikacart' ); ?></th><th></th></tr></thead>
					<tbody>
					<?php foreach ( $rows as $r ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( 'j M Y', strtotime( $r->spent_on . ' 12:00:00' ) ) ); ?></td>
							<td><?php echo esc_html( $cats[ $r->category ] ?? $r->category ); ?></td>
							<td><?php echo esc_html( (string) $r->note ); ?></td>
							<td class="num"><strong><?php echo esc_html( pkc_money( (int) $r->amount_paise ) ); ?></strong></td>
							<td>
								<?php self::form_open( 'delete_expense' ); ?>
									<input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">
									<button type="submit" class="pkc-btn pkc-btn-danger" onclick="return confirm('<?php echo esc_js( __( 'Delete this expense?', 'pikacart' ) ); ?>');"><?php esc_html_e( 'Delete', 'pikacart' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ---------- Coupons ---------- */

	private static function tab_coupons() {
		$rows = PKC_Coupons::all();
		?>
		<div class="pkc-panel">
			<h2 class="pkc-section-title"><?php esc_html_e( 'Create a coupon', 'pikacart' ); ?></h2>
			<?php self::form_open( 'save_coupon' ); ?>
				<div class="pkc-inline-form">
					<label><?php esc_html_e( 'Code', 'pikacart' ); ?><input type="text" name="code" required maxlength="40" placeholder="SCHOOL20" style="text-transform:uppercase"></label>
					<label><?php esc_html_e( 'Type', 'pikacart' ); ?><select name="type"><option value="percent"><?php esc_html_e( 'Percentage %', 'pikacart' ); ?></option><option value="flat"><?php esc_html_e( 'Flat amount ₹', 'pikacart' ); ?></option></select></label>
					<label><?php esc_html_e( 'Discount', 'pikacart' ); ?><input class="pkc-num" type="number" name="value" step="0.01" min="0.01" required></label>
					<label><?php esc_html_e( 'Expires on', 'pikacart' ); ?><input type="date" name="expires"></label>
					<label><?php esc_html_e( 'Usage limit', 'pikacart' ); ?><input class="pkc-num" type="number" name="limit" min="0" placeholder="0 = no limit"></label>
					<input type="hidden" name="active" value="1">
					<button type="submit" class="pkc-btn pkc-btn-primary"><?php esc_html_e( 'Create coupon', 'pikacart' ); ?></button>
				</div>
			</form>
			<p class="pkc-help"><?php esc_html_e( 'Coupons apply to one-time payments ("Pay once"). The final price never goes below ₹1. A use is counted only when the payment succeeds.', 'pikacart' ); ?></p>
		</div>
		<div class="pkc-panel pkc-panel-flush">
			<?php if ( ! $rows ) : ?>
				<div class="pkc-empty"><?php esc_html_e( 'No coupons yet.', 'pikacart' ); ?></div>
			<?php else : ?>
				<div class="pkc-table-wrap"><table class="pkc-table">
					<thead><tr><th><?php esc_html_e( 'Code', 'pikacart' ); ?></th><th><?php esc_html_e( 'Discount', 'pikacart' ); ?></th><th><?php esc_html_e( 'Expires', 'pikacart' ); ?></th><th class="num"><?php esc_html_e( 'Used', 'pikacart' ); ?></th><th><?php esc_html_e( 'Status', 'pikacart' ); ?></th><th></th></tr></thead>
					<tbody>
					<?php foreach ( $rows as $c ) : ?>
						<?php $expired = $c->expires_at && pkc_ts( $c->expires_at ) < time(); ?>
						<tr>
							<td><strong class="pkc-mono"><?php echo esc_html( $c->code ); ?></strong></td>
							<td><?php echo esc_html( PKC_Coupons::label( $c ) ); ?></td>
							<td><?php echo $c->expires_at ? esc_html( pkc_date( $c->expires_at, 'j M Y' ) ) : '—'; ?></td>
							<td class="num"><?php echo (int) $c->used_count; ?><?php echo $c->usage_limit ? ' / ' . (int) $c->usage_limit : ''; ?></td>
							<td>
								<?php if ( ! $c->is_active ) : ?>
									<span class="pkc-badge pkc-badge-cancelled"><?php esc_html_e( 'Off', 'pikacart' ); ?></span>
								<?php elseif ( $expired ) : ?>
									<span class="pkc-badge pkc-badge-suspended"><?php esc_html_e( 'Expired', 'pikacart' ); ?></span>
								<?php else : ?>
									<span class="pkc-badge pkc-badge-active"><?php esc_html_e( 'Active', 'pikacart' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php self::form_open( 'toggle_coupon' ); ?>
									<input type="hidden" name="id" value="<?php echo (int) $c->id; ?>">
									<button type="submit" class="pkc-btn"><?php echo esc_html( $c->is_active ? __( 'Turn off', 'pikacart' ) : __( 'Turn on', 'pikacart' ) ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table></div>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function action() {
		global $wpdb;
		PKC_Admin::guard( 'pkc_finance' );
		$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$tab = 'expenses';
		switch ( $do ) {
			case 'add_expense':
				$res = PKC_Finance::add_expense( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitized in add_expense().
				PKC_Admin::set_flash( is_wp_error( $res ) ? $res->get_error_message() : __( 'Expense added.', 'pikacart' ), is_wp_error( $res ) ? 'error' : 'success' );
				break;
			case 'delete_expense':
				PKC_Finance::delete_expense( isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0 );
				PKC_Admin::set_flash( __( 'Expense deleted.', 'pikacart' ) );
				break;
			case 'save_coupon':
				$tab = 'coupons';
				$res = PKC_Coupons::save( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitized in save().
				PKC_Admin::set_flash( is_wp_error( $res ) ? $res->get_error_message() : __( 'Coupon saved.', 'pikacart' ), is_wp_error( $res ) ? 'error' : 'success' );
				break;
			case 'toggle_coupon':
				$tab = 'coupons';
				$c   = PKC_Coupons::get( isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0 );
				if ( $c ) {
					$wpdb->update( pkc_table( 'coupons' ), array( 'is_active' => $c->is_active ? 0 : 1 ), array( 'id' => $c->id ) );
					PKC_Activity_Log::add( 'coupon.toggled', 0, $c->code );
				}
				PKC_Admin::set_flash( __( 'Coupon updated.', 'pikacart' ) );
				break;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=pikacart-finance&tab=' . $tab ) );
		exit;
	}
}
