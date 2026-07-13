<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GovId extends Model
{
    use HasFactory;

    protected $fillable = [
        'empid',
        'govid',
    ];

    public function govidsValue()
    {
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
