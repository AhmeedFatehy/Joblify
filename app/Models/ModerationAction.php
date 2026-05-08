<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_user_id',
        'comment_id',
        'action',
        'reason',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}
