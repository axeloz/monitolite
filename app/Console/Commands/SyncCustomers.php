<?php


/**
 * THIS COMMAND IS FOR MY OWN NEEDS ONLY
 * IT SYNCS ALL THE TASKS FROM A DISTANT API
 * IT IS PROBABLY WORTHLESS FOR YOU
 */


namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize all customers';

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
			$customers = json_decode($result);

			$bar = $this->output->createProgressBar(count($customers));
			$bar->start();


			// Getting existing tasks
			$query = app('db')->select('SELECT * FROM tasks');
			foreach ($query as $t) {
				$tasks[$t->id] = preg_replace('~^https?://~', '', $t->host);
			}

			// Getting existing contacts
			$contacts = app('db')->select('SELECT * FROM contacts');

			// First we insert new customers
			foreach($customers as $c) {
				$bar->advance();
				if (false === $key = array_search($c->domain, $tasks)) {
					$ret = app('db')->insert('
						INSERT INTO tasks (`host`, `type`, `params`, `creation_date`, `frequency`, `active`, `group_id`)
						VALUES(:host, :type, :params, :creation_date, :frequency, :active, :group_id)
					', [
						'host'					=> 'https://'.$c->domain,
						'type'					=> 'http',
						'params'				=> 'propulsé par',
						'creation_date'			=> date('Y-m-d H:i:s'),
						'frequency'				=> 600,
						'active'				=> 1,
						'group_id'				=> $c->id
					]);
				}
			}

			// Then we delete old customers
			foreach ($tasks as $t) {

			}
		}
    }
}

