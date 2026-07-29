<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Section 22 — a statement of duty with its share of working time. */
class PositionDescriptionDuty extends Model
{
    protected $fillable = ['position_description_id', 'percentage', 'duty', 'competency_level', 'sort_order'];

    protected $casts = ['percentage' => 'float'];

    public function positionDescription()
    {
        return $this->belongsTo(PositionDescription::class);
    }
}
