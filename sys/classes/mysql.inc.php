<?php

/**************************************************************\
 *     _____ _        _            _ _____ ___  ___ _____     *
 *    /  ___| |      (_)          | /  __ \|  \/  |/  ___|    *
 *    \ `--.| |_ _ __ _  __ _  ___| | /  \/| .  . |\ `--.     *
 *     `--. \ __| '__| |/ _` |/ _ \ | |    | |\/| | `--. \    *
 *    /\__/ / |_| |  | | (_| |  __/ | \__/\| |  | |/\__/ /    *
 *    \____/ \__|_|  |_|\__, |\___|_|\____/\_|  |_/\____/     *
 *                       __/ |                                *
 *                      |___/                >> StrigelCMS << *
 *                                                            *
 *   ## Info ##############################################   *
 *                                                            *
 *    Version: 1.0                                            *
 *    Greetings to BSG Memmingen and Herr Wetzstein           *
 *                                                            *
 *   ## Licence ###########################################   *
 *                                                            *
 *    Copyright: Klemens Schölhorn, 2008 - 2011               *
 *    Website: http://schoelhorn.eu                           *
 *    Licence: http://creativecommons.org/licenses/by/3.0/    *
 *    Any OpenSource licence would be nice for a remix!       *
 *                                                            *
\**************************************************************/

//Prevent direct access
if(!defined('SCMS')) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied.');
}
//--

define("SQL_ARRAY", 10);
define("SQL_ASSOC_ARRAY", 20);
define("SQL_BOTH", 30);
define("SQL_OBJECT", 40);

class mysql
{
	protected $host;
	protected $user;
	protected $pw;
	protected $database;
	
	protected $connection;
	protected $connected = false;
	protected $query_count = 0;
	protected $time = 0.0;
	
	protected $error_list = array();
	protected $error = false;
	
	public function __construct($host = null, $user = null, $pw = null, $database = null)
	{
		if(isset($host, $user, $pw, $database)) {
			if($this->set_connection_parameters($host, $user, $pw, $database)) {
				$this->connect();
			}
		}
	}
	
	public function __destruct()
	{
        if($this->error) {
            logit('class/db/destructor', implode(" - ", $this->error_list));
        }
	}
	
	public function set_connection_parameters($host, $user, $pw, $database = null)
	{
		if(isset($host, $user, $pw)) {
			$this->host = $host;
			$this->user = $user;
			$this->pw = $pw;
			
			if($database) {
				if($this->set_database($database))
					return true;
				else
					return false;
				
			}
		} else {
			$this->log_error("Not all parameters for database connection connection given.");
			return false;
		}
	}
	
	public function set_database($database)
	{
		if($database) {
			$this->database = $database;
			return true;
		}
	}
	
	public function connect()
	{
		if($this->connected) {
			$this->log_error("connect(): A connection has already been established.");
			return false;
		}
		
		if(isset($this->host, $this->user, $this->pw, $this->database)) {
			$this->connection = @mysql_connect($this->host, $this->user, $this->pw);
			
			if($this->connection === false) {
				$this->log_error("Connecting to database failed:\n".mysql_errno().":".mysql_error());
				return false;
			} else {
				if(mysql_select_db($this->database, $this->connection)) {
					$this->connected = true;
					return true;
				} else {
					$this->log_error("Could not select database. Wrong name?");
					return false;
				}
			}
		} else {
			$this->log_error("Please define host, user, pw and database before connecting.");
			return false;
		}
	}
		
	private function log_error($string)
	{
		if($string) {
			$this->error_list[] = $string;
			$this->error = true;
		}
	}
	
	public function get_last_error()
	{
		if(isset($this->error_list[count($this->error_list) - 1])) {
            return $this->error_list[count($this->error_list) - 1];
        } else {
            return false;
        }
	}
	
	public function get_all_error()
	{
        if(empty($this->error_list)) {
            return false;
        } else {
            return $this->error_list;
        }
	}
	
	public function connected()
	{
        return $this->connected;
	}
	
	public function query($query)
	{
		if($this->connected == false) {
			$this->log_error("Please connect to a database before doing a query.");
			return false;
		}
		
		$this->query_count++;
		return new mysql_do_query($query, $this->connection, $this);
	}
	
