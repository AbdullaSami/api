<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Course extends Model
{
    use HasSlug;
    protected $fillable = [
        'title',
        'slug',
        'category_id',
        'description',
        'thumbnail',
        'level',
        'is_published',
        'instructor_id',
        'duration'
    ];
    protected $appends = ['available_in_package'];
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'course_users')
            ->withPivot(['enrolled_at', 'completed_at', 'progress'])
            ->withTimestamps();
    }
    public function enrolledUsers()
    {
        return $this->hasMany(CourseUser::class);
    }

    public function enrolledUserCount()
    {
        return $this->enrolledUsers()->count();
    }

    public function category()
    {
        return $this->belongsTo(CoursesCategory::class, 'category_id');
    }
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    // Cache expensive attribute
    protected function getAvailableInPackageAttribute()
    {
        return cache()->remember(
            "course_packages_{$this->level}",
            3600,
            fn() => Package::where('level', $this->level)->pluck('name')
        );
    }
}
