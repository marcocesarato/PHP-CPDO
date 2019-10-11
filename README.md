# CPDO - Cache PDO Query Class

**Version:** 0.2.3.46 beta

**Github:** https://github.com/marcocesarato/PHP-CPDO 

**Author:** Marco Cesarato

## Description

This package can retrieve PDO query results from cache variables.

It extends the base PDO class and override some functions to handle database query execution and store the query results in variables.

The class can also return query results for cached queries for previously executed queries to retrieve the results faster for repeated queries.

It permit to have cached `SELECT/SHOW/DESCRIBE` queries on Memory (RAM). Then after the execution the cache will be deleted.

Cache is cleaned on `INSERT/UPDATE/DELETE/TRUNCATE...` only for the single table.

## What problem this solves
When we call the same query (for example on ORM based system) we retrieve from the database the same data doing the same operation and some time we overload the database server (for example retrieving big data multiple times).

This class prevent to do the same query on the database, retrieving the data from memory without overload the database server in some cases.

### Requirements
* PHP
* Database

## Databases supported (`CPDO::connect`)
* 4D
* CUBRID
* Firebird/Interbase
* IBM
* Informix
* MS SQL Server
* MySQL
* ODBC and DB2
* Oracle
* PostgreSQL
* SQLite

## Install
### Composer
1. Install composer
2. Type `composer require marcocesarato/cpdo`
4. Enjoy

## Usage

You have to use this class as PDO.

CPDO introduced these new methods:

### Method `connect`

This feature is a DSN autogeneration and you have to use it instead of the constructor.

#### Usage of `connect`

```php
$db = CPDO::connect($database_type, $database_name, $database_host = null, $database_user = null, $database_pswd = null);
```

### Method `getTables`

Return the names of all database tables as `array`

### Method `backup`

You can backup **Data** (At the moment no TRIGGERS, VIEWS and EVENTS _if you need it you can request it to the developer_).
You can choose if you want the tables you want backup through an `array`.

#### Usage of `backup`

```php
$db->backup($backup_dir, $backup_tables = '*');
```

## Cache 
You can disable/enable the cache using the following methods (default is *disabled*):
```php
$db->disableCache();
$db->enableCache();
```
or
```php
CPDOCache::disable();
CPDOCache::enable();
```

### Methods available:
- `void CPDOCache::enable()`  ENABLE Cache
- `void CPDOCache::disable()` DISABLE Cache
- `void CPDOCache::populate(array $cache)` Populate cache (see below persistent cache)
- `array CPDOCache::retrieve()` Retrieve cache (see below persistent cache)
- `void CPDOCache::addException(string $table_name)` Add not cacheable table
- `void CPDOCache::addExceptions(array $tables_name)` Add not cacheable tables

## Debugger
You can enable/disable the debugger using the following methods (default is *enabled*):
```php
$db->enableDebug();
$db->disableDebug();
```
or
```php
CPDOLogger::enable();
CPDOLogger::disable();
```

### Methods available:
- `void CPDOLogger::enable()`  ENABLE Cache
- `void CPDOLogger::disable()` DISABLE Cache
- `array CPDOLogger::getLogs();` Get complete logs (with time of execution and if cache used)
- `array CPDOLogger::getQueries()` Get all queries requested
- `int CPDOLogger::getCounter()` Get the counter of queries requested
- `void CPDOLogger::cleanLogs();` Clean Logs

### Example of complete logs `getLogs()`
```php
array (
  'count' => 3,
  'queries' => 
  array (
    'SET NAMES \'utf8\'' => 
        array (
          0 => 
          array (
            'time' => 1530610903,
            'execution_time' => 0.000247955322265625,
            'cached' => false,
          ),
          1 => 
            array (
              'time' => 1530610903,
              'execution_time' => 0.000077955322265625,
              'cached' => false,
            ),
        ),
    'SELECT id FROM _deleted_records_ WHERE table = \'settings\' LIMIT 1' => 
        array (
          0 => 
          array (
            'time' => 1530610903,
            'execution_time' => 0.00050687789916992188,
            'cached' => false,
          ),
        ),
  ),
)
```

## (Not recommended) Persistent cache
*PS: this usage is not recommended!!!*

If you want a persitent you can use the method `CPDOCache::populate` for populate the cache and `CPDOCache::retrieve` for retrieve the cache.

Thanks these methods you could implement a persistent cache system saving the data encoded (with json or serialization) and after restore the cache.

Pro:
- Less database stress
- Less queries

Cons:
- Could compromise data
- Could be slower (disk performance/clients connected)

### Example of usage
```php

// Your loader/includes... => with require_once('CPDO.php');

$database_cache_path = '/your/cache/path/database.json';

$cache = file_get_contents($database_cache_path);
$cache = json_decode($cache); // Or unserialize (slower)
CPDOCache::populate($cache);
unset($cache);


// Your code...


$cache = CPDOCache::retrieve();
$cache = json_encode($cache); // Or serialize (slower)
file_put_contents($database_cache_path, $cache);
unset($cache);
```

## Methods


### CPDO

Same methods of PDO in additions the following:

| Methods      | Parameters                                         | Description                            |
| ------------ | -------------------------------------------------- | -------------------------------------- |
| connect      | 	  param $database_type<br>	  param null $database_name<br>	  param null $database_host<br>	  param null $database_user<br>	  param null $database_pswd<br> param null $database_charset<br>	  return bool\|static | Autogeneration of the DSN              |
| enableDebug  |                                                    | Enable debug                           |
| disableDebug |                                                    | Disable debug                          |
| enableCache  |                                                    | Enable cache                           |
| disableCache |                                                    | Disable cache                          |
| getTables    | 	  return array\|bool                              | Get list of database tables            |
| backup       | 	  param string $tables                            | Backup database tables or just a table |
| escape       | 	  param string $value                             | Escape variable                        |

### CPDOLogger

| Methods      | Parameters                                         | Description                            |
| ------------ | -------------------------------------------------- | -------------------------------------- |
| enable       |                                                    | Enable logs                            |
| disable      |                                                    | Disable logs                           |
| isEnabled    | 	  return bool                                     | Return if logs are enabled             |
| addLog       | 	  param $query<br>	  param $time<br>	  param $cache | Add new log                            |
| getLogs      | 	  return array                                    | Get Logs                               |
| getCounter   | 	  return int                                      | Get Counter                            |
| getQueries   | 	  return array                                    | Get Counter                            |
| cleanLogs    |                                                    | Clean Logs                             |

### CPDOCache

| Methods             | Parameters                                         | Description                            |
| ------------------- | -------------------------------------------------- | -------------------------------------- |
| disable             |                                                    | Disable cache                          |
| isEnabled           | 	  return bool                                     | Return if cache is enabled             |
| populate            | 	  param $mixed<br>	  return bool                  | Populate cache                         |
| retrieve            | 	  return array                                    | Retrieve Cache                         |
| getExceptions       | 	  return array                                    | Get all excluded tables                |
| addException        | 	  param $exclude                                  | Add exception                          |
| addExceptions       | 	  param $exclude                                  | Add exceptions                         |
| setcache            | 	  param $query<br>	  param $value<br>	  param null $arg | Set Cache                              |
| getcache            | 	  param $query<br>	  param null $arg<br>	  return null | Get Cache                              |
| deletecache         | 	  param $query                                    | Delete Cache                           |