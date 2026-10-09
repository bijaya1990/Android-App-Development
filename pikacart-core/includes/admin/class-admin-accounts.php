<?php
/**
 * Accounts: list, search, filter, export, account detail and actions.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Accounts {

	const PER_PAGE = 25;

	public static function init() {
		add_action( 'admin_post_pkc_account_action', array( __CLASS__, 'action' ) );
		add_action( 'admin_post_pkc_export_accounts', array( __CLASS__, 'export' ) );
		add_action( 'admin_post_pkc_view_as', array( __CLASS__, 'view_as' ) );
		add_action( 'admin_post_pkc_view_as_exit', array( __CLASS__, 'view_as_exit' ) );
	}

	/**
	 * Build WHERE from filters. Returns [sql, args].
	 */
	private static function filters() {
		// phpcs:disable WordPress.Security.NonceVerification
		$q      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$cat    = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0;
		// phpcs:enable
		global $wpdb;
		$where = array( '1=1' );
		$args  = array();
		if ( '' !== $q ) {
			$like    = '%' . $wpdb->esc_like( $q ) . '%';
			$where[] = '(o.name LIKE %s OR o.email LIKE %s OR o.mobile LIKE %s OR o.contact_name LIKE %s)';
			array_push( $args, $like, $like, $like, $like );
		}
		if ( $status && array_key_exists( $status, PKC_Access::labels() ) ) {
			$where[] = 'o.status = %s';
			$args[]  = $status;
		}
		if ( $cat ) {
			$where[] = 'o.category_id = %d';
			$args[]  = $cat;
		}
		return array( implode( ' AND ', $where ), $args, compact( 'q', 'status', 'cat' ) );
	}

	private static function query( $where, $args, $limit = 0, $offset = 0 ) {
		global $wpdb;
		$sql = 'SELECT o.*, c.name AS category_name,
			(SELECT COALESCE(SUM(p.amount_paise),0) FROM ' . pkc_table( 'payments' ) . " p WHERE p.org_id = o.id AND p.status = 'captured') AS total_paid,
			(SELECT COUNT(*) FROM " . pkc_table( 'members' ) . ' m WHERE m.org_id = o.id AND m.deleted_at IS NULL) AS cards
			FROM ' . pkc_table( 'organisations' ) . ' o LEFT JOIN ' . pkc_table( 'categories' ) . " c ON c.id = o.category_id
			WHERE $where ORDER BY o.id DESC";
		if ( $limit ) {
			$sql   .= ' LIMIT %d OFFSET %d';
			$args[] = $limit;
			$args[] = $offset;
		}
		return $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) : $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	public static function render() {
		$org_id = isset( $_GET['org'] ) ? absint( $_GET['org'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $org_id ) {
			self::detail( $org_id );
			return;
		}
		global $wpdb;
		list( $where, $args, $f ) = self::filters();
		$paged = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification
		$count_sql = 'SELECT COUNT(*) FROM ' . pkc_table( 'organisations' ) . " o WHERE $where";
		$total     = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( $count_sql, $args ) ) : $wpdb->get_var( $count_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$rows      = self::query( $where, $args, self::PER_PAGE, ( $paged - 1 ) * self::PER_PAGE );
		$cats      = $wpdb->get_results( 'SELECT id, name FROM ' . pkc_table( 'categories' ) . ' ORDER BY sort_order' );

		$export = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'pkc_export_accounts',
					's'      => $f['q'],
					'status' => $f['status'],
					'cat'    => $f['cat'],
				),
				admin_url( 'admin-post.php' )
			),
			'pkc_export_accounts'
		);

		PKC_Admin::header(
			__( 'Accounts', 'pikacart' ),
			/* translators: %s: number of accounts */
			sprintf( _n( '%s organisation', '%s organisations', $total, 'pikacart' ), number_format_i18n( $total ) ),
			'<a class="pkc-btn" href="' . esc_url( $export ) . '">' . esc_html__( 'Export to Excel (CSV)', 'pikacart' ) . '</a>'
		);
		?>
		<form class="pkc-filters" method="get">
			<input type="hidden" name="page" value="pikacart-accounts">
			<input type="search" name="s" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="<?php esc_attr_e( 'Search name, email or mobile', 'pikacart' ); ?>">
			<select name="status">
				<option value=""><?php esc_html_e( 'All statuses', 'pikacart' ); ?></option>
				<?php foreach ( PKC_Access::labels() as $k => $label ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f['status'], $k ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="cat">
				<option value="0"><?php esc_html_e( 'All categories', 'pikacart' ); ?></option>
				<?php foreach ( $cats as $c ) : ?>
					<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $f['cat'], (int) $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<button class="pkc-btn pkc-btn-primary" type="submit"><?php esc_html_e( 'Filter', 'pikacart' ); ?></button>
		</form>

		<div class="pkc-panel pkc-panel-flush">
		<?php if ( $rows ) : ?>
			<div class="pkc-table-wrap">
			<table class="pkc-table pkc-table-lg">
				<thead><tr>
					<th><?php esc_html_e( 'Organisation', 'pikacart' ); ?></th>
					<th><?php esc_html_e( 'Mobile', 'pikacart' ); ?></th>
					<th><?php esc_html_e( 'Category', 'pikacart' ); ?></th>
					<th><?php esc_html_e( 'Status', 'pikacart' ); ?></th>
					<th><?php esc_html_e( 'Joined', 'pikacart' ); ?></th>
					<th><?php esc_html_e( 'Plan ends', 'pikacart' ); ?></th>
					<th class="num"><?php esc_html_e( 'Total paid', 'pikacart' ); ?></th>
					<th class="num"><?php esc_html_e( 'Cards', 'pikacart' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $rows as $o ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-accounts&org=' . (int) $o->id ) ); ?>"><strong><?php echo esc_html( $o->name ); ?></strong></a><br><small><?php echo esc_html( $o->email ); ?></small></td>
						<td><?php echo esc_html( $o->mobile ); ?></td>
						<td><?php echo esc_html( $o->category_name ? $o->category_name : '—' ); ?></td>
						<td><?php echo PKC_Admin::status_badge( $o->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						<td class="pkc-muted"><?php echo esc_html( pkc_date( $o->created_at, 'j M Y' ) ); ?></td>
						<td class="pkc-muted"><?php echo esc_html( $o->period_end ? pkc_date( $o->period_end, 'j M Y' ) : '—' ); ?></td>
						<td class="num"><?php echo esc_html( pkc_money( $o->total_paid ) ); ?></td>
						<td class="num"><?php echo esc_html( number_format_i18n( $o->cards ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			<?php
			PKC_Admin::pagination(
				$total,
				self::PER_PAGE,
				$paged,
				array(
					'page'   => 'pikacart-accounts',
					's'      => $f['q'],
					'status' => $f['status'],
					'cat'    => $f['cat'],
				)
			);
			?>
		<?php else : ?>
			<p class="pkc-empty"><?php esc_html_e( 'No accounts match these filters.', 'pikacart' ); ?></p>
		<?php endif; ?>
		</div>
		<?php
		PKC_Admin::footer();
	}

	private static function action_form( $org_id, $do, $button, $fields = '', $class = 'pkc-btn', $confirm = '' ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pkc-inline-form" <?php echo $confirm ? 'data-confirm="' . esc_attr( $confirm ) . '"' : ''; ?>>
			<input type="hidden" name="action" value="pkc_account_action">
			<input type="hidden" name="org" value="<?php echo esc_attr( $org_id ); ?>">
			<input type="hidden" name="do" value="<?php echo esc_attr( $do ); ?>">
			<?php wp_nonce_field( 'pkc_account_' . $org_id ); ?>
			<?php echo $fields; // phpcs:ignore WordPress.Security.EscapeOutput -- Static inputs built below. ?>
			<button type="submit" class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $button ); ?></button>
		</form>
		<?php
	}

	private static function detail( $org_id ) {
		global $wpdb;
		$org = PKC_Organisations::get( $org_id );
		if ( ! $org ) {
			PKC_Admin::header( __( 'Account not found', 'pikacart' ) );
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=pikacart-accounts' ) ) . '">' . esc_html__( 'Back to accounts', 'pikacart' ) . '</a></p>';
			PKC_Admin::footer();
			return;
		}
		$org      = PKC_Access::refresh( $org );
		$cat      = $org->category_id ? $wpdb->get_var( $wpdb->prepare( 'SELECT name FROM ' . pkc_table( 'categories' ) . ' WHERE id = %d', $org->category_id ) ) : '';
		$payments = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'payments' ) . " WHERE org_id = %d AND status <> 'created' ORDER BY id DESC LIMIT 50", $org_id ) );
		$projects = $wpdb->get_results( $wpdb->prepare( 'SELECT id, name, status, updated_at FROM ' . pkc_table( 'projects' ) . ' WHERE org_id = %d AND deleted_at IS NULL ORDER BY id DESC LIMIT 50', $org_id ) );
		$log      = PKC_Activity_Log::for_org( $org_id, 40 );
		$sub      = PKC_Billing::latest_subscription( $org_id );
		$user     = get_userdata( (int) $org->user_id );
		$del_req  = PKC_Organisations::meta( $org, 'delete_requested' );

		PKC_Admin::header(
			$org->name,
			/* translators: %s: date */
			sprintf( __( 'Joined %s', 'pikacart' ), pkc_date( $org->created_at ) ),
			'<a class="pkc-btn" href="' . esc_url( admin_url( 'admin.php?page=pikacart-accounts' ) ) . '">← ' . esc_html__( 'All accounts', 'pikacart' ) . '</a>'
		);
		if ( $del_req ) {
			/* translators: %s: date */
			echo '<div class="pkc-flash pkc-flash-error">' . esc_html( sprintf( __( 'This account asked to be deleted on %s.', 'pikacart' ), pkc_date( $del_req ) ) ) . '</div>';
		}
		?>
		<div class="pkc-detail">
			<div class="pkc-detail-main">
				<div class="pkc-panel">
					<div class="pkc-panel-head"><h3><?php esc_html_e( 'Profile', 'pikacart' ); ?></h3><?php echo PKC_Admin::status_badge( $org->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<dl class="pkc-dl">
						<dt><?php esc_html_e( 'Contact person', 'pikacart' ); ?></dt><dd><?php echo esc_html( $org->contact_name ); ?></dd>
						<dt><?php esc_html_e( 'Email', 'pikacart' ); ?></dt><dd><a href="mailto:<?php echo esc_attr( $org->email ); ?>"><?php echo esc_html( $org->email ); ?></a> <?php echo ( $user && PKC_Accounts::is_verified( $user->ID ) ) ? '<span class="pkc-badge pkc-badge-active">' . esc_html__( 'Verified', 'pikacart' ) . '</span>' : '<span class="pkc-badge pkc-badge-expired">' . esc_html__( 'Not verified', 'pikacart' ) . '</span>'; ?></dd>
						<dt><?php esc_html_e( 'Mobile', 'pikacart' ); ?></dt><dd><?php echo esc_html( $org->mobile ); ?></dd>
						<dt><?php esc_html_e( 'Category', 'pikacart' ); ?></dt><dd><?php echo esc_html( $cat ? $cat : '—' ); ?></dd>
						<dt><?php esc_html_e( 'Address', 'pikacart' ); ?></dt><dd><?php echo nl2br( esc_html( (string) $org->address ) ); ?></dd>
						<dt><?php esc_html_e( 'Trial', 'pikacart' ); ?></dt><dd><?php echo esc_html( pkc_date( $org->trial_start ) . ' → ' . pkc_date( $org->trial_end ) ); ?></dd>
						<dt><?php esc_html_e( 'Plan ends', 'pikacart' ); ?></dt><dd><?php echo esc_html( $org->period_end ? pkc_date( $org->period_end ) : '—' ); ?></dd>
						<dt><?php esc_html_e( 'Autopay', 'pikacart' ); ?></dt><dd><?php echo esc_html( $sub ? $sub->status . ' · ' . $sub->rzp_subscription_id : '—' ); ?></dd>
						<?php if ( 'suspended' === $org->status ) : ?>
							<dt><?php esc_html_e( 'Suspension reason', 'pikacart' ); ?></dt><dd><?php echo esc_html( (string) $org->suspend_reason ); ?></dd>
						<?php endif; ?>
					</dl>
					<div class="pkc-brand-assets">
						<?php foreach ( array( 'logo' => __( 'Logo', 'pikacart' ), 'sign' => __( 'Signature', 'pikacart' ), 'seal' => __( 'Seal', 'pikacart' ) ) as $k => $label ) : ?>
							<figure><?php echo $org->{$k} ? '<img src="' . esc_url( $org->{$k} ) . '" alt="">' : '<span>—</span>'; ?><figcaption><?php echo esc_html( $label ); ?></figcaption></figure>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="pkc-panel pkc-panel-flush">
					<div class="pkc-panel-head"><h3><?php esc_html_e( 'Payments', 'pikacart' ); ?></h3></div>
					<?php if ( $payments ) : ?>
					<div class="pkc-table-wrap"><table class="pkc-table">
						<thead><tr><th><?php esc_html_e( 'Date', 'pikacart' ); ?></th><th><?php esc_html_e( 'Amount', 'pikacart' ); ?></th><th><?php esc_html_e( 'Method', 'pikacart' ); ?></th><th><?php esc_html_e( 'Status', 'pikacart' ); ?></th><th><?php esc_html_e( 'Invoice', 'pikacart' ); ?></th><th><?php esc_html_e( 'Payment ID', 'pikacart' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $payments as $p ) : ?>
							<tr>
								<td><?php echo esc_html( pkc_date( $p->created_at ) ); ?></td>
								<td><strong><?php echo esc_html( pkc_money( $p->amount_paise ) ); ?></strong></td>
								<td><?php echo esc_html( PKC_Billing::method_label( $p->method ) ); ?></td>
								<td><?php echo PKC_Admin::pay_badge( $p->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td><?php echo esc_html( $p->invoice_no ? $p->invoice_no : '—' ); ?></td>
								<td><code><?php echo esc_html( (string) $p->rzp_payment_id ); ?></code></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table></div>
					<?php else : ?>
						<p class="pkc-empty"><?php esc_html_e( 'No payments yet.', 'pikacart' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="pkc-panel pkc-panel-flush">
					<div class="pkc-panel-head"><h3><?php esc_html_e( 'Card projects', 'pikacart' ); ?></h3></div>
					<?php if ( $projects ) : ?>
						<table class="pkc-table"><tbody>
						<?php foreach ( $projects as $pr ) : ?>
							<tr><td><?php echo esc_html( $pr->name ); ?></td><td><?php echo esc_html( $pr->status ); ?></td><td class="pkc-muted"><?php echo esc_html( pkc_date( $pr->updated_at ) ); ?></td></tr>
						<?php endforeach; ?>
						</tbody></table>
					<?php else : ?>
						<p class="pkc-empty"><?php esc_html_e( 'No card projects yet.', 'pikacart' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="pkc-panel pkc-panel-flush">
					<div class="pkc-panel-head"><h3><?php esc_html_e( 'Activity log', 'pikacart' ); ?></h3></div>
					<?php if ( $log ) : ?>
						<ul class="pkc-log">
						<?php foreach ( $log as $entry ) : ?>
							<li><span class="pkc-muted"><?php echo esc_html( pkc_date( $entry->created_at ) ); ?></span> <strong><?php echo esc_html( PKC_Activity_Log::label( $entry->action ) ); ?></strong> <?php echo esc_html( (string) $entry->details ); ?></li>
						<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="pkc-empty"><?php esc_html_e( 'Nothing recorded yet.', 'pikacart' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<aside class="pkc-detail-side">
				<div class="pkc-panel">
					<h3><?php esc_html_e( 'Actions', 'pikacart' ); ?></h3>
					<p><a class="pkc-btn pkc-btn-primary pkc-btn-block" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pkc_view_as&org=' . $org_id ), 'pkc_view_as_' . $org_id ) ); ?>"><?php esc_html_e( 'View as this customer', 'pikacart' ); ?></a></p>
					<p><a class="pkc-btn pkc-btn-block" href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-notices&org=' . $org_id ) ); ?>"><?php esc_html_e( 'Send a notice', 'pikacart' ); ?></a></p>
					<p><a class="pkc-btn pkc-btn-block" href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-activity&org=' . $org_id ) ); ?>"><?php esc_html_e( 'Full activity log', 'pikacart' ); ?></a></p>
					<hr>

					<?php if ( 'suspended' === $org->status ) : ?>
						<?php self::action_form( $org_id, 'reactivate', __( 'Reactivate account', 'pikacart' ), '', 'pkc-btn pkc-btn-primary pkc-btn-block' ); ?>
					<?php else : ?>
						<?php
						self::action_form(
							$org_id,
							'suspend',
							__( 'Suspend account', 'pikacart' ),
							'<label>' . esc_html__( 'Reason (sent to the customer)', 'pikacart' ) . '<textarea name="reason" rows="2" required></textarea></label>',
							'pkc-btn pkc-btn-danger pkc-btn-block',
							__( 'Suspend this account? The customer will not be able to log in.', 'pikacart' )
						);
						?>
					<?php endif; ?>

					<hr>
					<?php self::action_form( $org_id, 'extend_trial', __( 'Extend trial', 'pikacart' ), '<label>' . esc_html__( 'Add hours', 'pikacart' ) . '<input type="number" name="hours" min="1" max="720" value="2" required></label>' ); ?>
					<?php self::action_form( $org_id, 'free_days', __( 'Grant free days', 'pikacart' ), '<label>' . esc_html__( 'Days', 'pikacart' ) . '<input type="number" name="days" min="1" max="3650" value="30" required></label>' ); ?>
					<?php
					$end_local = $org->period_end ? pkc_date( $org->period_end, 'Y-m-d' ) : '';
					self::action_form( $org_id, 'set_end', __( 'Change plan end date', 'pikacart' ), '<label>' . esc_html__( 'Plan ends on', 'pikacart' ) . '<input type="date" name="end" value="' . esc_attr( $end_local ) . '" required></label>' );
					?>
					<hr>
					<?php self::action_form( $org_id, 'reset_link', __( 'Email a password reset link', 'pikacart' ), '', 'pkc-btn pkc-btn-block' ); ?>
					<hr>
					<?php
					self::action_form(
						$org_id,
						'delete',
						__( 'Delete account and all data', 'pikacart' ),
						'<label class="pkc-check"><input type="checkbox" name="sure" value="1" required> ' . esc_html__( 'I understand this cannot be undone', 'pikacart' ) . '</label>',
						'pkc-btn pkc-btn-danger pkc-btn-block',
						__( 'Permanently delete this account, its cards and files? Payment records are kept for your accounts.', 'pikacart' )
					);
					?>
				</div>
			</aside>
		</div>
		<?php
		PKC_Admin::footer();
	}

	/**
	 * Handle the action buttons on the account page.
	 */
	public static function action() {
		$org_id = isset( $_POST['org'] ) ? absint( $_POST['org'] ) : 0;
		PKC_Admin::guard( 'pkc_account_' . $org_id );
		$org  = PKC_Organisations::get( $org_id );
		$do   = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$back = admin_url( 'admin.php?page=pikacart-accounts&org=' . $org_id );
		if ( ! $org ) {
			wp_safe_redirect( admin_url( 'admin.php?page=pikacart-accounts' ) );
			exit;
		}

		switch ( $do ) {
			case 'suspend':
				$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
				PKC_Organisations::update(
					$org_id,
					array(
						'status'         => 'suspended',
						'suspend_reason' => $reason,
					)
				);
				PKC_Activity_Log::add( 'account.suspended', $org_id, $reason );
				PKC_Emails::send_to_org( $org, 'suspended', array( 'reason' => $reason ) );
				// End the customer's sessions on every device.
				if ( class_exists( 'WP_Session_Tokens' ) ) {
					WP_Session_Tokens::get_instance( (int) $org->user_id )->destroy_all();
				}
				PKC_Admin::set_flash( __( 'Account suspended and the customer was emailed.', 'pikacart' ) );
				break;

			case 'reactivate':
				PKC_Organisations::update(
					$org_id,
					array(
						'status'         => 'expired',
						'suspend_reason' => '',
					)
				);
				$org = PKC_Access::refresh( PKC_Organisations::get( $org_id ) );
				PKC_Activity_Log::add( 'account.reactivated', $org_id, PKC_Access::label( $org->status ) );
				PKC_Emails::send_to_org( $org, 'reactivated' );
				PKC_Notifications::add( $org_id, 'account', __( 'Your account is active again', 'pikacart' ), __( 'Welcome back! Everything is available again.', 'pikacart' ) );
				PKC_Admin::set_flash( __( 'Account reactivated and the customer was emailed.', 'pikacart' ) );
				break;

			case 'extend_trial':
				$hours = isset( $_POST['hours'] ) ? max( 1, min( 720, absint( $_POST['hours'] ) ) ) : 2;
				$start = max( time(), pkc_ts( $org->trial_end ) );
				PKC_Organisations::update( $org_id, array( 'trial_end' => gmdate( 'Y-m-d H:i:s', $start + $hours * HOUR_IN_SECONDS ) ) );
				PKC_Organisations::set_meta( $org_id, 'trial_warned', 0 );
				PKC_Access::refresh( PKC_Organisations::get( $org_id ) );
				/* translators: %d: hours */
				PKC_Activity_Log::add( 'trial.extended', $org_id, sprintf( _n( '%d hour', '%d hours', $hours, 'pikacart' ), $hours ) );
				PKC_Admin::set_flash( __( 'Trial extended.', 'pikacart' ) );
				break;

			case 'free_days':
				$days  = isset( $_POST['days'] ) ? max( 1, min( 3650, absint( $_POST['days'] ) ) ) : 30;
				$start = max( time(), pkc_ts( $org->period_end ) );
				$plan  = PKC_Billing::default_plan();
				PKC_Organisations::update(
					$org_id,
					array(
						'period_end' => gmdate( 'Y-m-d H:i:s', $start + $days * DAY_IN_SECONDS ),
						'plan_id'    => $org->plan_id ? $org->plan_id : ( $plan ? (int) $plan->id : 0 ),
					)
				);
				PKC_Access::refresh( PKC_Organisations::get( $org_id ) );
				/* translators: %d: days */
				PKC_Notifications::add( $org_id, 'plan', __( 'Free days added to your plan', 'pikacart' ), sprintf( _n( 'Pikacart added %d free day to your plan. Enjoy watermark-free cards!', 'Pikacart added %d free days to your plan. Enjoy watermark-free cards!', $days, 'pikacart' ), $days ), 'subscription' );
				/* translators: %d: days */
				PKC_Activity_Log::add( 'free.days', $org_id, sprintf( _n( '%d day', '%d days', $days, 'pikacart' ), $days ) );
				PKC_Admin::set_flash( __( 'Free days granted.', 'pikacart' ) );
				break;

			case 'set_end':
				$date = isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '';
				$dt   = DateTime::createFromFormat( 'Y-m-d H:i:s', $date . ' 23:59:59', wp_timezone() );
				if ( $dt ) {
					$dt->setTimezone( new DateTimeZone( 'UTC' ) );
					PKC_Organisations::update( $org_id, array( 'period_end' => $dt->format( 'Y-m-d H:i:s' ) ) );
					PKC_Access::refresh( PKC_Organisations::get( $org_id ) );
					PKC_Activity_Log::add( 'plan_end.changed', $org_id, $date );
					PKC_Admin::set_flash( __( 'Plan end date changed.', 'pikacart' ) );
				} else {
					PKC_Admin::set_flash( __( 'Please choose a valid date.', 'pikacart' ), 'error' );
				}
				break;

			case 'reset_link':
				$user = get_userdata( (int) $org->user_id );
				if ( $user && PKC_Accounts::send_reset_link( $user ) ) {
					PKC_Activity_Log::add( 'reset_link.sent', $org_id );
					PKC_Admin::set_flash( __( 'Password reset link sent.', 'pikacart' ) );
				} else {
					PKC_Admin::set_flash( __( 'The email could not be sent. Check your site email setup.', 'pikacart' ), 'error' );
				}
				break;

			case 'delete':
				if ( empty( $_POST['sure'] ) ) {
					PKC_Admin::set_flash( __( 'Please tick the confirmation box.', 'pikacart' ), 'error' );
					break;
				}
				PKC_Organisations::delete( $org_id );
				PKC_Admin::set_flash( __( 'Account deleted.', 'pikacart' ) );
				$back = admin_url( 'admin.php?page=pikacart-accounts' );
				break;
		}

		wp_safe_redirect( $back );
		exit;
	}

	public static function export() {
		PKC_Admin::guard( 'pkc_export_accounts' );
		list( $where, $args ) = self::filters();
		$rows = self::query( $where, $args );
		$out  = array();
		foreach ( $rows as $o ) {
			$out[] = array( $o->id, $o->name, $o->contact_name, $o->email, $o->mobile, $o->category_name, PKC_Access::label( $o->status ), pkc_date( $o->created_at, 'Y-m-d H:i' ), $o->period_end ? pkc_date( $o->period_end, 'Y-m-d' ) : '', number_format( $o->total_paid / 100, 2, '.', '' ), $o->cards );
		}
		PKC_Admin::send_csv(
			'pikacart-accounts-' . wp_date( 'Y-m-d' ) . '.csv',
			array( 'ID', 'Organisation', 'Contact', 'Email', 'Mobile', 'Category', 'Status', 'Joined', 'Plan ends', 'Total paid (INR)', 'Cards' ),
			$out
		);
	}

	/**
	 * Open the customer's dashboard read-only, for support.
	 */
	public static function view_as() {
		$org_id = isset( $_GET['org'] ) ? absint( $_GET['org'] ) : 0;
		if ( ! current_user_can( PKC_Roles::ADMIN_CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'pikacart' ), 403 );
		}
		check_admin_referer( 'pkc_view_as_' . $org_id );
		if ( ! PKC_Organisations::get( $org_id ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=pikacart-accounts' ) );
			exit;
		}
		update_user_meta( get_current_user_id(), 'pkc_view_as', $org_id . '|' . time() );
		PKC_Activity_Log::add( 'view_as', $org_id );
		wp_safe_redirect( pkc_url( 'app' ) );
		exit;
	}

	public static function view_as_exit() {
		if ( ! current_user_can( PKC_Roles::ADMIN_CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'pikacart' ), 403 );
		}
		check_admin_referer( 'pkc_view_as_exit' );
		$org_id = PKC_Organisations::viewing_as();
		delete_user_meta( get_current_user_id(), 'pkc_view_as' );
		wp_safe_redirect( admin_url( 'admin.php?page=pikacart-accounts' . ( $org_id ? '&org=' . $org_id : '' ) ) );
		exit;
	}
}
