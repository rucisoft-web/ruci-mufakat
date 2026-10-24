<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiScenario extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'output' => 'array',
        ];
    }
}
