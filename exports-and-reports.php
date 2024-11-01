<?php
/**
 * Exports and Reports
 *
 * @package   ExportsReports
 * @author    Scott Kingsley Clark
 * @copyright 2024 Scott Kingsley Clark
 * @license   GPL v2 or later
 *
 * Plugin Name:       Exports and Reports
 * Plugin URI:        https://github.com/sc0ttkclark/exports-and-reports
 * Description:       Define custom exports / reports for users by creating each export / report and defining the fields as well as custom MySQL queries to run.
 * Version:           0.9.4
 * Author:            Scott Kingsley Clark
 * Author URI:        https://www.scottkclark.com/
 * Text Domain:       exports-and-reports
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * GitHub Plugin URI: https://github.com/sc0ttkclark/exports-and-reports
 * Primary Branch:    main
 */

define( 'EXPORTS_REPORTS_TBL', 'exportsreports_' );
define( 'EXPORTS_REPORTS_VERSION', '094' );
define( 'EXPORTS_REPORTS_URL', plugin_dir_url( __FILE__ ) );
define( 'EXPORTS_REPORTS_DIR', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'WP_ADMIN_UI_EXPORT_URL' ) ) {
	define( 'WP_ADMIN_UI_EXPORT_URL', WP_CONTENT_URL . '/exports' );
}

if ( ! defined( 'WP_ADMIN_UI_EXPORT_DIR' ) ) {
	define( 'WP_ADMIN_UI_EXPORT_DIR', WP_CONTENT_DIR . '/exports' );
}

register_activation_hook( __FILE__, 'exports_reports_install_upgrade' );

add_action( 'admin_init', 'exports_reports_init' );
add_action( 'admin_menu', 'exports_reports_menu' );
add_action( 'admin_menu', 'exports_reports_admin_menu' );

add_action( 'wp_ajax_wp_admin_ui_export', 'exports_reports_wp_admin_ui_export' );

if ( ! class_exists( 'TCPDF' ) ) {
	require_once EXPORTS_REPORTS_DIR . 'vendor/autoload.php';
}

require_once EXPORTS_REPORTS_DIR . 'vendor/vendor-prefixed/autoload.php';

/**
 *
 */
function exports_reports_wp_admin_ui_export() {
	require_once EXPORTS_REPORTS_DIR . 'wp-admin-ui/export.php';

	die( 'Invalid request' ); // AJAX dies
}

function exports_reports_assets() {
	wp_register_script( 'exports-reports-admin', plugins_url( 'exports-and-reports/assets/admin.js' ), [ 'jquery' ], EXPORTS_REPORTS_VERSION, [ 'in_footer' => true ] );
	wp_register_style( 'exports-reports-admin', plugins_url( 'exports-and-reports/assets/admin.css' ), [], EXPORTS_REPORTS_VERSION );
}
add_action( 'admin_enqueue_scripts', 'exports_reports_assets' );

/**
 *
 */
function exports_reports_sql_install( $upgrade = false ) {
	/** @var wpdb $wpdb */ global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/file.php' );

	/**
	 * @var $wp_filesystem WP_Filesystem_Base
	 */ global $wp_filesystem;

	WP_Filesystem();

	$sql = $wp_filesystem->get_contents( EXPORTS_REPORTS_DIR . 'assets/dump.sql' );

	$charset_collate = 'DEFAULT CHARSET utf8';

	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARSET {$wpdb->charset}";
	}

	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}

	if ( 'DEFAULT CHARSET utf8' !== $charset_collate ) {
		$sql = str_replace( 'DEFAULT CHARSET utf8', $charset_collate, $sql );
	}

	$sql = explode( ";\n", str_replace( [ "\r", 'wp_' ], [ "\n", $wpdb->prefix ], $sql ) );

	foreach ( $sql as $query ) {
		$query = trim( $query );

		if ( empty( $query ) ) {
			continue;
		}

		if ( 0 !== stripos( $query, 'CREATE TABLE' ) ) {
			if ( $upgrade ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $query );

			continue;
		}

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		dbDelta( $query );
	}

	delete_option( 'exports_reports_version' );
	add_option( 'exports_reports_version', EXPORTS_REPORTS_VERSION );

	if ( ! $upgrade ) {
		$token = md5( microtime() . wp_generate_password( 20, true ) );

		update_option( 'exports_reports_token', $token );
	}

	exports_reports_schedule_cleanup();
}

/**
 *
 */
function exports_reports_install_upgrade() {
	/** @var \wpdb $wpdb */ global $wpdb;

	// check version
	$version = (int) get_option( 'exports_reports_version' );

	if ( empty( $version ) ) {
		exports_reports_sql_install();
	} else {
		exports_reports_upgrade();
	}
}

/**
 *
 */
function exports_reports_upgrade() {
	/** @var \wpdb $wpdb */ global $wpdb;

	// check version
	$version = (int) get_option( 'exports_reports_version' );

	if ( ! empty( $version ) && (int) EXPORTS_REPORTS_VERSION !== $version ) {
		$version = absint( $version );

		if ( $version < 60 ) {
			$token = md5( microtime() . wp_generate_password( 20, true ) );
			update_option( 'exports_reports_token', $token );
		}

		exports_reports_sql_install( true );
	}
}

/**
 *
 */
function exports_reports_init() {
	exports_reports_upgrade();

	$capabilities = exports_reports_capabilities();

	add_filter( 'members_get_capabilities', 'exports_reports_get_capabilities' );

	// thx gravity forms, great way of integration with members!
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( function_exists( 'members_get_capabilities' ) ) {
		if ( exports_reports_current_user_can_any( 'exports_reports_full_access' ) ) {
			$user = get_userdata( get_current_user_id() );

			if ( $user instanceof WP_User ) {
				$user->remove_cap( 'exports_reports_full_access' );
			}
		}

		$is_admin_with_no_permissions = exports_reports_has_role( 'administrator' ) && ! exports_reports_current_user_can_any( exports_reports_capabilities() );

		if ( $is_admin_with_no_permissions ) {
			$role = get_role( 'administrator' );

			if ( $role ) {
				foreach ( $capabilities as $cap ) {
					$role->add_cap( $cap );
				}
			}
		}
	} else {
		$exports_reports_full_access = exports_reports_has_role( 'administrator' ) ? 'exports_reports_full_access' : '';
		$exports_reports_full_access = apply_filters( 'exports_reports_full_access', $exports_reports_full_access );

		if ( ! empty( $exports_reports_full_access ) ) {
			$user = get_userdata( get_current_user_id() );

			if ( $user instanceof WP_User ) {
				$user->add_cap( $exports_reports_full_access );
			}
		}
	}
}

/**
 *
 */
