<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_code',
        'username',
        'first_name',
        'last_name',
        'email',
        'country',
        'password',
        'phone',
        'status',
        'image'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    protected static function boot()
    {
        parent::boot();
        static::creating(function ($user) {
            if (is_null($user->id_code)) {
                $lastIdCode = DB::table('users')->max('id_code');
                $user->id_code = $lastIdCode ? $lastIdCode + 1 : 400000;
            }
        });
    }

    public function tickets()
    {
        return $this->hasMany(Tickets::class);
    }

    public function pin()
    {
        return $this->morphOne(\App\Models\Pin::class, 'pinable');
    }

    public function otps()
    {
        return $this->hasMany(\App\Models\Otp::class);
    }

    public function member()
    {
        return $this->hasOne(Member::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_users')
            ->withPivot(['progress', 'enrolled_at', 'completed_at'])
            ->withTimestamps();
    }

    public function courseEnrollments()
    {
        return $this->hasMany(CourseUser::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(MemberMessage::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(MemberMessageRecipient::class, 'recipient_id');
    }
}
