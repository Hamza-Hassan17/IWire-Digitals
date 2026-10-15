<?php

namespace App\Models;

use App\Support\Iw;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listener extends Model
{
    protected $guarded = [];

    protected $casts = ['joined_at' => 'date', 'last_active_at' => 'datetime'];

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function getInitialsAttribute(): string
    {
        return Iw::initials($this->name);
    }

    public function getArtAttribute(): string
    {
        return Iw::art($this->art_seed, 0);
    }
}
