<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use \Exception;
use Illuminate\Queue\Console\MonitorCommand;

class RunMonitoring extends Command
{
	private $rounds = 50;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitolite:monitoring:run {rounds?}';

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
		$rounds = $this->argument('rounds') ?? $this->rounds;

		// Getting pending tasks
		$tasks = DB::table('tasks')
			->where(function($query) {
				$query->whereRaw('DATE_SUB('.time().', INTERVAL frequency SECOND) > last_execution');
				$query->orWhereNull('last_execution');
			})
			->where('active', 1)
			->orderBy('last_execution', 'ASC')
			->take($rounds)
			->get()
		;

		if (is_null($tasks) || count($tasks) == 0) {
			$this->info('No task to process, going back to sleep');
			return true;
		}

		$this->info('I have '.count($tasks).' tasks to process. Better get started ...');

		$this->newLine();
		$bar = $this->output->createProgressBar(count($tasks));
		$bar->start();

		foreach ($tasks as $task) {
			$last_status = $new_status = $output = null;
			$bar->advance();

			// Getting current task last status
			$query = DB::table('tasks_history')
				->select('status')
				->where('task_id', $task->id)
				->orderBy('datetime',  'DESC')
				->first()
			;
			if ($query !== false && ! is_null($query)) {
				$last_status = $query->status;
			}

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

				$this->saveHistory($task, true);
			}
			catch(MonitoringException $e) {
				$this->saveHistory($task, false, $e->getMessage());
			}
			catch(Exception $e) {
				$this->saveHistory($task, false, $e->getMessage());
			}
		}
		$bar->finish();
		$this->newLine(2);

		if (!empty($this->results)) {
			$this->table(
				['Host', 'Result', 'Message'],
				$this->results
			);
		}
    }

	final private function saveHistory($task, $status, $output = null) {
		$date = date('Y-m-d H:i:s');

		$this->results[] = [
			'host'		=> $task->host,
			'result'	=> $status === true ? 'OK' : 'FAILED',
			'message'	=> $output
		];

		$insert = DB::table('tasks_history')
			->insert([
				'status'		=> $status === true ? 1 : 0,
				'datetime'		=> $date,
				'output'		=> $output ?? '',
				'task_id'		=> $task->id
			]
		);

		if (false !== $insert) {
			DB::table('tasks')
				->where('id', $task->id)
				->update([
					'last_execution'	=> $date
				])
			;
			return true;
		}
	}

	final private function checkPing($task) {
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

	final private function checkHttp($task) {
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

