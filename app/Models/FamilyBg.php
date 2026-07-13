<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyBg extends Model
{
    use HasFactory;

    protected $fillable = [
        'empid',
        'spouse_sname',
        'spouse_fname',
        'spouse_mname',
        'spouse_ext',
        'name_child',
        'date_birth',
        'occupation',
        'bus_name',
        'bus_address',
        'telephone',
        'father_sname',
        'father_fname',
        'father_mname',
        'father_ext',
        'mother_maiden',
        'mother_sname',
        'mother_fname',
        'mother_mname',
    ];

    public function famhasAnyValue()
    {
        $columns = array_filter($this->fillable, function($col) {
            $excludedColumns = ['empid', 'name_child', 'date_birth'];
            return !in_array($col, $excludedColumns);
        });
        

        foreach ($columns as $col) {
            if (!empty($this->{$col})) {
                return "1"; 
            }
        }

        return "0"; 
    }

    public $timestamps = false;
}
