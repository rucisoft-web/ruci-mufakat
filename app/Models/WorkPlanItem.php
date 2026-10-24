<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkPlanItem extends Model
{
    protected $guarded = [];

    public function organ(): BelongsTo
    {
        return $this->belongsTo(Organ::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
}
