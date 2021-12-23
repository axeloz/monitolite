<?php

namespace App\Http\Controllers;

use Exception;
use \Carbon\Carbon;
use App\Models\Task;
use App\Models\TaskHistory;
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

	public function getTaskDetails(Request $request, $id) {
		$days = ($request->input('days', 15) - 1);

		$task = Task::with(['group'])
			->find($id)
		;

		if (! is_null($task)) {
			// First, we get the first date of the stats
			// In this case, one month ago
			$date = $last_days = Carbon::now()->subDays($days);
			// Then we get all history for the past month
			$history = $task
				->history()
				->orderBy('created_at', 'asc')
				->where('created_at', '>', $last_days->toDateString())
				->selectRaw('date(created_at) as date, status')
				->get()
			;

			// Then we start building an array for the entire month
			$stats = [];
			do {
				$stats[$date->toDateString()] = [
					'up'	=> 0,
					'down'	=> 0
				];
				$date = $date->addDay();
			}
			while ($date->lt(Carbon::now()));

			// Then we populate the stats data
			if (! is_null($history)) {
				foreach ($history as $r) {
					if (empty($stats[$r->date])) {
						$stats[$r->date] = [
							'up'	=> 0,
							'down'	=> 0
						];
					}

					if ($r->status == 1) {
						++$stats[$r->date]['up'];
					}
					else {
						++$stats[$r->date]['down'];
					}
				}
			}

			// Then we populate the history data
			if (! is_null($history)) {
				$prev = null;

				foreach ($history as $k => $h) {
					// We only take tasks when status has changed between them
					if ($h->status == $prev) {
						unset($history[$k]);
					}
					$prev = $h->status;
				}
			}

			return response()->json([
				'task'		=> $task,
				'stats'		=> $stats,
				'history'	=> $history
			]);
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
