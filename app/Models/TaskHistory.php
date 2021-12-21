<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskHistory extends Model
{
    use HasFactory;

    protected $table = 'task_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

	public function notifications() {
		return $this->hasMany('App\Models\Notification');
	}

	public function task() {
		return $this->belongsTo('App\Models\Task');
	}

}