function exports_reports_admin_menu() {
	if ( defined( 'EXPORTS_REPORTS_DISABLE_MENU' ) ) {
		return;
	}

	$has_full_access = exports_reports_current_user_can_any( 'exports_reports_full_access' );

	if ( is_super_admin() || ( ! $has_full_access && exports_reports_has_role( 'administrator' ) ) ) {
		$has_full_access = true;
	}

	$min_cap = exports_reports_current_user_can_which( exports_reports_capabilities() );

	if ( empty( $min_cap ) ) {
		$min_cap = 'exports_reports_full_access';
	}

	if ( $has_full_access || exports_reports_current_user_can_any( $min_cap ) || exports_reports_current_user_can_any( 'exports_reports_settings' ) ) {
		add_menu_page( 'Reports Admin', 'Reports Admin', $has_full_access ? 'read' : $min_cap, 'exports-reports-admin', null, EXPORTS_REPORTS_URL . 'assets/icons/16.png' );
		add_submenu_page( 'exports-reports-admin', 'Manage Groups', 'Manage Groups', $has_full_access ? 'read' : 'exports_reports_settings', 'exports-reports-admin', 'exports_reports_groups' );
		add_submenu_page( 'exports-reports-admin', 'Manage Reports', 'Manage Reports', $has_full_access ? 'read' : 'exports_reports_settings', 'exports-reports-admin-reports', 'exports_reports_reports' );
		add_submenu_page( 'exports-reports-admin', 'Settings', 'Settings', $has_full_access ? 'read' : 'exports_reports_settings', 'exports-reports-admin-settings', 'exports_reports_settings' );
	}
}

/**
 *
 */
function exports_reports_menu() {
	/** @var wpdb $wpdb */ global $wpdb;

	if ( defined( 'EXPORTS_REPORTS_DISABLE_MENU' ) ) {
		return;
	}

	$has_full_access = exports_reports_current_user_can_any( 'exports_reports_full_access' );

	if ( is_super_admin() || ( ! $has_full_access && exports_reports_has_role( 'administrator' ) ) ) {
		$has_full_access = true;
	}

	$init = false;

	$sql = '
		SELECT `id`, `role_access`, `name`
		FROM `' . $wpdb->prefix . EXPORTS_REPORTS_TBL . 'groups`
		WHERE `disabled` = 0
		ORDER BY `weight`, `name`	
	';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$groups = $wpdb->get_results( $sql );

	if ( ! empty( $groups ) ) {
		foreach ( $groups as $group ) {
			$sql = '
				SELECT `id`, `role_access`
				FROM `' . $wpdb->prefix . EXPORTS_REPORTS_TBL . 'reports`
				WHERE `disabled` = 0 AND `group` = %d
				ORDER BY `weight`, `name`
			';

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare( $sql, [ $group->id ] );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$reports = $wpdb->get_results( $sql );

			if ( 0 < @count( $reports ) ) {
				foreach ( $reports as $report ) {
					if ( $has_full_access || exports_reports_current_user_can_any( 'exports_reports_view' ) || exports_reports_current_user_can_any( 'exports_reports_view_group_' . $group->id ) || exports_reports_current_user_can_any( 'exports_reports_view_report_' . $report->id ) ) {
						$menu_page = 'exports-reports-group-' . $group->id;

						if ( ! $init ) {
							add_menu_page( 'Reports', 'Reports', 'read', $menu_page, null, EXPORTS_REPORTS_URL . 'assets/icons/16.png' );

							$init = $menu_page;
						}

						add_submenu_page( $init, $group->name, $group->name, 'read', $menu_page, 'exports_reports_view' );

						break;
					}

					$roles = array_unique( array_merge( explode( ',', $group->role_access ), explode( ',', $report->role_access ) ) );

					if ( empty( $roles ) ) {
						continue;
					}

					foreach ( $roles as $role ) {
						if ( exports_reports_has_role( $role ) ) {
							$menu_page = 'exports-reports-group-' . $group->id;

							if ( ! $init ) {
								add_menu_page( 'Reports', 'Reports', 'read', $menu_page, null, EXPORTS_REPORTS_URL . 'assets/icons/16.png' );

								$init = $menu_page;
							}

							add_submenu_page( $init, $group->name, $group->name, 'read', $menu_page, 'exports_reports_view' );

							break 2;
						}
					}
				}
			}
		}
	}
}

/**
 * Reset settings for plugin.
 *
 * @since 0.9.4
 */
function exports_reports_reset() {
	update_option( 'exports_reports_version', EXPORTS_REPORTS_VERSION );

	$token = md5( microtime() . wp_generate_password( 20 ) );
	update_option( 'exports_reports_token', $token );
}

/**
 * Output the plugin settings page.
 */
function exports_reports_settings() {
	if ( ! empty( $_POST['cronjob_token'] ) ) {
		update_option( 'exports_reports_token', sanitize_title( sanitize_text_field( $_POST['cronjob_token'] ) ) );
	}

	$api_url = EXPORTS_REPORTS_URL . 'api.php?report=YOUR_REPORT_ID%s&token=' . urlencode( get_option( 'exports_reports_token' ) );

	wp_enqueue_style( 'exports-reports-admin' );
	?>
	<div class="wrap">
		<div id="icon-edit-pages" class="icon32" style="background-position:0 0;background-image:url(<?php echo esc_url( EXPORTS_REPORTS_URL . 'assets/icons/32.png' ); ?>)">
			<br /></div>
		<h2>Exports and Reports - Settings</h2>

		<?php
		if ( ! empty( $_POST['clear'] ) || ! empty( $_POST['reset'] ) ) {
			exports_reports_cleanup( true );
			?>
			<div id="message" class="updated fade">
				<p>Your Exports directory has been cleaned up and all export files have been removed.</p>
			</div>
			<?php
		}

		if ( ! empty( $_POST['reset'] ) ) {
			exports_reports_reset();
			?>
			<div id="message" class="updated fade">
				<p>Your Settings have been reset.</p>
			</div>
			<?php
		}
		?>

		<div style="height:20px;"></div>
		<form method="post" action="">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="clear">Clear Exports Directory</label></th>
					<td>
						<input name="clear" type="submit" id="clear" value=" Clear Now " />
						<span class="description">This will remove all files from your Exports directory - <?php echo esc_html( str_replace( ABSPATH, '', WP_ADMIN_UI_EXPORT_DIR ) ); ?></span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="cronjob_token">Cronjob/JSON API Token</label></th>
					<td>
						<input name="cronjob_token" type="text" id="cronjob_token" size="50" value="<?php echo esc_attr( get_option( 'exports_reports_token' ) ); ?>" /><br />
						<span class="description">
							Make sure this token is secure and that no one else gets this -- this key allows you to Export your report from a Cronjob on your server, by accessing the URL, or for the JSON API. You can change it anytime as needed.<br /><br />

							<label for="export_api_url1" style="font-style:normal;"><strong>URL to Export (useful for Cronjobs):</strong></label><br />
							<input type="text" id="export_api_url1" style="width:100%;" value="<?php echo esc_url( sprintf( $api_url, '&export_type=YOUR_EXPORT_TYPE' ) ); ?>" /><br />

							<label for="export_api_url2" style="font-style:normal;"><strong>URL to JSON API (paginated):</strong></label><br />
							<input type="text" id="export_api_url2" style="width:100%;" value="<?php echo esc_url( sprintf( $api_url, '&pg=PAGE_NUMBER&action=json' ) ); ?>" /><br />

							<label for="export_api_url3" style="font-style:normal;"><strong>URL to JSON API (full data):</strong></label><br />
							<input type="text" id="export_api_url3" style="width:100%;" value="<?php echo esc_url( sprintf( $api_url, '&full=1&action=json' ) ); ?>" />

							<label for="export_api_url4" style="font-style:normal;"><strong>URL to Export and then Download File:</strong></label><br />
							<input type="text" id="export_api_url4" style="width:100%;" value="<?php echo esc_url( sprintf( $api_url, '&exports_and_reports_download=1&export_type=YOUR_EXPORT_TYPE' ) ); ?>" /><br />
						</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="reset">Reset All Settings</label></th>
					<td>
						<input name="reset" type="submit" id="reset" value=" Reset Now " />
						<span class="description">This will clear all groups / reports and remove all files from your Exports directory too - <?php echo esc_html( str_replace( ABSPATH, '', WP_ADMIN_UI_EXPORT_DIR ) ); ?></span>
					</td>
				</tr>
				<!--
				<tr valign="top">
					<th scope="row"><label for=""></label></th>
					<td>
						<input name="" type="text" id="" value="0" class="small-text" />
						<span class="description"></span>
					</td>
				</tr>-->
			</table>
			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="Save Settings" />
			</p>
		</form>
	</div>
	<?php
}

