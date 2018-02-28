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

defined('MOODLE_INTERNAL') || die();

/**
 * local_monitor_external class
 *
 * @package monitor
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Lucas S. Vieira <lucassouzavieiraengcomp@gmail.com>
 */
class local_monitor_forum extends external_api {

    /**
     * Returns description of get_tutor_forum_answers parameters
     * @return external_function_parameters
     */
    public static function get_tutor_forum_answers_parameters() {
        return new external_function_parameters(array(
            'pes_id' => new external_value(PARAM_INT, 'id do tutor do lado do Harpia'),
            'trm_id' => new external_value(PARAM_INT, 'id de turma do lado do Harpia')
        ));
    }

    /**
     * Returns forum tutor answers
     * @param $pesid
     * @param $trmid
     * @return array
     * @throws Exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
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
            throw new Exception("O tutor de pes_id: " . $pesid . " não está mapeado no ambiente virtual.");
        }

        $datacourse = $DB->get_record('int_turma_course', array('trm_id' => $trmid), '*');
        $courseid = $datacourse->courseid;

        if (!$courseid) {
            throw new Exception("A turma com id: " . $trmid . " não está mapeada com o ambiente virtual.");
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
            $posts = self::make_tree_of_discussions($discussion->id, $userid);

            $poststutoranswered = 0;
            $postsstudents = 0;
            $primeiro = array_shift($posts['posts']);
            $numerador = 0;
            $denominador = 0;

            foreach ($primeiro->children as $key => $post) {

                if ($post->userid != $userid) {
                    $postsstudents++;
                    foreach ($post->children as $value) {
                        if ($value->userid == $userid) {
                            $numerador = $numerador + $value->created - $post->created;
                            $denominador++;
                            $poststutoranswered++;
                            break;
                        }
                    }

                }

            }

            $media = $numerador / $denominador;
            $dias = floor($media / (3600 * 24));
            $horas = floor(($media - ($dias * 3600 * 24)) / 3600);
            $minutos = floor(($media - ($horas * 3600) - ($dias * 3600 * 24)) / 60);
            $segundos = floor($media % 60);

            if (!is_nan($media)) {
                $tempo = $dias . 'd' . $horas . "h" . $minutos . "min";
            } else {
                $tempo = '';
            }

            $returndata['itens'][] = array(
                'idgrupo' => $discussion->groupid,
                'grupo' => $discussion->groupname,
                'discussion' => $discussion->name,
                'postsstudents' => $postsstudents,
                'poststutor' => $poststutoranswered,
                'participacaototal' => $posts['participacaototal'],
                'percentual' => number_format($poststutoranswered / $postsstudents, 2),
                'tempo' => $tempo
            );
        }

        return $returndata;
    }

    public static function make_tree_of_discussions($discussionid, $userid) {
        global $DB, $CFG;

        $parameters = array(
            (int) $discussionid,
            $userid
        );

        try {
            $postssql = "SELECT id, parent, userid, created FROM {forum_posts}  WHERE discussion = ?";
            $poststutorsql = "SELECT id, parent, userid FROM {forum_posts}  WHERE discussion = ? AND parent != 0 AND userid = ?";
            $postsstudentssql = "SELECT id, parent, userid FROM {forum_posts}  WHERE discussion = ? and userid != ?";

            $posts = $DB->get_records_sql($postssql, $parameters);
            $poststutor = count($DB->get_records_sql($poststutorsql, $parameters));
            $postsstudents = count($DB->get_records_sql($postsstudentssql, $parameters));

            foreach ($posts as $pid => $p) {
                if (!$p->parent) {
                    continue;
                }

                if (!isset($posts[$p->parent])) {
                    continue;
                }

                if (!isset($posts[$p->parent]->children)) {
                    $posts[$p->parent]->children = array();
                }

                $posts[$p->parent]->children[$pid] =& $posts[$pid];
            }
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

        return $returndata = [
            'posts' => $posts,
            'participacaototal' => number_format($poststutor / $postsstudents, 2)
        ];

    }

    /**
     * Returns description of get_tutor_forum_answers return values
     * @return external_function_parameters
     * @throws coding_exception
     */
    public static function get_tutor_forum_answers_returns() {
        return new external_function_parameters(array(
                'id' => new external_value(
                    PARAM_INT,
                    get_string('returnid', 'local_monitor')
                ),
                'course' => new external_value(
                    PARAM_TEXT,
                    get_string('returncoursefullname', 'local_monitor')
                ),
                'itens' => new external_multiple_structure(
                    new external_single_structure(array(
                            'idgrupo' => new external_value(
                                PARAM_TEXT,
                                get_string('paramgroupid', 'local_monitor')
                            ),
                            'grupo' => new external_value(
                                PARAM_TEXT,
                                get_string('groupname', 'local_monitor')
                            ),
                            'discussion' => new external_value(
                                PARAM_TEXT,
                                get_string('discussionname', 'local_monitor')
                            ),
                            'poststutor' => new external_value(
                                PARAM_INT,
                                get_string('tutorposts', 'local_monitor')
                            ),
                            'postsstudents' => new external_value(
                                PARAM_TEXT,
                                get_string('studentsposts', 'local_monitor')
                            ),
                            'percentual' => new external_value(
                                PARAM_TEXT,
                                get_string('tutorpercent', 'local_monitor')
                            ),
                            'participacaototal' => new external_value(
                                PARAM_TEXT,
                                get_string('tutorparticipation', 'local_monitor')
                            ),
                            'tempo' => new external_value(
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
