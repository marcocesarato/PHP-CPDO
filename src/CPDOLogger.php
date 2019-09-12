<?php

namespace marcocesarato\cpdo;

/**
 * Class CPDOLogger.
 */
class CPDOLogger
{
    protected static $__enabled = false;
    protected static $__count = 0;
    protected static $__logs = array();

    /**
     * Enable logs.
     */
    public static function enable()
    {
        self::$__enabled = true;
    }

    /**
     * Disable logs.
     */
    public static function disable()
    {
        self::$__enabled = false;
    }

    /**
     * Return if logs are enabled.
     *
     * @return bool
     */
    public static function isEnabled()
    {
        return self::$__enabled;
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
            self::$__count++;
            self::$__logs[$query][] = array('time' => time(), 'execution_time' => $time, 'cached' => $cache);
        }
    }

    /**
     * Get Logs.
     *
     * @return array
     */
    public static function getLogs()
    {
        return array('count' => self::$__count, 'queries' => self::$__logs);
    }

    /**
     * Get Counter.
     *
     * @return int
     */
    public static function getCounter()
    {
        return self::$__count;
    }

    /**
     * Get Counter.
     *
     * @return array
     */
    public static function getQueries()
    {
        return array_keys(self::$__logs);
    }

    /**
     * Clean Logs.
     */
    public static function cleanLogs()
    {
        self::$__count = 0;
        self::$__logs = array();
    }
}
