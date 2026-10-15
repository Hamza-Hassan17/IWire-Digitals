<?php

namespace App\Models;

use App\Support\Iw;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Creator extends Model
{
    protected $guarded = [];

    protected $casts = ['joined_at' => 'date'];

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function getInitialsAttribute(): string
    {
        return Iw::initials($this->name);
    }

    public function getArtAttribute(): string
    {
        return Iw::art($this->art_seed, 0);
    }

    public function getIconAttribute(): string
    {
        return $this->type === 'Filmmaker' ? 'movie' : 'graphic_eq';
    }

    /** Upload / approval counts derived from reviewed content. */
    public function getStatsAttribute(): array
    {
        $uploads = $this->contents()->count();
        $rejected = $this->contents()->where('status', 'Rejected')->count();
        $reviewed = $this->contents()->where('status', '!=', 'Pending')->count();
        $approved = $reviewed - $rejected;
        $rate = $reviewed ? (int) round($approved / $reviewed * 100) : 100;

        return compact('uploads', 'approved', 'rejected', 'rate');
    }
}
