<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;
use App\Models\Category;
use App\Models\Skill;
use App\Models\Application;

class Job extends Model
{
    protected $table = 'job_listings';


    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function categories() {
        return $this->belongsToMany(Category::class);
    }

    public function skills() {
        return $this->belongsToMany(Skill::class);
    }

    public function applications() {
        return $this->hasMany(Application::class);
    }
}
