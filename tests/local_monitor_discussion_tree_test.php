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

global $CFG;
require_once("$CFG->dirroot/course/lib.php");

/**
 * Discussion tree unit tests
 *
 * @group monitor
 * @package monitor
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Lucas S. Vieira <lucassouzavieiraengcomp@gmail.com>
 */
class local_monitor_discussion_tree_test extends advanced_testcase {

    /**
     * @var object $course Course to host forum discussion
     */
    protected $course;

    /**
     * @var object $forum Forum with discussion
     */
    protected $forum;

    /**
     * @var int $discussion Discussion id
     */
    protected $discussion;

    /**
     * @var array $users Users enrolled in course
     */
    protected $users;

    /**
     * @var object $targetuser User to check participation with \local_monitor\discussion_tree class
     */
    protected $targetuser;

    /**
     * Mock data for tests
     * @throws \dml_exception
     */
    private function mock_forum() {
        global $DB;

        $this->course = $this->getDataGenerator()->create_course();

        // Forum entry.
        $forum = new \stdClass();
        $forum->course = $this->course->id;
        $forum->type = "general";
        $forum->intro = "Intro text";
        $forum->timemodified = time();
        $forumid = $DB->insert_record("forum", $forum);

        // Forum module.
        $module = $DB->get_record("modules", array("name" => "forum"), "*", MUST_EXIST);

        // Course module.
        $mod = new \stdClass();
        $mod->course = $this->course->id;
        $mod->module = $module->id;
        $mod->instance = $forumid;
        $mod->section = 0;

        $mod->coursemodule = add_course_module($mod);
        $sectionid = course_add_cm_to_section($this->course, $mod->coursemodule, $mod->section);

        $params = array("id" => $mod->coursemodule);
        $DB->set_field("course_modules", "section", $sectionid, $params);

        $this->forum = $DB->get_record("forum", array("id" => $forumid), "*", MUST_EXIST);
        rebuild_course_cache($this->course->id);
    }
}