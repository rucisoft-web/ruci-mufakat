<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'regulatory_value' => 'float',
            'internal_value' => 'float',
        ];
    }
}
