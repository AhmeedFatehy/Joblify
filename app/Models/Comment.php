<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Job;
use App\Models\User;

class Comment extends Model
{
    public function job() {
        return $this->belongsTo(Job::class);
    }
    public function user() {
        return $this->belongsTo(User::class);
    }
}
