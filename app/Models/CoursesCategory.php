<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
class CoursesCategory extends Model
{
    use HasSlug;
    protected $table = 'courses_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'status',
    ];


    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
    public function courses()
    {
        return $this->hasMany(Course::class, 'category_id');
    }
}
