<?php
/**
 * Notices: post an announcement to all users or to one account.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Notices {

	public static function init() {
		add_action( 'admin_post_pkc_notice', array( __CLASS__, 'action' ) );
	}

	public static function render() {
		global $wpdb;
		$rows = PKC_Notices::all();
		$pre  = isset( $_GET['org'] ) ? absint( $_GET['org'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$orgs = $wpdb->get_results( 'SELECT id, name, email FROM ' . pkc_table( 'organisations' ) . ' ORDER BY name ASC LIMIT 2000' );
		PKC_Admin::header( __( 'Notices', 'pikacart' ), __( 'Announcements appear on the customer dashboard and, if you choose, in their notification bell.', 'pikacart' ) );
		?>
		<div class="pkc-panel">
			<h2 class="pkc-section-title"><?php esc_html_e( 'Post a notice', 'pikacart' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pkc-settings-form">
				<?php wp_nonce_field( 'pkc_notice' ); ?>
				<input type="hidden" name="action" value="pkc_notice"><input type="hidden" name="do" value="create">
				<div class="pkc-grid-2">
					<label class="pkc-field"><span><?php esc_html_e( 'Send to', 'pikacart' ); ?></span>
						<select name="org_id">
							<option value="0"><?php esc_html_e( 'Everyone', 'pikacart' ); ?></option>
							<?php foreach ( $orgs as $o ) : ?>
								<option value="<?php echo (int) $o->id; ?>" <?php selected( $pre, (int) $o->id ); ?>><?php echo esc_html( $o->name . ' · ' . $o->email ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="pkc-field"><span><?php esc_html_e( 'Title', 'pikacart' ); ?></span><input type="text" name="title" required maxlength="200"></label>
				</div>
				<label class="pkc-field"><span><?php esc_html_e( 'Message', 'pikacart' ); ?></span><textarea name="body" rows="4" maxlength="3000"></textarea></label>
				<div class="pkc-grid-2">
					<label class="pkc-field"><span><?php esc_html_e( 'Show from (optional)', 'pikacart' ); ?></span><input type="date" name="starts"></label>
					<label class="pkc-field"><span><?php esc_html_e( 'Show until (optional)', 'pikacart' ); ?></span><input type="date" name="ends"></label>
				</div>
				<label class="pkc-check"><input type="checkbox" name="bell" value="1" checked> <?php esc_html_e( 'Also send to the notification bell', 'pikacart' ); ?></label>
				<p><button type="submit" class="pkc-btn pkc-btn-primary"><?php esc_html_e( 'Post notice', 'pikacart' ); ?></button></p>
			</form>
		</div>
		<div class="pkc-panel pkc-panel-flush">
			<?php if ( ! $rows ) : ?>
				<div class="pkc-empty"><?php esc_html_e( 'No notices yet.', 'pikacart' ); ?></div>
			<?php else : ?>
				<div class="pkc-table-wrap"><table class="pkc-table">
					<thead><tr><th><?php esc_html_e( 'Notice', 'pikacart' ); ?></th><th><?php esc_html_e( 'To', 'pikacart' ); ?></th><th><?php esc_html_e( 'Shown', 'pikacart' ); ?></th><th><?php esc_html_e( 'Posted', 'pikacart' ); ?></th><th></th></tr></thead>
					<tbody>
					<?php
					foreach ( $rows as $n ) :
						$org    = $n->org_id ? PKC_Organisations::get( $n->org_id ) : null;
						$active = ( ! $n->starts_at || pkc_ts( $n->starts_at ) <= time() ) && ( ! $n->ends_at || pkc_ts( $n->ends_at ) >= time() );
						?>
						<tr>
							<td><strong><?php echo esc_html( $n->title ); ?></strong><br><span class="pkc-small pkc-muted"><?php echo esc_html( wp_html_excerpt( (string) $n->body, 160, '…' ) ); ?></span></td>
							<td><?php echo $n->org_id ? esc_html( $org ? $org->name : __( 'Deleted account', 'pikacart' ) ) : esc_html__( 'Everyone', 'pikacart' ); ?></td>
							<td class="pkc-small">
								<span class="pkc-badge <?php echo $active ? 'pkc-badge-active' : 'pkc-badge-cancelled'; ?>"><?php echo esc_html( $active ? __( 'Showing', 'pikacart' ) : __( 'Not showing', 'pikacart' ) ); ?></span><br>
								<?php echo esc_html( ( $n->starts_at ? pkc_date( $n->starts_at, 'j M Y' ) : '…' ) . ' – ' . ( $n->ends_at ? pkc_date( $n->ends_at, 'j M Y' ) : '…' ) ); ?>
							</td>
							<td class="pkc-small"><?php echo esc_html( pkc_date( $n->created_at, 'j M Y, g:i a' ) ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'pkc_notice' ); ?>
									<input type="hidden" name="action" value="pkc_notice"><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?php echo (int) $n->id; ?>">
									<button type="submit" class="pkc-btn pkc-btn-danger" onclick="return confirm('<?php echo esc_js( __( 'Delete this notice?', 'pikacart' ) ); ?>');"><?php esc_html_e( 'Delete', 'pikacart' ); ?></button>
								</form>
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

	public static function action() {
		PKC_Admin::guard( 'pkc_notice' );
		$do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		if ( 'delete' === $do ) {
			PKC_Notices::delete( isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0 );
			PKC_Admin::set_flash( __( 'Notice deleted.', 'pikacart' ) );
		} else {
			$res = PKC_Notices::create( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitized in create().
			PKC_Admin::set_flash( is_wp_error( $res ) ? $res->get_error_message() : __( 'Notice posted.', 'pikacart' ), is_wp_error( $res ) ? 'error' : 'success' );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=pikacart-notices' ) );
		exit;
	}
}
