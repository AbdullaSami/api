<?php

namespace App\Services;

use App\Contracts\PinCheckerInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;

class PinCheckService implements PinCheckerInterface
{
    protected $maxAttempts;
    protected $lockoutSeconds;
    protected $hashed;

    public function __construct()
    {
        $this->maxAttempts = Config::get('pin.max_attempts', 5);
        $this->lockoutSeconds = Config::get('pin.lockout_seconds', 300);
        $this->hashed = Config::get('pin.hashed', true);
    }

    public function check($model, string $pin): array
    {
        // Expect model to have either a relation pin() or attributes pin_hash, failed_attempts, locked_until
        $now = Carbon::now();

        // Get pin record or attributes
        $pinRecord = $this->getPinRecord($model);

        if (! $pinRecord) {
            return ['ok' => false, 'reason' => 'no_pin_set'];
        }

        // Locked?
        if ($pinRecord->locked_until && Carbon::parse($pinRecord->locked_until)->isFuture()) {
            return ['ok' => false, 'reason' => 'locked', 'locked_until' => $pinRecord->locked_until];
        }

        // Verify
        $match = $this->verifyPin($pin, $pinRecord->pin_hash);

        if ($match) {
            $this->resetAttempts($model);
            return ['ok' => true, 'reason' => null];
        }

        // Failed: increment attempts
        $this->incrementFailedAttempt($model, $pinRecord);

        // If reached max -> lock
        if (($pinRecord->failed_attempts + 1) >= $this->maxAttempts) {
            $this->lock($model);
            return ['ok' => false, 'reason' => 'locked', 'locked_until' => Carbon::now()->addSeconds($this->lockoutSeconds)->toDateTimeString()];
        }

        return ['ok' => false, 'reason' => 'invalid'];
    }

    public function resetAttempts($model): void
    {
        if ($this->hasPinRelation($model)) {
            $pin = $this->getPinRecord($model);
            if ($pin) {
                $pin->failed_attempts = 0;
                $pin->locked_until = null;
                $pin->save();
                return;
            }
        }

        // fallback: attributes on model
        if (isset($model->pin_failed_attempts) || isset($model->pin_locked_until)) {
            $model->pin_failed_attempts = 0;
            $model->pin_locked_until = null;
            $model->save();
        }
    }

    protected function verifyPin(string $pin, string $storedHash): bool
    {
        if ($this->hashed) {
            return Hash::check($pin, $storedHash);
        }

        return hash_equals($storedHash, $pin);
    }

    protected function incrementFailedAttempt($model, $pinRecord): void
    {
        if ($pinRecord) {
            $pinRecord->failed_attempts = ($pinRecord->failed_attempts ?? 0) + 1;
            $pinRecord->save();
            return;
        }

        // fallback to model fields
        $model->pin_failed_attempts = ($model->pin_failed_attempts ?? 0) + 1;
        $model->save();
    }

    protected function lock($model): void
    {
        $lockedUntil = Carbon::now()->addSeconds($this->lockoutSeconds);
        if ($this->hasPinRelation($model)) {
            $pin = $this->getPinRecord($model);
            $pin->locked_until = $lockedUntil;
            $pin->failed_attempts = $this->maxAttempts;
            $pin->save();
            return;
        }

        $model->pin_locked_until = $lockedUntil;
        $model->pin_failed_attempts = $this->maxAttempts;
        $model->save();
    }

    protected function getPinRecord($model)
    {
        // If model has pin() relation (morph one) use it
        if ($this->hasPinRelation($model)) {
            return $model->pin; // assume relation defined
        }

        // Fallback to attributes stored on user model; construct a simple object
        if (isset($model->pin_hash)) {
            return (object)[
                'pin_hash' => $model->pin_hash,
                'failed_attempts' => $model->pin_failed_attempts ?? 0,
                'locked_until' => $model->pin_locked_until ?? null,
                'save' => function() use ($model) {
                    // save fallback: update model attributes to DB
                    $model->save();
                }
            ];
        }

        return null;
    }

    protected function hasPinRelation($model): bool
    {
        return method_exists($model, 'pin');
    }
}
