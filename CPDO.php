<?php

/**
 * CPDO
 * Memory Cached PDO Class
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2018
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link https://github.com/marcocesarato/CPDO
 * @version 0.1.4.19
 */
class CPDO extends PDO
{
	/**
	 * CPDO constructor.
	 */
	function __construct($dsn, $username = "", $password = "", $driver_options = array()) {
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
	public static function connect($database_type, $database_name = null, $database_host = null, $database_user = null, $database_pswd = null) {

		if (empty($database_host) && in_array($database_type, array('mysql', 'pgsql', 'mssql', 'ibm', 'firebird', '4D', 'interbase', 'informix'))) {
			trigger_error("CDPO: Database host is empty", E_USER_WARNING);
		}
		if (empty($database_name) && in_array($database_name, array('mysql', 'pgsql', 'mssql', 'sqlite', 'oracle', 'ibm', 'firebird', 'interbase', 'informix'))) {
			trigger_error("CDPO: Database name is empty", E_USER_WARNING);
		}
		if (empty($database_user) && in_array($database_name, array('mysql', 'pgsql', 'mssql', 'ibm', '4D', 'informix'))) {
			trigger_error("CDPO: Database user is empty", E_USER_WARNING);
		}

		if ($database_type == 'mysql') {
			return new static('mysql:host=' . $database_host . ';dbname=' . $database_name, $database_user, $database_pswd);
		} elseif ($database_type == 'pgsql') {
			return new static('pgsql:host=' . $database_host . ';dbname=' . $database_name, $database_user, $database_pswd);
		} elseif ($database_type == 'mssql') {
			return new static('sqlsrv:Server=' . $database_host . ';Database=' . $database_name, $database_user, $database_pswd);
		} elseif ($database_type == 'sqlite') {
			return new static('sqlite:/' . $database_name);
		} elseif ($database_type == 'oracle') {
			return new static('oci:dbname=' . $database_name);
		} elseif ($database_type == 'ibm') {
			return new static('ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=' . $database_name . ';HOSTNAME=' . $database_host . ';PROTOCOL=TCPIP;', $database_user, $database_pswd);
		} elseif (($database_type == 'firebird') || ($database_type == 'interbase')) {
			return new static('firebird:dbname=' . $database_name . ';host=' . $database_host);
		} elseif ($database_type == '4D') {
			return new static('4D:host=' . $database_host, $database_user, $database_pswd);
		} elseif ($database_type == 'informix') {
			return new static('informix:host=' . $database_host . '; database=' . $database_name . '; server=' . $database_host, $database_user, $database_pswd);
		} elseif (empty($database_type)) {
			trigger_error("CDPO: Database type is empty!", E_USER_ERROR);
			return false;
		}
		trigger_error("CDPO: Database type `" . htmlentities($database_type) . "` is not supported yet", E_USER_ERROR);
		return false;
	}

	/**
	 * @param string $statement
	 * @return bool|int|null
	 */
	public function exec($statement) {
		$method = CPDOCache::parseMethod($statement);
		if (in_array($method, CPDOCache::$actions['archive'])) {
			$cache = CPDOCache::getcache($statement);
			if (empty($cache)) {
				$result = parent::exec($statement);
				CPDOCache::setcache($statement, $result, 'exec');
				return $result;
			}
			return $cache;
		} elseif (in_array($method, CPDOCache::$actions['delete'])) {
			CPDOCache::deletecache($statement);
		}
		return parent::exec($statement);
	}

	/**
	 * @param string $statement
	 * @param int $mode
	 * @param null $arg3
	 * @param array $ctorargs
	 * @return array|bool|null|PDOStatement
	 */
	public function query($statement, $mode = null, $arg3 = null, $ctorargs = null) {

		$method = CPDOCache::parseMethod($statement);

		if (in_array($method, CPDOCache::$actions['archive'])) {
			$cache = CPDOCache::getcache($statement);
			if (!empty($cache))
				return $cache;
		} elseif (in_array($method, CPDOCache::$actions['delete'])) {
			CPDOCache::deletecache($statement);
		}

		if (!empty($ctorargs)) {
			if (empty($mode)) $mode = PDO::ATTR_DEFAULT_FETCH_MODE;
			$result = parent::query($statement, $mode, $arg3, $ctorargs);
		} elseif (!empty($arg3)) {
			if (empty($mode)) $mode = PDO::ATTR_DEFAULT_FETCH_MODE;
			$result = parent::query($statement, $mode, $arg3);
		} elseif (!empty($mode))
			$result = parent::query($statement, $mode);
		else
			$result = parent::query($statement);

		if (in_array($method, CPDOCache::$actions['archive']))
			CPDOCache::setcache($statement, $result, 'query' . $mode);

		return $result;
	}

}

/**
 * Class CPDOStatement
 */
class CPDOStatement extends PDOStatement
{

