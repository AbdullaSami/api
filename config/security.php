<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure the rate limit for OTP-related endpoints.
    | Format: 'attempts,decay_minutes'
    | Example: '3,1' = 3 attempts per 1 minute
    |
    */
    'otp_rate_limit' => env('OTP_RATE_LIMIT', '3,1'),

    /*
    |--------------------------------------------------------------------------
    | OTP Expiration (in minutes)
    |--------------------------------------------------------------------------
    */
    'otp_expiration' => env('OTP_EXPIRATION', 5),

    /*
    |--------------------------------------------------------------------------
    | Pending Update Cache Expiration (in minutes)
    |--------------------------------------------------------------------------
    */
    'pending_update_expiration' => env('PENDING_UPDATE_EXPIRATION', 10),

    /*
    |--------------------------------------------------------------------------
    | Max OTP Verification Attempts
    |--------------------------------------------------------------------------
    */
    'otp_max_attempts' => env('OTP_MAX_ATTEMPTS', 5),
];
