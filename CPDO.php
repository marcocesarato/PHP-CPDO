<?php

/**
 * CPDO
 * Memory Cached PDO Class
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2018
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link https://github.com/marcocesarato/CPDO
 * @version 0.1.2.10
 */
class CPDO extends PDO
{

    /**
     * CPDO constructor.
     */
    function __construct($dsn, $username = "", $password = "", $driver_options = array())
    {
        parent::__construct($dsn, $username, $password, $driver_options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('CPDOStatement', array($this)));
    }

    /**
     * Autogeneration of the DSN
     * @param $database_type
     * @param null $database_name
     * @param null $database_host
     * @param null $database_user
     * @param null $database_pswd
     * @return bool|static
     */
    public static function connect($database_type, $database_name = null, $database_host = null, $database_user = null, $database_pswd = null){

    	if(empty($database_host) && in_array($database_type, array('mysql', 'pgsql', 'mssql', 'ibm', 'firebird', '4D', 'interbase', 'informix'))){
		    trigger_error("CDPO: Database host is empty", E_USER_WARNING);
	    }
	    if(empty($database_name) && in_array($database_name, array('mysql', 'pgsql', 'mssql', 'sqlite', 'oracle', 'ibm', 'firebird', 'interbase', 'informix'))){
		    trigger_error("CDPO: Database name is empty", E_USER_WARNING);
	    }
	    if(empty($database_user) && in_array($database_name, array('mysql', 'pgsql', 'mssql', 'ibm', '4D', 'informix'))){
		    trigger_error("CDPO: Database user is empty", E_USER_WARNING);
	    }

        if ($database_type == 'mysql') {
            return new static('mysql:host='.$database_host.';dbname='.$database_name, $database_user, $database_pswd);
        } elseif ($database_type == 'pgsql') {
            return new static('pgsql:host='.$database_host.';dbname='.$database_name, $database_user, $database_pswd);
        } elseif ($database_type == 'mssql') {
            return new static('sqlsrv:Server='.$database_host.';Database='.$database_name, $database_user, $database_pswd);
        } elseif ($database_type == 'sqlite') {
            return new static('sqlite:/'.$database_name);
        } elseif ($database_type == 'oracle') {
            return new static('oci:dbname='.$database_name);
        } elseif ($database_type == 'ibm') {
            return new static('ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE='.$database_name.';HOSTNAME='.$database_host.';PROTOCOL=TCPIP;', $database_user, $database_pswd);
        } elseif (($database_type == 'firebird') || ($database_type == 'interbase')) {
            return new static('firebird:dbname='.$database_name.';host='.$database_host);
        } elseif ($database_type == '4D') {
            return new static('4D:host='.$database_host, $database_user, $database_pswd);
        } elseif ($database_type == 'informix') {
            return new static('informix:host='.$database_host.'; database='.$database_name.'; server='.$database_host, $database_user, $database_pswd);
        } elseif(empty($database_type)) {
	        trigger_error("CDPO: Database type is empty!", E_USER_ERROR);
	        return false;
        }
	    trigger_error("CDPO: Database type `".htmlentities($database_type)."` is not supported yet", E_USER_ERROR);
        return false;
    }

}

/**
 * Class CPDOStatement
 */
class CPDOStatement extends PDOStatement
{

    public $queryString;
    public static $__cache = array();

    // From Light SQL Parser Class
    protected static $__parser_method = array();
    protected static $__parser_tables = array();

    /**
     * @param null $input_parameters
     * @return bool|void
     */
    public function execute($input_parameters = null)
    {
        $method = $this->__parseMethod();
        switch ($method) {
            case 'SELECT':
            case 'SHOW':
            case 'DESCRIBE':
                if (empty($this->__getcache())) {
                    parent::execute($input_parameters);
                }
                break;
            case 'INSERT':
            case 'UPDATE':
            case 'DELETE':
            case 'DROP':
            case 'TRUNCATE':
            case 'ALTER':
                $this->__deletecache();
                parent::execute($input_parameters);
                break;
            default:
                parent::execute($input_parameters);
        }
    }

    /**
     * @param null $fetch_style
     * @param int $cursor_orientation
     * @param int $cursor_offset
     * @return mixed
     */
    public function fetch($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        $cache = $this->__getcache($fetch_style);
        if (empty($cache)) {
            $result = parent::fetch($fetch_style/*, $cursor_orientation, $cursor_offset*/);
            $this->__setcache($result, $fetch_style);
            return $result;
        }
        return $cache;
    }

