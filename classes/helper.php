<?php

/**
 * Class helper
 *
 * @package monitor
 * @copyright 2016 Uemanet
 * @author Lucas S. Vieira
 */
abstract class helper
{
    const FILE = "monitor.json";

    public static function debug()
    {
        return helper::parse_config_file('debug');
    }

    private static function parse_config_file($key = '')
    {
        $fileContent = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . helper::FILE);
        $json = json_decode($fileContent, true);

        if (array_key_exists($key, $json)) {
            return $json[$key];
        }

        return null;
    }
}
