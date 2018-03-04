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
     * @throws coding_exception
     */
    public function setUp() {
        // Generate a course and enroll users
        $this->course = $this->getDataGenerator()->create_course();

        // Tutor role
        $record = array(
            'name' => 'Tutor',
            'shortname' => 'Tutor',
            'description' => 'Tutor role',
            'archetype' => 'teacher'
        );

        $roleid = $this->getDataGenerator()->create_role($record);

        // Add an tutor
        $tutor = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($tutor->id, $this->course->id, $roleid);
        $this->targetuser = $tutor; // Test tutor participation

        // Add some students
        for ($i = 0; $i < 10; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $this->course->id, 5);

            $this->users[] = $user;
        }

        parent::setUp();
    }

    /**
     * Mock data for tests
     * @throws \dml_exception
     */
    private function mock_forum() {
        global $DB;

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

    /**
     * Mocks an forum discussion
     * @param null $forumid
     * @throws dml_exception
     * @return \stdClass Discussion object
     */
    private function mock_forum_discussion($forumid = null, $message = "") {
        global $DB;

        $forum = $this->forum;

        if (!is_null($forumid)) {
            $forum = $DB->get_record("forum", array("id" => $forumid), "*", MUST_EXIST);
        }

        // Create a new discussion
        $discussion = new \stdClass();
        $discussion->course = $forum->course;
        $discussion->course = $forum->course;
        $discussion->forum = $forum->id;
        $discussion->name = $forum->name;
        $discussion->assessed = $forum->assessed;
        $discussion->message = $forum->intro;
        $discussion->messageformat = $forum->introformat;
        $discussion->messagetrust = trusttext_trusted(context_course::instance($forum->course));
        $discussion->mailnow = false;
        $discussion->groupid = -1;

        $discussion->id = forum_add_discussion($discussion, null, $message);
        rebuild_course_cache($this->course->id);

        return $discussion;
    }

    /**
     * Mocks an forum post by an user
     * @param null $userid
     * @param string $content
     */
    private function mock_forum_post($userid = null, $content = "") {

    }
}