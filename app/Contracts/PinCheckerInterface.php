<?php

namespace App\Contracts;

interface PinCheckerInterface
{
    /**
     * Check the provided pin for the given model (e.g., User).
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $pin
     * @return array ['ok' => bool, 'reason' => string|null]
     */
    public function check($model, string $pin): array;

    /**
     * Reset failed attempts for the model (on success).
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function resetAttempts($model): void;
}
