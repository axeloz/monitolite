<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
	use HasFactory;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [];

	public function contact() {
		return $this->belongsTo('App\Models\Contact');
	}

	public function task_history() {
		return $this->belongsTo('App\Models\TaskHistory');
	}

	public static function addNotificationTask(TaskHistory $history) {
		$contacts = $history->task->contacts()->get();
		if (! is_null($contacts)) {
			foreach ($contacts as $c) {
				$notification 					= new Notification;
				$notification->contact_id 		= $c->id;
				$notification->task_history_id	= $history->id;
				$notification->status 			= 'pending';
				$notification->save();
			}
		}
	}

}
