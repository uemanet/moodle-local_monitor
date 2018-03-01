<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_monitor;

defined('MOODLE_INTERNAL') || die();

/**
 * local_monitor_external class
 *
 * @package monitor
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Lucas S. Vieira <lucassouzavieiraengcomp@gmail.com>
 */
class local_monitor_external extends \external_api {
    private static $day = 60 * 60 * 24;

    /**
     * Returns default values for get_online_tutors_parameters
     * @return array
     * @throws \Exception
     */
    private static function get_online_time_default_parameters() {
        $startdate = new \DateTime("NOW", \core_date::get_server_timezone_object());
        $enddate = new \DateTime("NOW", \core_date::get_server_timezone_object());

        $enddate->add(new \DateInterval('P7D'));

        return array(
            'timebetweenclicks' => 60,
            'startdate' => $startdate->getTimestamp(),
            'enddate' => $enddate->getTimestamp()
        );
    }

    /**
     * Validate rules for get_online_tutors
     * @param $timebetweenclicks
     * @param $startdate
     * @param $enddate
     * @return bool
     * @throws \moodle_exception
     */
    private static function get_online_time_validate_rules($timebetweenclicks, $startdate, $enddate) {
        $startdate = new \DateTime($startdate, \core_date::get_server_timezone_object());
        $enddate = new \DateTime($enddate, \core_date::get_server_timezone_object());

        $now = new \DateTime("NOW", \core_date::get_server_timezone_object());

        $start = $startdate->getTimestamp();
        $end = $enddate->getTimestamp();

        if (!($timebetweenclicks > 0)) {
            throw new \moodle_exception('timebetweenclickserror', 'local_monitor', null, null, '');
        }

        if ($start > $end) {
            throw new \moodle_exception('startdateerror', 'local_monitor', null, null, '');
        }

        if ($end >= $now->getTimestamp()) {
            throw new \moodle_exception('enddateerror', 'local_monitor', null, null, '');
        }

        return true;
    }

    /**
     * Returns description of get_online_time parameters
     * @return \external_function_parameters
     * @throws \moodle_exception
     * @throws \Exception
     */
    public static function get_online_time_parameters() {
        $default = self::get_online_time_default_parameters();

        $timebetweenclicks = $default['timebetweenclicks'];
        $startdate = $default['startdate'];
        $enddate = $default['enddate'];

        return new \external_function_parameters(array(
            'timebetweenclicks' => new \external_value(
                PARAM_INT,
                get_string('paramtimebetweenclicks', 'local_monitor'),
                VALUE_DEFAULT,
                $timebetweenclicks
            ),
            'startdate' => new \external_value(
                PARAM_TEXT,
                get_string('paramstartdate', 'local_monitor'),
                VALUE_DEFAULT,
                $startdate
            ),
            'enddate' => new \external_value(
                PARAM_TEXT,
                get_string('paramenddate', 'local_monitor'),
                VALUE_DEFAULT,
                $enddate
            ),
            'pesid' => new \external_value(
                PARAM_INT,
                get_string('paramtutorid', 'local_monitor'),
                VALUE_REQUIRED
            )
        ));
    }

