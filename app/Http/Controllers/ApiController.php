<?php

namespace App\Http\Controllers;

use Exception;
use \Carbon\Carbon;
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
			->select(
				'tasks.id', 'tasks.host', 'tasks.status', 'tasks.type', 'tasks.params', 'tasks.frequency', 'tasks.created_at', 'tasks.executed_at', 'tasks.active', 'tasks.group_id',
				'groups.name as group_name')
			->get()
		;

		//dd($query->toSql());

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
			->findOrFail($id)
		;

		if (! is_null($task)) {
			// First, we get the first date of the stats
			// In this case, one month ago
			$first_day = Carbon::now()->startOfDay()->subDays($days);
			// Then we get all history for the past month
			$history = $task
				->history()
				->orderBy('created_at', 'desc')
				->where('created_at', '>', $first_day->toDateString())
				->selectRaw('id, date(created_at) as date, created_at, status, duration, output')
				->get()
			;

			// Then we start building an array for the entire month
			$stats = $times = [];
			$tmpdate = Carbon::now()->subDays($days);
			do {
				$stats['uptime'][$tmpdate->toDateString()] = [
					'up'	=> 0,
					'down'	=> 0
				];

				$stats['times'][$tmpdate->toDateString()] = [
					'duration'	=> 0,
					'count'		=> 0
				];

				$tmpdate = $tmpdate->addDay();
			}
			while ($tmpdate->lt(Carbon::now()));

			// Then we populate the stats data
			$prev = null;
			if (! is_null($history)) {
				$history = $history->reverse();

				foreach ($history as $k => $r) {
					if (empty($stats['uptime'][$r->date])) {
						$stats['uptime'][$r->date] = [
							'up'	=> 0,
							'down'	=> 0
						];
					}

					// Populating the stats
					if ($r->status == 1) {
						++$stats['uptime'][$r->date]['up'];
					}
					else {
						++$stats['uptime'][$r->date]['down'];
					}

					// Populating the response times
					if ($r->status == 1 && $r->duration > 0) {
						$stats['times'][$r->date]['duration'] += $r->duration;
						$stats['times'][$r->date]['count'] ++;
					}

					// We only take tasks when status has changed between them
					if (! is_null($prev) && $r->status == $prev) {
						unset($history[$k]);
					}
					$prev = $r->status;
				}
			}

			// Getting the notifications sent
			$notifications = $task
				->notifications()
				->with(['contact', 'task_history'])
				->where('notifications.created_at', '>', $first_day->toDateString())
				->orderBy('notifications.created_at', 'desc')
				->get()
			;

			return response()->json([
				'task'				=> $task,
				'stats'				=> $stats,
				'history'			=> $history,
				'notifications'		=> $notifications,
				'first_day'			=> $first_day->toDateTimeString()
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
			return response()->json($task);
		}
		else {
			throw new ApiException('Cannot disable this task');
		}
	}
}

class ApiException extends Exception {}
