<?php
namespace ExportAndReports;

class ExportReportsHelper
{

    /**
     * @return mixed
     */
    static public function schedule_cleanup($what )
    {

        $schedules = _get_cron_array();
        $timestamp = false;

        $key = md5(serialize(array()));

        foreach ($schedules as $ts => $schedule) {
            if (isset($schedule[$what]) && isset($schedule[$what][$key])) {
                $timestamp = $ts;
                break;
            }
        }

        if (false !== $timestamp) {
            wp_unschedule_event($timestamp, $what, array());
        }

        $timestamp = time();
        $recurrence = 'daily';

        return wp_schedule_event($timestamp, $recurrence, $what, array());

    }
}
