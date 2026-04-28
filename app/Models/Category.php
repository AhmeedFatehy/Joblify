<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Job;

class Category extends Model
{
    public function jobs() {
        return $this->belongsToMany(Job::class);
    }
}
