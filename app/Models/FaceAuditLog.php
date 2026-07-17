<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FaceAuditLog extends Model
{
    use HasFactory;

    public const REGISTERED = 'FACE_REGISTERED';
    public const REMOVED    = 'FACE_REMOVED';

    protected $table = 'face_audit_logs';

    protected $fillable = [
        'employee_id',
        'action',
        'performed_by',
        'performed_by_name',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Record an action against an employee's face data.
     *
     * The actor is read off the guards rather than passed in, so a caller
     * cannot accidentally attribute the action to the wrong person. A registrar
     * on the web guard wins; otherwise a self-service employee is named with a
     * "(self)" marker — performed_by stays null because that column holds a
     * users.id, not an employees.id.
     */
    public static function record(int $employeeId, string $action, Request $request, array $meta = []): self
    {
        $actor = auth()->guard('web')->user();
        $self  = $actor ? null : auth()->guard('employee')->user();

        return static::create([
            'employee_id'       => $employeeId,
            'action'            => $action,
            'performed_by'      => $actor?->id,
            'performed_by_name' => $actor
                ? trim("{$actor->fname} {$actor->lname}")
                : ($self ? trim("{$self->fname} {$self->lname}") . ' (self)' : null),
            'ip_address'        => $request->ip(),
            'user_agent'        => substr((string) $request->userAgent(), 0, 255),
            'meta'              => $meta ?: null,
        ]);
    }
}