/**
 *
 */
function exports_reports_groups() {
	global $wpdb;

	require_once EXPORTS_REPORTS_DIR . 'wp-admin-ui/class-exports-reports-admin-ui.php';

	$columns = [
		'name',
		'disabled' => [
			'label' => __( 'Disabled', 'exports-and-reports' ),
			'type'  => 'bool',
		],
		'created'  => [
			'label' => __( 'Date Created', 'exports-and-reports' ),
			'type'  => 'datetime',
		],
		'updated'  => [
			'label' => __( 'Last Modified', 'exports-and-reports' ),
			'type'  => 'datetime',
		],
		'id'       => [
			'label' => __( 'Group ID', 'exports-and-reports' ),
			'type'  => 'number',
		],
	];

	$form_columns = $columns;

	unset( $form_columns['id'] );

	$roles = exports_reports_get_roles();

	$form_columns['role_access'] = [
		'label'            => 'WP Roles with Access',
		'comments'         => 'Add the exports_reports_full_access capability to a role for full access to reports, exports_reports_settings for only access to settings, exports_reports_view for access to view all reports, exports_reports_view_group_{ID} for access to view a group and all of the reports within, or exports_reports_view_report_{ID} for access to view a single report',
		'type'             => 'related',
		'related'          => $roles,
		'related_multiple' => true,
	];

	$form_columns['created']['date_touch_on_create'] = true;
	$form_columns['created']['display']              = false;
	$form_columns['updated']['date_touch']           = true;
	$form_columns['updated']['display']              = false;

	$admin_ui = [
		'reorder'      => 'weight',
		'order'        => '`weight`',
		'order_dir'    => 'ASC',
		'css'          => EXPORTS_REPORTS_URL . 'assets/admin.css',
		'item'         => 'Report Group',
		'items'        => 'Report Groups',
		'table'        => $wpdb->prefix . EXPORTS_REPORTS_TBL . 'groups',
		'columns'      => $columns,
		'form_columns' => $form_columns,
		'icon'         => EXPORTS_REPORTS_URL . 'assets/icons/32.png',
		'duplicate'    => true,
	];

	$admin = new Exports_Reports_Admin_UI( $admin_ui );

	$admin->go();
}

/**
 *
 */
function exports_reports_reports() {
	global $wpdb;

	if ( ! wp_script_is( 'jquery-ui-core', 'queue' ) && ! wp_script_is( 'jquery-ui-core', 'to_do' ) && ! wp_script_is( 'jquery-ui-core', 'done' ) ) {
		wp_print_scripts( 'jquery-ui-core' );
	}

	if ( ! wp_script_is( 'jquery-ui-sortable', 'queue' ) && ! wp_script_is( 'jquery-ui-sortable', 'to_do' ) && ! wp_script_is( 'jquery-ui-sortable', 'done' ) ) {
		wp_print_scripts( 'jquery-ui-sortable' );
	}

	require_once EXPORTS_REPORTS_DIR . 'wp-admin-ui/class-exports-reports-admin-ui.php';

	$columns = [
		'name',
		'group'    => [
			'label'   => 'Group',
			'type'    => 'related',
			'related' => $wpdb->prefix . EXPORTS_REPORTS_TBL . 'groups',
		],
		'disabled' => [
			'label' => 'Disabled',
			'type'  => 'bool',
		],
		'created'  => [
			'label' => 'Date Created',
			'type'  => 'datetime',
		],
		'updated'  => [
			'label' => 'Last Modified',
			'type'  => 'datetime',
		],
		'id'       => [
			'label' => 'Report ID',
			'type'  => 'number',
		],
	];

	$columns['created']['filter']       = true;
	$columns['created']['filter_label'] = 'Lifespan (created / modified)';
	$columns['created']['date_ongoing'] = 'updated';

	$form_columns = $columns;

	unset( $form_columns['id'] );

	$form_columns['disabled']['label'] = 'Disabled?';

	$form_columns['disable_export'] = [
		'label' => 'Disable Export?',
		'type'  => 'bool',
	];

	$form_columns['default_none'] = [
		'label'    => 'Default to No Results?',
		'type'     => 'bool',
		'comments' => 'On = Show no results and require search; Off (default) = Show all results',
	];

	$form_columns['page_orientation'] = [
		'label'   => 'PDF Report Page Orientation',
		'type'    => 'related',
		'related' => [
			'L' => 'Landscape',
			'P' => 'Portrait',
		],
	];

	$form_columns['created']['date_touch_on_create'] = true;
	$form_columns['created']['display']              = false;
	$form_columns['updated']['date_touch']           = true;
	$form_columns['updated']['display']              = false;

	$roles = exports_reports_get_roles();

	$form_columns['role_access'] = [
		'label'            => 'WP Roles with Access',
		'type'             => 'related',
		'related'          => $roles,
		'related_multiple' => true,
	];

	$form_columns['sql_query'] = [
		'label'    => 'SQL Query',
		'type'     => 'desc',
		'comments' => 'Available Variables: %%WHERE%% %%HAVING%% %%ORDERBY%% %%LIMIT%%<br />(example: WHERE %%WHERE%% my_field=1)',
	];

	$form_columns['sql_query_count'] = [
		'label'    => 'SQL Query for Count (advanced, optional)',
		'type'     => 'desc',
		'comments' => 'For advanced/complex queries above, you can SELECT minimal fields here for better "Total Count" performance.<br />Available Variables: %%WHERE%% %%HAVING%%<br />(example: WHERE %%WHERE%% my_field=1)',
	];

	$form_columns['field_data'] = [
		'label'        => 'Fields (optional)',
		'custom_input' => 'exports_reports_report_field',
		'custom_save'  => 'exports_reports_report_field_save',
	];

	$admin_ui = [
		'reorder'       => 'weight',
		'reorder_order' => '`group` ASC,`weight`',
		'order'         => '`group` ASC,`weight`',
		'order_dir'     => 'ASC',
		'css'           => EXPORTS_REPORTS_URL . 'assets/admin.css',
		'item'          => 'Report',
		'items'         => 'Reports',
		'table'         => $wpdb->prefix . EXPORTS_REPORTS_TBL . 'reports',
		'columns'       => $columns,
		'form_columns'  => $form_columns,
		'icon'          => EXPORTS_REPORTS_URL . 'assets/icons/32.png',
		'duplicate'     => true,
	];

	$admin = new Exports_Reports_Admin_UI( $admin_ui );

	$admin->go();
}

/**
 * @param $column
 * @param $attributes
 * @param $obj
 */