	public function qquery($query, $method = '')
	{
		if($this->connected == false) {
			$this->log_error("Please connect to a database before doing a query.");
			return false;
		}
		
		$this->query_count++;
		$q = new mysql_do_query($query, $this->connection, $this);
		if(!$q->success())
			return false;
		if($method)
			$result = $q->fetch($method);
		else
			$result = $q->fetch();
		
		$q = null;
		return $result;
	}
	
	public function execute($query)
	{
		if($this->connected == false) {
			$this->log_error("Please connect to a database before doing a query.");
			return false;
		}
		
		$this->query_count++;
		$q = new mysql_do_query($query, $this->connection, $this);
		$succ = $q->success();
		$q = null;
		return $succ;
	}
	
	public function get_count()
	{
		return $this->query_count;
	}
	
	public function status()
	{
		if($this->connected)
			return mysql_stat($this->connection);
		else
			return false;
	}
	
	public function escape($string)
	{
		return mysql_real_escape_string($string, $this->connection);
	}
	
	public function affected_rows()
	{
		return mysql_affected_rows($this->connection);
	}
	
	public function insert_id()
	{
		return mysql_insert_id($this->connection);
	}
	
	public function error()
	{
		return $this->error;
	}
	
	public function add_time($time)
	{
        $this->time += $time;
        return true;
	}
	
	public function get_time()
	{
        return round($this->time, 4);
	}
}

class mysql_do_query
{
    private $mysql;
	private $result_handler;
	private $connection;
	private $successed = false;
	private $time = 0.0;
	
	private $error_list = array();
	private $error = false;
	
	public function __construct($query, &$connection, &$mysql)
	{
        $time = microtime(true);
        
		if($query && $connection) {
            $this->mysql = &$mysql;
			$this->connection = &$connection;
			$this->result_handler = @mysql_query($query, $this->connection);
		} else {
			$this->log_error("Error with Query or MySQL connection.\nSee logs of connection class for more information");
			$this->successed = false;
			return false;
		}
		
		if(!$this->result_handler) {
			$this->log_error("Query (".$query.") failed:\n".mysql_errno($this->connection).":".mysql_error($this->connection));
			$this->successed = false;
			return false;
		}
		
		$this->successed = true;

		$this->mysql->add_time(round(microtime(true)-$time, 5));
		
		return true;
	}
	
	public function __destruct()
	{
        if($this->error) {
            logit('class/do_query/destructor', implode(" - ", $this->error_list));
        }
	}
	
	public function success()
	{
		return $this->successed;
	}
	
	public function fetch($method = 40)
	{
		switch($method) {
			case 10: //SQL_ARRAY
				return mysql_fetch_row($this->result_handler);
				break;
			case 20: //SQL_ASSOC_ARRAY
				return mysql_fetch_array($this->result_handler, MYSQL_ASSOC);
				break;
			case 30: //SQL_BOTH
				return mysql_fetch_array($this->result_handler, MYSQL_BOTH);
				break;
			case 40: //SQL_OBJECT
				return mysql_fetch_object($this->result_handler);
				break;
			default:
				$this->log_error('query-fetch: no such fetch-method: ' . $method);
				return false;
				break;
		}
	}
	
	public function num_rows()
	{
		if($this->result_handler)
			return mysql_num_rows($this->result_handler);
		else
			return false;
	}
	
	public function num_fields()
	{
		if($this->result_handler)
			return mysql_num_fields($this->result_handler);
		else
			return false;
	}
	
	public function affected_rows()
	{
		return mysql_affected_rows($this->connection);
	}
	
	private function log_error($string)
	{
		if($string) {
			$this->error_list[] = $string;
			$this->error = true;
		}
	}
	
	public function get_last_error()
	{
		if(isset($this->error_list[count($this->error_list) - 1])) {
            return $this->error_list[count($this->error_list) - 1];
        } else {
            return false;
        }
	}
	
	public function get_all_error()
	{
        if(empty($this->error_list)) {
            return false;
        } else {
            return $this->error_list;
        }
	}
	
	public function error()
	{
		return $this->error;
	}
}
