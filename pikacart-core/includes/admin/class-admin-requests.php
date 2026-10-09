<?php
/**
 * Design on Demand queue: status, countdown to the promised time, open in the
 * builder, publish, or reject with a reason.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Requests {

	public static function init() {
		add_action( 'admin_post_pkc_request_action', array( __CLASS__, 'action' ) );
	}

	public static function render() {
		global $wpdb;
		$labels = PKC_Designs::status_labels();
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'open'; // phpcs:ignore WordPress.Security.NonceVerification
		$where  = 'open' === $status ? "status IN ('new','progress')" : ( isset( $labels[ $status ] ) ? $wpdb->prepare( 'status = %s', $status ) : '1=1' );
		$rows   = $wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'design_requests' ) . " WHERE $where ORDER BY CASE WHEN status IN ('new','progress') THEN 0 ELSE 1 END, due_at ASC, id DESC LIMIT 200" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$counts = array();
		foreach ( $wpdb->get_results( 'SELECT status, COUNT(*) AS n FROM ' . pkc_table( 'design_requests' ) . ' GROUP BY status' ) as $c ) {
			$counts[ $c->status ] = (int) $c->n;
		}
		$counts['open'] = ( $counts['new'] ?? 0 ) + ( $counts['progress'] ?? 0 );
		$tabs           = array_merge( array( 'open' => __( 'Open', 'pikacart' ) ), $labels, array( 'all' => __( 'All', 'pikacart' ) ) );

		PKC_Admin::header(
			__( 'Design requests', 'pikacart' ),
			/* translators: %d: hours */
			sprintf( __( 'Design on Demand queue. Customers are promised their design within %d hours.', 'pikacart' ), PKC_Designs::hours() )
		);
		?>
		<nav class="pkc-tabs">
			<?php foreach ( $tabs as $key => $label ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-requests&status=' . $key ) ); ?>" class="<?php echo $key === $status ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?><?php echo isset( $counts[ $key ] ) ? ' (' . (int) $counts[ $key ] . ')' : ''; ?></a>
			<?php endforeach; ?>
		</nav>
		<div class="pkc-panel pkc-panel-flush">
			<?php if ( ! $rows ) : ?>
				<div class="pkc-empty"><?php esc_html_e( 'No requests here. New requests also arrive by email.', 'pikacart' ); ?></div>
			<?php else : ?>
				<div class="pkc-table-wrap"><table class="pkc-table">
					<thead><tr><th>#</th><th><?php esc_html_e( 'Organisation', 'pikacart' ); ?></th><th><?php esc_html_e( 'Artwork', 'pikacart' ); ?></th><th><?php esc_html_e( 'Details', 'pikacart' ); ?></th><th><?php esc_html_e( 'Due', 'pikacart' ); ?></th><th><?php esc_html_e( 'Status', 'pikacart' ); ?></th><th></th></tr></thead>
					<tbody>
					<?php
					foreach ( $rows as $r ) :
						$org     = PKC_Organisations::get( $r->org_id );
						$subtype = $r->subtype_id ? PKC_Catalog::subtype( $r->subtype_id ) : null;
						$open    = in_array( $r->status, array( 'new', 'progress' ), true );
						?>
						<tr>
							<td>#<?php echo (int) $r->id; ?></td>
							<td>
								<?php if ( $org ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-accounts&org=' . $org->id ) ); ?>"><strong><?php echo esc_html( $org->name ); ?></strong></a><br><span class="pkc-small pkc-muted"><?php echo esc_html( $org->email ); ?></span>
								<?php else : ?>
									<?php esc_html_e( 'Deleted account', 'pikacart' ); ?>
								<?php endif; ?>
							</td>
							<td class="pkc-art">
								<?php foreach ( array( 'front' => __( 'Front', 'pikacart' ), 'back' => __( 'Back', 'pikacart' ) ) as $side => $label ) : ?>
									<?php if ( $r->$side ) : ?>
										<a href="<?php echo esc_url( $r->$side ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $label ); ?>"><img src="<?php echo esc_url( $r->$side ); ?>" alt="<?php echo esc_attr( $label ); ?>" loading="lazy"></a>
									<?php endif; ?>
								<?php endforeach; ?>
							</td>
							<td class="pkc-small">
								<?php echo esc_html( $subtype ? $subtype->name : '—' ); ?> · <?php echo esc_html( 'landscape' === $r->orientation ? __( 'Landscape', 'pikacart' ) : __( 'Portrait', 'pikacart' ) ); ?>
								<?php if ( $r->public_ok ) : ?>
									<br><span class="pkc-badge pkc-badge-active"><?php esc_html_e( 'May be published publicly', 'pikacart' ); ?></span>
								<?php endif; ?>
								<?php if ( $r->notes ) : ?>
									<br><span class="pkc-muted"><?php echo esc_html( wp_html_excerpt( $r->notes, 140, '…' ) ); ?></span>
								<?php endif; ?>
								<?php if ( 'rejected' === $r->status && $r->reject_reason ) : ?>
									<br><em><?php echo esc_html( $r->reject_reason ); ?></em>
								<?php endif; ?>
							</td>
							<td class="pkc-small">
								<?php echo esc_html( pkc_date( $r->created_at, 'j M, g:i a' ) ); ?><br>
								<?php if ( $open ) : ?>
									<strong class="pkc-countdown" data-due="<?php echo (int) pkc_ts( $r->due_at ); ?>"></strong>
								<?php endif; ?>
							</td>
							<td><span class="pkc-badge pkc-req-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( $labels[ $r->status ] ?? $r->status ); ?></span></td>
							<td class="pkc-req-actions">
								<?php if ( 'rejected' !== $r->status ) : ?>
									<a class="pkc-btn pkc-btn-primary" href="<?php echo esc_url( PKC_Admin_Templates::builder_url( 0, $r->id ) ); ?>"><?php echo esc_html( 'live' === $r->status ? __( 'Edit design', 'pikacart' ) : __( 'Open in builder', 'pikacart' ) ); ?></a>
								<?php endif; ?>
								<?php if ( $open ) : ?>
									<details class="pkc-reject">
										<summary class="pkc-btn pkc-btn-danger"><?php esc_html_e( 'Reject', 'pikacart' ); ?></summary>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
											<?php wp_nonce_field( 'pkc_request_' . $r->id ); ?>
											<input type="hidden" name="action" value="pkc_request_action">
											<input type="hidden" name="request" value="<?php echo (int) $r->id; ?>">
											<input type="hidden" name="do" value="reject">
											<textarea name="reason" rows="3" required placeholder="<?php esc_attr_e( 'Reason the customer will see, e.g. the artwork copies a government ID.', 'pikacart' ); ?>"></textarea>
											<button type="submit" class="pkc-btn pkc-btn-danger"><?php esc_html_e( 'Reject and email the customer', 'pikacart' ); ?></button>
										</form>
									</details>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table></div>
			<?php endif; ?>
		</div>
		<script>
		( function () {
			function tick() {
				document.querySelectorAll( '.pkc-countdown' ).forEach( function ( el ) {
					var left = parseInt( el.dataset.due, 10 ) - Math.floor( Date.now() / 1000 );
					var a = Math.abs( left ), h = Math.floor( a / 3600 ), m = Math.floor( ( a % 3600 ) / 60 );
					el.textContent = ( left >= 0 ? <?php echo wp_json_encode( __( 'Due in', 'pikacart' ) ); ?> : <?php echo wp_json_encode( __( 'Late by', 'pikacart' ) ); ?> ) + ' ' + h + 'h ' + m + 'm';
					el.classList.toggle( 'is-late', left < 0 );
				} );
			}
			tick();
			setInterval( tick, 30000 );
		}() );
		</script>
		<?php
		PKC_Admin::footer();
	}

	public static function action() {
		$id = isset( $_POST['request'] ) ? absint( $_POST['request'] ) : 0;
		PKC_Admin::guard( 'pkc_request_' . $id );
		$req = PKC_Designs::request( $id );
		$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		if ( $req && 'reject' === $do ) {
			$res = PKC_Designs::reject( $req, isset( $_POST['reason'] ) ? wp_unslash( $_POST['reason'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitized in reject().
			if ( is_wp_error( $res ) ) {
				PKC_Admin::set_flash( $res->get_error_message(), 'error' );
			} else {
				PKC_Admin::set_flash( __( 'Request rejected and the customer was emailed.', 'pikacart' ) );
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=pikacart-requests' ) );
		exit;
	}
}
