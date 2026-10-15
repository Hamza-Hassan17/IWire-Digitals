<?php

namespace App\Models;

use App\Support\Iw;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    protected $guarded = [];

    protected $casts = [
        'explicit' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'reject_reasons' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'Pending');
    }

    public function scopeLibrary(Builder $q): Builder
    {
        return $q->where('status', '!=', 'Pending');
    }

    public function scopeCtx(Builder $q, ?string $ctx): Builder
    {
        return $ctx && $ctx !== 'All' ? $q->where('type', $ctx) : $q;
    }

    public function getIsMusicAttribute(): bool
    {
        return $this->type === 'Music';
    }

    public function getArtAttribute(): string
    {
        return Iw::art($this->art_seed, $this->art_style);
    }

    public function getIconAttribute(): string
    {
        return $this->is_music ? 'graphic_eq' : 'movie';
    }

    public function getPlaysKAttribute(): string
    {
        return Iw::k($this->plays);
    }

    public function getWaitHoursAttribute(): int
    {
        return $this->submitted_at ? (int) $this->submitted_at->diffInHours(now(), true) : 0;
    }

    public function getUrgentAttribute(): bool
    {
        return $this->wait_hours >= 36;
    }

    /** Lightweight payload for the global player / palette. */
    public function toPlayer(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'creator' => $this->creator?->name,
            'type' => $this->type,
            'status' => $this->status,
            'dur' => $this->duration,
            'secs' => Iw::seconds($this->duration),
            'art' => $this->art,
            'wave' => Iw::wave(72, mb_strlen($this->title)),
            'url' => $this->status === 'Pending' ? route('review.show', $this) : route('library.show', $this),
        ];
    }
}