function exports_reports_report_field( $column, $attributes, $obj ) {
	$field_data = @json_decode( $obj->row[ $column ], true );
	?>
	<style type="text/css">
		.field_data {
			overflow: visible;
		}

		.field_data .sortable td div {
			min-width: 160px;
		}

		.field_data .sortable td div.dragme {
			background:   url(<?php echo esc_attr( EXPORTS_REPORTS_URL ); ?>assets/icons/move.png) !important;
			width:        16px;
			min-width:    16px;
			height:       16px;
			margin-right: 8px;
			cursor:       pointer;
			margin:       auto auto;
		}

		.field_data tbody.field_show,
		.field_data tbody tr.field_hide_link {
			background-color: #EEE;
		}

		.field_data tbody.field_advanced {
			display: none;
		}
	</style>
	<div class="field_data">
		<p><input type="button" class="button" value=" Add Field " onclick="field_add_row(0);" /></p>
		<table class="widefat" id="field_data">
			<tbody class="sortable">
			<?php
			$field_types = [
				'text'     => __( 'Text', 'exports-and-reports' ),
				'bool'     => __( 'Boolean (Checkbox)', 'exports-and-reports' ),
				'date'     => __( 'Date', 'exports-and-reports' ),
				'time'     => __( 'Time', 'exports-and-reports' ),
				'datetime' => __( 'Date + Time', 'exports-and-reports' ),
				'number'   => __( 'Number (no decimal)', 'exports-and-reports' ),
				'decimal'  => __( 'Decimal (two places)', 'exports-and-reports' ),
				'related'  => __( 'Related', 'exports-and-reports' ),
			];

			ob_start();
			?>
			<tr class="field_row">
				<td>
					<div class="dragme"></div>
				</td>
				<td>
					<table class="widefat">
						<tr>
							<td>
								<div>Field Name</div>
								<input type="text" name="field_name[0]" value="" class="medium-text" /></td>
							<td>
								<div>Data Type</div>
								<select name="field_type[0]">
									<?php
									foreach ( $field_types as $field_type => $field_label ) {
										?>
										<option value="<?php echo esc_attr( $field_type ); ?>"><?php echo esc_html( $field_label ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
							<td>
								<div>Label (optional)</div>
								<input type="text" name="field_label[0]" value="" class="medium-text" /></td>
						</tr>
						<tbody id="field_show_0" class="field_show">
						<tr>
							<td colspan="3">
								<a href="#advanced" onclick="jQuery('#field_show_0').hide();jQuery('#field_advanced_0').show();exports_reports_reset_alt();return false;">+&nbsp;&nbsp;Show Advanced Field Options</a>
							</td>
						</tr>
						</tbody>
						<tbody id="field_advanced_0" class="field_advanced">
						<tr class="field_hide_link">
							<td colspan="3">
								<a href="#advanced" onclick="jQuery('#field_show_0').show();jQuery('#field_advanced_0').hide();exports_reports_reset_alt();return false;">-&nbsp;&nbsp;Hide Advanced Field Options</a>
							</td>
						</tr>
						<tr>
							<td>
								<div>Real Field (optional if using Alias)</div>
								<input type="text" name="field_real_name[0]" value="" class="medium-text" /></td>
							<td>
								<div>Hide from Report</div>
								Yes
								<input type="radio" name="field_hide_report[0]" value="1" class="medium-text" />&nbsp;&nbsp; No<input type="radio" name="field_hide_report[0]" value="0" class="medium-text" CHECKED />
							</td>
							<td>
								<div>Searchable</div>
								Yes
								<input type="radio" name="field_search[0]" value="0" class="medium-text" CHECKED />&nbsp;&nbsp; No<input type="radio" name="field_search[0]" value="1" class="medium-text" />
							</td>
						</tr>
						<tr>
							<td>
								<div>Display Function (optional)</div>
								<input type="text" name="field_custom_display[0]" value="" class="medium-text" /></td>
							<td>
								<div>Hide from Export</div>
								Yes
								<input type="radio" name="field_hide_export[0]" value="1" class="medium-text" />&nbsp;&nbsp; No<input type="radio" name="field_hide_export[0]" value="0" class="medium-text" CHECKED />
							</td>
							<td>
								<div>Filterable (optional)</div>
								Yes
								<input type="radio" name="field_filter[0]" value="1" class="medium-text" />&nbsp;&nbsp; No<input type="radio" name="field_filter[0]" value="0" class="medium-text" CHECKED />
							</td>
						</tr>
						<tr>
							<td>
								<div>Filter Label (optional)</div>
								<input type="text" name="field_filter_label[0]" value="" class="medium-text" /></td>
							<td>
								<div>Filter using HAVING</div>
								Yes
								<input type="radio" name="field_group_related[0]" value="1" class="medium-text" />&nbsp;&nbsp; No<input type="radio" name="field_group_related[0]" value="0" class="medium-text" CHECKED />
							</td>
							<td>
								<div>Default Filter Value (optional)</div>
								<input type="text" name="field_filter_default[0]" value="" class="medium-text" /></td>
						</tr>
						<tr>
							<td>
								<div>Ongoing Date Field (optional)</div>
								<input type="text" name="field_filter_ongoing[0]" value="" class="medium-text" /></td>
							<td>
								<div>Ongoing Default Filter Value (optional)</div>
								<input type="text" name="field_filter_ongoing_default[0]" value="" class="medium-text" />
							</td>
							<td><!--<div>Total Field?</div> Yes <input type="radio" name="field_total_field[0]" value="1" class="medium-text" />&nbsp;&nbsp; No<input type="radio" name="field_total_field[0]" value="0" class="medium-text" CHECKED />--></td>
						</tr>
						<tr>
							<td>
								<div>Related Table (if related type)</div>
								<input type="text" name="field_related[0]" value="" class="medium-text" /></td>
							<td>
								<div>Related Field (if related type)</div>
								<input type="text" name="field_related_field[0]" value="" class="medium-text" /></td>
							<td>
								<div>Related WHERE/ORDER BY SQL (if related type)</div>
								<input type="text" name="field_related_sql[0]" value="" class="medium-text" /></td>
						</tr>
						<tr>
							<td>
								<div>Width</div>
								<input type="text" name="width[0]" value="" class="medium-text" /></td>
							<td>
								<div>Related ID Field (if related type)</div>
								<input type="text" name="field_related_id[0]" value="" class="medium-text" /></td>
							<td></td>
						</tr>
						</tbody>
					</table>
				</td>
				<td>[<a href="#" onclick="return field_remove_row(this);">remove</a>]</td>
			</tr>
			<?php
			$field_html = ob_get_clean();
			if ( is_array( $field_data ) && ! empty( $field_data ) ) {
				$count = 0;
				foreach ( $field_data as $field ) {
					$field = exports_reports_field_defaults( $field );
					?>
					<tr class="field_row">
						<td>
							<div class="dragme"></div>
						</td>
						<td>
							<table class="widefat">
								<tr>
									<td>
										<div>Field Name</div>
										<input type="text" name="field_name[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['name'] ); ?>" class="medium-text" />
									</td>
									<td>
										<div>Data Type</div>
										<select name="field_type[<?php echo esc_attr( $count ); ?>]">
											<?php
											foreach ( $field_types as $field_type => $field_label ) {
												?>
												<option value="<?php echo esc_attr( $field_type ); ?>"<?php selected( $field['type'], $field_type ); ?>><?php echo esc_html( $field_label ); ?></option>
												<?php
											}
											?>
										</select>
									</td>
									<td>
										<div>Label (optional)</div>
										<input type="text" name="field_label[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['label'] ); ?>" class="medium-text" />
									</td>
								</tr>
								<tbody id="field_show_<?php echo esc_attr( $count ); ?>" class="field_show">
								<tr>
									<td colspan="3">
										<a href="#advanced" onclick="jQuery('#field_show_<?php echo esc_attr( $count ); ?>').hide();jQuery('#field_advanced_<?php echo esc_attr( $count ); ?>').show();exports_reports_reset_alt();return false;">+&nbsp;&nbsp;Show Advanced Field Options</a>
									</td>
								</tr>
								</tbody>
								<tbody id="field_advanced_<?php echo esc_attr( $count ); ?>" class="field_advanced">
								<tr class="field_hide_link">
									<td colspan="3">
										<a href="#advanced" onclick="jQuery('#field_show_<?php echo esc_attr( $count ); ?>').show();jQuery('#field_advanced_<?php echo esc_attr( $count ); ?>').hide();exports_reports_reset_alt();return false;">-&nbsp;&nbsp;Hide Advanced Field Options</a>
									</td>
								</tr>
								<tr>
									<td>
										<div>Real Field (optional if using Alias)</div>
										<input type="text" name="field_real_name[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['real_name'] ); ?>" class="medium-text" />
									</td>
									<td>
										<div>Hide from Report</div>
										Yes
										<input type="radio" name="field_hide_report[<?php echo esc_attr( $count ); ?>]" value="1" class="medium-text"<?php echo( $field['hide_report'] == 1 ? ' CHECKED' : '' ); ?> />&nbsp;&nbsp; No<input type="radio" name="field_hide_report[<?php echo esc_attr( $count ); ?>]" value="0" class="medium-text"<?php echo( $field['hide_report'] !== 1 ? ' CHECKED' : '' ); ?> />
									</td>
									<td>
										<div>Searchable</div>
										Yes
										<input type="radio" name="field_search[<?php echo esc_attr( $count ); ?>]" value="0" class="medium-text"<?php echo( $field['search'] !== 1 ? ' CHECKED' : '' ); ?> />&nbsp;&nbsp; No<input type="radio" name="field_search[<?php echo esc_attr( $count ); ?>]" value="1" class="medium-text"<?php echo( $field['search'] == 1 ? ' CHECKED' : '' ); ?> />
									</td>
								</tr>
								<tr>
									<td>
										<div>Display Function (optional)</div>
										<input type="text" name="field_custom_display[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['custom_display'] ); ?>" class="medium-text" />
									</td>
									<td>
										<div>Hide from Export</div>
										Yes
										<input type="radio" name="field_hide_export[<?php echo esc_attr( $count ); ?>]" value="1" class="medium-text"<?php echo( $field['hide_export'] == 1 ? ' CHECKED' : '' ); ?> />&nbsp;&nbsp; No<input type="radio" name="field_hide_export[<?php echo esc_attr( $count ); ?>]" value="0" class="medium-text"<?php echo( $field['hide_export'] !== 1 ? ' CHECKED' : '' ); ?> />
									</td>
									<td>
										<div>Filterable (optional)</div>
										Yes
										<input type="radio" name="field_filter[<?php echo esc_attr( $count ); ?>]" value="1" class="medium-text"<?php echo( $field['filter'] == 1 ? ' CHECKED' : '' ); ?> />&nbsp;&nbsp; No<input type="radio" name="field_filter[<?php echo esc_attr( $count ); ?>]" value="0" class="medium-text"<?php echo( $field['filter'] !== 1 ? ' CHECKED' : '' ); ?> />
									</td>
								</tr>
								<tr>
									<td>
										<div>Filter Label (optional)</div>
										<input type="text" name="field_filter_label[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['filter_label'] ); ?>" class="medium-text" />
									</td>
									<td>
										<div>Filter using HAVING</div>
										Yes
										<input type="radio" name="field_group_related[<?php echo esc_attr( $count ); ?>]" value="1" class="medium-text"<?php echo( $field['group_related'] == 1 ? ' CHECKED' : '' ); ?> />&nbsp;&nbsp; No<input type="radio" name="field_group_related[<?php echo esc_attr( $count ); ?>]" value="0" class="medium-text"<?php echo( $field['group_related'] !== 1 ? ' CHECKED' : '' ); ?> />
									</td>
									<td>
										<div>Default Filter Value (optional)</div>
										<input type="text" name="field_filter_default[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['filter_default'] ); ?>" class="medium-text" />
									</td>
								</tr>
								<tr>
									<td>
										<div>Ongoing Date Field (optional)</div>
										<input type="text" name="field_filter_ongoing[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['filter_ongoing'] ); ?>" class="medium-text" />
									</td>
									<td>
										<div>Ongoing Default Filter Value (optional)</div>
										<input type="text" name="field_filter_ongoing_default[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['filter_ongoing_default'] ); ?>" class="medium-text" />
									</td>
									<td><!--<div>Total Field?</div> Yes <input type="radio" name="field_total_field[<?php echo esc_attr( $count ); ?>]" value="1" class="medium-text"<?php echo( $field['total_field'] == 1 ? ' CHECKED' : '' ); ?> />&nbsp;&nbsp; No<input type="radio" name="field_total_field[<?php echo esc_attr( $count ); ?>]" value="0" class="medium-text"<?php echo( $field['total_field'] !== 1 ? ' CHECKED' : '' ); ?> />--></td>
								</tr>
								<tr>
									<td>
										<div>Related Table (if related type)</div>
										<input type="text" name="field_related[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['related'] ); ?>" class="medium-text" />
									</td>
									<td>
										<div>Related Field (if related type)</div>
										<input type="text" name="field_related_field[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['related_field'] ); ?>" class="medium-text" />
									</td>
									<td>
										<div>Related WHERE/ORDER BY SQL (if related type)</div>
										<input type="text" name="field_related_sql[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['related_sql'] ); ?>" class="medium-text" />
									</td>
								</tr>
								<tr>
									<td>
										<div>Width</div>
										<input type="text" name="width[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['width'] ); ?>" class="medium-text" />
									</td>
									<td>
										<div>Related ID Field (if related type)</div>
										<input type="text" name="field_related_id[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $field['related_id'] ); ?>" class="medium-text" />
									</td>
									<td></td>
								</tr>
								</tbody>
							</table>
						</td>
						<td>[<a href="#" onclick="return field_remove_row(this);">remove</a>]</td>
					</tr>
					<?php
					$count ++;
				}
			} else {
				// phpcs:ignore
				echo $field_html;
			}
			$field_html = str_replace( [ '  ', "\n", "\r", "'" ], [ ' ', ' ', ' ', "\'" ], $field_html );
			?>
			</tbody>
		</table>
		<p><input type="button" class="button" value=" Add Field " onclick="field_add_row(1);" /></p>
	</div>
	<input type="hidden" name="<?php echo esc_attr( $column ); ?>" value="" />
	<script type="text/javascript">
		function field_remove_row( it ) {
			var conf = confirm( 'Are you sure you want to delete it?' );
			if ( conf ) {
				jQuery( it ).parent().parent().remove();
			}
			return false;
		}

		function field_add_row( append ) {
			var field_count = jQuery( '.field_data tbody.sortable tr.field_row' ).length + 1;
			var row = '<?php
				// phpcs:ignore
				echo str_replace( "_0\'", "_'+field_count+'\'", str_replace( '_0"', "_'+field_count+'\"", str_replace( '[0]', "['+field_count+']", $field_html ) ) );
				?>';
			if ( append == 1 ) {
				jQuery( '.field_data table#field_data tbody.sortable' ).append( row );
			}
			else {
				jQuery( '.field_data table#field_data tbody.sortable' ).prepend( row );
			}
		}

		function exports_reports_reset_alt() {
			jQuery( 'table.widefat tbody tr' ).removeClass( 'alternate' );
			jQuery( 'table.widefat tbody tr:even' ).addClass( 'alternate' );
			jQuery( 'table.widefat tbody tbody tr' ).removeClass( 'alternate' );
			jQuery( 'table.widefat tbody tbody tr:even' ).addClass( 'alternate' );
		}

		jQuery( function () {
			exports_reports_reset_alt();
			jQuery( ".sortable" ).sortable( {
				axis                 : "y",
				handle               : ".dragme",
				forcePlaceholderSize : true,
				forceHelperSize      : true,
				placeholder          : 'ui-state-highlight'
			} );
		} );
	</script>
	<?php
}

/**
 * @param $value
 * @param $column
 * @param $attributes
 * @param $obj
 *
 * @return string
 */
function exports_reports_report_field_save( $value, $column, $attributes, $obj ) {
	$value = [];

	$defaults = exports_reports_field_defaults();

	if ( isset( $_POST['field_name'] ) ) {
		foreach ( $_POST['field_name'] as $key => $field ) {
			if ( empty( $field ) ) {
				continue;
			}

			foreach ( $defaults as $default ) {
				if ( ! isset( $_POST[ $default ] ) ) {
					$_POST[ $default ] = [];
				}

				if ( ! isset( $_POST[ $default ][ $key ] ) ) {
					$_POST[ $default ][ $key ] = '';
				}
			}

			$value[] = [
				'name'                   => $field,
				'real_name'              => $_POST['field_real_name'][ $key ],
				'label'                  => $_POST['field_label'][ $key ],
				'filter_label'           => $_POST['field_filter_label'][ $key ],
				'hide_report'            => absint( $_POST['field_hide_report'][ $key ] ),
				'hide_export'            => absint( $_POST['field_hide_export'][ $key ] ),
				'group_related'          => $_POST['field_group_related'][ $key ],
				'custom_display'         => $_POST['field_custom_display'][ $key ],
				'type'                   => $_POST['field_type'][ $key ],
				'width'                  => $_POST['width'][ $key ],
				'search'                 => absint( $_POST['field_search'][ $key ] ),
				'filter'                 => absint( $_POST['field_filter'][ $key ] ),
				'filter_default'         => $_POST['field_filter_default'][ $key ],
				'filter_ongoing'         => $_POST['field_filter_ongoing'][ $key ],
				'filter_ongoing_default' => $_POST['field_filter_ongoing_default'][ $key ],
				'total_field'            => '',
				// 'total_field'            => absint( $_POST['field_total_field'][ $key ] ),
				'related'                => $_POST['field_related'][ $key ],
				'related_field'          => $_POST['field_related_field'][ $key ],
				'related_sql'            => $_POST['field_related_sql'][ $key ],
				'related_id'             => $_POST['field_related_id'][ $key ],
			];
		}
	}

	return wp_json_encode( $value );
}

/**
 * @param null $field
 *
 * @return array|null
 */
function exports_reports_field_defaults( $field = null ) {
	if ( ! is_array( $field ) ) {
		$field = [];
	}

	$defaults = [
		'name'                   => '',
		'real_name'              => '',
		'label'                  => '',
		'filter_label'           => '',
		'hide_report'            => '',
		'hide_export'            => '',
		'group_related'          => '',
		'custom_display'         => '',
		'type'                   => '',
		'width'                  => '',
		'search'                 => '',
		'filter'                 => '',
		'filter_default'         => '',
		'filter_ongoing'         => '',
		'filter_ongoing_default' => '',
		'total_field'            => '',
		'related'                => '',
		'related_field'          => '',
		'related_sql'            => '',
		'related_id'             => '',
	];

	$field = array_merge( $defaults, $field );

	return $field;
}

/**
 * @param bool      $has_full_access
 * @param bool|null $group_id
 *
 * @return bool
 */
function exports_reports_view( $group_id = false, $has_full_access = null ) {
	if ( empty( $_GET['page'] ) ) {
		return false;
	}

	/** @var wpdb $wpdb */ global $wpdb;

	wp_enqueue_script( 'exports-reports-admin' );

	if ( null === $has_full_access ) {
		$has_full_access = exports_reports_current_user_can_any( 'exports_reports_full_access' );

		if ( is_super_admin() || ( ! $has_full_access && exports_reports_has_role( 'administrator' ) ) ) {
			$has_full_access = true;
		}
	}

	if ( 'exports-reports' !== $_GET['page'] && false !== strpos( $_GET['page'], 'exports-reports-group-' ) ) {
		if ( empty( $group_id ) ) {
			$group_id = (int) str_replace( 'exports-reports-group-', '', sanitize_text_field( $_GET['page'] ) );
		}

		$sql = '
			SELECT `id`, `role_access`
			FROM `' . $wpdb->prefix . EXPORTS_REPORTS_TBL . 'groups`
			WHERE `disabled` = 0 AND `id` = %d
			ORDER BY `weight`, `name`
			LIMIT 1
		';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare( $sql, [ $group_id ] );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$group = $wpdb->get_row( $sql );

		if ( empty( $group ) ) {
			return false;
		}
		$group_roles = explode( ',', $group->role_access );
	} else {
		$group_id    = 0;
		$group_roles = [];

		$sql = '
			SELECT `id`, `role_access`
			FROM `' . $wpdb->prefix . EXPORTS_REPORTS_TBL . 'groups`
			WHERE `disabled` = 0
			ORDER BY `weight`, `name`
		';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$groups = $wpdb->get_results( $sql );

		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group ) {
				$sql = '
					SELECT `id`, `role_access`
					FROM `' . $wpdb->prefix . EXPORTS_REPORTS_TBL . 'reports`
					WHERE `disabled` = 0 AND `group` = %d
					ORDER BY `weight`, `name`
				';

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql = $wpdb->prepare( $sql, [ $group->id ] );

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$reports = $wpdb->get_results( $sql );

				if ( 0 < @count( $reports ) ) {
					foreach ( $reports as $report ) {
						if ( $has_full_access || exports_reports_current_user_can_any( 'exports_reports_view' ) || exports_reports_current_user_can_any( 'exports_reports_view_group_' . $group->id ) || exports_reports_current_user_can_any( 'exports_reports_view_report_' . $report->id ) ) {
							$group_id    = $group->id;
							$group_roles = explode( ',', $group->role_access );

							break;
						}

						$roles = array_unique( array_merge( explode( ',', $group->role_access ), explode( ',', $report->role_access ) ) );

						if ( empty( $roles ) ) {
							continue;
						}

						foreach ( $roles as $role ) {
							if ( exports_reports_has_role( $role ) ) {
								$group_id    = $group->id;
								$group_roles = explode( ',', $group->role_access );

								break 2;
							}
						}
					}
				}
			}
		}
	}

	if ( empty( $group_id ) ) {
		return false;
	}

	$sql = '
		SELECT *
		FROM `' . $wpdb->prefix . EXPORTS_REPORTS_TBL . 'reports`
		WHERE `disabled` = 0 AND `group` = %d
		ORDER BY `weight`, `name`
	';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$sql = $wpdb->prepare( $sql, [ $group_id ] );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$reports = $wpdb->get_results( $sql );

	if ( empty( $reports ) ) {
		return false;
	}

	$selectable_reports = [];
	$current_report     = false;

	foreach ( $reports as $report ) {
		if ( $has_full_access || exports_reports_current_user_can_any( 'exports_reports_view' ) || exports_reports_current_user_can_any( 'exports_reports_view_group_' . $group_id ) || exports_reports_current_user_can_any( 'exports_reports_view_report_' . $report->id ) ) {
			if ( false === $current_report ) {
				$current_report = absint( $report->id );
			}

			$selectable_reports[ $report->id ] = [
				'name'             => $report->name,
				'sql_query'        => $report->sql_query,
				'sql_query_count'  => $report->sql_query_count,
				'default_none'     => $report->default_none,
				'export'           => ( 0 === (int) $report->disable_export ? true : false ),
				'field_data'       => $report->field_data,
				'page_orientation' => $report->page_orientation,
			];

			continue;
		}

		$roles = array_unique( array_merge( $group_roles, explode( ',', $report->role_access ) ) );

		if ( empty( $roles ) ) {
			continue;
		}

		foreach ( $roles as $role ) {
			if ( exports_reports_has_role( $role ) ) {
				if ( false === $current_report ) {
					$current_report = absint( $report->id );
				}

				$selectable_reports[ $report->id ] = [
					'name'             => $report->name,
					'sql_query'        => $report->sql_query,
					'sql_query_count'  => $report->sql_query_count,
					'default_none'     => $report->default_none,
					'export'           => ( 0 === (int) $report->disable_export ? true : false ),
					'field_data'       => $report->field_data,
					'page_orientation' => $report->page_orientation,
				];
			}
		}
	}

	if ( empty( $selectable_reports ) ) {
		return false;
	}

	if ( isset( $_GET['report'] ) && isset( $selectable_reports[ $_GET['report'] ] ) ) {
		$current_report = absint( $_GET['report'] );
	}

	require_once EXPORTS_REPORTS_DIR . 'wp-admin-ui/class-exports-reports-admin-ui.php';

	$options = [];

	$options['css']      = EXPORTS_REPORTS_URL . 'assets/admin.css';
	$options['readonly'] = true;

	// $options['identifier']   = true;

	$options['export']       = $selectable_reports[ $current_report ]['export'];
	$options['search']       = ( strlen( $selectable_reports[ $current_report ]['field_data'] ) > 0 ? true : false );
	$options['default_none'] = ( 1 === (int) $selectable_reports[ $current_report ]['default_none'] ? true : false );
	$options['sql']          = trim( $selectable_reports[ $current_report ]['sql_query'] );
	$options['sql_count']    = trim( $selectable_reports[ $current_report ]['sql_query_count'] );

	if ( empty( $options['sql_count'] ) ) {
		unset( $options['sql_count'] );
	}

	$options['page_orientation'] = $selectable_reports[ $current_report ]['page_orientation'];
	$options['item']             = 'item';
	$options['items']            = $selectable_reports[ $current_report ]['name'];
	$options['icon']             = EXPORTS_REPORTS_URL . 'assets/icons/32.png';
	$options['heading']          = [
		'manage' => 'View Report:',
	];

	$field_data = @json_decode( $selectable_reports[ $current_report ]['field_data'], true );

	if ( is_array( $field_data ) && ! empty( $field_data ) ) {
		$options['columns'] = [];

		foreach ( $field_data as $field ) {
			$field = exports_reports_field_defaults( $field );

			$options['columns'][ $field['name'] ] = [];

			if ( 0 < strlen( $field['real_name'] ) ) {
				$options['columns'][ $field['name'] ]['real_name'] = $field['real_name'];
			}

			if ( 0 < strlen( $field['label'] ) ) {
				$options['columns'][ $field['name'] ]['label'] = $field['label'];
			}

			if ( 0 < strlen( $field['filter_label'] ) ) {
				$options['columns'][ $field['name'] ]['filter_label'] = $field['filter_label'];
			}

			if ( 0 < strlen( $field['custom_display'] ) ) {
				$options['columns'][ $field['name'] ]['custom_display'] = $field['custom_display'];
			}

			if ( 0 < strlen( $field['type'] ) ) {
				$options['columns'][ $field['name'] ]['type'] = $field['type'];
			}

			if ( 0 < strlen( $field['width'] ) ) {
				$options['columns'][ $field['name'] ]['width'] = $field['width'];
			}

			if ( 1 === (int) $field['hide_report'] ) {
				$options['columns'][ $field['name'] ]['display'] = false;
			}

			if ( 1 === (int) $field['hide_export'] ) {
				$options['columns'][ $field['name'] ]['export'] = false;
			}

			if ( 1 === (int) $field['search'] || 1 === (int) $field['hide_report'] ) {
				$options['columns'][ $field['name'] ]['search'] = false;
			} else {
				$options['columns'][ $field['name'] ]['search'] = true;
			}

			if ( 1 === (int) $field['filter'] ) {
				$options['columns'][ $field['name'] ]['filter'] = true;

				if ( 0 < strlen( $field['filter_default'] ) ) {
					$options['columns'][ $field['name'] ]['filter_default'] = $field['filter_default'];
				}

				if ( 0 < strlen( $field['filter_ongoing'] ) ) {
					$options['columns'][ $field['name'] ]['date_ongoing'] = $field['filter_ongoing'];

					if ( 0 < strlen( $field['filter_ongoing_default'] ) ) {
						$options['columns'][ $field['name'] ]['filter_ongoing_default'] = $field['filter_ongoing_default'];
					}
				}
			}

			if ( 1 === (int) $field['total_field'] ) {
				$options['columns'][ $field['name'] ]['total_field'] = true;
			}

			if ( 1 === (int) $field['group_related'] ) {
				$options['columns'][ $field['name'] ]['group_related'] = true;
			}

			if ( 'related' === $field['type'] ) {
				if ( 0 < strlen( $field['related'] ) ) {
					$options['columns'][ $field['name'] ]['related'] = $field['related'];
				}

				if ( 0 < strlen( $field['related_field'] ) ) {
					$options['columns'][ $field['name'] ]['related_field'] = $field['related_field'];
				}

				if ( 0 < strlen( $field['related_id'] ) ) {
					$options['columns'][ $field['name'] ]['related_id'] = $field['related_id'];
				}

				if ( 0 < strlen( $field['related_sql'] ) ) {
					$options['columns'][ $field['name'] ]['related_sql'] = $field['related_sql'];
				}
			}
		}
	}

	$options['report_id'] = $current_report;

	$options = apply_filters( 'exports_reports_report_options', $options, $current_report );

	$admin = new Exports_Reports_Admin_UI( $options );

	if ( 1 < count( $selectable_reports ) ) {
		?>
		<div style="background-color:#E7E7E7;border:1px solid #D7D7D7; padding:5px 15px;margin:15px 15px 0px 5px;">
			<strong style="padding-right:10px;">Exports and Reports:</strong>
			<label for="report" style="vertical-align:baseline;">Choose Report</label>
			<select id="report" onchange="document.location=this.value;">
				<?php
				foreach ( $selectable_reports as $report_id => $report ) {
					?>
					<option value="
					<?php
					echo esc_attr( $admin->var_update( [
							'page'   => sanitize_text_field( $_GET['page'] ),
							'report' => absint( $report_id ),
						], false, false, true ) );
					?>
					"<?php selected( $current_report, $report_id ); ?>><?php echo esc_html( $report['name'] ); ?></option>
					<?php
				}
				?>
			</select>
		</div>
		<?php
	}

	$admin->go();

	return true;
}

