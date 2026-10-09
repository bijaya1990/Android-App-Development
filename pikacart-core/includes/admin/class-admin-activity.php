<?php
/**
 * Activity log: who did what and when (payments, suspensions, template and
 * settings changes, and more), with filters and CSV export.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Activity {

	public static function init() {
		add_action( 'admin_post_pkc_export_activity', array( __CLASS__, 'export' ) );
	}

	private static function groups() {
		return array(
			''         => __( 'Everything', 'pikacart' ),
			'payment'  => __( 'Payments and subscriptions', 'pikacart' ),
			'account'  => __( 'Accounts and suspensions', 'pikacart' ),
			'template' => __( 'Templates and designs', 'pikacart' ),
			'settings' => __( 'Settings and content', 'pikacart' ),
		);
	}

	private static function where() {
		global $wpdb;
		$where = array( '1=1' );
		$group = isset( $_GET['group'] ) ? sanitize_key( wp_unslash( $_GET['group'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$like  = array(
			'payment'  => array( 'payment_%', 'subscription_%', 'coupon_%', 'expense_%' ),
			'account'  => array( 'account_%', 'trial_%', 'free_%', 'plan_end_%', 'status_%', 'deletion_%', 'report_%', 'reset_link_%', 'view_as%' ),
			'template' => array( 'template_%', 'design_%', 'catalog_%' ),
			'settings' => array( 'settings_%', 'plans_%', 'notice_%' ),
		);
		if ( isset( $like[ $group ] ) ) {
			$parts = array();
			foreach ( $like[ $group ] as $l ) {
				$parts[] = $wpdb->prepare( 'action LIKE %s', $l );
			}
			$where[] = '(' . implode( ' OR ', $parts ) . ')';
		}
		$org = isset( $_GET['org'] ) ? absint( $_GET['org'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $org ) {
			$where[] = $wpdb->prepare( 'org_id = %d', $org );
		}
		$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' !== $q ) {
			$where[] = $wpdb->prepare( '(details LIKE %s OR action LIKE %s)', '%' . $wpdb->esc_like( $q ) . '%', '%' . $wpdb->esc_like( $q ) . '%' );
		}
		foreach ( array( 'from' => '>=', 'to' => '<' ) as $k => $op ) {
			$d = isset( $_GET[ $k ] ) ? sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ) {
				$dt = new DateTime( $d . ' 00:00:00', wp_timezone() );
				if ( 'to' === $k ) {
					$dt->modify( '+1 day' );
				}
				$dt->setTimezone( new DateTimeZone( 'UTC' ) );
				$where[] = $wpdb->prepare( "created_at $op %s", $dt->format( 'Y-m-d H:i:s' ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			}
		}
		return implode( ' AND ', $where );
	}

	private static function who( $user_id ) {
		if ( ! $user_id ) {
			return __( 'System', 'pikacart' );
		}
		$u = get_userdata( $user_id );
		if ( ! $u ) {
			return '#' . $user_id;
		}
		return user_can( $u, PKC_Roles::ADMIN_CAP ) ? $u->display_name . ' (' . __( 'Super Admin', 'pikacart' ) . ')' : $u->display_name;
	}

	public static function render() {
		global $wpdb;
		$per   = 50;
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		$where = self::where();
		$t     = pkc_table( 'activity_log' );
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE $where" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d", $per, ( $paged - 1 ) * $per ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$args  = array( 'page' => 'pikacart-activity' );
		foreach ( array( 'group', 'org', 'q', 'from', 'to' ) as $k ) {
			if ( ! empty( $_GET[ $k ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$args[ $k ] = sanitize_text_field( wp_unslash( $_GET[ $k ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
		}
		$export = wp_nonce_url( add_query_arg( array_merge( $args, array( 'action' => 'pkc_export_activity' ) ), admin_url( 'admin-post.php' ) ), 'pkc_export_activity' );
		PKC_Admin::header( __( 'Activity log', 'pikacart' ), __( 'Who did what and when.', 'pikacart' ), '<a class="pkc-btn" href="' . esc_url( $export ) . '">' . esc_html__( 'Export to Excel (CSV)', 'pikacart' ) . '</a>' );
		?>
		<form method="get" class="pkc-panel pkc-filters">
			<input type="hidden" name="page" value="pikacart-activity">
			<select name="group"><?php foreach ( self::groups() as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $args['group'] ?? '', $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select>
			<input type="search" name="q" value="<?php echo esc_attr( $args['q'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Search details', 'pikacart' ); ?>">
			<label><?php esc_html_e( 'From', 'pikacart' ); ?> <input type="date" name="from" value="<?php echo esc_attr( $args['from'] ?? '' ); ?>"></label>
			<label><?php esc_html_e( 'To', 'pikacart' ); ?> <input type="date" name="to" value="<?php echo esc_attr( $args['to'] ?? '' ); ?>"></label>
			<?php if ( ! empty( $args['org'] ) ) : ?><input type="hidden" name="org" value="<?php echo (int) $args['org']; ?>"><?php endif; ?>
			<button type="submit" class="pkc-btn"><?php esc_html_e( 'Filter', 'pikacart' ); ?></button>
		</form>
		<div class="pkc-panel pkc-panel-flush">
			<?php if ( ! $rows ) : ?>
				<div class="pkc-empty"><?php esc_html_e( 'Nothing found.', 'pikacart' ); ?></div>
			<?php else : ?>
				<div class="pkc-table-wrap"><table class="pkc-table">
					<thead><tr><th><?php esc_html_e( 'When', 'pikacart' ); ?></th><th><?php esc_html_e( 'What', 'pikacart' ); ?></th><th><?php esc_html_e( 'Details', 'pikacart' ); ?></th><th><?php esc_html_e( 'Account', 'pikacart' ); ?></th><th><?php esc_html_e( 'By', 'pikacart' ); ?></th><th><?php esc_html_e( 'IP', 'pikacart' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $rows as $r ) : ?>
						<?php $org = $r->org_id ? PKC_Organisations::get( $r->org_id ) : null; ?>
						<tr>
							<td class="pkc-small pkc-nowrap"><?php echo esc_html( pkc_date( $r->created_at, 'j M Y, g:i a' ) ); ?></td>
							<td><strong><?php echo esc_html( PKC_Activity_Log::label( $r->action ) ); ?></strong></td>
							<td class="pkc-small"><?php echo esc_html( wp_html_excerpt( (string) $r->details, 200, '…' ) ); ?></td>
							<td class="pkc-small"><?php echo $org ? '<a href="' . esc_url( admin_url( 'admin.php?page=pikacart-accounts&org=' . $org->id ) ) . '">' . esc_html( $org->name ) . '</a>' : '—'; ?></td>
							<td class="pkc-small"><?php echo esc_html( self::who( (int) $r->user_id ) ); ?></td>
							<td class="pkc-small pkc-mono"><?php echo esc_html( $r->ip ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table></div>
			<?php endif; ?>
		</div>
		<?php
		PKC_Admin::pagination( $total, $per, $paged, $args );
		PKC_Admin::footer();
	}

	public static function export() {
		global $wpdb;
		if ( ! current_user_can( PKC_Roles::ADMIN_CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'pikacart' ), 403 );
		}
		check_admin_referer( 'pkc_export_activity' );
		$where = self::where();
		$rows  = $wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'activity_log' ) . " WHERE $where ORDER BY id DESC LIMIT 20000" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$out   = array();
		foreach ( $rows as $r ) {
			$org   = $r->org_id ? PKC_Organisations::get( $r->org_id ) : null;
			$out[] = array( pkc_date( $r->created_at, 'Y-m-d H:i' ), PKC_Activity_Log::label( $r->action ), $r->details, $org ? $org->name : '', self::who( (int) $r->user_id ), $r->ip );
		}
		PKC_Admin::send_csv( 'pikacart-activity-' . wp_date( 'Y-m-d' ) . '.csv', array( 'When', 'What', 'Details', 'Account', 'By', 'IP' ), $out );
	}
}
