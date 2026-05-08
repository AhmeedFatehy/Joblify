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

        $message = "Your application for \"{$application->job->title}\" has been {$statusLabel}.";

        if ($application->status->isRejected() && $application->rejection_reason) {
            $message .= ' Reason: '.$application->rejection_reason;
        }

        $application->user->notifications()->create([
            'type' => 'application_status_changed',
            'message' => $message,
            'is_read' => false,
        ]);
    }

    /**
     * Generic notification helper.
     */
    public function send(User $user, string $type, string $message): void
    {
        $user->notifications()->create([
            'type' => $type,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