add_action( 'wp_admin_ui_post_export', 'exports_reports_log', 10, 2 );
add_action( 'wp_admin_ui_post_remove_export', 'exports_reports_delete_log', 10, 2 );

/**
 * @param $args
 * @param $obj
 */
function exports_reports_log( $args, $obj ) {
	/** @var wpdb $wpdb */ global $wpdb;

	$filename = $args[1];

	$wpdb->insert( $wpdb->prefix . EXPORTS_REPORTS_TBL . 'log', [
			'report_id' => $obj[0]->report_id,
			'filename'  => $filename,
			'created'   => date_i18n( 'Y-m-d H:i:s' ),
	], [
		'%d',
		'%s',
		'%s',
	] );
}

/**
 * @param $args
 * @param $obj
 */
function exports_reports_delete_log( $args, $obj ) {
	/** @var wpdb $wpdb */ global $wpdb;

	$filename = $args[1];

	if ( false !== $args[2] ) {
		$sql = '
			DELETE FROM `' . $wpdb->prefix . EXPORTS_REPORTS_TBL . 'log`
			WHERE `report_id` = %d AND `filename` = %s
		';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare( $sql, [
			$obj[0]->report_id,
			$filename,
		] );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $sql );
	}
}

/**
 * @return mixed
 */
function exports_reports_schedule_cleanup() {
	$schedules = _get_cron_array();
	$timestamp = false;

	$key = md5( serialize( [] ) );

	foreach ( $schedules as $ts => $schedule ) {
		if ( isset( $schedule['exports_reports_cleanup'] ) && isset( $schedule['exports_reports_cleanup'][ $key ] ) ) {
			$timestamp = $ts;
			break;
		}
	}

	if ( false !== $timestamp ) {
		wp_unschedule_event( $timestamp, 'exports_reports_cleanup', [] );
	}

	$timestamp  = time();
	$recurrence = 'daily';

	return wp_schedule_event( $timestamp, $recurrence, 'exports_reports_cleanup', [] );
}

