<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitolite:history:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates and cleans tasks history';

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
		$lastweek = \Carbon\Carbon::now()->subWeek();
		$history = app('db')->select('
			SELECT * FROM tasks_history as h
			WHERE datetime < :lastweek
		', [
			'lastweek'		=> $lastweek
		]);


    }
}