    /**
     * Returns the time online day by day
     * @param $timebetweenclicks
     * @param $startdate
     * @param $enddate
     * @param $tutorid
     * @return mixed
     * @throws \Exception
     */
    public static function get_online_time($timebetweenclicks, $startdate, $enddate, $tutorid) {
        global $DB, $CFG;

        self::validate_parameters(self::get_online_time_parameters(), array(
            'time_between_clicks' => $timebetweenclicks,
            'start_date' => $startdate,
            'end_date' => $enddate,
            'pes_id' => $tutorid
        ));

        self::get_online_time_validate_rules($timebetweenclicks, $startdate, $enddate);

        $start = new \DateTime($startdate, \core_date::get_server_timezone_object());
        $end = new \DateTime($enddate, \core_date::get_server_timezone_object());

        $start = $start->getTimestamp();
        $end = $end->getTimestamp() + self::$day;

        $interval = $end - $start;
        $days = $interval / self::$day;

        try {
            $tutorgrupo = $DB->get_record('int_pessoa_user', array('pes_id' => $tutorid));

            if (!$tutorgrupo) {
                throw new \moodle_exception('tutornonexistserror', 'local_monitor', null, null, '');
            }

            $tutor = $DB->get_record('user', array('id' => $tutorgrupo->userid));
            $name = $tutor->firstname . ' ' . $tutor->lastname;
            $result = array('id' => $tutor->id, 'fullname' => $name, 'items' => array());

            for ($i = $days; $i > 0; $i--) {

                $parameters = array(
                    (integer)$tutor->id,
                    $end - self::$day * $i,
                    $end - self::$day * ($i - 1)
                );

                $query = "SELECT id, timecreated
                            FROM {logstore_standard_log}
                            WHERE userid = ?
                            AND timecreated >= ?
                            AND timecreated <= ?
                            ORDER BY timecreated ASC";

                // Get user logs.
                $logs = $DB->get_records_sql($query, $parameters);

                $date = new \DateTime("NOW", \core_date::get_server_timezone_object());
                $date->setTimestamp($end - (self::$day * $i));

                $previouslog = array_shift($logs);
                $previouslogtime = isset($previouslog) ? $previouslog->timecreated : 0;
                $sessionstart = isset($previouslog) ? $previouslog->timecreated : 0;
                $onlinetime = 0;

                foreach ($logs as $log) {
                    if (($log->timecreated - $previouslogtime) < $timebetweenclicks) {
                        $onlinetime += $log->timecreated - $previouslogtime;
                        $sessionstart = $log->timecreated;
                    }

                    $previouslogtime = $log->timecreated;
                }

                $result['items'][] = array('onlinetime' => $onlinetime, 'date' => $date->format("d-m-Y"));
            }

            return $result;
        } catch (\Exception $exception) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                throw $exception;
            }

