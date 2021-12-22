<?php

namespace App\Console\Commands;

use \Exception;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\Notification;
use Illuminate\Console\Command;

class RunMonitoring extends Command
{
	private $limit = 50;
	private $max_tries = 3;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitolite:run
				{--limit=50 : the number of tasks to handle in one run}
				{--task= : the ID of an individual task to handle}
				{--force : handles tasks even if they are pending}
	';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes all the monitoring tasks';

	/**
	 * Storing all the results for output
	 */
	private $results;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		$count = 0;
		$limit = $this->option('limit') ?? $this->limit;
		$this->max_tries = env('NB_TRIES', $this->max_tries);

		// If a force has been asked via command line
		$force = false;
		if (! empty($this->option('force'))) {
			if (empty($this->option('task'))) {
				if ($this->confirm('You asked me to force the execution (--force) but you did not specify a particular task ID (--task). I might have to handle a large amount of tasks. Are you sure?')) {
					$force = true;
				}
			}
			else {
				$force = true;
			}
		}

		// Getting pending tasks
		$tasks = Task::where(function($query) use ($force) {
				$query->whereRaw('DATE_SUB(NOW(), INTERVAL frequency SECOND) > executed_at');
				$query->orWhereBetween('attempts', [1, ($this->max_tries - 1)]);
				$query->orWhereNull('executed_at');

				if ($force === true) {
					$query->orWhere('id', '>', 0);
				}
			})
			->where('active', 1)
			->orderBy('attempts', 'DESC')
			->orderBy('executed_at', 'ASC')
			->take($limit)
		;


		// If a particular task has been set via the command line
		if (! empty($this->option('task'))) {
			$tasks = $tasks->where('id', '=', $this->option('task'));
		}

		// Now getting tasks
		$tasks = $tasks->get();

		if (is_null($tasks) || count($tasks) == 0) {
			$this->info('No task to process, going back to sleep');
			return true;
		}

		$this->info('I have '.count($tasks).' tasks to process. Better get started ...');

		$this->newLine();
		$bar = $this->output->createProgressBar(count($tasks));
		$bar->start();

		foreach ($tasks as $task) {
			$bar->advance();

			// Getting current task last status
			$previous_status = $task->status;

			try {
				switch ($task->type) {
					case 'ping':
						$new_status = $this->checkPing($task);
					break;

					case 'http':
						$new_status = $this->checkHttp($task);
					break;

					default:
						// Nothing to do here
						continue 2;
				}

				$history = $this->saveHistory($task, true);
			}
			catch(MonitoringException $e) {
				$history = $this->saveHistory($task, false, $e->getMessage());
			}
			catch(Exception $e) {
				$history = $this->saveHistory($task, false, $e->getMessage());
			}
			finally {
				// Changing task timestamps and status
				$task->executed_at		= $history->created_at; # Using the same timestamp as the task history
				$task->attempts			= $history->status == 1 ? 0 : $task->attempts + 1; # when success, resetting counter
				/**
				 * We don't want to change the primary status in the task table
				 * as long as failed tasks have reached the max tries limit
				 * In the cast of a success, we can change the status straight away
				 */
				if ($history->status == 0 && $task->attempts >= $this->max_tries) {
					$task->status			= 0;
				}
				else if ($history->status === 1) {
					$task->status			= 1;
				}

				if (! $task->save()) {
					throw new Exception('Cannot save task details');
				}


				// Task status has changed
				// But not from null (new task)
				if (! is_null($previous_status) && $task->status != $previous_status) {
					// If host is up, no double-check
					if ($task->status == 1 || ($task->status == 0 && $task->attempts == $this->max_tries)) {
						Notification::addNotificationTask($history);
					}
				}
			}
		}
		$bar->finish();
		$this->newLine(2);

		if (!empty($this->results)) {
			$this->table(
				['ID', 'Host', 'Type', 'Result', 'Attempts', 'Message'],
				$this->results
			);
		}
    }

	final private function saveHistory(Task $task, $status, $output = null) {
		$date = date('Y-m-d H:i:s');

		// Inserting new history
		$insert 			= new TaskHistory;
		$insert->status		= $status === true ? 1 : 0;
		$insert->created_at	= $date;
		$insert->output		= $output ?? '';
		$insert->task_id	= $task->id;
		if (! $insert->save()) {
			throw new Exception('Cannot insert history for task #'.$task->id);
		}

		$this->results[] = [
			'id'		=> $task->id,
			'host'		=> $task->host,
			'type'		=> $task->type,
			'result'	=> $status === true ? 'OK' : 'FAILED',
			'attempts'	=> $task->attempts,
			'message'	=> $output
		];


		return  $insert;
	}

	final private function checkPing(Task $task) {
		if (! function_exists('exec') || ! is_callable('exec')) {
			throw new MonitoringException('The "exec" command is required');
		}

		// Different command line for different OS
		switch (strtolower(php_uname('s'))) {
			case 'darmin':
				$cmd = 'ping -n 1 -t 5';
			break;
			case 'windows':
				$cmd = 'ping /n 1 /w 5';
			break;
			case 'linux':
			case 'freebsd':
			default:
				$cmd = 'ping -c 1 -W 5';
			break;
		}

		// If command failed
		if (false === $exec = exec($cmd.' '.$task->host, $output, $code)) {
			throw new MonitoringException('Unable to execute ping command');
		}

		// If command returned a non-zero code
		if ($code > 0) {
			throw new MonitoringException('Ping task failed ('.$exec.')');
		}

		// Double check
		$output = implode(' ', $output);
		// Looking for the 100% package loss output
		if (preg_match('~([0-9]{1,3})\.[0-9]{0,2}% +(packet)? +loss~', $output, $matches)) {
			if (! empty($matches[1])) {
				if (floatval($matches[1]) == 100) {
					throw new MonitoringException('Packet loss detected ('.($matches[0] ?? 'n/a').')');
				}
			}
		}
		// Else everything is fine
		return true;
	}

	final private function checkHttp(Task $task) {
		if (app()->environment() == 'local') {
			throw new MonitoringException('Forcing error for testing');
		}

		// Preparing cURL
		$opts = [
			CURLOPT_HTTPGET					=> true,
			CURLOPT_FRESH_CONNECT			=> true,
			CURLOPT_PROTOCOLS				=> CURLPROTO_HTTP | CURLPROTO_HTTPS,
			CURLOPT_SSL_VERIFYHOST			=> 2,
			CURLOPT_RETURNTRANSFER			=> true,
			CURLOPT_FOLLOWLOCATION			=> true,
			CURLOPT_MAXREDIRS				=> 3,
			CURLOPT_FAILONERROR				=> true,
			CURLOPT_CONNECTTIMEOUT			=> 10,
			CURLOPT_URL						=> trim($task->host)
		];

		$ch = curl_init();
		curl_setopt_array($ch, $opts);
		if ($result = curl_exec($ch)) {

			// We have nothing to check into the page
			// So for me, this is a big YES
			if (empty($task->params)) {
				return true;
			}
			// We are looking for a string in the page
			else {
				if (strpos($result, $task->params) !== false) {
					return true;
				}
				else {
					throw new MonitoringException('Cannot find the required string into the page');
				}
			}
		}
		throw new MonitoringException(curl_error($ch), curl_errno($ch));
	}
}

class MonitoringException extends Exception {}

