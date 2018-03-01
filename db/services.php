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

/*
 * Monitor web service plugin functions and services definitions
 *
 * @package monitor
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Lucas S. Vieira <lucassouzavieiraengcomp@gmail.com>
 */
$functions = array(
    'local_monitor_get_tutor_online_time' => array(
        'classname' => 'local_monitor_external',
        'methodname' => 'get_online_time',
        'classpath' => 'local/monitor/classes/external',
        'description' => get_string('functiongettutoronlinetime', 'local_monitor'),
        'type' => 'read'
    ),

    'local_monitor_ping' => array(
        'classname' => 'local_monitor_external',
        'methodname' => 'monitor_ping',
        'classpath' => 'local/monitor/classes/external',
        'description' => get_string('functionping', 'local_monitor'),
        'type' => 'read'
    ),

    'local_monitor_tutor_answers' => array(
        'classname' => 'local_monitor_external',
        'methodname' => 'get_tutor_forum_answers',
        'classpath' => 'local/monitor/classes/external',
        'description' => get_string('functiontutoransweres', 'local_monitor'),
        'type' => 'read'
    )
);

$services = array(
    'Monitor' => array(
        'functions' => array('local_monitor_get_tutor_online_time', 'local_monitor_ping', 'local_monitor_tutor_answers'),
        'restrictedusers' => 0,
        'enabled' => 1
    )
);
