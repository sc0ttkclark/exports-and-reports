<?php
/**
 * Capabilities and roles: who may see and manage reports.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gives the administrator role the plugin's capabilities: on activation, on the upgrade to 1.0 and on a
 * site the activation did not reach, each site of a network on its own. The role keeps them from then
 * on, so a site may take them away again (with a roles plugin such as Members) and the plugin will not
 * put them back until it is activated again. The current user's capabilities are refreshed so the
 * menus show on the same request.
 *
 * @since 1.0.0 Replaces the user_has_cap filter that granted administrators full access on every request.
 */
function exports_reports_add_role_capabilities() {
	$role = get_role( 'administrator' );

	if ( ! $role ) {
		return;
	}

	foreach ( exports_reports_capabilities() as $cap ) {
		if ( empty( $role->capabilities[ $cap ] ) ) {
			$role->add_cap( $cap );
		}
	}

	if ( is_user_logged_in() ) {
		wp_get_current_user()->get_role_caps();
	}
}

/**
 * Adds the plugin's capabilities to the list the Members plugin manages (members_get_capabilities).
 *
 * @param string[] $caps The capabilities Members knows.
 *
 * @return string[]
 */
function exports_reports_get_capabilities( $caps ) {
	$caps = array_merge( $caps, exports_reports_capabilities() );

	return $caps;
}

/**
 * The capabilities the plugin registers, filtered.
 *
 * @return string[]
 */
function exports_reports_capabilities() {
	$caps = array(
		'exports_reports_full_access',
		'exports_reports_settings',
		'exports_reports_view',
	);

	/**
	 * Filters the capabilities the plugin registers.
	 *
	 * @since 0.9.5 Replaces export_reports_capabilites, which still runs first.
	 *
	 * @param string[] $caps Capability names.
	 */
	$caps = apply_filters( 'exports_reports_capabilities', apply_filters_deprecated( 'export_reports_capabilites', array( $caps ), '0.9.5', 'exports_reports_capabilities' ) );

	return $caps;
}

/**
 * Whether the current user may manage the groups, the reports and the settings: full access (which a
 * network's super admins have) or the settings capability, the capabilities the Reports Admin menu asks for.
 *
 * @since 1.2.0
 *
 * @return bool
 */
function exports_reports_current_user_can_manage() {
	return is_super_admin() || exports_reports_current_user_can_any( array( 'exports_reports_full_access', 'exports_reports_settings' ) );
}

/**
 * Whether the current user has any of the capabilities, or full access.
 *
 * @param string|string[] $caps One capability or several.
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
 * The first of the capabilities the current user has.
 *
 * @param string[] $caps The capabilities, in order of preference.
 *
 * @return string|false The capability, or false when the user has none of them.
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
 * The site's roles.
 *
 * @return string[] Role names by slug.
 */
function exports_reports_get_roles() {
	return wp_roles()->get_names();
}

/**
 * Whether the current user has a role.
 *
 * @param string $role The role slug.
 *
 * @return bool
 */
function exports_reports_has_role( $role ) {
	global $current_user;

	if ( ! is_user_logged_in() ) {
		return false;
	}

	wp_get_current_user();

	if ( null !== get_role( $role ) && in_array( $role, $current_user->roles, true ) ) {
		return true;
	}

	return false;
}

/**
 * Whether the current user may view a report: the rule the Reports menu and the report page apply.
 *
 * @since 0.9.5
 *
 * @param object $report A row of the reports table.
 * @param object $group  The row of its group.
 * @return bool
 */
function exports_reports_user_can_view_report( $report, $group ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$has_full_access = is_super_admin() || exports_reports_current_user_can_any( 'exports_reports_full_access' );

	if ( $has_full_access || exports_reports_current_user_can_any( 'exports_reports_view' ) || exports_reports_current_user_can_any( 'exports_reports_view_group_' . $group->id ) || exports_reports_current_user_can_any( 'exports_reports_view_report_' . $report->id ) ) {
		return true;
	}

	$roles = array_unique( array_merge( explode( ',', (string) $group->role_access ), explode( ',', (string) $report->role_access ) ) );

	foreach ( $roles as $role ) {
		if ( '' !== $role && exports_reports_has_role( $role ) ) {
			return true;
		}
	}

	return false;
}
