<?php

/**
 * ping
 *
 * @package monitor
 * @copyright 2016 Uemanet
 * @author    Lucas S. Vieira
 */
class local_monitor_ping extends external_api
{
    public static function monitor_ping_parameters()
    {
        return new external_function_parameters([]);
    }

    public static function monitor_ping()
    {
        return array('response' => true);
    }

    public static function monitor_ping_returns()
    {
        return new external_function_parameters(array(
            'response' => new external_value(PARAM_BOOL, 'Default response')
        ));
    }
}
