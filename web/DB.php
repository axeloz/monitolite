<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(realpath('..'));
$dotenv->load();
$dotenv->required(['DB_TYPE', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);


class DB {
	private $link;
	
	
	public function __construct() {
		if (! is_resource($this->link)) {
			$dsn = $_ENV['DB_TYPE'].':dbname='.$_ENV['DB_NAME'].';host='.$_ENV['DB_HOST'].';port='.$_ENV['DB_PORT'];
			$this->link = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
		}
	}
	
	function get_all_contacts($task = null) {
		$query = '
			SELECT c.id, c.surname, c.firstname, c.email, c.phone, c.creation_date, c.active 
			FROM contacts c 
			LEFT JOIN notifications as n ON (n.contact_id = c.id)
			LEFT JOIN tasks as t ON (n.task_id = t.id)
		';
		
		if (! is_null($task)) {
			$query .= ' WHERE t.id = '.$task;
		}
		return $this->query($query);
	}


	function get_all_tasks() {
		$query = '
			SELECT id, host, type, params, creation_date, frequency, last_execution, active 
			FROM tasks
		';
		return $this->query($query);
	}

	function get_all_history($task = null, $limit = 100) {
		$args = [
			':limit'	=> $limit
		];
		
		$query = '
			SELECT id, status, datetime, task_id 
			FROM tasks_history
		';
		
		if (! is_null($task)) {
			$query .= ' WHERE task_id = :task_id';
			$args[':task_id'] = $task;
		}
		$query .= ' ORDER BY datetime DESC LIMIT :limit ';
		
		return $this->query($query, $args);
	}
	
	function get_task_last_status($task) {
		$result = $this->query('SELECT t.id, th.status FROM tasks_history th JOIN tasks t ON (th.task_id = t.id) WHERE t.id = :task ORDER BY datetime DESC LIMIT 1', [':task' => $task]);
		foreach ($result as $r) {
			return $r['status'];
		}
	}


	function query($query, $args = null) {

		if (! $result = $this->link->prepare($query)) {
			throw new DatabaseException('Cannot prepare query ('.$query.') for execution');
		}
		
		if (! is_null($args)) {
			foreach ($args as $n => $v) {
				$type = gettype($v);
				
				switch ($type) {
				
					case 'boolean':
						$cast = PDO::PARAM_BOOL;
					break;
					
					case 'integer':
						$cast = PDO::PARAM_INT;
					break;
					
					case 'null':
						$cast = PDO::PARAM_NULL;
					break;
					
					case 'double':
					case 'string':
					default:
						$cast = PDO::PARAM_STR;
				
				}
				$result->bindValue($n, $v, $cast);
			}
		}
		
		
		if (! $result->execute()) {
			throw new DatabaseException($result->errorInfo()[2]. ' in ('.$query.')');
		}
		
		return $result->fetchAll();
	}
}

$db = new DB;



class DatabaseException extends Exception {}


?>
