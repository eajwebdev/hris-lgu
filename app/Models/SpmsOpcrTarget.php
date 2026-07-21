<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpmsOpcrTarget extends Model
{
    use HasFactory;

    protected $table = 'spms_opcr_targets';

    protected $fillable = [
        'opcr_id',
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
    ];

    public function opcr()
    {
        return $this->belongsTo(SpmsOpcr::class, 'opcr_id');
    }

    public function cascadedIpcrTargets()
    {
        return $this->hasMany(SpmsIpcrTarget::class, 'opcr_target_id');
    }
}
