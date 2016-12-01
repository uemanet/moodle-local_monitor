<?php

/**
 * monitor
 *
 * @package monitor
 * @copyright   2016 Uemanet
 * @author      Lucas S. Vieira
 */

defined('MOODLE_INTERNAL') || die();

class local_monitor_external extends external_api
{
    private static $day = 60 * 60 * 24;

    /**
     * Returns default values for get_online_tutors_parameters
     * @return array
     */
    private static function get_online_time_default_parameters()
    {
        return array(
            'time_between_clicks' => 60,
            'start_date' => gmdate('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 7, date('Y'))),
            'end_date' => gmdate('Y-m-d', mktime(0, 0, 0, date('m'), date('d'), date('Y')))
        );
    }

    /**
     * Validate rules for get_online_tutors
     * @param $timeBetweenClicks
     * @param $startdate
     * @param $enddate
     * @return bool
     * @throws moodle_exception
     */
    private static function get_online_time_validate_rules($timeBetweenClicks, $startdate, $enddate)
    {
        $startdate = (integer)strtotime($startdate);
        $enddate = (integer)strtotime($enddate);

        if (!($timeBetweenClicks > 0)) {
            throw new moodle_exception('timebetweenclickserror', 'local_monitor', null, null, '');
        }

        if ($startdate > $enddate) {
            throw new moodle_exception('startdateerror', 'local_monitor', null, null, '');
        }

        if ($enddate >= time()) {
            throw new moodle_exception('enddateerror', 'local_monitor', null, null, '');
        }

        return true;
    }

    /**
     * Returns description of get_online_time parameters
     * @return external_function_parameters
     */
    public static function get_online_time_parameters()
    {
        $default = local_monitor_external::get_online_time_default_parameters();

        return new external_function_parameters(array(
            'time_between_clicks' => new external_value(PARAM_INT, get_string('getonlinetime_param_time_between_clicks', 'local_monitor'), VALUE_DEFAULT, $default['time_between_clicks']),
            'start_date' => new external_value(PARAM_TEXT, get_string('getonlinetime_param_start_date', 'local_monitor'), VALUE_DEFAULT, $default['start_date']),
            'end_date' => new external_value(PARAM_TEXT, get_string('getonlinetime_param_end_date', 'local_monitor'), 'Data de fim da consulta: dd-mm-YYYY', VALUE_DEFAULT, $default['end_date']),
            'tutor_id' => new external_value(PARAM_INT, get_string('getonlinetime_param_tutor_id', 'local_monitor'), 'ID do Tutor', VALUE_DEFAULT)
        ));
    }

    /**
     * Returns the time online day by day
     * @param $timeBetweenClicks
     * @param $startdate
     * @param $enddate
     * @param $tutorid
     * @return array
     * @throws Exception
     */
    public static function get_online_time($timeBetweenClicks, $startdate, $enddate, $tutorid)
    {
        global $DB;

        self::validate_parameters(self::get_online_time_parameters(), array(
                'time_between_clicks' => $timeBetweenClicks,
                'start_date' => $startdate,
                'end_date' => $enddate,
                'tutor_id' => $tutorid,
            )
        );

        local_monitor_external::get_online_time_validate_rules($timeBetweenClicks, $startdate, $enddate);

        $start = (integer)strtotime($startdate);
        $end = (integer)strtotime($enddate) + local_monitor_external::$day;

        $interval = $end - $start;
        $days = $interval / local_monitor_external::$day;

        $tutor = $DB->get_record('user', array('id' => $tutorid));
        $name = $tutor->firstname . ' ' . $tutor->lastname;

        $result = array('id' => $tutor->id, 'fullname' => $name, 'items' => array());

        for ($i = $days; $i > 0; $i--) {

            $parameters = array(
                (integer)$tutorid,
                $end - local_monitor_external::$day * $i,
                $end - local_monitor_external::$day * ($i - 1)
            );

            $query = "SELECT id, timecreated
                        FROM {logstore_standard_log}
                        WHERE userid = ?
                        AND timecreated >= ?
                        AND timecreated <= ?
                        ORDER BY timecreated ASC";

            try {
                // Get user logs
                $logs = $DB->get_records_sql($query, $parameters);
                $date = gmdate("d-m-Y", $end - (local_monitor_external::$day * $i));

                $previousLog = array_shift($logs);
                $previousLogTime = isset($previousLog) ? $previousLog->timecreated : 0;
                $sessionStart = isset($previousLog) ? $previousLog->timecreated : 0;
                $onlineTime = 0;

                foreach ($logs as $log) {
                    if (($log->timecreated - $previousLogTime) < $timeBetweenClicks) {
                        $onlineTime += $log->timecreated - $previousLogTime;
                        $sessionStart = $log->timecreated;
                    }

                    $previousLogTime = $log->timecreated;
                }

                $result['items'][] = array('onlinetime' => $onlineTime, 'date' => $date);
            } catch (\Exception $e) {

                if(helper::debug()){
                    throw new moodle_exception('databaseaccesserror', 'local_monitor', null, null, '');
                }

                continue;
            }
        }

        return $result;
    }

    /**
     * Returns description of get_online_time return values
     * @return external_value
     */
    public static function get_online_time_returns()
    {
        return new external_function_parameters(array(
            'id' => new external_value(PARAM_INT, get_string('getonlinetime_return_id', 'local_monitor')),
            'fullname' => new external_value(PARAM_TEXT, get_string('getonlinetime_return_fullname', 'local_monitor')),
            'items' => new external_multiple_structure(
                new external_single_structure(array(
                    'onlinetime' => new external_value(PARAM_TEXT, get_string('getonlinetime_return_onlinetime', 'local_monitor')),
                    'date' => new external_value(PARAM_TEXT, get_string('getonlinetime_return_date', 'local_monitor'))
                ))
            )
        ));
    }
}