            return new \external_warnings(
                get_class($exception),
                $exception->getCode(),
                $exception->getMessage()
            );
        }
    }

    /**
     * Returns description of get_online_time return values
     * @return \external_function_parameters
     * @throws \coding_exception
     */
    public static function get_online_time_returns() {
        return new \external_function_parameters(array(
            'id' => new \external_value(PARAM_INT, get_string('return_id', 'local_monitor')),
            'fullname' => new \external_value(PARAM_TEXT, get_string('return_fullname', 'local_monitor')),
            'items' => new \external_multiple_structure(
                        new \external_single_structure(array(
                            'onlinetime' => new \external_value(PARAM_TEXT, get_string('return_onlinetime', 'local_monitor')),
                            'date' => new \external_value(PARAM_TEXT, get_string('return_date', 'local_monitor'))
                        )
                    ))
            )
        );
    }

    /**
     * Returns description of ping parameters
     * @return \external_function_parameters
     */
    public static function monitor_ping_parameters() {
        return new \external_function_parameters([]);
    }

    /**
     * Ping function
     * @return array
     */
    public static function monitor_ping() {
        return array(
            'response' => true
        );
    }

    /**
     * Returns description of ping return values
     * @return \external_function_parameters
     */
    public static function monitor_ping_returns() {
        return new \external_function_parameters(array(
            'response' => new \external_value(PARAM_BOOL, 'Default response')
        ));
    }

    /**
     * Returns description of get_tutor_forum_answers parameters
     * @return \external_function_parameters
     * @throws \coding_exception
     */
    public static function get_tutor_forum_answers_parameters() {
        return new \external_function_parameters(array(
            'pes_id' => new \external_value(PARAM_INT, get_string('paramgroupid', 'local_monitor')),
            'trm_id' => new \external_value(PARAM_INT, get_string('paramgroupid', 'local_monitor'))
        ));
    }

    /**
     * Returns forum tutor answers
     * @param $pesid
     * @param $trmid
     * @return array
     * @throws \Exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function get_tutor_forum_answers($pesid, $trmid) {
        global $DB, $CFG;

        self::validate_parameters(
            self::get_tutor_forum_answers_parameters(), array(
            'pes_id' => $pesid,
            'trm_id' => $trmid
        ));

        $userdata = $DB->get_record('int_tutor_group', array('pes_id' => $pesid), '*');
        $userid = $userdata->userid;

        if (!$userid) {
            throw new \Exception("O tutor de pes_id: " . $pesid . " não está mapeado no ambiente virtual.");
        }

        $datacourse = $DB->get_record('int_turma_course', array('trm_id' => $trmid), '*');
        $courseid = $datacourse->courseid;

        if (!$courseid) {
            throw new \Exception("A turma com id: " . $trmid . " não está mapeada com o ambiente virtual.");
        }

        $course = $DB->get_record('course', array('id' => $courseid), '*');

        $returndata = [];

        $parameters = array(
            $userid,
            $courseid
        );

        $returndata['id'] = $userid;
        $returndata['course'] = $course->fullname;
        $returndata['itens'] = [];

        // Receive all discussions for an given course.
        $query = "SELECT {forum_discussions}.*, {groups}.id as groupid ,{groups}.name as groupname
                    FROM {forum_discussions}
                    INNER JOIN {groups}
                    ON {groups}.id = {forum_discussions}.groupid
                    WHERE userid = ? and course = ?
                    ORDER BY groupname, {forum_discussions}.name";

        $discussions = $DB->get_records_sql($query, $parameters);

        foreach ($discussions as $key => $discussion) {
            $tree = new \local_monitor\discussion_tree($discussion->id, $userid);
            $data = $tree->get_analitycs();

            $returndata['itens'][] = array(
                'idgrupo' => $discussion->groupid,
                'grupo' => $discussion->groupname,
                'discussion' => $discussion->name,
                'postsstudents' => $data['everyoneelseposts'],
                'poststutor' => $data['userposts'],
                'participacaototal' => $tree->user_participation(),
                'percentual' => $tree->user_answer_rate(),
                'tempo' => $data['mediumresponsetime']
            );
        }

        return $returndata;
    }

    /**
     * Returns description of get_tutor_forum_answers return values
     * @return \external_function_parameters
     * @throws \coding_exception
     */
    public static function get_tutor_forum_answers_returns() {
        return new \external_function_parameters(array(
                'id' => new \external_value(
                    PARAM_INT,
                    get_string('returnid', 'local_monitor')
                ),
                'course' => new \external_value(
                    PARAM_TEXT,
                    get_string('returncoursefullname', 'local_monitor')
                ),
                'itens' => new \external_multiple_structure(
                    new \external_single_structure(array(
                            'idgrupo' => new \external_value(
                                PARAM_TEXT,
                                get_string('paramgroupid', 'local_monitor')
                            ),
                            'grupo' => new \external_value(
                                PARAM_TEXT,
                                get_string('groupname', 'local_monitor')
                            ),
                            'discussion' => new \external_value(
                                PARAM_TEXT,
                                get_string('discussionname', 'local_monitor')
                            ),
                            'poststutor' => new \external_value(
                                PARAM_INT,
                                get_string('tutorposts', 'local_monitor')
                            ),
                            'postsstudents' => new \external_value(
                                PARAM_TEXT,
                                get_string('studentsposts', 'local_monitor')
                            ),
                            'percentual' => new \external_value(
                                PARAM_TEXT,
                                get_string('tutorpercent', 'local_monitor')
                            ),
                            'participacaototal' => new \external_value(
                                PARAM_TEXT,
                                get_string('tutorparticipation', 'local_monitor')
                            ),
                            'tempo' => new \external_value(
                                PARAM_TEXT,
                                get_string('responsetime', 'local_monitor')
                            )
                        )
                    )
                )
            )
        );
    }
}
