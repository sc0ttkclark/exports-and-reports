<?php
/**
 * The Manage Groups screen.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Manage Groups page: an Exports_Reports_Admin_UI on the groups table.
 */
function exports_reports_groups() {
	global $wpdb;

	require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-admin-ui.php';

	$columns = array(
		'name',
		'disabled' => array(
			'label' => __( 'Disabled', 'exports-and-reports' ),
			'type'  => 'bool',
		),
		'created'  => array(
			'label' => __( 'Date Created', 'exports-and-reports' ),
			'type'  => 'datetime',
		),
		'updated'  => array(
			'label' => __( 'Last Modified', 'exports-and-reports' ),
			'type'  => 'datetime',
		),
		'id'       => array(
			'label' => __( 'Group ID', 'exports-and-reports' ),
			'type'  => 'number',
		),
	);

	$form_columns = $columns;

	unset( $form_columns['id'] );

	$roles = exports_reports_get_roles();

	$form_columns['role_access'] = array(
		'label'            => __( 'WP Roles with Access', 'exports-and-reports' ),
		'comments'         => __( 'Add the exports_reports_full_access capability to a role for full access to reports, exports_reports_settings for only access to settings, exports_reports_view for access to view all reports, exports_reports_view_group_{ID} for access to view a group and all of the reports within, or exports_reports_view_report_{ID} for access to view a single report', 'exports-and-reports' ),
		'type'             => 'related',
		'related'          => $roles,
		'related_multiple' => true,
	);

	$form_columns['created']['date_touch_on_create'] = true;
	$form_columns['created']['display']              = false;
	$form_columns['updated']['date_touch']           = true;
	$form_columns['updated']['display']              = false;

	$admin_ui = array(
		'reorder'       => 'weight',
		'reorder_order' => '`weight`, `name`',
		'order'         => '`weight`, `name`', // The Reports menu's order: by weight, ties by name.
		'order_dir'     => 'ASC',
		'css'           => EXPORTS_REPORTS_URL . 'assets/css/admin.css',
		'item'          => __( 'Report Group', 'exports-and-reports' ),
		'items'         => __( 'Report Groups', 'exports-and-reports' ),
		'table'         => $wpdb->exports_reports_groups,
		'columns'       => $columns,
		'form_columns'  => $form_columns,
		'duplicate'     => true,
	);

	$admin = new Exports_Reports_Admin_UI( $admin_ui );

	$admin->go();
}
