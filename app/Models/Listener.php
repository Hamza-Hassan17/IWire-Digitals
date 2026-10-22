<?php

namespace App\Models;

use App\Support\Iw;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listener extends Model
{
    protected $guarded = [];

    protected $casts = ['joined_at' => 'date', 'last_active_at' => 'datetime'];

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getInitialsAttribute(): string
    {
        return Iw::initials($this->name);
    }

    /** Avatar background: the app profile picture if the listener uploaded one. */
    public function getArtAttribute(): string
    {
        if ($url = $this->user?->avatar_url) {
            return "url('$url') center/cover no-repeat, #0F2340";
        }

        return Iw::art($this->art_seed, 0);
    }
}