    /**
     * @param null $fetch_style
     * @param null $fetch_argument
     * @param array $ctor_args
     * @return array
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, $ctor_args = array())
    {
        $cache = $this->__getcache($fetch_style);
        if (empty($cache)) {
            $result = parent::fetchAll($fetch_style/*, $fetch_argument, $ctor_args*/);
            $this->__setcache($result, 'all_' . $fetch_style);
            return $result;
        }
        return $cache;
    }

    /**
     * @param string $class_name
     * @param array $ctor_args
     * @return mixed
     */
    public function fetchObject($class_name = "stdClass", $ctor_args = array())
    {
        $cache = $this->__getcache($class_name);
        if (empty($cache)) {
            $result = parent::fetchObject($class_name/*, $ctor_args*/);
            $this->__setcache($result, $class_name);
            return $result;
        }
        return $cache;
    }

    /**
     * @param int $column_number
     * @return mixed
     */
    public function fetchColumn($column_number = 0)
    {
        $cache = $this->__getcache($column_number);
        if (empty($cache)) {
            $result = parent::fetchColumn($column_number);
            $this->__setcache($result, $column_number);
            return $result;
        }
        return $cache;
    }

    /**
     * Set Cache
     * @param $value
     */
    protected function __setcache($value, $arg = null)
    {
        $e = new \Exception();
        $trace = $e->getTrace();
        $function = $trace[1]['function'];

        $table = $this->__keycache();
        self::$__cache[$table][$this->queryString][$function][$arg] = $value;
    }

    /**
     * Get Cache
     * @return mixed
     */
    protected function __getcache($arg = null)
    {
        $e = new \Exception();
        $trace = $e->getTrace();
        $function = $trace[1]['function'];

        $table = $this->__keycache();
        if (isset(self::$__cache[$table][$this->queryString][$function][$arg]))
            return self::$__cache[$table][$this->queryString][$function][$arg];
        return null;
    }

    /**
     * Delete Cache
     */
    protected function __deletecache()
    {
        $tables = $this->__parseTables();
        foreach (array_keys(self::$__cache) as $key){
            foreach ($tables as $table){
                if(strpos($key, $table) !== false ) {
                    self::$__cache[$key] = array();
                }
            }
        }
    }

    /**
     * Get key cache
     * @param $query
     * @return string
     */
    protected function __keycache(){
        $tables = $this->__parseTables();
        return implode('/',$tables);
    }

    /**
     * Get SQL Query method
     * @package Light-SQL-Parser-Class
     * @link https://github.com/marcocesarato/PHP-Light-SQL-Parser-Class
     *
     * @param $query
     * @return string
     */
    protected function __parseMethod(){

        if(!empty(self::$__parser_method[$this->queryString]))
            return self::$__parser_method[$this->queryString];

        $methods = array('SELECT','INSERT','UPDATE','DELETE','RENAME','SHOW','SET','DROP','CREATE INDEX','CREATE TABLE','EXPLAIN','DESCRIBE','TRUNCATE','ALTER');
        $queries = $this->__parseQueries();
        foreach($queries as $query){
            foreach($methods as $method) {
                $_method = str_replace(' ', '[\s]+', $method);
                if(preg_match('#^[\s]*'.$_method.'[\s]+#i', $query)){
                    self::$__parser_method[$this->queryString] = $method;
                    return $method;
                }
            }
        }
        return '';
    }

    /**
     * Get SQL Query Tables
     * @package Light-SQL-Parser-Class
     * @link https://github.com/marcocesarato/PHP-Light-SQL-Parser-Class
     *
     * @return array
     */
    protected function __parseTables(){

	    $connectors = "OR|AND|ON|LIMIT|WHERE|JOIN|GROUP|ORDER|OPTION|LEFT|INNER|RIGHT|OUTER|SET|HAVING|VALUES|SELECT|\(|\)";

        if(!empty(self::$__parser_tables[$this->queryString]))
            return self::$__parser_tables[$this->queryString];

        $results = array();
        $queries = $this->__parseQueries();
        foreach($queries as $query) {
            $patterns = array(
                '#[\s]+FROM[\s]+(([\s]*(?!'.$connectors.')[\w]+([\s]+(AS[\s]+)?(?!'.$connectors.')[\w]+)?[\s]*[,]?)+)#i',
                '#[\s]*INSERT[\s]+INTO[\s]+([\w]+)#i',
                '#[\s]*UPDATE[\s]+([\w]+)#i',
                '#[\s]+[\s]+JOIN[\s]+([\w]+)#i',
                '#[\s]+TABLE[\s]+([\w]+)#i'
            );
            foreach($patterns as $pattern){
                preg_match_all($pattern,$query, $matches, PREG_SET_ORDER);
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
        self::$__parser_tables[$this->queryString] = $tables;
        return $tables;
    }

    /**
     * Get SQL Query method
     * @package Light-SQL-Parser-Class
     * @link https://github.com/marcocesarato/PHP-Light-SQL-Parser-Class
     *
     * Get all queries
     * @return array
     */
    protected function __parseQueries(){
        $queries = preg_replace('#\/\*[\s\S]*?\*\/#','', $this->queryString);
        $queries = preg_replace('#;(?:(?<=["\'];)|(?=["\']))#', '', $queries);
        $queries = preg_replace('#[\s]*UNION([\s]+ALL)?[\s]*#', ';', $queries);
        $queries = explode(';', $queries);
        return $queries;
    }
}