<?php

namespace App\Console\Commands;

use \Exception;
use App\Models\Notification;
use App\Mail\TaskNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitolite:notify
		{--limit=1000 : maximum notifications to process at once }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends the notifications alerts';

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
		$notifications = Notification::with(['contact', 'task_history', 'task_history.task'])
			->where('status', '=', 'pending')
			->orderBy('created_at', 'ASC')
			->limit($this->option('limit'), 1000)
			->get()
		;

		$results = [];
		if (! empty($notifications)) {
			foreach ($notifications as $n) {
				if (! isset($results[$n->contact_id])) {
					$results[$n->contact_id] = [
						'contact'	=> $n->contact->toArray(),
						'tasks'		=> []
					];
				}
				//else {
					$history = $n->task_history;
					$task = $history->task;

					if (! isset($results[$n->contact_id]['tasks'][$task->id])) {
						$results[$n->contact_id]['tasks'][$task->id] = [
							'history' => []
						];
					}
					array_push($results[$n->contact_id]['tasks'][$task->id]['history'], $history->toArray());

				//}
			}
		}

		if (count($results) > 0) {
			foreach ($results as $r) {
				$this->info('Sending notifications to '.$r['contact']['email']);
				try {
					Mail::to($r['contact']['email'])->send(new TaskNotification($r));
					Notification::where('contact_id', '=', $r['contact']['id'])->update(
						['status'	=> 'sent']
					);
				}
				catch (Exception $e) {
					Notification::where('contact_id', '=', $r['contact']['id'])->update(
						['status'	=> 'error']
					);
				}
			}
		}
    }
}

