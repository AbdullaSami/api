<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditCodes extends Model
{
    /** @use HasFactory<\Database\Factories\CreditCodesFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'credit',
        'created_by',
        'charged_by',
        'status'
    ];



    public function createdBy()  
    {  
        return $this->belongsTo(Admin::class, 'created_by');  
    }  

    public function chargedBy()  
    {  
        return $this->belongsTo(User::class, 'charged_by');  
    }  
}
