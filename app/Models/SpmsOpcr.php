<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpmsOpcr extends Model
{
    use HasFactory;

    protected $table = 'spms_opcrs';

    protected $fillable = [
        'office_id',
        'office_head_id',
        'year',
        'semester',
        'status',
        'total_core_score',
        'total_support_score',
        'final_numerical_rating',
        'final_adjectival_rating',
        'remarks',
    ];

    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function head()
    {
        return $this->belongsTo(Employee::class, 'office_head_id');
    }

    public function items()
    {
        return $this->hasMany(SpmsOpcrItem::class, 'opcr_id');
    }
}
