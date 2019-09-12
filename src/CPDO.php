<?php

namespace marcocesarato\cpdo;

use Exception;
use PDO;
use PDOStatement;

/**
 * CPDO
 * Memory Cached PDO Class.
 *
 * @author    Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2019
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 *
 * @see      https://github.com/marcocesarato/PHP-CPDO
 *
 * @version   0.2.3.44
 */
class CPDO extends PDO
{
    /**
     * CPDO constructor.
     *
     * @param          $dsn
     * @param  string  $username
     * @param  string  $password
     * @param  array   $driver_options
     */
    public function __construct($dsn, $username = '', $password = '', $driver_options = array())
    {
        parent::__construct($dsn, $username, $password, $driver_options);
        parent::setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        try {
            @parent::setAttribute(PDO::ATTR_STATEMENT_CLASS, array('marcocesarato\cpdo\CPDOStatement', array($this)));
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        //parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Auto generation of the DSN.
     *
     * @param        $database_type
     * @param  null  $database_name
     * @param  null  $database_host
     * @param  null  $database_user
     * @param  null  $database_pswd
     * @param  null  $database_charset
     *
     * @return bool|static
     */
    public static function connect($database_type, $database_name = null, $database_host = null, $database_user = null, $database_pswd = null, $database_charset = null)
    {
        if (empty($database_host) && in_array($database_type, array(
            'mysql',
            'pgsql',
            'mssql',
            'ibm',
            'firebird',
            '4D',
            'interbase',
            'informix',
        ))) {
            trigger_error('CDPO: Database host is empty', E_USER_WARNING);
        }
        if (empty($database_name) && in_array($database_name, array(
            'mysql',
            'pgsql',
            'mssql',
            'sqlite',
            'oracle',
            'ibm',
            'firebird',
            'interbase',
            'informix',
        ))) {
            trigger_error('CDPO: Database name is empty', E_USER_WARNING);
        }
        if (empty($database_user) && in_array($database_name, array(
            'mysql',
            'pgsql',
            'mssql',
            'ibm',
            '4D',
            'informix',
        ))) {
            trigger_error('CDPO: Database user is empty', E_USER_WARNING);
        }
        if ($database_type == 'mysql') {
            return new CPDO('mysql:host=' . $database_host . ';dbname=' . $database_name . (!empty($database_charset) ? ';charset=' . $database_charset : ''), $database_user, $database_pswd);
        } elseif ($database_type == 'pgsql') {
            return new CPDO('pgsql:host=' . $database_host . ';dbname=' . $database_name . (!empty($database_charset) ? ';options=\'--client_encoding=' . $database_charset . '\'' : ''), $database_user, $database_pswd);
        } elseif ($database_type == 'mssql') {
            return new CPDO('sqlsrv:Server=' . $database_host . ';Database=' . $database_name, $database_user, $database_pswd);
        } elseif ($database_type == 'sqlite') {
            return new CPDO('sqlite:/' . $database_name);
        } elseif ($database_type == 'oracle') {
            return new CPDO('oci:dbname=' . $database_name);
        } elseif ($database_type == 'ibm') {
            return new CPDO('ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=' . $database_name . ';HOSTNAME=' . $database_host . ';PROTOCOL=TCPIP;', $database_user, $database_pswd);
        } elseif (($database_type == 'firebird') || ($database_type == 'interbase')) {
            return new CPDO('firebird:dbname=' . $database_name . ';host=' . $database_host);
        } elseif ($database_type == '4D') {
            return new CPDO('4D:host=' . $database_host, $database_user, $database_pswd);
        } elseif ($database_type == 'informix') {
            return new CPDO('informix:host=' . $database_host . '; database=' . $database_name . '; server=' . $database_host, $database_user, $database_pswd);
        } elseif (empty($database_type)) {
            trigger_error('CDPO: Database type is empty!', E_USER_ERROR);

            return false;
        }
        trigger_error('CDPO: Database type `' . htmlentities($database_type) . '` is not supported yet', E_USER_ERROR);

        return false;
    }

    /**
     * Exec.
     *
     * @param  string  $statement
     *
     * @return bool|int|null
     */
    public function exec($statement)
    {
        $args = 'exec';
        $result = null;
        $cache = null;
        $__logger_start = microtime(true);
        $method = CPDOCache::parseMethod($statement);
        if (in_array($method, CPDOCache::getOperationMethods('read'))) {
            $cache = CPDOCache::getcache($statement, $args);
            if (empty($cache)) {
                $result = parent::exec($statement);
                CPDOCache::setcache($statement, $result, $args);
            } else {
                $result = $cache;
            }
        } elseif (in_array($method, CPDOCache::getOperationMethods('write'))) {
            CPDOCache::deletecache($statement);
        }
        if (is_null($result)) {
            $result = parent::exec($statement);
        }
        $__logger_end = microtime(true);
        CPDOLogger::addLog($statement, $__logger_end - $__logger_start, !is_null($cache));

        return $result;
    }

    /**
     * Query.
     *
     * @param  string  $statement
     * @param  int     $mode
     * @param  null    $arg3
     * @param  array   $ctorargs
     *
     * @return array|bool|PDOStatement|null
     */
    public function query($statement, $mode = null, $arg3 = null, $ctorargs = null)
    {
        $args = array('query', $mode, $arg3, $ctorargs);

        $method = CPDOCache::parseMethod($statement);
        if (in_array($method, CPDOCache::getOperationMethods('read'))) {
            $cache = CPDOCache::getcache($statement, $args);
            if (!empty($cache)) {
                return $cache;
            }
        } elseif (in_array($method, CPDOCache::getOperationMethods('write'))) {
            CPDOCache::deletecache($statement);
        }
        if (!empty($ctorargs)) {
            if (empty($mode)) {
                $mode = PDO::ATTR_DEFAULT_FETCH_MODE;
            }
            $result = parent::query($statement, $mode, $arg3, $ctorargs);
        } elseif (!empty($arg3)) {
            if (empty($mode)) {
                $mode = PDO::ATTR_DEFAULT_FETCH_MODE;
            }
            $result = parent::query($statement, $mode, $arg3);
        } elseif (!empty($mode)) {
            $result = parent::query($statement, $mode);
        } else {
            $result = parent::query($statement);
        }
        if (in_array($method, CPDOCache::getOperationMethods('read'))) {
            CPDOCache::setcache($statement, $result, $args);
        }

        return $result;
    }

    /**
     * Enable debug.
     */
    public function enableDebug()
    {
        CPDOLogger::enable();
    }

    /**
     * Disable debug.
     */
    public function disableDebug()
    {
        CPDOLogger::disable();
    }

    /**
     * Enable cache.
     */
    public function enableCache()
    {
        CPDOCache::enable();
    }

    /**
     * Disable cache.
     */
    public function disableCache()
    {
        CPDOCache::disable();
    }

    /**
     * Get list of database tables.
     *
     * @return array|bool
     */
    public function getTables()
    {
        $sql = 'SHOW TABLES';
        $query = $this->query($sql);

        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Backup database tables or just a table.
     *
     * @param  string  $tables
     */
    public function backup($backup_dir, $backup_tables = '*')
    {
        $tables = array();
        $data = "\n-- DATABASE BACKUP --\n\n";
        $data .= "--\n-- Date: " . date('d/m/Y H:i:s', time()) . "\n";
        $data .= "\n\n-- --------------------------------------------------------\n\n";
        if ($backup_tables == '*') {
            $tables = $this->getTables();
        } else {
            $tables = is_array($backup_tables) ? $backup_tables : explode(',', $backup_tables);
        }
        foreach ($tables as $table) {
            $sth = $this->prepare('SELECT count(*) FROM ' . $table);
            $sth->execute();
            $num_fields = $sth->fetch(PDO::FETCH_NUM);
            $num_fields = $num_fields[0];
            $result = $this->prepare('SELECT * FROM ' . $table);
            $result->execute();
            $data .= "--\n-- CREATE TABLE `" . $table . "`\n--";
            $data .= "\n\nDROP TABLE IF EXISTS `" . $table . '`;';
            $result2 = $this->prepare('SHOW CREATE TABLE ' . $table);
            $result2->execute();
            $row2 = $result2->fetch(PDO::FETCH_NUM);
            $row2[1] = preg_replace("/AUTO_INCREMENT=[\w]*./", '', $row2[1]);
            $row2[1] = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $row2[1]);
            $data .= "\n\n" . $row2[1] . ";\n\n";
            $data .= "-- --------------------------------------------------------\n\n";
            $data .= "--\n-- INSERT INTO table `" . $table . "`\n--\n\n";
            for ($i = 0; $i < $num_fields; $i++) {
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $data .= 'INSERT INTO ' . $table . ' VALUES(' . implode(',', array_map(array(
                        $this,
                        'escape',
                    ), $row)) . ");\n";
                }
            }
        }
        $data .= "\n-- --------------------------------------------------------\n\n\n";
        $filename = $backup_dir . '/db-backup' . ((is_array($backup_tables)) ? '-' . (implode(',', $tables)) : '') . '-' . date('dmY', time()) . '-' . time() . '.sql';
        $f = fopen($filename, 'w+');
        fwrite($f, pack('CCC', 0xef, 0xbb, 0xbf));
        fwrite($f, $data);
        fclose($f);
    }

    /**
     * Escape variable.
     *
     * @param  string  $value
     */
    protected function escape($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if ((string)intval($value) === $value) {
            return (int)$value;
        }

        return $this->quote($value);
    }
}
