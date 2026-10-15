<?php

namespace App\Models;

use App\Support\Iw;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $guarded = [];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function getArtAttribute(): string
    {
        return $this->content ? $this->content->art : Iw::art($this->art_seed, 0);
    }

    public function getIconAttribute(): string
    {
        return match ($this->kind) {
            'Track' => 'graphic_eq',
            'Film' => 'movie',
            default => 'chat_bubble',
        };
    }
}
