<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'empid',
        'skills_hob',
        'recognition',
        'mem_org',
    ];

    public function otherinfoAnyValue()
    {
        // Filter out the 'empid' column from the fillable columns
        $columns = array_filter($this->fillable, function($col) {
            return $col !== 'empid';
        });
    
        $hasValidValue = false;
        $allCommaValues = true;
    
        foreach ($columns as $col) {
            $value = $this->{$col};
    
            if (!empty($value)) {
                $trimmedValue = trim($value, ',');
                if (strlen($trimmedValue) > 0) {
                    $hasValidValue = true;
                    $allCommaValues = false; 
                    break;
                }
            } else {
                $allCommaValues = false;
            }
        }
    
        if ($hasValidValue) {
            return "1";
        }
    
        if ($allCommaValues) {
            return "zero";
        }
    
        return "0";
    }
    
}
