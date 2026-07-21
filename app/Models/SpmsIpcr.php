<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpmsIpcr extends Model
{
    use HasFactory;

    protected $table = 'spms_ipcrs';

    protected $fillable = [
        'employee_id',
        'office_id',
        'opcr_id',
        'year',
        'semester',
        'status',
        'total_core_score',
        'total_support_score',
        'final_numerical_rating',
        'final_adjectival_rating',
        'comments_recommendations',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function opcr()
    {
        return $this->belongsTo(SpmsOpcr::class, 'opcr_id');
    }

    public function items()
    {
        return $this->hasMany(SpmsIpcrItem::class, 'ipcr_id');
    }
}
