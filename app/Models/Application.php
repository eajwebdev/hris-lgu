<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'jid',
        // Set when the applicant is already employed here. The three columns
        // after it are the snapshot the Comparative Assessment reads, taken at
        // application time so a later promotion cannot rewrite what the board
        // assessed.
        'employee_id',
        'present_position',
        'salary_grade',
        'appointment_status',
        'performance_rating',
        'app_number',
        'position',	
        'first_name',	
        'middle_name',	
        'last_name',	
        'age',	
        'sex',	
        'mobile',	
        'email',
        'address',	
        'education',	
        'eligibility',	
        'pds',	
        'wes',	
        'intent',	
        'resume',	
        'tor',	
        'coe',	
        'cert_training',
        'dq_reason',
        'ctrl_no',
        'interview_datetime',
        'venue',
        'status',
        'checked',
        'is_complete',
        'created_at'
    ];

    protected $casts = [
        'performance_rating' => 'float',
    ];

    /** The 201 record, when the applicant already works here. */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function job()
    {
        return $this->belongsTo(JobHiring::class, 'jid');
    }

    /** An internal applicant is one seeking promotion or transfer. */
    public function isInternal(): bool
    {
        return $this->employee_id !== null;
    }

    /**
     * Take the present position, grade and status from the 201 file.
     *
     * Copied rather than read live, because the Comparative Assessment has to
     * keep showing what was true when the board assessed the candidate. The
     * performance rating is left to HR: an IPCR is a period rating, and which
     * period applies is a judgement the system should not make.
     */
    public function snapshotFromEmployee(?Employee $employee = null): self
    {
        $employee ??= $this->employee;

        if (! $employee) {
            return $this;
        }

        $this->employee_id        = $employee->id;
        $this->present_position   = $employee->position;
        $this->salary_grade       = $employee->sg_grade ?? $this->salary_grade;
        $this->appointment_status = $employee->emp_status == 1 ? 'Permanent' : $this->appointment_status;

        return $this;
    }

    /** "Administrative Aide IV / SG 4 / Permanent" */
    public function getPresentPositionLineAttribute(): string
    {
        return collect([$this->present_position, $this->salary_grade, $this->appointment_status])
            ->filter()
            ->implode(' / ');
    }
}
