<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A member of the Personnel Selection Board, as printed in the signatory block
 * of the Comparative Assessment Form.
 *
 * The name is stored rather than read through the employee link, so a board
 * that signed a past assessment still prints correctly after that person
 * leaves the LGU.
 */
class PsbMember extends Model
{
    protected $fillable = ['employee_id', 'name', 'credentials', 'role', 'sort_order', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /** "ERNIE T. UY, RN, JD" */
    public function getPrintedNameAttribute(): string
    {
        $name = strtoupper(trim((string) $this->name));

        return $this->credentials ? $name . ', ' . $this->credentials : $name;
    }

    /** The board in printing order: chairperson first, then members. */
    public static function board()
    {
        return static::active()
            ->orderByRaw("CASE role WHEN 'Chairperson' THEN 0 WHEN 'Vice-Chairperson' THEN 1 ELSE 2 END")
            ->orderBy('sort_order')
            ->get();
    }
}
