<?php

require_once __DIR__.'/DB.php';


class Api {
	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function get_tasks() {
		$tasks = [];
		$ret = $this->db->get_all_tasks();
		foreach ($ret as $t) {
			if (is_null($t['group_id'])) {
				$group_id = $t['id'];
				$group_name = 'ungrouped';
			}
			else {
				$group_id = $t['group_id'];
				$group_name = $t['group_name'];
			}


			if (isset($tasks[$group_id])) {
				array_push($tasks[$group_id]['tasks'], $t);
			}
			else {
				$tasks[$group_id] = [
					'id'			=> $group_id,
					'name'			=> $group_name,
					'tasks'			=> [ $t ]
				];
			}
		}
		return $tasks;
	}

}



if (isset($_GET['a'])) {
	$action = trim(htmlentities($_GET['a']), '_');
	$api = new Api($db);

	if (method_exists($api, $action)) {
		try {
			echo json_encode(call_user_func([$api, $action]));
		}
		catch (Exception $e) {
			echo json_encode([
				'result'	=> false
			]);
		}

		exit;
	}
}

header("HTTP/1.1 404 Not Found");