/**
 * Delete all files in a directory
 *
 * @param string|null $directory
 */
function exports_reports_delete_dir_files( $directory ) {
	require_once( ABSPATH . 'wp-admin/includes/file.php' );

	/**
	 * @var $wp_filesystem WP_Filesystem_Base
	 */ global $wp_filesystem;

	WP_Filesystem();

	if ( ! $wp_filesystem ) {
		return;
	}

	if ( ! $wp_filesystem->is_dir( $directory ) ) {
		return;
	}

	$file_list = $wp_filesystem->dirlist( $directory, false );

	foreach ( $file_list as $file ) {
		$file_path = $directory . DIRECTORY_SEPARATOR . $file['name'];

		// d = folder, f = file
		if ( 'd' === $file['type'] ) {
			// Delete folder
			$wp_filesystem->rmdir( $file_path, true );
		} else {
			// Delete file
			$wp_filesystem->delete( $file_path );
		}
	}

	if ( WP_ADMIN_UI_EXPORT_DIR !== $directory ) {
		$wp_filesystem->rmdir( $directory, true );
	}
}

/**
 * @param bool $full
 *
 * @return bool
 */
function exports_reports_cleanup( $full = false ) {
	/** @var wpdb $wpdb */ global $wpdb;

	if ( $full ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( 'TRUNCATE ' . $wpdb->prefix . EXPORTS_REPORTS_TBL . 'log' );

		exports_reports_delete_dir_files( WP_ADMIN_UI_EXPORT_DIR );
	} else {
		$purge_age = 1; // day(s) in age to purge

		$sql = '
			SELECT *
			FROM `' . $wpdb->prefix . EXPORTS_REPORTS_TBL . 'log`
			WHERE `created` < DATE_ADD( NOW(), INTERVAL -%d DAY )
		';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare( $sql, [ $purge_age ] );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$cleanup = $wpdb->get_results( $sql );

		if ( false !== $cleanup && ! empty( $cleanup ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );

			/**
			 * @var $wp_filesystem WP_Filesystem_Base
			 */ global $wp_filesystem;

			WP_Filesystem();

			foreach ( $cleanup as $export ) {
				if ( $wp_filesystem ) {
					$file = WP_ADMIN_UI_EXPORT_DIR . '/' . str_replace( [ '/', '..' ], '', $export->filename );

					$wp_filesystem->delete( $file );
				}

				$sql = '
					DELETE FROM `' . $wpdb->prefix . EXPORTS_REPORTS_TBL . 'log`
					WHERE `id` = %d
				';

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql = $wpdb->prepare( $sql, [ $export->id ] );

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query( $sql );
			}

			return true;
		}
	}

	return false;
}

