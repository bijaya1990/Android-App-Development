<?php
/**
 * Settings screen with tabs. Every field is defined in PKC_Settings::fields().
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Settings {

	public static function init() {
		add_action( 'admin_post_pkc_save_settings', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_pkc_save_plans', array( __CLASS__, 'save_plans' ) );
	}

	private static function current_tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification
		return array_key_exists( $tab, PKC_Settings::tabs() ) ? $tab : 'general';
	}

	public static function render() {
		$tab = self::current_tab();
		PKC_Admin::header( __( 'Settings', 'pikacart' ), __( 'Everything here can be changed any time without touching code.', 'pikacart' ) );
		?>
		<nav class="pkc-tabs">
			<?php foreach ( PKC_Settings::tabs() as $key => $label ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-settings&tab=' . $key ) ); ?>" class="<?php echo $key === $tab ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
		if ( 'trial' === $tab ) {
			self::plans_panel();
		}
		if ( 'razorpay' === $tab ) {
			self::razorpay_help();
		}
		if ( 'legal' === $tab ) {
			self::legal_panel();
			PKC_Admin::footer();
			return;
		}
		if ( 'emails' === $tab ) {
			echo '<div class="pkc-panel pkc-note"><p>' . esc_html__( 'You can use these placeholders: {name} {org} {site} {link} {amount} {invoice_no} {date} {support_email} {reason} {minutes} {price}. Put [button]Button text[/button] on its own line to show a button that opens {link}.', 'pikacart' ) . '</p></div>';
		}
		if ( 'maintenance' === $tab ) {
			self::maintenance_help();
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pkc-panel pkc-settings-form">
			<input type="hidden" name="action" value="pkc_save_settings">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
			<?php wp_nonce_field( 'pkc_save_settings' ); ?>
			<?php
			foreach ( PKC_Settings::fields() as $key => $def ) {
				if ( $def[0] === $tab ) {
					self::field( $key, $def );
				}
			}
			if ( 'razorpay' === $tab ) {
				self::plan_id_fields();
			}
			?>
			<div class="pkc-form-foot"><button type="submit" class="pkc-btn pkc-btn-primary"><?php esc_html_e( 'Save changes', 'pikacart' ); ?></button></div>
		</form>
		<?php
		PKC_Admin::footer();
	}

	private static function field( $key, $def ) {
		list( , $type, $label, $default, $help ) = $def;
		$value = PKC_Settings::get( $key, '' );
		$id    = 'pkc-' . $key;
		$name  = esc_attr( $key );
		echo '<div class="pkc-field pkc-field-' . esc_attr( $type ) . '">';
		if ( 'checkbox' === $type ) {
			echo '<label class="pkc-check"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . $name . '" value="1" ' . checked( (int) $value, 1, false ) . '> ' . esc_html( $label ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
			switch ( $type ) {
				case 'textarea':
					echo '<textarea id="' . esc_attr( $id ) . '" name="' . $name . '" rows="3">' . esc_textarea( (string) $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput
					break;
				case 'emailbody':
				case 'code':
					echo '<textarea id="' . esc_attr( $id ) . '" name="' . $name . '" rows="' . ( 'code' === $type ? 4 : 7 ) . '" class="pkc-mono">' . esc_textarea( (string) $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput
					break;
				case 'select':
					echo '<select id="' . esc_attr( $id ) . '" name="' . $name . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
					foreach ( $def[5] as $opt => $opt_label ) {
						echo '<option value="' . esc_attr( $opt ) . '" ' . selected( $value, $opt, false ) . '>' . esc_html( $opt_label ) . '</option>';
					}
					echo '</select>';
					break;
				case 'secret':
					$saved = '' !== (string) $value;
					echo '<input type="password" id="' . esc_attr( $id ) . '" name="' . $name . '" value="" autocomplete="new-password" placeholder="' . esc_attr( $saved ? __( 'Saved. Type a new value to replace it.', 'pikacart' ) : '' ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
					break;
				case 'color':
					echo '<span class="pkc-color"><input type="color" id="' . esc_attr( $id ) . '" name="' . $name . '" value="' . esc_attr( (string) $value ) . '"><code>' . esc_html( (string) $value ) . '</code></span>'; // phpcs:ignore WordPress.Security.EscapeOutput
					break;
				case 'media':
					echo '<span class="pkc-media"><input type="url" id="' . esc_attr( $id ) . '" name="' . $name . '" value="' . esc_attr( (string) $value ) . '"><button type="button" class="pkc-btn" data-media="' . esc_attr( $id ) . '">' . esc_html__( 'Choose image', 'pikacart' ) . '</button></span>'; // phpcs:ignore WordPress.Security.EscapeOutput
					if ( $value ) {
						echo '<img class="pkc-media-preview" src="' . esc_url( $value ) . '" alt="">';
					}
					break;
				case 'number':
					echo '<input type="number" step="any" min="0" id="' . esc_attr( $id ) . '" name="' . $name . '" value="' . esc_attr( (string) $value ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
					break;
				default:
					$html_type = in_array( $type, array( 'email', 'url' ), true ) ? $type : 'text';
					echo '<input type="' . esc_attr( $html_type ) . '" id="' . esc_attr( $id ) . '" name="' . $name . '" value="' . esc_attr( (string) $value ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
		if ( $help ) {
			echo '<p class="pkc-help">' . esc_html( $help ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Razorpay setup steps and the webhook URL to copy.
	 */
	private static function razorpay_help() {
		$events = 'subscription.activated, subscription.charged, subscription.pending, subscription.halted, subscription.cancelled, subscription.completed, payment.failed, payment.captured, order.paid';
		?>
		<div class="pkc-panel pkc-note">
			<h3><?php esc_html_e( 'How to connect Razorpay', 'pikacart' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'In the Razorpay Dashboard open Account & Settings → API Keys and generate keys. Paste the Key ID and Key Secret below (test keys first).', 'pikacart' ); ?></li>
				<li><?php esc_html_e( 'Open Subscriptions → Plans and create a plan: Monthly, every 1 month, amount ₹59. Copy its Plan ID (starts with plan_) into the plan box at the bottom of this page.', 'pikacart' ); ?></li>
				<li><?php esc_html_e( 'Open Account & Settings → Webhooks → Add new webhook. Paste the URL below, type any long secret, tick the events listed below, and save. Paste the same secret here.', 'pikacart' ); ?></li>
				<li><?php esc_html_e( 'Test a payment in Test mode. When everything works, add your live keys and live Plan ID and switch the mode to Live.', 'pikacart' ); ?></li>
			</ol>
			<p><strong><?php esc_html_e( 'Webhook URL', 'pikacart' ); ?></strong></p>
			<div class="pkc-copy"><input type="text" readonly value="<?php echo esc_attr( PKC_Razorpay::webhook_url() ); ?>" id="pkc-webhook-url"><button type="button" class="pkc-btn" data-copy="pkc-webhook-url"><?php esc_html_e( 'Copy', 'pikacart' ); ?></button></div>
			<p><strong><?php esc_html_e( 'Events to tick', 'pikacart' ); ?></strong><br><code><?php echo esc_html( $events ); ?></code></p>
			<p class="pkc-help">
				<?php
				echo PKC_Razorpay::configured()
					/* translators: %s: test or live */
					? esc_html( sprintf( __( 'Status: connected in %s mode.', 'pikacart' ), PKC_Razorpay::mode() ) )
					: esc_html__( 'Status: not connected yet. Customers cannot pay until keys are added.', 'pikacart' );
				?>
			</p>
		</div>
		<?php
	}

	private static function plan_id_fields() {
		echo '<h3 class="pkc-form-sub">' . esc_html__( 'Razorpay Plan IDs (for autopay)', 'pikacart' ) . '</h3>';
		foreach ( PKC_Billing::plans( false ) as $plan ) {
			echo '<div class="pkc-grid-2">';
			/* translators: %s: plan name */
			echo '<div class="pkc-field"><label>' . esc_html( sprintf( __( '%s — Test Plan ID', 'pikacart' ), $plan->name ) ) . '</label><input type="text" name="plans_rzp[' . (int) $plan->id . '][test]" value="' . esc_attr( $plan->rzp_plan_test ) . '" placeholder="plan_..."></div>';
			/* translators: %s: plan name */
			echo '<div class="pkc-field"><label>' . esc_html( sprintf( __( '%s — Live Plan ID', 'pikacart' ), $plan->name ) ) . '</label><input type="text" name="plans_rzp[' . (int) $plan->id . '][live]" value="' . esc_attr( $plan->rzp_plan_live ) . '" placeholder="plan_..."></div>';
			echo '</div>';
		}
	}

	/**
	 * Plans table: edit price, period, status; add a new plan.
	 */
	private static function plans_panel() {
		$plans = PKC_Billing::plans( false );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pkc-panel">
			<input type="hidden" name="action" value="pkc_save_plans">
			<?php wp_nonce_field( 'pkc_save_plans' ); ?>
			<div class="pkc-panel-head"><h3><?php esc_html_e( 'Plans', 'pikacart' ); ?></h3></div>
			<p class="pkc-help"><?php esc_html_e( 'The first active plan is the main plan shown to customers. Prices include GST. If you change a price, also create a new plan in Razorpay with the same price and paste its Plan ID in the Razorpay tab.', 'pikacart' ); ?></p>
			<div class="pkc-table-wrap">
			<table class="pkc-table pkc-plans-table">
				<thead><tr><th><?php esc_html_e( 'Name', 'pikacart' ); ?></th><th><?php esc_html_e( 'Price (₹)', 'pikacart' ); ?></th><th><?php esc_html_e( 'Period (days)', 'pikacart' ); ?></th><th><?php esc_html_e( 'Description', 'pikacart' ); ?></th><th><?php esc_html_e( 'Order', 'pikacart' ); ?></th><th><?php esc_html_e( 'Active', 'pikacart' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $plans as $plan ) : $i = (int) $plan->id; ?>
					<tr>
						<td><input type="text" name="plans[<?php echo esc_attr( $i ); ?>][name]" value="<?php echo esc_attr( $plan->name ); ?>" required></td>
						<td><input type="number" min="1" step="0.01" name="plans[<?php echo esc_attr( $i ); ?>][price]" value="<?php echo esc_attr( $plan->price_paise / 100 ); ?>" required></td>
						<td><input type="number" min="1" name="plans[<?php echo esc_attr( $i ); ?>][period_days]" value="<?php echo esc_attr( $plan->period_days ); ?>" required></td>
						<td><input type="text" name="plans[<?php echo esc_attr( $i ); ?>][description]" value="<?php echo esc_attr( (string) $plan->description ); ?>"></td>
						<td><input type="number" name="plans[<?php echo esc_attr( $i ); ?>][sort_order]" value="<?php echo esc_attr( $plan->sort_order ); ?>" class="pkc-small"></td>
						<td><input type="checkbox" name="plans[<?php echo esc_attr( $i ); ?>][is_active]" value="1" <?php checked( (int) $plan->is_active, 1 ); ?>></td>
					</tr>
				<?php endforeach; ?>
					<tr class="pkc-new-row">
						<td><input type="text" name="plans[new][name]" placeholder="<?php esc_attr_e( 'Add a plan, e.g. Yearly', 'pikacart' ); ?>"></td>
						<td><input type="number" min="1" step="0.01" name="plans[new][price]" placeholder="599"></td>
						<td><input type="number" min="1" name="plans[new][period_days]" placeholder="365"></td>
						<td><input type="text" name="plans[new][description]"></td>
						<td><input type="number" name="plans[new][sort_order]" value="<?php echo esc_attr( count( $plans ) + 1 ); ?>" class="pkc-small"></td>
						<td><input type="checkbox" name="plans[new][is_active]" value="1"></td>
					</tr>
				</tbody>
			</table>
			</div>
			<div class="pkc-form-foot"><button type="submit" class="pkc-btn pkc-btn-primary"><?php esc_html_e( 'Save plans', 'pikacart' ); ?></button></div>
		</form>
		<?php
	}

	private static function legal_panel() {
		$labels = array(
			'about'   => __( 'About', 'pikacart' ),
			'contact' => __( 'Contact (with working form)', 'pikacart' ),
			'pricing' => __( 'Pricing', 'pikacart' ),
			'privacy' => __( 'Privacy Policy', 'pikacart' ),
			'terms'   => __( 'Terms and Conditions', 'pikacart' ),
			'refund'  => __( 'Refund and Cancellation Policy', 'pikacart' ),
			'aup'     => __( 'Acceptable Use Policy', 'pikacart' ),
		);
		$pages  = get_option( 'pkc_pages', array() );
		?>
		<div class="pkc-panel">
			<p class="pkc-help"><?php esc_html_e( 'These pages were created for you with sensible text. Razorpay asks for them before approving your account. Edit them like any WordPress page.', 'pikacart' ); ?></p>
			<table class="pkc-table">
				<tbody>
				<?php foreach ( $labels as $key => $label ) : $pid = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0; ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong></td>
						<?php if ( $pid && get_post_status( $pid ) ) : ?>
							<td><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" target="_blank"><?php echo esc_html( get_permalink( $pid ) ); ?></a></td>
							<td><a class="pkc-btn" href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>"><?php esc_html_e( 'Edit', 'pikacart' ); ?></a></td>
						<?php else : ?>
							<td colspan="2" class="pkc-muted"><?php esc_html_e( 'Missing. Deactivate and activate the plugin to create it again.', 'pikacart' ); ?></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private static function maintenance_help() {
		$cron = 'wget -q -O - ' . site_url( 'wp-cron.php?doing_wp_cron' ) . ' >/dev/null 2>&1';
		?>
		<div class="pkc-panel pkc-note">
			<h3><?php esc_html_e( 'Backups', 'pikacart' ); ?></h3>
			<p><?php esc_html_e( 'Your customers\' cards and payments live in your database and uploads folder. Take a full backup regularly from cPanel → Backup (or JetBackup on MilesWeb), and always before updating plugins.', 'pikacart' ); ?></p>
			<h3><?php esc_html_e( 'On-time reminder emails (recommended)', 'pikacart' ); ?></h3>
			<p><?php esc_html_e( 'WordPress runs timed jobs only when someone visits the site. For exact "trial ending" emails, open cPanel → Cron Jobs, choose "Once Per Five Minutes" and paste this command:', 'pikacart' ); ?></p>
			<div class="pkc-copy"><input type="text" readonly value="<?php echo esc_attr( $cron ); ?>" id="pkc-cron-cmd"><button type="button" class="pkc-btn" data-copy="pkc-cron-cmd"><?php esc_html_e( 'Copy', 'pikacart' ); ?></button></div>
			<h3><?php esc_html_e( 'Email delivery', 'pikacart' ); ?></h3>
			<p><?php esc_html_e( 'Emails sent straight from shared hosting can land in spam. Install a free SMTP plugin (for example "WP Mail SMTP") and connect your domain email. Pikacart works with it automatically.', 'pikacart' ); ?></p>
		</div>
		<?php
	}

	public static function save() {
		PKC_Admin::guard( 'pkc_save_settings' );
		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'general';
		if ( ! array_key_exists( $tab, PKC_Settings::tabs() ) ) {
			$tab = 'general';
		}
		$old_mode = pkc_setting( 'free_mode', 'forever' );
		PKC_Settings::save_tab( $tab, $_POST ); // Each field is sanitised by type inside save_tab().
		if ( 'trial' === $tab && $old_mode !== pkc_setting( 'free_mode', 'forever' ) ) {
			PKC_Access::recompute_all();
		}

		if ( 'razorpay' === $tab && isset( $_POST['plans_rzp'] ) && is_array( $_POST['plans_rzp'] ) ) {
			global $wpdb;
			foreach ( wp_unslash( $_POST['plans_rzp'] ) as $plan_id => $ids ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$wpdb->update(
					pkc_table( 'plans' ),
					array(
						'rzp_plan_test' => sanitize_text_field( $ids['test'] ?? '' ),
						'rzp_plan_live' => sanitize_text_field( $ids['live'] ?? '' ),
					),
					array( 'id' => absint( $plan_id ) )
				);
			}
		}
		$tabs = PKC_Settings::tabs();
		PKC_Activity_Log::add( 'settings.saved', 0, $tabs[ $tab ] );
		PKC_Admin::set_flash( __( 'Settings saved.', 'pikacart' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=pikacart-settings&tab=' . $tab ) );
		exit;
	}

	public static function save_plans() {
		global $wpdb;
		PKC_Admin::guard( 'pkc_save_plans' );
		$plans = isset( $_POST['plans'] ) && is_array( $_POST['plans'] ) ? wp_unslash( $_POST['plans'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( $plans as $id => $p ) {
			$row = array(
				'name'        => sanitize_text_field( $p['name'] ?? '' ),
				'price_paise' => (int) round( max( 0, (float) ( $p['price'] ?? 0 ) ) * 100 ),
				'period_days' => max( 1, absint( $p['period_days'] ?? 30 ) ),
				'description' => sanitize_text_field( $p['description'] ?? '' ),
				'sort_order'  => (int) ( $p['sort_order'] ?? 0 ),
				'is_active'   => empty( $p['is_active'] ) ? 0 : 1,
			);
			if ( 'new' === $id ) {
				if ( '' === $row['name'] || $row['price_paise'] < 100 ) {
					continue;
				}
				$row['slug']       = sanitize_title( $row['name'] );
				$row['created_at'] = pkc_now();
				$wpdb->insert( pkc_table( 'plans' ), $row );
			} elseif ( '' !== $row['name'] && $row['price_paise'] >= 100 ) {
				$wpdb->update( pkc_table( 'plans' ), $row, array( 'id' => absint( $id ) ) );
			}
		}
		PKC_Activity_Log::add( 'plans.saved', 0 );
		PKC_Admin::set_flash( __( 'Plans saved.', 'pikacart' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=pikacart-settings&tab=trial' ) );
		exit;
	}
}
