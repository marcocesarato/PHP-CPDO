<?php

namespace marcocesarato\cpdo;

use Exception;

/**
 * Class CPDOCache.
 */
class CPDOCache
{

    protected static $operations = array(
        'read' => array('SELECT', 'SHOW', 'DESCRIBE'),
        'write' => array('INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER'),
    );
    protected static $exclude = array();
    protected static $enabled = true;
    protected static $cache = array();
    // From Light SQL Parser Class
    protected static $parserMethod = array();
    protected static $parserTables = array();

    /**
     * Enable cache.
     */
    public static function enable()
    {
        self::$enabled = true;
    }

    /**
     * Disable cache.
     */
    public static function disable()
    {
        self::$enabled = false;
    }

    /**
     * Return if cache is enabled.
     *
     * @return bool
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }

    /**
     * Populate cache.
     *
     * @param $mixed
     *
     * @return bool
     */
    public static function populate($cache)
    {
        if (is_array($cache)) {
            self::$cache = $cache;
        }
    }

    /**
     * Retrieve Cache.
     *
     * @return array
     */
    public static function retrieve()
    {
        return self::$cache;
    }

    /**
     * Get all excluded tables.
     *
     * @return array
     */
    public static function getExceptions()
    {
        return self::$exclude;
    }

    /**
     * Add exception.
     *
     * @param $exclude
     */
    public static function addException($exclude)
    {
        self::$exclude[] = $exclude;
        self::$exclude   = array_unique(self::$exclude);
    }

    /**
     * Add exceptions.
     *
     * @param $exclude
     */
    public static function addExceptions($exclude)
    {
        self::$exclude = array_merge(self::$exclude, $exclude);
        self::$exclude = array_unique(self::$exclude);
    }

    /**
     * Get query methods for operation type.
     *
     * @param $operation
     *
     * @return mixed
     */
    public static function getOperationMethods($operation)
    {
        return self::$operations[$operation];
    }

    /**
     * Set Cache.
     *
     * @param        $query
     * @param        $value
     * @param  null  $arg
     */
    public static function setcache($query, $value, $arg = null)
    {
        if (!self:: isEnabled()) {
            return null;
        }

        $e = new Exception();
        $trace = $e->getTrace();
        $function = $trace[1]['function'];
        $table = self::keycache($query);
        $tables = self::parseTables($query);
        $arg = self::argkey($arg);
        foreach (self::getExceptions() as $key) {
            foreach ($tables as $table) {
                if (strpos($key, $table) !== false) {
                    return false;
                }
            }
        }
    }

    /**
     * Get Cache.
     *
     * @param        $query
     * @param  null  $arg
     *
     * @return null
     */
    public static function getcache($query, $arg = null)
    {
        if (!self:: isEnabled()) {
            return null;
        }
        $e = new Exception();
        $trace = $e->getTrace();
        $function = $trace[1]['function'];
        $table = self::keycache($query);
        $arg = self::argkey($arg);

        if (isset(self::$cache[$table][$query][$function][$arg])) {
            return self::$cache[$table][$query][$function][$arg];
        }

        return null;
    }

    /**
     * Delete Cache.
     *
     * @param $query
     */
    public static function deletecache($query)
    {
        if (!self:: isEnabled()) {
            return null;
        }
        $tables = self::parseTables($query);
        foreach (array_keys(self::$cache) as $key) {
            foreach ($tables as $table) {
                if (strpos($key, $table) !== false) {
                    self::$cache[$key] = array();
                }
            }
        }
    }

    /**
     * Get key cache.
     *
     * @param $query
     *
     * @return string
     */
    protected static function keycache($query)
    {
        $tables = self::parseTables($query);

        return implode('/', $tables);
    }

    /**
     * Get args key cache.
     *
     * @param $query
     *
     * @return string
     */
    protected static function argkey($arg)
    {
        $arg = crc32(json_encode($arg));

        return $arg;
    }

    /**
     * Get SQL Query method.
     *
     * @param $query
     *
     * @return mixed|string
     *
     * @see    https://github.com/marcocesarato/PHP-Light-SQL-Parser-Class
     */
    public static function parseMethod($query)
    {
        if (!empty(self::$parserMethod[$query])) {
            return self::$parserMethod[$query];
        }
        $methods = array(
            'SELECT',
            'INSERT',
            'UPDATE',
            'DELETE',
            'RENAME',
            'SHOW',
            'SET',
            'DROP',
            'CREATE INDEX',
            'CREATE TABLE',
            'EXPLAIN',
            'DESCRIBE',
            'TRUNCATE',
            'ALTER',
        );
        $queries = self::parseQueries($query);
        foreach ($queries as $query) {
            foreach ($methods as $method) {
                $_method = str_replace(' ', '[\s]+', $method);
                if (preg_match('#^[\s]*' . $_method . '[\s]+#i', $query)) {
                    self::$parserMethod[$query] = $method;

                    return $method;
                }
            }
        }

        return '';
    }

    /**
     * Get SQL Query Tables.
     *
     * @param $_query
     *
     * @return array|mixed
     *
     * @see    https://github.com/marcocesarato/PHP-Light-SQL-Parser-Class
     */
    public static function parseTables($_query)
    {
        $connectors = "OR|AND|ON|LIMIT|WHERE|JOIN|GROUP|ORDER|OPTION|LEFT|INNER|RIGHT|OUTER|SET|HAVING|VALUES|SELECT|\(|\)";
        if (!empty(self::$parserTables[$_query])) {
            return self::$parserTables[$_query];
        }
        $results = array();
        $queries = self::parseQueries($_query);
        foreach ($queries as $query) {
            $patterns = array(
                '#[\s]+FROM[\s]+(([\s]*(?!' . $connectors . ')[\w]+([\s]+(AS[\s]+)?(?!' . $connectors . ')[\w]+)?[\s]*[,]?)+)#i',
                '#[\s]*INSERT[\s]+INTO[\s]+([\w]+)#i',
                '#[\s]*UPDATE[\s]+([\w]+)#i',
                '#[\s]+[\s]+JOIN[\s]+([\w]+)#i',
                '#[\s]+TABLE[\s]+([\w]+)#i',
            );
            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $query, $matches, PREG_SET_ORDER);
                foreach ($matches as $val) {
                    $tables = explode(',', $val[1]);
                    foreach ($tables as $table) {
                        $table = trim(preg_replace('#[\s]+(AS[\s]+)[\w\.]+#i', '', $table));
                        $results[] = $table;
                    }
                }
            }
        }
        $tables = array_unique($results);

        self::$parserTables[$_query] = $tables;

        return $tables;
    }

    /**
     * Get SQL Query method.
     *
     * @param $query
     *
     * @return array|string|string[]|null
     *
     * @see    https://github.com/marcocesarato/PHP-Light-SQL-Parser-Class
     *          Get all queries
     */
    public static function parseQueries($query)
    {
        $queries = preg_replace('#\/\*[\s\S]*?\*\/#', '', $query);
        $queries = preg_replace('#;(?:(?<=["\'];)|(?=["\']))#', '', $queries);
        $queries = preg_replace('#[\s]*UNION([\s]+ALL)?[\s]*#', ';', $queries);
        $queries = explode(';', $queries);

        return $queries;
    }
}
