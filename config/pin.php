<?php

return [
    // Max failed attempts before lockout
    'max_attempts' => env('PIN_MAX_ATTEMPTS', 5),

    // Lockout seconds after reaching max attempts
    'lockout_seconds' => env('PIN_LOCKOUT_SECONDS', 300), // 5 minutes

    // Whether PINs are stored hashed (true recommended)
    'hashed' => env('PIN_HASHED', true),
];
