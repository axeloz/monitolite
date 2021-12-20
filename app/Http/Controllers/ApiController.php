<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//
	}


	public function getTasks() {
		$tasks = [];

		$query = DB::select('
			SELECT DISTINCT t.id, t.host, t.type, t.params, t.frequency, t.creation_date, t.last_execution, t.active, t.group_id, h.status, h.output, g.name as group_name
			FROM `tasks` as t
			LEFT JOIN `tasks_history` as h ON (h.task_id = t.id)
			LEFT JOIN `groups` as g ON (g.id = t.group_id)
			WHERE (t.last_execution IS NULL OR h.datetime = t.last_execution)
			ORDER BY group_name ASC
		');

		foreach ($query as $t) {
			if (is_null($t->group_id)) {
				$group_id = $t->id;
				$group_name = 'ungrouped';
			}
			else {
				$group_id = $t->group_id;
				$group_name = $t->group_name;
			}
			$tasks[$group_id]['tasks'][$t->id] = $t;
		}

		return response()->json($tasks);
	}


	public function getTaskDetails($id) {
		$query = DB::select('
			SELECT t.id, t.host, t.type, t.params, t.frequency, t.creation_date, t.last_execution, t.active, t.group_id, h.status, h.output, g.name as group_name
			FROM `tasks` as t
			LEFT JOIN `tasks_history` as h ON (h.task_id = t.id)
			LEFT JOIN `groups` as g ON (g.id = t.group_id)
			WHERE (t.last_execution IS NULL OR h.datetime = t.last_execution) AND t.id = :task_id
			LIMIT 1
		', [
			'task_id'   => $id
		]);

		if ($query) {
			foreach ($query as $q) {
				return response()->json($q);
			}
		}
	}

	public function toggleTaskStatus(Request $request, $id) {
		if($active = $request->input('active')) {
			//throw new ApiException('Invalid parameters');
		}

		$active = intval($active);

		$query = DB::update('
			UPDATE tasks
			SET active = :active
			WHERE id = :id
		', [
			'active'	=> $active,
			'id'    	=> $id
		]);

		if ($query !== false) {
			return $this->getTaskDetails($id);
		}
		else {
			throw new ApiException('Cannot disable this task');
		}
	}
}

class ApiException extends Exception {}
