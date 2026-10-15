<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurationRow extends Model
{
    protected $guarded = [];

    protected $casts = ['content_ids' => 'array'];
}
