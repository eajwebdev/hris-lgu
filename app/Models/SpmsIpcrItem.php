<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpmsIpcrItem extends Model
{
    use HasFactory;

    protected $table = 'spms_ipcr_items';

    protected $fillable = [
        'ipcr_id',
        'employee_id',
        'opcr_item_id',
        'assigned_by',
        'category',
        'subcategory',
        'mfo_pap',
        'success_indicators',
        'actual_accomplishment',
        'evidence_file',
        'rating_q',
        'rating_e',
        'rating_t',
        'rating_ave',
        'remarks',
        'status',
    ];

    public function ipcr()
    {
        return $this->belongsTo(SpmsIpcr::class, 'ipcr_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function opcrItem()
    {
        return $this->belongsTo(SpmsOpcrItem::class, 'opcr_item_id');
    }

    public function assigner()
    {
        return $this->belongsTo(Employee::class, 'assigned_by');
    }
}