/**
 * @param $caps
 *
 * @return array
 */
function exports_reports_get_capabilities( $caps ) {
	$caps = array_merge( $caps, exports_reports_capabilities() );

	return $caps;
}

/**
 * @return array
 */
function exports_reports_capabilities() {
	$caps = [
		'exports_reports_full_access',
		'exports_reports_settings',
		'exports_reports_view',
	];

	$caps = apply_filters( 'export_reports_capabilites', $caps );

	return $caps;
}

/**
 * @param $caps
 *
 * @return bool
 */
function exports_reports_current_user_can_any( $caps ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	wp_get_current_user();

	if ( ! is_array( $caps ) ) {
		return current_user_can( $caps );
	}

	foreach ( $caps as $cap ) {
		if ( current_user_can( $cap ) ) {
			return true;
		}
	}

	return current_user_can( 'exports_reports_full_access' );
}

/**
 * @param $caps
 *
 * @return bool
 */
function exports_reports_current_user_can_which( $caps ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	wp_get_current_user();

	foreach ( $caps as $cap ) {
		if ( current_user_can( $cap ) ) {
			return $cap;
		}
	}

	return false;
}

/**
 * @return mixed
 */
function exports_reports_get_roles() {
	/** @var WP_Roles $wp_roles */ global $wp_roles;

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	return $wp_roles->get_names();
}

/**
 * @param $role
 *
 * @return bool
 */
function exports_reports_has_role( $role ) {
	global $current_user;

	if ( ! is_user_logged_in() ) {
		return false;
	}

	wp_get_current_user();

	if ( null !== get_role( $role ) && in_array( $role, $current_user->roles ) ) {
		return true;
	}

	return false;
}

/**
 * Filter the robots.txt contents and add the /exports/ directory to the list of disallowed folders.
 *
 * @since 0.9.0
 *
 * @param string $robots_txt The robots.txt contents.
 *
 * @return string The robots.txt contents.
 */
function exports_reports_robots_txt( $robots_txt ) {
	$exclude_path = str_replace( ABSPATH, '', WP_ADMIN_UI_EXPORT_DIR );
	$exclude_path = str_replace( DIRECTORY_SEPARATOR, '/', $exclude_path );
	$exclude_path = trim( $exclude_path, '/' );

	return $robots_txt . "\n" . 'Disallow: */' . $exclude_path . '/*';
}

add_filter( 'robots_txt', 'exports_reports_robots_txt' );
