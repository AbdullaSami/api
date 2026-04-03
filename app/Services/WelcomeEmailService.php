<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\WelcomeEmail;

class WelcomeEmailService
{
    /**
     * Send welcome email to newly registered user
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function sendWelcomeEmail($user): bool
    {
        try {
            $appName = config('app.name', 'Our Application');
            $dashboardUrl = config('app.url') . '/dashboard';

            Mail::to($user->email)->send(new WelcomeEmail([
                'name' => $user->first_name,
                'email' => $user->email,
                'app_name' => $appName,
                'dashboard_url' => $dashboardUrl,
                'app_name_lower' => strtolower(str_replace(' ', '', $appName)),
                'unsubscribe_url' => config('app.url') . '/unsubscribe/' . $user->id,
                'privacy_url' => config('app.url') . '/privacy',
                'terms_url' => config('app.url') . '/terms'
            ]));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage());
            return false;
        }
    }
}
