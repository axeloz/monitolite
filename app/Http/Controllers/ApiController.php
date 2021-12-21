<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Task;
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

		$query = Task
			::leftJoin('groups', 'groups.id', 'tasks.group_id')
			->leftJoinSub(
				DB::table('task_history')
					->select('id', DB::raw('MAX(created_at) as created_at'), 'output', 'status', 'task_id')
					->groupBy('id')
					->groupBy('output')
					->groupBy('status')
					->groupBy('task_id')
					->groupBy('created_at')
				, 'task_history', function($join) {
				$join
					->on('task_history.task_id', '=', 'tasks.id')
				;
			})
			->select(
				'tasks.id', 'tasks.host', 'tasks.status', 'tasks.type', 'tasks.params', 'tasks.frequency', 'tasks.created_at', 'tasks.executed_at', 'tasks.active', 'tasks.group_id',
				'task_history.output',
				'groups.name as group_name')
			->get()
		;

		foreach ($query as $t) {
			if (is_null($t->group_id)) {
				$group_id = $t->id;
				$group_name = 'ungrouped';
			}
			else {
				$group_id = $t->group_id;
				$group_name = $t->group_name;
			}

			if (empty($tasks[$group_id])) {
				$tasks[$group_id] = [
					'id'			=> $group_id,
					'name'			=> $group_name,
					'tasks'			=> null
				];
			}
			$tasks[$group_id]['tasks'][$t->id] = $t;
		}

		return response()->json($tasks);
	}


	public function getTaskDetails($id) {

		$task = Task
			::leftJoin('groups', 'groups.id', 'tasks.group_id')
			->leftJoinSub(
				DB::table('task_history')
					->select('id', DB::raw('MAX(created_at) as created_at'), 'output', 'status', 'task_id')
					->groupBy('id')
				, 'task_history', function($join) {
				$join
					->on('task_history.task_id', '=', 'tasks.id')
				;
			})
			->select(
				'tasks.id', 'tasks.host', 'tasks.status', 'tasks.type', 'tasks.params', 'tasks.frequency', 'tasks.created_at', 'tasks.executed_at', 'tasks.active', 'tasks.group_id',
				'task_history.output',
				'groups.name as group_name')
			->findOrFail($id)
		;

		if (! is_null($task)) {
			return response()->json($task);
		}
	}

	public function toggleTaskStatus(Request $request, $id) {
		$active = $request->input('active', null);

		if (is_null($active)) {
			throw new ApiException('Invalid parameters');
		}
		$active = intval($active);

		$task = Task::findOrFail($id);
		$task->active	= $active;

		if ($task->save()) {
			return $this->getTaskDetails($id);
		}
		else {
			throw new ApiException('Cannot disable this task');
		}
	}
}

class ApiException extends Exception {}
