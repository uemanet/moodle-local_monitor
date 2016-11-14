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
        'description' => 'Tempo on-line do tutor',
        'type' => 'read'
    )
);


$services = array(
    'Monitor' => array(
        'functions' => array('get_tutor_online_time'),
        'restrictedusers' => 0,
        'enabled' => 1
    )
);
