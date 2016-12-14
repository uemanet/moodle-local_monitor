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
    'ping' => array(
        'classname' => 'local_monitor_ping',
        'methodname' => 'ping',
        'classpath' => 'local/monitor/classes/ping',
        'description' => 'Checks the connection with Moodle',
        'type' => 'read'
    )
);


$services = array(
    'Monitor' => array(
        'functions' => array('get_tutor_online_time', 'ping'),
        'restrictedusers' => 0,
        'enabled' => 1
    )
);
