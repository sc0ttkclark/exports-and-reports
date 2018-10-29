<?php

namespace ExportAndReports;


class ExportReportsAdmin
{

    function run(){
        add_action('admin_menu',['ExportReportsAdmin','register_menu']);
        add_action('admin_menu',['ExportReportsAdmin','register_admin_menu']);
    }

    /**
     *
     */
    public function register_admin_menu()
    {

        if (defined('EXPORTS_REPORTS_DISABLE_MENU')) {
            return;
        }

        $has_full_access = ExportReports::$acl->current_user_can_any('exports_reports_full_access');

        if (is_super_admin() || (!$has_full_access && ExportReports::$acl->has_role('administrator'))) {
            $has_full_access = true;
        }

        $min_cap = ExportReports::$acl->current_user_can_which();

        if (empty($min_cap)) {
            $min_cap = 'exports_reports_full_access';
        }

        if ($has_full_access || ExportReports::$acl->current_user_can_any($min_cap) || ExportReports::$acl->current_user_can_any('exports_reports_settings')) {
            add_menu_page('Reports Admin', 'Reports Admin', $has_full_access ? 'read' : $min_cap, 'exports-reports-admin', null, EXPORTS_REPORTS_URL . 'assets/icons/16.png');
            add_submenu_page('exports-reports-admin', 'Manage Groups', 'Manage Groups', $has_full_access ? 'read' : 'exports_reports_settings', 'exports-reports-admin', 'exports_reports_groups');
            add_submenu_page('exports-reports-admin', 'Manage Reports', 'Manage Reports', $has_full_access ? 'read' : 'exports_reports_settings', 'exports-reports-admin-reports', 'exports_reports_reports');
            add_submenu_page('exports-reports-admin', 'Settings', 'Settings', $has_full_access ? 'read' : 'exports_reports_settings', 'exports-reports-admin-settings', 'exports_reports_settings');
        }

    }
/**
 *
 */
    public function register_menu()
    {

        global $wpdb;

        if (defined('EXPORTS_REPORTS_DISABLE_MENU')) {
            return;
        }

        $has_full_access = ExportReports::$acl->current_user_can_any('exports_reports_full_access');

        if (is_super_admin() || (!$has_full_access && ExportReports::$acl->has_role('administrator'))) {
            $has_full_access = true;
        }

        $init = false;

        $sql = '
		SELECT `id`, `name`
		FROM `' . EXPORTS_REPORTS_TBL . 'groups`
		WHERE `disabled` = 0
		ORDER BY `weight`, `name`
	';

        $groups = $wpdb->get_results($sql);

        if (!empty($groups)) {
            foreach ($groups as $group) {
                $sql = '
				SELECT `id`, `role_access`
				FROM `' . EXPORTS_REPORTS_TBL . 'reports`
				WHERE `disabled` = 0 AND `group` = %d
				ORDER BY `weight`, `name`
				LIMIT 1
			';

                $sql = $wpdb->prepare($sql, array($group->id));

                $reports = $wpdb->get_results($sql);

                if (0 < @count($reports)) {
                    foreach ($reports as $report) {
                        if ($has_full_access || ExportReports::$acl->current_user_can_any('exports_reports_view') || ExportReports::$acl->current_user_can_any('exports_reports_view_group_' . $group->id) || ExportReports::$acl->current_user_can_any('exports_reports_view_report_' . $report->id)) {
                            $menu_page = 'exports-reports-group-' . $group->id;

                            if (!$init) {
                                add_menu_page('Reports', 'Reports', 'read', 'exports-reports', null, EXPORTS_REPORTS_URL . 'assets/icons/16.png');

                                $menu_page = 'exports-reports';

                                $init = true;
                            }

                            add_submenu_page('exports-reports', $group->name, $group->name, 'read', $menu_page, 'exports_reports_view');

                            break;
                        }

                        $roles = explode(',', $report->role_access);

                        if (empty($roles)) {
                            continue;
                        }

                        foreach ($roles as $role) {
                            if (ExportReports::$acl->has_role($role)) {
                                $menu_page = 'exports-reports-group-' . $group->id;

                                if (!$init) {
                                    add_menu_page('Reports', 'Reports', 'read', 'exports-reports', null, EXPORTS_REPORTS_URL . 'assets/icons/16.png');

                                    $menu_page = 'exports-reports';

                                    $init = true;
                                }

                                add_submenu_page('exports-reports', $group->name, $group->name, 'read', $menu_page, 'exports_reports_view');

                                break;
                            }
                        }
                    }
                }
            }
        }
    }

}
