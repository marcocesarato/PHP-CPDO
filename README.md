# CPDO - Cache PDO Query Class

Version: 0.1

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

## Usage
You can use this class as PDO.

The only one new feature implemented in this class is the DSN autogeneration if you use the method 

`CPDO::connect($database_type, $database_name, $database_host = null, $database_user = null, $database_pswd = null)` 

instead use the constructor.