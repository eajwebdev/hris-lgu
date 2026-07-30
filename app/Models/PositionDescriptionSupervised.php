<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Section 15 — a position directly supervised by this one. */
class PositionDescriptionSupervised extends Model
{
    protected $table = 'position_description_supervised';

    protected $fillable = ['position_description_id', 'position_title', 'item_number', 'sort_order'];

    public function positionDescription()
    {
        return $this->belongsTo(PositionDescription::class);
    }
}
