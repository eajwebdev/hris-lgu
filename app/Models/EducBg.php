<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducBg extends Model
{
    use HasFactory;

    protected $fillable = [
        'empid',
        'elem_school',
        'elem_period',
        'elem_level',
        'elem_grad',
        'elem_honor',
        'sec_school',
        'sec_period',
        'sec_level',
        'sec_grad',
        'sec_honor',
        'voc_school',
        'voc_course',
        'voc_period',
        'voc_level',
        'voc_grad',
        'voc_honor',
        'coll_school',
        'coll_course',
        'coll_period',
        'coll_level',
        'coll_grad',
        'coll_honor',
        'grad_school',
        'grad_course',
        'grad_period',
        'grad_level',
        'grad_grad',
        'grad_honor',
    ];

    public function educhasAnyValue()
    {
        $columns = array_filter($this->fillable, function($col) {
            return $col !== 'empid';
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
