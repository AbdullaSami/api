<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = ['name', 'description'];

    public function courses()
    {
        return $this->belongsToMany(Course::class);
    }
}
