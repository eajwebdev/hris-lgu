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
        'sort_order',
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

    /**
     * Check if the evidence_file contains an external URL (e.g. Google Drive link).
     */
    public function getIsEvidenceUrlAttribute(): bool
    {
        return !empty($this->evidence_file) && \Illuminate\Support\Str::startsWith($this->evidence_file, ['http://', 'https://']);
    }

    /**
     * Accessor for rating_average mapping to DB column rating_ave
     */
    public function getRatingAverageAttribute()
    {
        return $this->attributes['rating_ave'] ?? null;
    }
}
