<?php
/**
 * Report queue: cards reported from public verification pages. The Super Admin
 * can dismiss a report, cancel the card, or suspend the account and cancel all
 * of its cards (verification pages then show Cancelled).
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Reports {

	public static function init() {
		add_action( 'admin_post_pkc_report', array( __CLASS__, 'action' ) );
	}

	public static function open_count() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . pkc_table( 'reports' ) . " WHERE status = 'open'" );
	}

	public static function render() {
		global $wpdb;
		$status = isset( $_GET['status'] ) && 'closed' === $_GET['status'] ? 'closed' : 'open'; // phpcs:ignore WordPress.Security.NonceVerification
		$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'reports' ) . ' WHERE ' . ( 'open' === $status ? 'status = %s' : 'status <> %s' ) . ' ORDER BY id DESC LIMIT 200', 'open' ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		PKC_Admin::header( __( 'Reports', 'pikacart' ), __( 'Cards reported by the public from verification pages. Pikacart must never be used for forged identity documents.', 'pikacart' ) );
		?>
		<nav class="pkc-tabs">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-reports' ) ); ?>" class="<?php echo 'open' === $status ? 'is-active' : ''; ?>"><?php esc_html_e( 'Open', 'pikacart' ); ?> (<?php echo (int) self::open_count(); ?>)</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-reports&status=closed' ) ); ?>" class="<?php echo 'closed' === $status ? 'is-active' : ''; ?>"><?php esc_html_e( 'Handled', 'pikacart' ); ?></a>
		</nav>
		<div class="pkc-panel pkc-panel-flush">
			<?php if ( ! $rows ) : ?>
				<div class="pkc-empty"><?php esc_html_e( 'No reports. Good news!', 'pikacart' ); ?></div>
			<?php else : ?>
				<div class="pkc-table-wrap"><table class="pkc-table">
					<thead><tr><th><?php esc_html_e( 'Card', 'pikacart' ); ?></th><th><?php esc_html_e( 'Organisation', 'pikacart' ); ?></th><th><?php esc_html_e( 'Reason', 'pikacart' ); ?></th><th><?php esc_html_e( 'Reported', 'pikacart' ); ?></th><th></th></tr></thead>
					<tbody>
					<?php
					foreach ( $rows as $r ) :
						$m   = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'members' ) . ' WHERE id = %d', $r->member_id ) );
						$org = PKC_Organisations::get( $r->org_id );
						?>
						<tr>
							<td>
								<?php if ( $m ) : ?>
									<strong><?php echo esc_html( $m->name ); ?></strong><br><span class="pkc-small pkc-muted"><?php echo esc_html( $m->id_no ); ?> · <?php echo esc_html( PKC_Members::status_labels()[ PKC_Members::display_status( $m ) ] ?? $m->status ); ?></span><br>
									<a class="pkc-small" href="<?php echo esc_url( home_url( '/verify/' . $m->verify_token . '/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open verification page', 'pikacart' ); ?> ↗</a>
								<?php else : ?>
									<?php esc_html_e( 'Card deleted', 'pikacart' ); ?>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $org ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-accounts&org=' . $org->id ) ); ?>"><?php echo esc_html( $org->name ); ?></a><br><?php echo PKC_Admin::status_badge( $org->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<?php else : ?>
									<?php esc_html_e( 'Deleted account', 'pikacart' ); ?>
								<?php endif; ?>
							</td>
							<td class="pkc-small"><?php echo esc_html( (string) $r->reason ); ?><?php echo $r->reporter_email ? '<br><span class="pkc-muted">' . esc_html( $r->reporter_email ) . '</span>' : ''; ?></td>
							<td class="pkc-small"><?php echo esc_html( pkc_date( $r->created_at, 'j M Y, g:i a' ) ); ?><?php echo 'open' !== $r->status ? '<br><span class="pkc-badge">' . esc_html( $r->status ) . '</span>' : ''; ?></td>
							<td class="pkc-req-actions">
								<?php if ( 'open' === $r->status ) : ?>
									<?php self::button( $r->id, 'dismiss', __( 'Dismiss', 'pikacart' ), 'pkc-btn' ); ?>
									<?php if ( $m ) : ?>
										<?php self::button( $r->id, 'cancel_card', __( 'Cancel this card', 'pikacart' ), 'pkc-btn pkc-btn-danger', __( 'Cancel this card? Its verification page will show Cancelled.', 'pikacart' ) ); ?>
									<?php endif; ?>
									<?php if ( $org && 'suspended' !== $org->status ) : ?>
										<?php self::button( $r->id, 'suspend', __( 'Suspend account and cancel all cards', 'pikacart' ), 'pkc-btn pkc-btn-danger', __( 'Suspend this account and cancel all of its cards?', 'pikacart' ) ); ?>
									<?php endif; ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table></div>
			<?php endif; ?>
		</div>
		<?php
		PKC_Admin::footer();
	}

	private static function button( $id, $do, $label, $class, $confirm = '' ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'pkc_report_' . $id ); ?>
			<input type="hidden" name="action" value="pkc_report"><input type="hidden" name="report" value="<?php echo (int) $id; ?>"><input type="hidden" name="do" value="<?php echo esc_attr( $do ); ?>">
			<button type="submit" class="<?php echo esc_attr( $class ); ?>" <?php echo $confirm ? 'onclick="return confirm(\'' . esc_js( $confirm ) . '\');"' : ''; ?>><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	public static function action() {
		global $wpdb;
		$id = isset( $_POST['report'] ) ? absint( $_POST['report'] ) : 0;
		PKC_Admin::guard( 'pkc_report_' . $id );
		$r  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'reports' ) . ' WHERE id = %d', $id ) );
		$do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		if ( ! $r ) {
			wp_safe_redirect( admin_url( 'admin.php?page=pikacart-reports' ) );
			exit;
		}
		switch ( $do ) {
			case 'dismiss':
				$wpdb->update( pkc_table( 'reports' ), array( 'status' => 'dismissed' ), array( 'id' => $id ) );
				PKC_Admin::set_flash( __( 'Report dismissed.', 'pikacart' ) );
				break;
			case 'cancel_card':
				$wpdb->update( pkc_table( 'members' ), array( 'status' => 'cancelled', 'updated_at' => pkc_now() ), array( 'id' => $r->member_id ) );
				$wpdb->update( pkc_table( 'reports' ), array( 'status' => 'card_cancelled' ), array( 'id' => $id ) );
				PKC_Activity_Log::add( 'report.card_cancelled', (int) $r->org_id, '#' . $r->member_id );
				PKC_Admin::set_flash( __( 'Card cancelled. Its verification page now shows Cancelled.', 'pikacart' ) );
				break;
			case 'suspend':
				$org    = PKC_Organisations::get( $r->org_id );
				$reason = __( 'A card from this account was reported and found to break the Acceptable Use Policy.', 'pikacart' );
				if ( $org ) {
					PKC_Organisations::update(
						$org->id,
						array(
							'status'         => 'suspended',
							'suspend_reason' => $reason,
						)
					);
					$wpdb->query( $wpdb->prepare( 'UPDATE ' . pkc_table( 'members' ) . " SET status = 'cancelled', updated_at = %s WHERE org_id = %d AND deleted_at IS NULL", pkc_now(), $org->id ) );
					if ( class_exists( 'WP_Session_Tokens' ) ) {
						WP_Session_Tokens::get_instance( (int) $org->user_id )->destroy_all();
					}
					PKC_Emails::send_to_org( $org, 'suspended', array( 'reason' => $reason ) );
					PKC_Activity_Log::add( 'account.suspended', $org->id, $reason );
				}
				$wpdb->query( $wpdb->prepare( 'UPDATE ' . pkc_table( 'reports' ) . " SET status = 'account_suspended' WHERE org_id = %d AND status = 'open'", $r->org_id ) );
				PKC_Admin::set_flash( __( 'Account suspended and all of its cards cancelled.', 'pikacart' ) );
				break;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=pikacart-reports' ) );
		exit;
	}
}
