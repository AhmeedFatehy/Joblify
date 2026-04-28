<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Job;

class Company extends Model
{
    

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function jobs() {
        return $this->hasMany(Job::class);
    }
}
