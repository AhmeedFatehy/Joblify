<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;

class NotificationService
{
    /**
     * Notify a candidate that their application status changed.
     */
    public function notifyApplicationStatusChanged(Application $application): void
    {
        $statusLabel = ucfirst($application->status->value);

        $application->user->notifications()->create([
            'type'    => 'application_status_changed',
            'message' => "Your application for \"{$application->job->title}\" has been {$statusLabel}.",
            'is_read' => false,
        ]);
    }

    /**
     * Generic notification helper.
     */
    public function send(User $user, string $type, string $message): void
    {
        $user->notifications()->create([
            'type'    => $type,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}