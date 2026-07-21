<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpmsIpcrTarget extends Model
{
    use HasFactory;

    protected $table = 'spms_ipcr_targets';

    protected $fillable = [
        'ipcr_id',
        'opcr_target_id',
        'category',
        'subcategory',
        'mfo_pap',
        'success_indicators',
        'actual_accomplishment',
        'rating_q',
        'rating_e',
        'rating_t',
        'rating_ave',
        'remarks',
        'assigned_by',
    ];

    public function ipcr()
    {
        return $this->belongsTo(SpmsIpcr::class, 'ipcr_id');
    }

    public function opcrTarget()
    {
        return $this->belongsTo(SpmsOpcrTarget::class, 'opcr_target_id');
    }

    public function assigner()
    {
        return $this->belongsTo(Employee::class, 'assigned_by');
    }
}
