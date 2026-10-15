<?php

namespace App\Models;

use App\Support\Iw;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

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

    /** CSS background for the control room: the uploaded cover if there is one, else generative art. */
    public function getArtAttribute(): string
    {
        if ($this->cover_path) {
            return "url('".Storage::disk('public')->url($this->cover_path)."') center/cover no-repeat, #0F2340";
        }

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

    /** Cover / poster image: the uploaded file, or a PNG rendered from the generative art. */
    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_path) {
            return Storage::disk('public')->url($this->cover_path);
        }
        [$w, $h] = $this->is_music ? [600, 600] : [400, 600];

        return route('media.art', ['size' => "{$w}x{$h}", 'art' => "{$this->art_seed}-{$this->art_style}"]);
    }

    /** Wide 16:9 banner for the films hero carousel. */
    public function getBannerUrlAttribute(): string
    {
        if ($this->banner_path) {
            return Storage::disk('public')->url($this->banner_path);
        }

        return route('media.art', ['size' => '1280x720', 'art' => "{$this->art_seed}-{$this->art_style}"]);
    }

    /** Time-limited signed URL to the media file, or null until a file has been uploaded. */
    public function streamUrl(int $minutes = 360): ?string
    {
        if (! $this->file_path || ! Storage::disk('local')->exists($this->file_path)) {
            return null;
        }

        return URL::temporarySignedRoute('api.stream', now()->addMinutes($minutes), ['content' => $this->id]);
    }

    public function getDurationSecondsAttribute(): int
    {
        return Iw::seconds($this->duration);
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
