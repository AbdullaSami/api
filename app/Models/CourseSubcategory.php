<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class CourseSubcategory extends Model
{
    use HasSlug;
    protected $table = 'course_subcategories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'status',
        'category_id',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function category()
    {
        return $this->belongsTo(CoursesCategory::class, 'category_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'sub_category_id');
    }
}