	public $queryString;

	/**
	 * @param null $input_parameters
	 * @return bool
	 */
	public function execute($input_parameters = null) {
		$method = CPDOCache::parseMethod($this->queryString);
		if (in_array($method, CPDOCache::$actions['archive'])) {
			$cache = CPDOCache::getcache($this->queryString);
			if (empty($cache)) {
				$cache = CPDOCache::getcache($this->queryString);
				if (empty($cache)) {
					if (!empty($input_parameters))
						return parent::execute($input_parameters);
					return parent::execute();
				}
			}
			return $cache;
		} elseif (in_array($method, CPDOCache::$actions['delete'])) {
			CPDOCache::deletecache($this->queryString);
		}
		return parent::execute($input_parameters);
	}

	/**
	 * @param null $fetch_style
	 * @param int $cursor_orientation
	 * @param int $cursor_offset
	 * @return mixed|null
	 */
	public function fetch($fetch_style = null, $cursor_orientation = null, $cursor_offset = null) {
		$cache = CPDOCache::getcache($this->queryString, $fetch_style);
		if (empty($cache)) {
			if (!empty($cursor_offset)) {
				if (empty($cursor_orientation)) $cursor_orientation = PDO::FETCH_ORI_NEXT;
				$result = parent::fetch($fetch_style, $cursor_orientation, $cursor_offset);
			} elseif (!empty($cursor_orientation))
				$result = parent::fetch($fetch_style, $cursor_orientation);
			else
				$result = parent::fetch($fetch_style);

			CPDOCache::setcache($this->queryString, $result, 'fetch' . $fetch_style);
			return $result;
		}
		return $cache;
	}

	/**
	 * @param null $fetch_style
	 * @param null $fetch_argument
	 * @param array $ctor_args
	 * @return array|null
	 */
	public function fetchAll($fetch_style = null, $fetch_argument = null, $ctor_args = null) {
		$cache = CPDOCache::getcache($this->queryString, $fetch_style);
		if (empty($cache)) {

			if (!empty($ctor_args))
				$result = parent::fetchAll($fetch_style, $fetch_argument, $ctor_args);
			elseif (!empty($fetch_argument))
				$result = parent::fetchAll($fetch_style, $fetch_argument);
			else
				$result = parent::fetchAll($fetch_style);

			CPDOCache::setcache($this->queryString, $result, 'fetchAll' . $fetch_style);
			return $result;
		}
		return $cache;
	}

	/**
	 * @param string $class_name
	 * @param array $ctor_args
	 * @return mixed|null
	 */
	public function fetchObject($class_name = "stdClass", $ctor_args = null) {
		$cache = CPDOCache::getcache($this->queryString, $class_name);

		if (empty($cache)) {
			if (!empty($ctor_args))
				$result = parent::fetchAll($class_name, $ctor_args);
			else
				$result = parent::fetchObject($class_name);

			CPDOCache::setcache($this->queryString, $result, $class_name);
			return $result;
		}
		return $cache;
	}

	/**
	 * @param int $column_number
	 * @return mixed|null
	 */
	public function fetchColumn($column_number = 0) {
		$cache = CPDOCache::getcache($this->queryString, $column_number);
		if (empty($cache)) {
			$result = parent::fetchColumn($column_number);
			CPDOCache::setcache($this->queryString, $result, $column_number);
			return $result;
		}
		return $cache;
	}
}

class CPDOCache
{

	public static $actions = array(
		'archive' => array('SELECT', 'SHOW', 'DESCRIBE'),
		'delete' => array('INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER')
	);

	protected static $__cache = array();

	// From Light SQL Parser Class
	protected static $__parser_method = array();
	protected static $__parser_tables = array();

