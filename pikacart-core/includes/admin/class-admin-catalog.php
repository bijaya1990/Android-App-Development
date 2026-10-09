<?php
/**
 * Content managers: categories and sub-types (icon, description, default
 * fields, order, hidden, SEO), card sizes and colour palettes.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Catalog {

	public static function init() {
		add_action( 'admin_post_pkc_catalog', array( __CLASS__, 'action' ) );
	}

	private static function tabs() {
		return array(
			'categories' => __( 'Categories and sub-types', 'pikacart' ),
			'sizes'      => __( 'Card sizes', 'pikacart' ),
			'palettes'   => __( 'Colour palettes', 'pikacart' ),
		);
	}

	public static function field_labels() {
		return array(
			'photo'       => __( 'Photo', 'pikacart' ),
			'name'        => __( 'Full name', 'pikacart' ),
			'id_no'       => __( 'ID number', 'pikacart' ),
			'class'       => __( 'Class / Course', 'pikacart' ),
			'section'     => __( 'Section', 'pikacart' ),
			'designation' => __( 'Designation', 'pikacart' ),
			'department'  => __( 'Department', 'pikacart' ),
			'guardian'    => __( 'Father / Guardian name', 'pikacart' ),
			'dob'         => __( 'Date of birth', 'pikacart' ),
			'blood_group' => __( 'Blood group', 'pikacart' ),
			'mobile'      => __( 'Mobile number', 'pikacart' ),
			'email'       => __( 'Email', 'pikacart' ),
			'address'     => __( 'Address', 'pikacart' ),
			'emergency'   => __( 'Emergency contact', 'pikacart' ),
			'valid_from'  => __( 'Valid from', 'pikacart' ),
			'valid_until' => __( 'Valid until', 'pikacart' ),
		);
	}

	public static function icons() {
		return array( 'school', 'college', 'book', 'briefcase', 'health', 'heart', 'ticket', 'shield', 'users', 'mic', 'building', 'idcard', 'user', 'star' );
	}

	private static function form_open( $do, $extra = '' ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="pkc-cat-form" ' . $extra . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		wp_nonce_field( 'pkc_catalog' );
		echo '<input type="hidden" name="action" value="pkc_catalog"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	}

	private static function seo_fields( $seo, $prefix = 'seo' ) {
		$seo = is_array( $seo ) ? $seo : array();
		?>
		<fieldset class="pkc-seo-box">
			<legend><?php esc_html_e( 'SEO', 'pikacart' ); ?></legend>
			<label><?php esc_html_e( 'SEO title', 'pikacart' ); ?><input type="text" name="<?php echo esc_attr( $prefix ); ?>[title]" value="<?php echo esc_attr( $seo['title'] ?? '' ); ?>" maxlength="70"></label>
			<label><?php esc_html_e( 'Meta description', 'pikacart' ); ?><textarea name="<?php echo esc_attr( $prefix ); ?>[description]" rows="2" maxlength="170"><?php echo esc_textarea( $seo['description'] ?? '' ); ?></textarea></label>
			<label><?php esc_html_e( 'Focus keywords', 'pikacart' ); ?><input type="text" name="<?php echo esc_attr( $prefix ); ?>[keywords]" value="<?php echo esc_attr( $seo['keywords'] ?? '' ); ?>"></label>
			<label><?php esc_html_e( 'Social share image URL', 'pikacart' ); ?><input type="url" name="<?php echo esc_attr( $prefix ); ?>[image]" value="<?php echo esc_attr( $seo['image'] ?? '' ); ?>"></label>
			<label><?php esc_html_e( 'SEO text at the bottom of the page', 'pikacart' ); ?><textarea name="<?php echo esc_attr( $prefix ); ?>[text]" rows="4"><?php echo esc_textarea( $seo['text'] ?? '' ); ?></textarea></label>
		</fieldset>
		<?php
	}

	public static function clean_seo( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		return array(
			'title'       => mb_substr( sanitize_text_field( (string) ( $raw['title'] ?? '' ) ), 0, 120 ),
			'description' => mb_substr( sanitize_textarea_field( (string) ( $raw['description'] ?? '' ) ), 0, 300 ),
			'keywords'    => mb_substr( sanitize_text_field( (string) ( $raw['keywords'] ?? '' ) ), 0, 255 ),
			'image'       => esc_url_raw( (string) ( $raw['image'] ?? '' ) ),
			'text'        => wp_kses_post( (string) ( $raw['text'] ?? '' ) ),
		);
	}

	public static function render() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'categories'; // phpcs:ignore WordPress.Security.NonceVerification
		$tab = array_key_exists( $tab, self::tabs() ) ? $tab : 'categories';
		PKC_Admin::header( __( 'Categories & sizes', 'pikacart' ), __( 'Add, edit, reorder or hide categories and sub-types, and manage card sizes and colour palettes.', 'pikacart' ) );
		echo '<nav class="pkc-tabs">';
		foreach ( self::tabs() as $key => $label ) {
			printf( '<a href="%s" class="%s">%s</a>', esc_url( admin_url( 'admin.php?page=pikacart-catalog&tab=' . $key ) ), $key === $tab ? 'is-active' : '', esc_html( $label ) );
		}
		echo '</nav>';
		call_user_func( array( __CLASS__, 'tab_' . $tab ) );
		PKC_Admin::footer();
	}

	/* ---------- Categories ---------- */

	private static function tab_categories() {
		global $wpdb;
		$cats  = $wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'categories' ) . ' ORDER BY sort_order ASC, id ASC' );
		$subs  = $wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'subtypes' ) . ' ORDER BY sort_order ASC, id ASC' );
		$count = array();
		foreach ( $wpdb->get_results( 'SELECT subtype_id, COUNT(*) AS n FROM ' . pkc_table( 'templates' ) . " WHERE owner_org_id = 0 AND status = 'published' GROUP BY subtype_id" ) as $r ) {
			$count[ (int) $r->subtype_id ] = (int) $r->n;
		}
		$labels = self::field_labels();
		?>
		<div class="pkc-panel">
			<h2 class="pkc-section-title"><?php esc_html_e( 'Add a category', 'pikacart' ); ?></h2>
			<?php self::form_open( 'add_category' ); ?>
				<div class="pkc-inline-form">
					<label><?php esc_html_e( 'Name', 'pikacart' ); ?><input type="text" name="name" required maxlength="120"></label>
					<label><?php esc_html_e( 'Icon', 'pikacart' ); ?><select name="icon"><?php foreach ( self::icons() as $i ) : ?><option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option><?php endforeach; ?></select></label>
					<label class="pkc-grow"><?php esc_html_e( 'Short description', 'pikacart' ); ?><input type="text" name="description" maxlength="255"></label>
					<button type="submit" class="pkc-btn pkc-btn-primary"><?php esc_html_e( 'Add category', 'pikacart' ); ?></button>
				</div>
				<p class="pkc-help"><?php esc_html_e( 'Then add its sub-types below. Every new sub-type gets the 50 built-in designs automatically.', 'pikacart' ); ?></p>
			</form>
		</div>

		<?php foreach ( $cats as $c ) : ?>
			<div class="pkc-panel pkc-cat <?php echo $c->is_hidden ? 'is-hidden' : ''; ?>">
				<div class="pkc-panel-head">
					<h2><?php echo esc_html( $c->name ); ?> <?php echo $c->is_hidden ? '<span class="pkc-badge pkc-badge-cancelled">' . esc_html__( 'Hidden', 'pikacart' ) . '</span>' : ''; ?></h2>
					<span class="pkc-muted pkc-small">/id-card/<?php echo esc_html( $c->slug ); ?>/</span>
				</div>
				<details class="pkc-edit">
					<summary class="pkc-btn"><?php esc_html_e( 'Edit category', 'pikacart' ); ?></summary>
					<?php self::form_open( 'save_category' ); ?>
						<input type="hidden" name="id" value="<?php echo (int) $c->id; ?>">
						<div class="pkc-grid-2">
							<label><?php esc_html_e( 'Name', 'pikacart' ); ?><input type="text" name="name" value="<?php echo esc_attr( $c->name ); ?>" required maxlength="120"></label>
							<label><?php esc_html_e( 'Icon', 'pikacart' ); ?><select name="icon"><?php foreach ( self::icons() as $i ) : ?><option value="<?php echo esc_attr( $i ); ?>" <?php selected( $c->icon, $i ); ?>><?php echo esc_html( $i ); ?></option><?php endforeach; ?></select></label>
							<label><?php esc_html_e( 'Order', 'pikacart' ); ?><input type="number" name="sort" value="<?php echo (int) $c->sort_order; ?>"></label>
							<label class="pkc-check"><input type="checkbox" name="hidden" value="1" <?php checked( (int) $c->is_hidden, 1 ); ?>> <?php esc_html_e( 'Hide this category', 'pikacart' ); ?></label>
						</div>
						<label><?php esc_html_e( 'Description', 'pikacart' ); ?><textarea name="description" rows="2"><?php echo esc_textarea( (string) $c->description ); ?></textarea></label>
						<?php self::seo_fields( pkc_json( $c->seo ) ); ?>
						<button type="submit" class="pkc-btn pkc-btn-primary"><?php esc_html_e( 'Save category', 'pikacart' ); ?></button>
					</form>
				</details>

				<div class="pkc-table-wrap"><table class="pkc-table">
					<thead><tr><th><?php esc_html_e( 'Sub-type', 'pikacart' ); ?></th><th><?php esc_html_e( 'Fields', 'pikacart' ); ?></th><th class="num"><?php esc_html_e( 'Designs', 'pikacart' ); ?></th><th></th></tr></thead>
					<tbody>
					<?php
					foreach ( $subs as $s ) :
						if ( (int) $s->category_id !== (int) $c->id ) {
							continue;
						}
						$fields = pkc_json( $s->fields );
						$by_key = array();
						foreach ( $fields as $f ) {
							$by_key[ $f['key'] ] = $f;
						}
						?>
						<tr class="<?php echo $s->is_hidden ? 'is-hidden' : ''; ?>">
							<td><strong><?php echo esc_html( $s->name ); ?></strong><?php echo $s->is_hidden ? ' <span class="pkc-badge pkc-badge-cancelled">' . esc_html__( 'Hidden', 'pikacart' ) . '</span>' : ''; ?><br><span class="pkc-small pkc-muted">/id-card/<?php echo esc_html( $c->slug . '/' . $s->slug ); ?>/</span></td>
							<td class="pkc-small"><?php echo esc_html( implode( ', ', array_map( function ( $f ) { return $f['label']; }, array_filter( $fields, function ( $f ) { return ! empty( $f['on'] ); } ) ) ) ); ?></td>
							<td class="num"><a href="<?php echo esc_url( admin_url( 'admin.php?page=pikacart-templates' ) ); ?>"><?php echo (int) ( $count[ (int) $s->id ] ?? 0 ); ?></a></td>
							<td>
								<details class="pkc-edit">
									<summary class="pkc-btn"><?php esc_html_e( 'Edit', 'pikacart' ); ?></summary>
									<?php self::form_open( 'save_subtype' ); ?>
										<input type="hidden" name="id" value="<?php echo (int) $s->id; ?>">
										<div class="pkc-grid-2">
											<label><?php esc_html_e( 'Name', 'pikacart' ); ?><input type="text" name="name" value="<?php echo esc_attr( $s->name ); ?>" required maxlength="120"></label>
											<label><?php esc_html_e( 'Order', 'pikacart' ); ?><input type="number" name="sort" value="<?php echo (int) $s->sort_order; ?>"></label>
										</div>
										<label><?php esc_html_e( 'Description', 'pikacart' ); ?><textarea name="description" rows="2"><?php echo esc_textarea( (string) $s->description ); ?></textarea></label>
										<label><?php esc_html_e( 'Default terms and conditions (back of the card)', 'pikacart' ); ?><textarea name="terms" rows="3"><?php echo esc_textarea( (string) $s->terms ); ?></textarea></label>
										<label class="pkc-check"><input type="checkbox" name="hidden" value="1" <?php checked( (int) $s->is_hidden, 1 ); ?>> <?php esc_html_e( 'Hide this sub-type', 'pikacart' ); ?></label>
										<fieldset class="pkc-fields-box">
											<legend><?php esc_html_e( 'Default fields', 'pikacart' ); ?></legend>
											<?php foreach ( $labels as $key => $label ) : ?>
												<?php
												$f     = $by_key[ $key ] ?? null;
												$state = ! $f ? 'none' : ( ! empty( $f['required'] ) ? 'required' : ( ! empty( $f['on'] ) ? 'on' : 'off' ) );
												$fixed = in_array( $key, array( 'photo', 'name', 'id_no' ), true );
												?>
												<div class="pkc-field-row">
													<input type="text" name="fields[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $f ? $f['label'] : $label ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
													<select name="fields[<?php echo esc_attr( $key ); ?>][state]" <?php disabled( $fixed ); ?>>
														<option value="none" <?php selected( $state, 'none' ); ?>><?php esc_html_e( 'Not offered', 'pikacart' ); ?></option>
														<option value="off" <?php selected( $state, 'off' ); ?>><?php esc_html_e( 'Offered, off by default', 'pikacart' ); ?></option>
														<option value="on" <?php selected( $state, 'on' ); ?>><?php esc_html_e( 'On by default', 'pikacart' ); ?></option>
														<option value="required" <?php selected( $state, 'required' ); ?>><?php esc_html_e( 'Required', 'pikacart' ); ?></option>
													</select>
												</div>
											<?php endforeach; ?>
										</fieldset>
										<?php self::seo_fields( pkc_json( $s->seo ) ); ?>
										<button type="submit" class="pkc-btn pkc-btn-primary"><?php esc_html_e( 'Save sub-type', 'pikacart' ); ?></button>
									</form>
								</details>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table></div>
				<?php self::form_open( 'add_subtype' ); ?>
					<input type="hidden" name="category_id" value="<?php echo (int) $c->id; ?>">
					<div class="pkc-inline-form">
						<label><?php esc_html_e( 'New sub-type', 'pikacart' ); ?><input type="text" name="name" required maxlength="120" placeholder="<?php esc_attr_e( 'e.g. Alumni', 'pikacart' ); ?>"></label>
						<label><?php esc_html_e( 'Starting fields', 'pikacart' ); ?><select name="kind"><option value="student"><?php esc_html_e( 'Student (class, section, guardian)', 'pikacart' ); ?></option><option value="staff" selected><?php esc_html_e( 'Staff (designation, department)', 'pikacart' ); ?></option><option value="other"><?php esc_html_e( 'Pass (pass type, area)', 'pikacart' ); ?></option></select></label>
						<button type="submit" class="pkc-btn"><?php esc_html_e( 'Add sub-type', 'pikacart' ); ?></button>
					</div>
				</form>
			</div>
		<?php endforeach; ?>
		<?php
	}

	/* ---------- Sizes ---------- */

	private static function tab_sizes() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'sizes' ) . ' ORDER BY sort_order ASC, id ASC' );
		?>
		<div class="pkc-panel pkc-panel-flush">
			<div class="pkc-table-wrap"><table class="pkc-table">
				<thead><tr><th><?php esc_html_e( 'Name', 'pikacart' ); ?></th><th><?php esc_html_e( 'Width × height (mm)', 'pikacart' ); ?></th><th><?php esc_html_e( 'Note', 'pikacart' ); ?></th><th><?php esc_html_e( 'Order', 'pikacart' ); ?></th><th><?php esc_html_e( 'Active', 'pikacart' ); ?></th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $s ) : ?>
					<tr>
						<?php self::form_open( 'save_size', 'id="size-' . (int) $s->id . '"' ); ?><input type="hidden" name="id" value="<?php echo (int) $s->id; ?>"></form>
						<td><input form="size-<?php echo (int) $s->id; ?>" type="text" name="name" value="<?php echo esc_attr( $s->name ); ?>" required></td>
						<td class="pkc-nowrap"><input form="size-<?php echo (int) $s->id; ?>" class="pkc-num" type="number" step="0.1" min="20" max="330" name="w" value="<?php echo esc_attr( $s->width_mm + 0 ); ?>"> × <input form="size-<?php echo (int) $s->id; ?>" class="pkc-num" type="number" step="0.1" min="20" max="330" name="h" value="<?php echo esc_attr( $s->height_mm + 0 ); ?>"></td>
						<td><input form="size-<?php echo (int) $s->id; ?>" type="text" name="note" value="<?php echo esc_attr( $s->note ); ?>"></td>
						<td><input form="size-<?php echo (int) $s->id; ?>" class="pkc-num" type="number" name="sort" value="<?php echo (int) $s->sort_order; ?>"></td>
						<td><input form="size-<?php echo (int) $s->id; ?>" type="checkbox" name="active" value="1" <?php checked( (int) $s->is_active, 1 ); ?>></td>
						<td><button form="size-<?php echo (int) $s->id; ?>" type="submit" class="pkc-btn"><?php esc_html_e( 'Save', 'pikacart' ); ?></button></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
		</div>
		<div class="pkc-panel">
			<h2 class="pkc-section-title"><?php esc_html_e( 'Add a size', 'pikacart' ); ?></h2>
			<?php self::form_open( 'save_size' ); ?>
				<div class="pkc-inline-form">
					<label><?php esc_html_e( 'Name', 'pikacart' ); ?><input type="text" name="name" required placeholder="<?php esc_attr_e( 'e.g. Large badge', 'pikacart' ); ?>"></label>
					<label><?php esc_html_e( 'Width (mm)', 'pikacart' ); ?><input class="pkc-num" type="number" step="0.1" min="20" max="330" name="w" required></label>
					<label><?php esc_html_e( 'Height (mm)', 'pikacart' ); ?><input class="pkc-num" type="number" step="0.1" min="20" max="330" name="h" required></label>
					<label class="pkc-grow"><?php esc_html_e( 'Note', 'pikacart' ); ?><input type="text" name="note"></label>
					<input type="hidden" name="active" value="1">
					<button type="submit" class="pkc-btn pkc-btn-primary"><?php esc_html_e( 'Add size', 'pikacart' ); ?></button>
				</div>
			</form>
			<p class="pkc-help"><?php esc_html_e( 'Turn a size off instead of deleting it; projects that already use it keep working.', 'pikacart' ); ?></p>
		</div>
		<?php
	}

	/* ---------- Palettes ---------- */

	private static function tab_palettes() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'palettes' ) . ' ORDER BY sort_order ASC, id ASC' );
		$cols = array(
			'p' => __( 'Primary', 'pikacart' ),
			's' => __( 'Secondary', 'pikacart' ),
			'a' => __( 'Accent', 'pikacart' ),
			't' => __( 'Text', 'pikacart' ),
		);
		$map  = array(
			'p' => 'primary_c',
			's' => 'secondary_c',
			'a' => 'accent_c',
			't' => 'text_c',
		);
		?>
		<div class="pkc-panel pkc-panel-flush">
			<div class="pkc-table-wrap"><table class="pkc-table">
				<thead><tr><th></th><th><?php esc_html_e( 'Name', 'pikacart' ); ?></th><?php foreach ( $cols as $label ) : ?><th><?php echo esc_html( $label ); ?></th><?php endforeach; ?><th><?php esc_html_e( 'Order', 'pikacart' ); ?></th><th><?php esc_html_e( 'Active', 'pikacart' ); ?></th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $p ) : ?>
					<?php $fid = 'pal-' . (int) $p->id; ?>
					<tr>
						<?php self::form_open( 'save_palette', 'id="' . esc_attr( $fid ) . '"' ); ?><input type="hidden" name="id" value="<?php echo (int) $p->id; ?>"></form>
						<td><span class="pkc-pal-dot" style="background:linear-gradient(135deg, <?php echo esc_attr( $p->primary_c ); ?> 50%, <?php echo esc_attr( $p->accent_c ); ?> 50%)"></span></td>
						<td><input form="<?php echo esc_attr( $fid ); ?>" type="text" name="name" value="<?php echo esc_attr( $p->name ); ?>" required></td>
						<?php foreach ( $map as $k => $col ) : ?>
							<td><input form="<?php echo esc_attr( $fid ); ?>" type="color" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $p->$col ); ?>"></td>
						<?php endforeach; ?>
						<td><input form="<?php echo esc_attr( $fid ); ?>" class="pkc-num" type="number" name="sort" value="<?php echo (int) $p->sort_order; ?>"></td>
						<td><input form="<?php echo esc_attr( $fid ); ?>" type="checkbox" name="active" value="1" <?php checked( (int) $p->is_active, 1 ); ?>></td>
						<td><button form="<?php echo esc_attr( $fid ); ?>" type="submit" class="pkc-btn"><?php esc_html_e( 'Save', 'pikacart' ); ?></button></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
		</div>
		<div class="pkc-panel">
			<h2 class="pkc-section-title"><?php esc_html_e( 'Add a palette', 'pikacart' ); ?></h2>
			<?php self::form_open( 'save_palette' ); ?>
				<div class="pkc-inline-form">
					<label><?php esc_html_e( 'Name', 'pikacart' ); ?><input type="text" name="name" required placeholder="<?php esc_attr_e( 'e.g. Royal Maroon', 'pikacart' ); ?>"></label>
					<?php foreach ( $cols as $k => $label ) : ?>
						<label><?php echo esc_html( $label ); ?><input type="color" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( array( 'p' => '#4F46E5', 's' => '#312E81', 'a' => '#F97316', 't' => '#1F2433' )[ $k ] ); ?>"></label>
					<?php endforeach; ?>
					<input type="hidden" name="active" value="1">
					<button type="submit" class="pkc-btn pkc-btn-primary"><?php esc_html_e( 'Add palette', 'pikacart' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}

	/* ---------- Saving ---------- */

	private static function unique_slug( $table, $name, $where = '' ) {
		global $wpdb;
		$base = sanitize_title( $name );
		$base = $base ? $base : 'item';
		$slug = $base;
		$i    = 2;
		while ( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . pkc_table( $table ) . ' WHERE slug = %s' . $where, $slug ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL
			$slug = $base . '-' . $i++;
		}
		return $slug;
	}

	public static function action() {
		global $wpdb;
		PKC_Admin::guard( 'pkc_catalog' );
		$do   = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$name = isset( $_POST['name'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['name'] ) ), 0, 120 ) : '';
		$tab  = 'categories';
		$msg  = __( 'Saved.', 'pikacart' );

		switch ( $do ) {
			case 'add_category':
				if ( '' === $name ) {
					break;
				}
				$wpdb->insert(
					pkc_table( 'categories' ),
					array(
						'slug'        => self::unique_slug( 'categories', $name ),
						'name'        => $name,
						'description' => isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '',
						'icon'        => isset( $_POST['icon'] ) ? sanitize_key( wp_unslash( $_POST['icon'] ) ) : 'idcard',
						'sort_order'  => (int) $wpdb->get_var( 'SELECT MAX(sort_order) FROM ' . pkc_table( 'categories' ) ) + 1,
						'seo'         => wp_json_encode( self::clean_seo( array() ) ),
						'created_at'  => pkc_now(),
					)
				);
				$msg = __( 'Category added. Now add its sub-types.', 'pikacart' );
				break;

			case 'save_category':
				$wpdb->update(
					pkc_table( 'categories' ),
					array(
						'name'        => $name ? $name : __( 'Category', 'pikacart' ),
						'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
						'icon'        => isset( $_POST['icon'] ) ? sanitize_key( wp_unslash( $_POST['icon'] ) ) : 'idcard',
						'sort_order'  => isset( $_POST['sort'] ) ? (int) $_POST['sort'] : 0,
						'is_hidden'   => empty( $_POST['hidden'] ) ? 0 : 1,
						'seo'         => wp_json_encode( self::clean_seo( isset( $_POST['seo'] ) ? wp_unslash( $_POST['seo'] ) : array() ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitized in clean_seo().
					),
					array( 'id' => $id )
				);
				break;

			case 'add_subtype':
				$cat_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
				if ( '' === $name || ! PKC_Catalog::category( $cat_id ) ) {
					break;
				}
				$kind = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : 'staff';
				$wpdb->insert(
					pkc_table( 'subtypes' ),
					array(
						'category_id' => $cat_id,
						'slug'        => self::unique_slug( 'subtypes', $name, $wpdb->prepare( ' AND category_id = %d', $cat_id ) ),
						'name'        => $name,
						'fields'      => wp_json_encode( PKC_Installer::default_fields( in_array( $kind, array( 'student', 'staff', 'other' ), true ) ? $kind : 'staff' ) ),
						'terms'       => '',
						'sort_order'  => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(sort_order) FROM ' . pkc_table( 'subtypes' ) . ' WHERE category_id = %d', $cat_id ) ) + 1,
						'seo'         => wp_json_encode( self::clean_seo( array() ) ),
					)
				);
				PKC_Catalog::seed_templates();
				$msg = __( 'Sub-type added with 50 ready designs.', 'pikacart' );
				break;

			case 'save_subtype':
				$sub = PKC_Catalog::subtype( $id );
				if ( ! $sub ) {
					break;
				}
				$posted = isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitized below.
				$fields = array();
				foreach ( self::field_labels() as $key => $label ) {
					$state = in_array( $key, array( 'photo', 'name', 'id_no' ), true ) ? 'required' : sanitize_key( $posted[ $key ]['state'] ?? 'none' );
					if ( 'none' === $state ) {
						continue;
					}
					$text     = sanitize_text_field( (string) ( $posted[ $key ]['label'] ?? '' ) );
					$fields[] = array(
						'key'      => $key,
						'label'    => '' !== $text ? mb_substr( $text, 0, 60 ) : $label,
						'required' => 'required' === $state,
						'on'       => in_array( $state, array( 'on', 'required' ), true ),
					);
				}
				$wpdb->update(
					pkc_table( 'subtypes' ),
					array(
						'name'        => $name ? $name : $sub->name,
						'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
						'terms'       => isset( $_POST['terms'] ) ? sanitize_textarea_field( wp_unslash( $_POST['terms'] ) ) : '',
						'sort_order'  => isset( $_POST['sort'] ) ? (int) $_POST['sort'] : 0,
						'is_hidden'   => empty( $_POST['hidden'] ) ? 0 : 1,
						'fields'      => wp_json_encode( $fields ),
						'seo'         => wp_json_encode( self::clean_seo( isset( $_POST['seo'] ) ? wp_unslash( $_POST['seo'] ) : array() ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitized in clean_seo().
					),
					array( 'id' => $id )
				);
				break;

			case 'save_size':
				$tab = 'sizes';
				$w   = isset( $_POST['w'] ) ? (float) $_POST['w'] : 0;
				$h   = isset( $_POST['h'] ) ? (float) $_POST['h'] : 0;
				if ( '' === $name || $w < 20 || $h < 20 || $w > 330 || $h > 330 ) {
					PKC_Admin::set_flash( __( 'Please enter a name and a size between 20 and 330 mm.', 'pikacart' ), 'error' );
					wp_safe_redirect( admin_url( 'admin.php?page=pikacart-catalog&tab=sizes' ) );
					exit;
				}
				$data = array(
					'name'       => $name,
					'width_mm'   => $w,
					'height_mm'  => $h,
					'note'       => isset( $_POST['note'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['note'] ) ), 0, 255 ) : '',
					'sort_order' => isset( $_POST['sort'] ) ? (int) $_POST['sort'] : (int) $wpdb->get_var( 'SELECT MAX(sort_order) FROM ' . pkc_table( 'sizes' ) ) + 1,
					'is_active'  => empty( $_POST['active'] ) ? 0 : 1,
				);
				if ( $id ) {
					$wpdb->update( pkc_table( 'sizes' ), $data, array( 'id' => $id ) );
				} else {
					$wpdb->insert( pkc_table( 'sizes' ), $data );
				}
				break;

			case 'save_palette':
				$tab  = 'palettes';
				$data = array(
					'name'       => $name ? $name : __( 'Palette', 'pikacart' ),
					'sort_order' => isset( $_POST['sort'] ) ? (int) $_POST['sort'] : (int) $wpdb->get_var( 'SELECT MAX(sort_order) FROM ' . pkc_table( 'palettes' ) ) + 1,
					'is_active'  => empty( $_POST['active'] ) ? 0 : 1,
				);
				foreach ( array( 'p' => 'primary_c', 's' => 'secondary_c', 'a' => 'accent_c', 't' => 'text_c' ) as $k => $col ) {
					$c            = isset( $_POST[ $k ] ) ? sanitize_hex_color( wp_unslash( $_POST[ $k ] ) ) : '';
					$data[ $col ] = $c ? $c : '#333333';
				}
				if ( $id ) {
					$wpdb->update( pkc_table( 'palettes' ), $data, array( 'id' => $id ) );
				} else {
					$wpdb->insert( pkc_table( 'palettes' ), $data );
				}
				break;
		}

		PKC_Activity_Log::add( 'catalog.' . $do, 0, $name );
		PKC_Admin::set_flash( $msg );
		wp_safe_redirect( admin_url( 'admin.php?page=pikacart-catalog&tab=' . $tab ) );
		exit;
	}
}
