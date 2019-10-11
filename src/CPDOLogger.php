<?php

namespace marcocesarato\cpdo;

/**
 * Class CPDOLogger.
 */
class CPDOLogger
{
    protected static $enabled = false;
    protected static $count = 0;
    protected static $logs = array();

    /**
     * Enable logs.
     */
    public static function enable()
    {
        self::$enabled = true;
    }

    /**
     * Disable logs.
     */
    public static function disable()
    {
        self::$enabled = false;
    }

    /**
     * Return if logs are enabled.
     *
     * @return bool
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }

    /**
     * Add new log.
     *
     * @param $query
     * @param $time
     * @param $cache
     */
    public static function addLog($query, $time, $cache)
    {
        if (self::isEnabled()) {
            self::$count++;
            self::$logs[$query][] = array('time' => time(), 'execution_time' => $time, 'cached' => $cache);
        }
    }

    /**
     * Get Logs.
     *
     * @return array
     */
    public static function getLogs()
    {
        return array('count' => self::$count, 'queries' => self::$logs);
    }

    /**
     * Get Counter.
     *
     * @return int
     */
    public static function getCounter()
    {
        return self::$count;
    }

    /**
     * Get Counter.
     *
     * @return array
     */
    public static function getQueries()
    {
        return array_keys(self::$logs);
    }

    /**
     * Clean Logs.
     */
    public static function cleanLogs()
    {
        self::$count = 0;
        self::$logs  = array();
    }
}
