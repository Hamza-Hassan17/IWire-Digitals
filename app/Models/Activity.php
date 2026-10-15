<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $guarded = [];

    public static function log(string $icon, string $color, string $action, string $target, string $context = 'All'): self
    {
        return static::create(compact('icon', 'color', 'action', 'target', 'context'));
    }

    public function getWhenAttribute(): string
    {
        $t = $this->created_at;
        if ($t->isToday()) {
            return 'Today, '.$t->format('H:i');
        }
        if ($t->isYesterday()) {
            return 'Yesterday, '.$t->format('H:i');
        }

        return $t->format('M j, H:i');
    }
}