	/**
	 * Populate cache
	 * @param $mixed
	 * @return bool
	 */
	public static function populate($cache){
		if(is_array($cache))
			self::$__cache = $cache;
	}

	/**
	 * Retrieve Cache
	 * @return array
	 */
	public static function retrieve(){
		return self::$__cache;
	}

	/**
	 * Set Cache
	 * @param $query
	 * @param $value
	 * @param null $arg
	 */
	public static function setcache($query, $value, $arg = null) {
		$e = new \Exception();
		$trace = $e->getTrace();
		$function = $trace[1]['function'];

		$table = self::keycache($query);
		self::$__cache[$table][$query][$function][$arg] = $value;
	}

	/**
	 * Get Cache
	 * @param $query
	 * @param null $arg
	 * @return null
	 */
	public static function getcache($query, $arg = null) {
		$e = new \Exception();
		$trace = $e->getTrace();
		$function = $trace[1]['function'];

		$table = self::keycache($query);
		if (isset(self::$__cache[$table][$query][$function][$arg]))
			return self::$__cache[$table][$query][$function][$arg];
		return null;
	}

	/**
	 * Delete Cache
	 * @param $query
	 */
	public static function deletecache($query) {
		$tables = self::parseTables($query);
		foreach (array_keys(self::$__cache) as $key) {
			foreach ($tables as $table) {
				if (strpos($key, $table) !== false) {
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
	protected static function keycache($query) {
		$tables = self::parseTables($query);
		return implode('/', $tables);
	}

	/**
	 * Get SQL Query method
	 * @package Light-SQL-Parser-Class
	 * @link https://github.com/marcocesarato/PHP-Light-SQL-Parser-Class
	 *
	 * @param $query
	 * @return mixed|string
	 */
	public static function parseMethod($query) {

		if (!empty(self::$__parser_method[$query]))
			return self::$__parser_method[$query];

		$methods = array('SELECT', 'INSERT', 'UPDATE', 'DELETE', 'RENAME', 'SHOW', 'SET', 'DROP', 'CREATE INDEX', 'CREATE TABLE', 'EXPLAIN', 'DESCRIBE', 'TRUNCATE', 'ALTER');
		$queries = self::parseQueries($query);
		foreach ($queries as $query) {
			foreach ($methods as $method) {
				$_method = str_replace(' ', '[\s]+', $method);
				if (preg_match('#^[\s]*' . $_method . '[\s]+#i', $query)) {
					self::$__parser_method[$query] = $method;
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
	 * @param $_query
	 * @return array|mixed
	 */
	public static function parseTables($_query) {

		$connectors = "OR|AND|ON|LIMIT|WHERE|JOIN|GROUP|ORDER|OPTION|LEFT|INNER|RIGHT|OUTER|SET|HAVING|VALUES|SELECT|\(|\)";

		if (!empty(self::$__parser_tables[$_query]))
			return self::$__parser_tables[$_query];

		$results = array();
		$queries = self::parseQueries($_query);
		foreach ($queries as $query) {
			$patterns = array(
				'#[\s]+FROM[\s]+(([\s]*(?!' . $connectors . ')[\w]+([\s]+(AS[\s]+)?(?!' . $connectors . ')[\w]+)?[\s]*[,]?)+)#i',
				'#[\s]*INSERT[\s]+INTO[\s]+([\w]+)#i',
				'#[\s]*UPDATE[\s]+([\w]+)#i',
				'#[\s]+[\s]+JOIN[\s]+([\w]+)#i',
				'#[\s]+TABLE[\s]+([\w]+)#i'
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
		self::$__parser_tables[$_query] = $tables;
		return $tables;
	}

	/**
	 * Get SQL Query method
	 * @package Light-SQL-Parser-Class
	 * @link https://github.com/marcocesarato/PHP-Light-SQL-Parser-Class
	 *
	 * Get all queries
	 * @param $query
	 * @return array|null|string|string[]
	 */
	public static function parseQueries($query) {
		$queries = preg_replace('#\/\*[\s\S]*?\*\/#', '', $query);
		$queries = preg_replace('#;(?:(?<=["\'];)|(?=["\']))#', '', $queries);
		$queries = preg_replace('#[\s]*UNION([\s]+ALL)?[\s]*#', ';', $queries);
		$queries = explode(';', $queries);
		return $queries;
	}
}