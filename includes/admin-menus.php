<?php
/**
 * The admin menus, and the admin pages' scripts and styles.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- the plugin runs the administrator's report queries and keeps its own tables; there is nothing to cache.

/**
 * The Reports Admin menu: Manage Groups, Manage Reports and Settings, for users with the settings
 * capability or full access (super admins count as full access).
 */
function exports_reports_admin_menu() {
	if ( defined( 'EXPORTS_REPORTS_DISABLE_MENU' ) ) {
		return;
	}

	$has_full_access = is_super_admin() || exports_reports_current_user_can_any( 'exports_reports_full_access' );

	$min_cap = exports_reports_current_user_can_which( exports_reports_capabilities() );

	if ( empty( $min_cap ) ) {
		$min_cap = 'exports_reports_full_access';
	}

	if ( $has_full_access || exports_reports_current_user_can_any( $min_cap ) || exports_reports_current_user_can_any( 'exports_reports_settings' ) ) {
		add_menu_page( __( 'Reports Admin', 'exports-and-reports' ), __( 'Reports Admin', 'exports-and-reports' ), $has_full_access ? 'read' : $min_cap, 'exports-reports-admin', '', 'dashicons-media-spreadsheet' );
		add_submenu_page( 'exports-reports-admin', __( 'Manage Groups', 'exports-and-reports' ), __( 'Manage Groups', 'exports-and-reports' ), $has_full_access ? 'read' : 'exports_reports_settings', 'exports-reports-admin', 'exports_reports_groups' );
		add_submenu_page( 'exports-reports-admin', __( 'Manage Reports', 'exports-and-reports' ), __( 'Manage Reports', 'exports-and-reports' ), $has_full_access ? 'read' : 'exports_reports_settings', 'exports-reports-admin-reports', 'exports_reports_reports' );
		add_submenu_page( 'exports-reports-admin', __( 'Settings', 'exports-and-reports' ), __( 'Settings', 'exports-and-reports' ), $has_full_access ? 'read' : 'exports_reports_settings', 'exports-reports-admin-settings', 'exports_reports_settings' );
	}
}

/**
 * The Reports menu: the enabled groups the current user may view, by role access or full access.
 */
