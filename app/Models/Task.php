<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    public $timestamps = [
        'created_at',
        'updated_at',
        'executed_at'
    ];

    public function group() {
        return $this->belongsTo('App\Models\Group');
    }

    public function contacts() {
        return $this->belongsToMany('App\Models\Contact');
    }

    public function history() {
        return $this->hasMany('App\Models\TaskHistory');
    }

    public function notifications() {
        return $this->hasManyThrough('App\Models\Notification', 'App\Models\TaskHistory');
    }
}
