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


	public function getTaskGraph(Request $request, $id) {
		$days = ($request->input('days', 15) - 1);

		// First, we get the first date of the stats
		// In this case, one month ago
		$date = $last_days = Carbon::now()->subDays($days);

		// Then we get all history for the past month
		$results = TaskHistory::orderBy('created_at', 'asc')
			->where('created_at', '>', $last_days->toDateString())
			->where('task_id', '=', $id)
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

		// Finally we populate the data
		if (! is_null($results)) {
			foreach ($results as $r) {
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
		return response()->json($stats);
	}


	public function getTaskDetails(Request $request, $id) {
		$days = ($request->input('days', 15) - 1);

		$task = Task::with(['group', 'history'])
			->find($id)
		;

		if (! is_null($task)) {

			$results = $task
				->history()
				->orderBy('created_at', 'desc')
				->where('created_at', '>', \Carbon\Carbon::now()->subDay($days)->toDateString())
				->selectRaw('date(created_at) as `date`, created_at, status, output')
				->get()
			;

			if (! is_null($results)) {
				$prev = null;
				$history = $averages = [];

				foreach ($results as $h) {
					if ($h->status != $prev) {
						array_push($history, $h);
					}
					$prev = $h->status;

					if (empty($averages[$h->date])) {
						$averages[$h->date] = [
							'sum'	=> 0,
							'count'	=> 0
						];
					}
					if ($h->status == 1) {
						$averages[$h->date]['sum'] ++;
					}
					$averages[$h->date]['count'] ++;
				}
			}

			return response()->json(array_merge($task->toArray(), [
				$task,
				'id'		=> $task->id,
				'host'		=> $task->host,
				'status'	=> $task->status,
				'type'		=> $task->type,
				'history' 	=> $history,
				'averages'	=> $averages,
				'group'		=> $task->group
			]));
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
