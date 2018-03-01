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
 * discussion_tree class
 *
 * @package monitor
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Lucas S. Vieira <lucassouzavieiraengcomp@gmail.com>
 */
class discussion_tree {

    /**
     * @var int Discussion id in forum.
     */
    protected $discussionid;

    /**
     * @var int User id to build tree relative of.
     */
    protected $userid;

    /**
     * @var array Tree data. Root is the first post in forum
     */
    protected $tree;

    /**
     * @var int User posts count
     */
    protected $usercount = 0;

    /**
     * @var int Everyone else posts count
     */
    protected $everyoneelsecount = 0;

    /**
     * @var bool Tree has been build
     */
    protected $isbuild = false;

    /**
     * @var string Medium response time of user
     */
    protected $mediumresponsetime = "";

    /**
     * @var int Posts answered by user
     */
    protected $postsanswered = 0;

    /**
     * @var int Posts answered by user
     */
    protected $usersanswered = 0;

    /**
     * discussion_tree constructor.
     * @param $discussionid
     * @param $userid
     * @throws \Exception
     */
    public function __construct(int $discussionid, int $userid) {
        $this->discussionid = $discussionid;
        $this->userid = $userid;
        $this->build();
        $this->analyze();
    }

    /**
     * User participation in discussion
     * @return float
     */
    public function user_participation() {
        return (float) number_format($this->usercount / $this->everyoneelsecount, 2);
    }

    /**
     * Percent of user answers
     * @return float
     */
    public function user_answer_rate() {
        return (float) number_format($this->postsanswered / $this->usersanswered, 2);
    }

    public function get_analitycs() {
        return array(
            'userposts' => $this->usercount,
            'everyoneelseposts' => $this->everyoneelsecount,
            'userparticipation' => $this->user_participation(),
            'mediumresponsetime' => $this->mediumresponsetime,
        );
    }

    /**
     * Analyze discussion tree
     */
    protected function analyze() {
        $useranswered = 0;

        $first = array_shift($this->tree);

        $responsetimesum = 0;
        $postsanswered = 0;

        foreach ($first->children as $key => $post) {
            if ($post->userid == $this->userid) {
                continue;
            }

            foreach ($post->children as $value) {
                if ($value->userid != $this->userid) {
                    continue;
                }

                $responsetimesum = $responsetimesum + $value->created - $post->created;
                $postsanswered++;
                $useranswered++;
                break;
            }
        }

        $this->usersanswered = $useranswered;
        $this->postsanswered = $postsanswered;
        $media = (int)($responsetimesum / $postsanswered);

        if ($media) {
            $dateobj = new \DateTime("@0");
            $datediff = new \DateTime("@$media");

            $time = $dateobj->diff($datediff);

            $this->mediumresponsetime = $time->d . 'd' . $time->h . "h" . $time->i . "min";
        }
    }

    /**
     * @throws \Exception
     */
    protected function build() {
        global $DB, $CFG;

        $postssql = "SELECT id, parent, userid, created
                      FROM {forum_posts}
                      WHERE discussion = ?";

        $poststutorsql = "SELECT id, parent, userid
                            FROM {forum_posts}
                            WHERE discussion = ?
                            AND parent != 0
                            AND userid = ?";

        $postsstudentssql = "SELECT id, parent, userid
                              FROM {forum_posts}
                              WHERE discussion = ?
                              AND userid != ?";

        $parameters = array($this->discussionid, $this->userid);

        try {
            $posts = $DB->get_records_sql($postssql, $parameters);

            $this->usercount = count($DB->get_records_sql($poststutorsql, $parameters));
            $this->everyoneelsecount = count($DB->get_records_sql($postsstudentssql, $parameters));

            foreach ($posts as $pid => $post) {
                if (!$post->parent) {
                    continue;
                }

                if (!isset($posts[$post->parent])) {
                    continue;
                }

                if (!isset($posts[$post->parent]->children)) {
                    $posts[$post->parent]->children = array();
                }

                $posts[$post->parent]->children[$pid] =& $posts[$pid];
            }

            $this->tree = $posts;
            $this->isbuild = true;
        } catch (\Exception $exception) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                // For development only.
                throw $exception;
            }

            $this->tree = [];
            $this->usercount = 0;
            $this->everyoneelsecount = 0;
            $this->isbuild = true;
        }
    }
}