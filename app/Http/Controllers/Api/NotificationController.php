<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends BaseApiController
{
    /**
     * List all notifications for the authenticated user.
     * GET /notifications
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $notifications = $user
            ->notifications()
            ->when(
                $request->boolean('unread'),
                fn ($q) => $q->where('is_read', false)
            )
            ->latest()
            ->paginate(20);

        $unreadCount = $user
            ->notifications()
            ->where('is_read', false)
            ->count();

        return $this->success([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ], 'Notifications retrieved successfully');
    }

    /**
     * Mark a single notification as read.
     * PATCH /notifications/{notification}/read
     */
    public function markRead(Notification $notification): JsonResponse
    {
        $this->authorizeNotification($notification);

        $notification->update(['is_read' => true]);

        return $this->success($notification, 'Notification marked as read');
    }

    /**
     * Mark all notifications as read.
     * POST /notifications/read-all
     */
    public function markAllRead(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $user
            ->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return $this->success(null, 'All notifications marked as read');
    }

    /**
     * Delete a notification.
     * DELETE /notifications/{notification}
     */
    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorizeNotification($notification);

        $notification->delete();

        return $this->noContent('Notification deleted');
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function authorizeNotification(Notification $notification): void
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'This action is unauthorized');
        }
    }
}