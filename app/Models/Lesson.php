<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
class Lesson extends Model
{
    use HasSlug;
    protected $fillable = [
        'section_id',
        'title',
        'slug',
        'description',
        'video_id',
        'video_status',
        'duration',
        'order',
        'is_preview',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
