<?php

namespace marcocesarato\cpdo;

use PDO;
use PDOStatement;

/**
 * Class CPDOStatement.
 */
class CPDOStatement extends PDOStatement
{
    public $queryString;
    private $queryParams = array();

    /**
     * CPDOStatement constructor.
     * @param $dbh
     */
    protected function __construct($dbh)
    {
        $this->dbh = $dbh;
    }

    /**
     * Execute.
     *
     * @param  null  $input_parameters
     *
     * @return bool
     */
    public function execute($input_parameters = null)
    {
        $args = array($this->queryParams, $input_parameters);

        $result = null;
        $cache = null;
        $__logger_start = microtime(true);
        $method = CPDOCache::parseMethod($this->queryString);
        if (in_array($method, CPDOCache::getOperationMethods('read'))) {
            $cache = CPDOCache::getcache($this->queryString, $args);
            if (empty($cache)) {
                $result = parent::execute($input_parameters);
                CPDOCache::setcache($this->queryString, $result, $args);
            } else {
                $result = $cache;
            }
        } elseif (in_array($method, CPDOCache::getOperationMethods('write'))) {
            CPDOCache::deletecache($this->queryString);
        }
        if (is_null($result)) {
            $result = parent::execute($input_parameters);
        }
        $__logger_end = microtime(true);
        CPDOLogger::addLog($this->queryString, $__logger_end - $__logger_start, !is_null($cache));

        return $result;
    }

    /**
     * @param  null  $fetch_style
     * @param  int   $cursor_orientation
     * @param  int   $cursor_offset
     *
     * @return mixed|null
     */
    public function fetch($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        $args = array('fetch', $this->queryParams, $fetch_style);

        $cache = CPDOCache::getcache($this->queryString, $args);
        if (empty($cache)) {
            if (!empty($cursor_offset)) {
                if (empty($cursor_orientation)) {
                    $cursor_orientation = PDO::FETCH_ORI_NEXT;
                }
                $result = parent::fetch($fetch_style, $cursor_orientation, $cursor_offset);
            } elseif (!empty($cursor_orientation)) {
                $result = parent::fetch($fetch_style, $cursor_orientation);
            } else {
                $result = parent::fetch($fetch_style);
            }
            CPDOCache::setcache($this->queryString, $result, $args);

            return $result;
        }

        return $cache;
    }

    /**
     * @param  null   $fetch_style
     * @param  null   $fetch_argument
     * @param  array  $ctor_args
     *
     * @return array|null
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, $ctor_args = null)
    {
        $args = array('fetchAll', $this->queryParams, $fetch_style);

        $cache = CPDOCache::getcache($this->queryString, $args);
        if (empty($cache)) {
            if (!empty($ctor_args)) {
                $result = parent::fetchAll($fetch_style, $fetch_argument, $ctor_args);
            } elseif (!empty($fetch_argument)) {
                $result = parent::fetchAll($fetch_style, $fetch_argument);
            } else {
                $result = parent::fetchAll($fetch_style);
            }
            CPDOCache::setcache($this->queryString, $result, $args);

            return $result;
        }

        return $cache;
    }

    /**
     * @param  string  $class_name
     * @param  array   $ctor_args
     *
     * @return mixed|null
     */
    public function fetchObject($class_name = 'stdClass', $ctor_args = array())
    {
        $args = array('fetchObject', $this->queryParams, $ctor_args);

        $cache = CPDOCache::getcache($this->queryString, $args);
        if (empty($cache)) {
            if (!empty($ctor_args)) {
                $result = parent::fetchAll($class_name, $ctor_args);
            } else {
                $result = parent::fetchObject($class_name);
            }
            CPDOCache::setcache($this->queryString, $result, $args);

            return $result;
        }

        return $cache;
    }

    /**
     * @param  int  $column_number
     *
     * @return mixed|null
     */
    public function fetchColumn($column_number = 0)
    {
        $args = array('fetchColumn', $this->queryParams, $column_number);

        $cache = CPDOCache::getcache($this->queryString, $args);
        if (empty($cache)) {
            $result = parent::fetchColumn($column_number);
            CPDOCache::setcache($this->queryString, $result, $args);

            return $result;
        }

        return $cache;
    }

    /**
     * @param  mixed  $parameter
     * @param  mixed  $variable
     * @param  int    $data_type
     * @param  null   $length
     * @param  null   $driver_options
     *
     * @return bool
     */
    public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null)
    {
        $this->queryParams[] = array($parameter, $variable, $data_type, $length, $driver_options);

        return parent::bindParam($parameter, $variable, $data_type, $length, $driver_options);
    }

    /**
     * @param  mixed  $column
     * @param  mixed  $param
     * @param  null   $type
     * @param  null   $maxlen
     * @param  null   $driverdata
     *
     * @return bool
     */
    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null)
    {
        $this->queryParams[] = array($column, $param, $type, $maxlen, $driverdata);

        return parent::bindColumn($column, $param, $type, $maxlen, $driverdata);
    }

    /**
     * @param  mixed  $parameter
     * @param  mixed  $value
     * @param  int    $data_type
     *
     * @return bool
     */
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR)
    {
        $this->queryParams[] = array($parameter, $value, $data_type);

        return parent::bindValue($parameter, $value, $data_type);
    }
}