function exports_reports_menu() {
	/** @var wpdb $wpdb */ global $wpdb;

	if ( defined( 'EXPORTS_REPORTS_DISABLE_MENU' ) ) {
		return;
	}

	$has_full_access = is_super_admin() || exports_reports_current_user_can_any( 'exports_reports_full_access' );

	$init = false;

	$groups = $wpdb->get_results( "SELECT `id`, `role_access`, `name` FROM {$wpdb->exports_reports_groups} WHERE `disabled` = 0 ORDER BY `weight`, `name`" );

	if ( ! empty( $groups ) ) {
		foreach ( $groups as $group ) {
			$reports = $wpdb->get_results( $wpdb->prepare( "SELECT `id`, `role_access` FROM {$wpdb->exports_reports_reports} WHERE `disabled` = 0 AND `group` = %d ORDER BY `weight`, `name`", $group->id ) );

			if ( ! empty( $reports ) ) {
				foreach ( $reports as $report ) {
					if ( $has_full_access || exports_reports_current_user_can_any( 'exports_reports_view' ) || exports_reports_current_user_can_any( 'exports_reports_view_group_' . $group->id ) || exports_reports_current_user_can_any( 'exports_reports_view_report_' . $report->id ) ) {
						$menu_page = 'exports-reports-group-' . $group->id;

						if ( ! $init ) {
							add_menu_page( __( 'Reports', 'exports-and-reports' ), __( 'Reports', 'exports-and-reports' ), 'read', $menu_page, '', 'dashicons-chart-bar' );

							$init = $menu_page;
						}

						add_submenu_page( $init, $group->name, $group->name, 'read', $menu_page, 'exports_reports_view' );

						break;
					}

					$roles = array_filter( array_unique( array_merge( explode( ',', $group->role_access ), explode( ',', $report->role_access ) ) ) );

					if ( empty( $roles ) ) {
						continue;
					}

					foreach ( $roles as $role ) {
						if ( exports_reports_has_role( $role ) ) {
							$menu_page = 'exports-reports-group-' . $group->id;

							if ( ! $init ) {
								add_menu_page( __( 'Reports', 'exports-and-reports' ), __( 'Reports', 'exports-and-reports' ), 'read', $menu_page, '', 'dashicons-chart-bar' );

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
 * Whether the admin page being built is one of the plugin's: the administration screens or a report page.
 *
 * @since 1.1.4
 *
 * @return bool
 */
function exports_reports_is_plugin_page() {
	/** @var string|null $plugin_page The admin page slug, as the admin sets it. */ global $plugin_page;

	return isset( $plugin_page ) && 0 === strpos( (string) $plugin_page, 'exports-reports' );
}

/**
 * Registers the admin script and style; enqueues the style, with the Dashicons it uses, on every page of the
 * plugin, so that it is in the head and the screens render styled from the first byte; and enqueues the report
 * form's editor assets on the Manage Reports page.
 *
 * @since 1.1.4 The style on every page of the plugin, in the head.
 *
 * @param string $hook_suffix The admin page, as admin_enqueue_scripts passes it.
 */
function exports_reports_assets( $hook_suffix ) {
	wp_register_script( 'exports-reports-admin', EXPORTS_REPORTS_URL . 'assets/js/admin.js', array( 'jquery' ), EXPORTS_REPORTS_VERSION, true );
	wp_register_style( 'exports-reports-admin', EXPORTS_REPORTS_URL . 'assets/css/admin.css', array(), EXPORTS_REPORTS_VERSION );

	if ( exports_reports_is_plugin_page() ) {
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'exports-reports-admin' );
	}

	if ( get_plugin_page_hookname( 'exports-reports-admin-reports', 'exports-reports-admin' ) === $hook_suffix ) {
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'exports-reports-report-fields', EXPORTS_REPORTS_URL . 'assets/js/report-fields.js', array( 'jquery', 'jquery-ui-sortable' ), EXPORTS_REPORTS_VERSION, true );
		exports_reports_sql_editor();
	}
}

/**
 * CodeMirror with SQL highlighting on the report form's query fields.
 *
 * Nothing is enqueued when the user has turned syntax highlighting off in their profile.
 */
function exports_reports_sql_editor() {
	$settings = wp_enqueue_code_editor( array( 'type' => 'text/x-sql' ) );

	if ( false === $settings ) {
		return;
	}

	wp_add_inline_script(
		'code-editor',
		sprintf(
			// The editor's instance stays on the field, where the field editor's Detect Fields button reads the query.
			'jQuery( function() { [ "admin_ui_sql_query", "admin_ui_sql_query_count" ].forEach( function( id ) { var field = document.getElementById( id ); if ( field ) { field.exportsReportsCodeMirror = wp.codeEditor.initialize( field, %s ).codemirror; } } ); } );',
			wp_json_encode( $settings )
		)
	);
}

/**
 * On a report page, sends what WordPress has written of the admin frame as soon as it is complete, so the
 * frame shows while the report's query runs; the page prints its own note for the wait after this. The
 * report pages send no headers after this point (nothing on them redirects).
 *
 * @since 1.1.4
 */
function exports_reports_flush_admin_header() {
	/** @var string|null $plugin_page The admin page slug, as the admin sets it. */ global $plugin_page;

	$page = isset( $plugin_page ) ? (string) $plugin_page : '';

	if ( 'exports-reports' !== $page && 0 !== strpos( $page, 'exports-reports-group-' ) ) {
		return;
	}

	if ( 0 < ob_get_level() ) {
		ob_flush();
	}

	flush();
}
