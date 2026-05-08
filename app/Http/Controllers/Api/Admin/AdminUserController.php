<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends BaseApiController
{
    /**
     * GET /admin/users
     * Supports `q` (search name/email) and `role` filters, paginated.
     */
    public function index(Request $request): JsonResponse
    {
        $q = $request->query('q');
        $role = $request->query('role');

        $users = User::query()
            ->when($q, fn ($qBuilder) => $qBuilder->where(fn ($b) => $b->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->when($role, fn ($b) => $b->where('role', $role))
            ->latest()
            ->paginate(20);

        return $this->success($users, 'Users retrieved successfully');
    }

    /**
     * PATCH /admin/users/{user}/suspend
     */
    public function suspend(User $user): JsonResponse
    {
        $user->update(['suspended' => true]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.suspend',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'meta' => ['email' => $user->email],
        ]);

        return $this->success($user, 'User suspended');
    }

    /**
     * PATCH /admin/users/{user}/activate
     */
    public function activate(User $user): JsonResponse
    {
        $user->update(['suspended' => false]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.activate',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'meta' => ['email' => $user->email],
        ]);

        return $this->success($user, 'User activated');
    }
}
