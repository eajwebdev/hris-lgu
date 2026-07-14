<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The searchable projection of an employee's face.
 *
 * Holds exactly one L2-normalised master embedding, which means a similarity
 * search is a dot product against a narrow table rather than a walk over every
 * employee's full capture set.
 */
class EmployeeFaceVector extends Model
{
    use HasFactory;

    protected $table = 'employee_face_vectors';

    protected $fillable = [
        'employee_id',
        'master_embedding',
        'embedding_dimension',
    ];

    protected $casts = [
        'master_embedding'    => 'array',
        'embedding_dimension' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function scopeRegistered($query)
    {
        return $query->whereNotNull('master_embedding');
    }
}
