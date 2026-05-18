<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionalTool extends Model
{

    protected $fillable = [
        'title',
        'description',
        'thumbnail',
        'pdf_link',
    ];
}
