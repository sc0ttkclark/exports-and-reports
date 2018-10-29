<?php


namespace ExportAndReports;

require 'ExportReportsACL.php';


class ExportReports
{
    static $acl = null;

    public static function run()
    {
        global $wpdb;
        define('EXPORTS_REPORTS_TBL', $wpdb->prefix . 'exportsreports_');
        define('EXPORTS_REPORTS_VERSION', '074');

        if (!defined('WP_ADMIN_UI_EXPORT_URL')) {
            define('WP_ADMIN_UI_EXPORT_URL', WP_CONTENT_URL . '/exports');
        }

        if (!defined('WP_ADMIN_UI_EXPORT_DIR')) {
            define('WP_ADMIN_UI_EXPORT_DIR', WP_CONTENT_DIR . '/exports');
        }
        self::$acl = new ExportReportsACL();
    }

    public static function install()
    {
        // check version
        $version = (int) get_option('exports_reports_version');

        if (empty($version)) {
            self::reset();
        } elseif ($version != EXPORTS_REPORTS_VERSION) {
            $version = absint($version);

            if ($version < 32) {
                $wpdb->query('ALTER TABLE `' . EXPORTS_REPORTS_TBL . 'groups` ADD COLUMN `role_access` MEDIUMTEXT NOT NULL AFTER `disabled`');
                $wpdb->query('ALTER TABLE `' . EXPORTS_REPORTS_TBL . 'reports` ADD COLUMN `disabled` int(1) NOT NULL AFTER `group`');
                $wpdb->query('ALTER TABLE `' . EXPORTS_REPORTS_TBL . 'reports` ADD COLUMN `role_access` MEDIUMTEXT NOT NULL AFTER `disable_export`');
                $wpdb->query('ALTER TABLE `' . EXPORTS_REPORTS_TBL . 'reports` ADD COLUMN `weight` int(10) NOT NULL AFTER `role_access`');
            }

            if ($version < 42) {
                $wpdb->query('ALTER TABLE `' . EXPORTS_REPORTS_TBL . 'reports` ADD COLUMN `default_none` int(1) NOT NULL AFTER `disable_export`');
            }

            if ($version < 50) {
                $wpdb->query('ALTER TABLE `' . EXPORTS_REPORTS_TBL . 'groups` ADD COLUMN `weight` int(10) NOT NULL AFTER `role_access`');
                $wpdb->query('ALTER TABLE `' . EXPORTS_REPORTS_TBL . 'reports` ADD COLUMN `sql_query_count` longtext NOT NULL AFTER `sql_query`');
            }

            if ($version < 60) {
                $token = md5(microtime() . wp_generate_password(20, true));
                update_option('exports_reports_token', $token);
            }

            update_option('exports_reports_version', EXPORTS_REPORTS_VERSION);

            ExportReportsHelper::schedule('exports_reports_cleanup');
        }
    }

    public static function reset()
    {

        global $wpdb;

        $sql = file_get_contents(EXPORTS_REPORTS_DIR . 'assets/dump.sql');

        $charset_collate = 'DEFAULT CHARSET utf8';

        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARSET {$wpdb->charset}";
        }

        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        if ('DEFAULT CHARSET utf8' != $charset_collate) {
            $sql = str_replace('DEFAULT CHARSET utf8', $charset_collate, $sql);
        }

        $sql = explode(";\n", str_replace(array("\r", 'wp_'), array("\n", $wpdb->prefix), $sql));

        for ($i = 0, $z = count($sql); $i < $z; $i++) {
            $query = trim($sql[$i]);

            if (empty($query)) {
                continue;
            }

            $wpdb->query($query);
        }

        delete_option('exports_reports_version');
        add_option('exports_reports_version', EXPORTS_REPORTS_VERSION);

        $token = md5(microtime() . wp_generate_password(20, true));

        update_option('exports_reports_token', $token);

        ExportReportsHelper::schedule('exports_reports_cleanup');

    }

    /**
     *
     */
    public function wp_admin_ui_export()
    {

        require_once EXPORTS_REPORTS_DIR . 'wp-admin-ui/Admin.class.php';

        wp_die('Invalid request'); // AJAX dies

    }

    /**
     * @param bool $full
     *
     * @return bool
     */
    public function cleanup($full = false)
    {

        global $wpdb;

        if ($full) {
            $wpdb->query('TRUNCATE ' . EXPORTS_REPORTS_TBL . 'log');

            global $wp_filesystem;

            $directory = WP_ADMIN_UI_EXPORT_DIR;

            if ($dir = opendir($directory)) {
                while (false !== ($file = readdir($dir))) {
                    if (in_array($file, array('.', '..'))) {
                        continue;
                    }

                    $file_path = $directory . DIRECTORY_SEPARATOR . $file;

                    if ($wp_filesystem->is_file($file_path)) {
                        $wp_filesystem->delete($file_path);
                    } elseif ($wp_filesystem->is_dir($file_path)) {
                        $this->delete_files_in_directory($file_path);
                    }
                }

                closedir($dir);
            }
        } else {
            $purge_age = 1; // day(s) in age to purge

            $sql = '
			SELECT *
			FROM `' . EXPORTS_REPORTS_TBL . 'log`
			WHERE `created` < DATE_ADD( NOW(), INTERVAL -%d DAY )
		';

            $sql = $wpdb->prepare($sql, array($purge_age));

            $cleanup = $wpdb->get_results($sql);

            if (false !== $cleanup && !empty($cleanup)) {
                foreach ($cleanup as $export) {
                    @unlink(WP_ADMIN_UI_EXPORT_DIR . '/' . str_replace(array('/', '..'), '', $export->filename));

                    $sql = '
					DELETE FROM `' . EXPORTS_REPORTS_TBL . 'log`
					WHERE `id` = %d
				';

                    $sql = $wpdb->prepare($sql, array($export->id));

                    $wpdb->query($sql);
                }

                return true;
            }
        }

        return false;

    }
/**
 * @param $args
 * @param $obj
 *
 * @return bool
 */
    public function delete_log($args, $obj)
    {

        global $wpdb;

        $filename = $args[1];

        if (false !== $args[2]) {
            $sql = $wpdb->prepare(
                '
				DELETE FROM `' . EXPORTS_REPORTS_TBL . 'log`
				WHERE `report_id` = %d AND `filename` = %s
			',
                array(
                    $obj[0]->report_id,
                    $filename,
                )
            );

            $result = $wpdb->query($sql);

            return $result;
        } else {
            return false;
        }

    }
/**
 * @param $args
 * @param $obj
 *
 * @return mixed
 */
    public function log($args, $obj)
    {

        global $wpdb;

        $filename = $args[1];

        $result = $wpdb->insert(
            EXPORTS_REPORTS_TBL . 'log',
            array(
                'report_id' => $obj[0]->report_id,
                'filename' => $filename,
                'created' => date_i18n('Y-m-d H:i:s'),
            ),
            array(
                '%d',
                '%s',
                '%s',
            )
        );

        return $result;

    }

}
