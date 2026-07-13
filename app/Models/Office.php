<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;

    protected $table = 'offices';

    protected $fillable = ['office_name', 'office_abbr', 'office_head_id', 'oic_id', 'group_by'];

    /**
     * Get modified office_abbr with special rules.
     */
    public function getOfficeAbbrAttribute($value)
    {
        if ($this->id == '01') {
            return 'All Office';
        }

        if ($this->id == '02') {
            return 'All Employee';
        }

        return $value; // original office_abbr
    }
}
