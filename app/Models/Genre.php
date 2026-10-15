<?php

namespace App\Models;

use App\Support\Iw;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $guarded = [];

    public const KINDS = ['music', 'films', 'moods'];

    public function getArtAttribute(): string
    {
        return Iw::art($this->art_seed, $this->art_style);
    }

    /** Number of live items using this genre / mood. */
    public function getCountAttribute(): int
    {
        return match ($this->kind) {
            'music' => Content::where('type', 'Music')->where('genre', $this->name)->count(),
            'films' => Content::where('type', 'Films')->where('genre', $this->name)->count(),
            default => Content::where('mood', $this->name)->count(),
        };
    }
}
