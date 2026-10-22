<?php

namespace App\Models;

use App\Support\Iw;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $guarded = [];

    public function listener(): BelongsTo
    {
        return $this->belongsTo(Listener::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    /** Display name of whoever wrote it — an app user, or a listener record. */
    public function getAuthorNameAttribute(): string
    {
        return $this->user?->name ?? $this->listener?->name ?? 'Deleted user';
    }

    public function getAuthorInitialsAttribute(): string
    {
        return Iw::initials($this->author_name);
    }

    /** CSS background for the author's avatar in the control room. */
    public function getAuthorArtAttribute(): string
    {
        if ($url = $this->user?->avatar_url) {
            return "url('$url') center/cover no-repeat, #0F2340";
        }

        return $this->listener?->art ?? Iw::art($this->user_id ?? 0, 0);
    }

    public function getAuthorAvatarAttribute(): ?string
    {
        return $this->user?->avatar_url;
    }
}
