<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One period of an employee's work for the LGU.
 *
 * The open period (no date_end) mirrors the employee's record: it is opened
 * when the employee is added, kept in step when their position, office,
 * status, item no. or date hired is edited (Employee::booted), closed when
 * HR records a separation, and a new one is opened when they are rehired
 * (EmploymentHistoryController).
 */
class EmploymentHistory extends Model
{
    /** Why a period ended, as HR picks it when recording a separation. */
    public const SEPARATION_REASONS = [
        'Resignation',
        'Retirement',
        'End of contract / term',
        'Transfer to another agency',
        'Dismissal',
        'Dropped from the rolls',
        'Death',
        'Other',
    ];

    protected $fillable = [
        'employee_id', 'position', 'office_id', 'status_id', 'item_no',
        'date_start', 'date_end', 'separation_reason', 'remarks', 'recorded_by',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /** The period's columns as the employee's record holds them. */
    public static function fromEmployee(Employee $employee): array
    {
        return [
            'position' => $employee->position,
            'office_id' => $employee->emp_dept ?: null,
            'status_id' => $employee->emp_status ?: null,
            'item_no' => $employee->item_no,
            'date_start' => $employee->date_hired ?: null,
        ];
    }

    /**
     * How long the period lasted, or has lasted so far. None for a period
     * with no start date, or one that has not started yet (a rehire
     * recorded ahead of its date).
     */
    public function length(): ?\DateInterval
    {
        $end = $this->date_end ?? Carbon::today();

        return $this->date_start && $this->date_start->lte($end) ? $this->date_start->diff($end) : null;
    }
}
