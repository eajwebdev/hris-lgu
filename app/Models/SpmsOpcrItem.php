<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpmsOpcrItem extends Model
{
    use HasFactory;

    protected $table = 'spms_opcr_items';

    protected $fillable = [
        'opcr_id',
        'category',
        'subcategory',
        'mfo_pap',
        'success_indicators',
        'link_to_source',
        'allotted_budget',
        'division_accountable',
        'actual_accomplishment',
        'rating_q',
        'rating_e',
        'rating_t',
        'rating_ave',
        'remarks',
    ];

    public function opcr()
    {
        return $this->belongsTo(SpmsOpcr::class, 'opcr_id');
    }

    public function ipcrItems()
    {
        return $this->hasMany(SpmsIpcrItem::class, 'opcr_item_id');
    }

    public function assignedEmployees()
    {
        return $this->belongsToMany(Employee::class, 'spms_ipcr_items', 'opcr_item_id', 'employee_id');
    }
}
