<?php

namespace App\Console\Commands;

/**
 * R E A D    T H I S :
 * THIS COMMAND IS FOR MY OWN NEEDS ONLY
 * IT SYNCS ALL THE TASKS FROM A DISTANT API
 * IT IS PROBABLY WORTHLESS FOR YOU
 */

use Illuminate\Console\Command;

class SyncCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitolite:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronizes all customers\' websites with Monitolite';

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
		if (env('CMS_ENABLE_SYNC') != true) {
			$this->error('Customers synchronisation is globally disabled.');
			return null;
		}


		$this->line('Starting synchronisation');
		$customers = $tasks = $contacts = [];

		// Getting active customers
		$opts = [
			CURLOPT_POST					=> true,
			CURLOPT_RETURNTRANSFER			=> true,
			CURLOPT_FOLLOWLOCATION			=> false,
			CURLOPT_FAILONERROR				=> true,
			CURLOPT_POSTFIELDS				=> [
				'access'					=> env('CMS_API_ACCESS'),
				'token'						=> env('CMS_API_TOKEN')
			],
			CURLOPT_URL						=> env('CMS_API_URL')
		];

		$ch = curl_init();
		curl_setopt_array($ch, $opts);
		if ($result = curl_exec($ch)) {
			$hosts = [];

			$customers = json_decode($result);

			$bar = $this->output->createProgressBar(count($customers));
			$bar->start();

			// Getting existing tasks
			$tasks_flat = [];
			$tasks = app('db')->select('SELECT * FROM tasks');
			foreach ($tasks as $t) {
				$tasks_flat[$t->id] = preg_replace('~^https?://~', '', trim($t->host));
			}

			// Getting existing contacts
			$contacts = app('db')->select('SELECT * FROM contacts');

			// Getting existing groups
			$groups_flat = [];
			$groups = app('db')->select('SELECT * FROM `groups`');
			foreach ($groups as $g) {
				$groups_flat[$g->id] = $g->name;
			}

			// First we insert new customers
			foreach($customers as $c) {
				$bar->advance();

				$hosts[] = 'https://'.trim($c->domain);

				// Checking group existence
				if (empty($groups_flat[$c->id])) {
					app('db')->insert('INSERT INTO `groups` (`id`, `name`) VALUE (?, ?)', [ $c->id, $c->name ]);
					$groups_flat[$c->id] = $c->name;
				}

				if (false === array_search(trim($c->domain), $tasks_flat)) {
					$ret = app('db')->insert('
						INSERT INTO tasks (`host`, `type`, `params`, `created_at`, `frequency`, `active`, `group_id`)
						VALUES(:host, :type, :params, :creation_date, :frequency, :active, :group_id)
					', [
						'host'					=> 'https://'.trim($c->domain),
						'type'					=> 'http',
						'params'				=> 'restovisio.com',
						'creation_date'			=> date('Y-m-d H:i:s'),
						'frequency'				=> 600,
						'active'				=> 1,
						'group_id'				=> $c->id
					]);

					if ($ret === true) {
						$task_id = app('db')->getPdo()->lastInsertId();

						// Inserting contacts
						foreach ($contacts as $c) {
							app('db')->insert('INSERT INTO contact_task (`task_id`, `contact_id`) VALUES (:task_id, :contact_id)', [
								'task_id'		=> $task_id,
								'contact_id'	=> $c->id
							]);
						}
					}
				}
			}
			$bar->finish();

			$this->newLine(2);
			$this->line('Checking tasks to delete');
			$bar = $this->output->createProgressBar(count($tasks));
			$bar->start();

			// Then we delete old customers
			foreach ($tasks as $t) {
				$bar->advance();

				if (false === array_search($t->host, $hosts)) {
					// Must delete task
					//$this->line('must delete '.$t->host);
					app('db')->delete('DELETE FROM `tasks` WHERE host = ?', [$t->host]);
				}
			}
			$bar->finish();
		}
    }
}

