<?php
namespace ExportAndReports;

class ExportReportsACL
{

    protected $capabilites = [
        'exports_reports_full_access',
        'exports_reports_settings',
        'exports_reports_view',
    ];

    public function __construct()
    {
        global $current_user;
        wp_get_current_user();

        // thx gravity forms, great way of integration with members!
        if (function_exists('members_get_capabilities')) {
            add_filter('members_get_capabilities', 'exports_reports_get_capabilities');

            if (self::current_user_can_any('exports_reports_full_access')) {
                $current_user->remove_cap('exports_reports_full_access');
            }

            $is_admin_with_no_permissions = $this->has_role('administrator') && !self::current_user_can_any($this->capabilities());

            if ($is_admin_with_no_permissions) {
                $role = get_role('administrator');

                foreach ($this->capabilities() as $cap) {
                    $role->add_cap($cap);
                }
            }
        } else {
            $exports_reports_full_access = $this->has_role('administrator') ? 'exports_reports_full_access' : '';
            $exports_reports_full_access = apply_filters('exports_reports_full_access', $exports_reports_full_access);

            if (!empty($exports_reports_full_access)) {
                $current_user->add_cap($exports_reports_full_access);
            }
        }
    }

    /**
     * @return array
     */
    public function capabilities()
    {

        return apply_filters('export_reports_capabilites',self::$capabilites);

    }

    /**
     * @param $caps
     *
     * @return bool
     */
    public function current_user_can_any($caps = [])
    {

        if (!is_user_logged_in()) {
            return false;
        }

        if($caps == []) $caps = $this->capabilites();

        if (!is_array($caps)) {
            return current_user_can($caps);
        }

        foreach ($caps as $cap) {
            if (current_user_can($cap)) {
                return true;
            }
        }

        return current_user_can('exports_reports_full_access');

    }
    /**
     * @param $caps
     *
     * @return array
     */
    public function get_capabilities($caps = [])
    { 

        $caps = array_merge($caps, $this->capabilities());

        return $caps;

    }

    /**
     * @param $caps
     *
     * @return bool
     */
    public function current_user_can_which($caps = [])
    {
        
        
        if (!is_user_logged_in()) {
            return false;
        }
        if($caps == []) $caps = $this->capabilites();

        foreach ($caps as $cap) {
            if (current_user_can($cap)) {
                return $cap;
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function get_roles()
    {

        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        return $wp_roles->get_names();

    }

    /**
     * @param $role
     *
     * @return bool
     */
    public function has_role($role)
    {

        global $current_user;

        if (!is_user_logged_in()) {
            return false;
        }


        if (null !== get_role($role) && in_array($role, $current_user->roles)) {
            return true;
        }

        return false;

    }
}
