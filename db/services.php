<?php

/**
 * Monitor web service plugin functions and services definitions
 *
 * @package db
 * @copyright 2016 Uemanet
 */

$functions = array(
    'get_tutor_online_time' => array(
        'classname' => 'local_monitor_external',
        'methodname' => 'get_online_time',
        'classpath' => 'local/monitor/classes/external',
        'description' => 'Tutor online time',
        'type' => 'read'
    ),
    'monitor_ping' => array(
        'classname' => 'local_monitor_ping',
        'methodname' => 'monitor_ping',
        'classpath' => 'local/monitor/classes/ping',
        'description' => 'Checks the connection with Moodle',
        'type' => 'read'
    ),
    'monitor_tutor_answers' => array(
        'classname' => 'local_monitor_forum',
        'methodname' => 'get_tutor_forum_answers',
        'classpath' => 'local/monitor/classes/forum',
        'description' => 'Get tutor answers to foruns',
        'type' => 'read'
    )
);

$services = array(
    'Monitor' => array(
        'functions' => array('get_tutor_online_time', 'monitor_ping', 'monitor_tutor_answers'),
        'restrictedusers' => 0,
        'enabled' => 1
    )
);
