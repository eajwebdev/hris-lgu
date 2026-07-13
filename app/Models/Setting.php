<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr',
        'mayor',
        'vice_mayor',
        'dtr_acct',
        'hr_kiosk',
        'hrk_pw',
        'sync_backups',
        'te_rstrct_lvl',
        'records_office_email',
        'job_portal_email',
        'maintenance',
    ];

    /**
     * Either the Mayor or the Vice Mayor may approve a leave application,
     * whichever of them is available.
     */
    public function isApprovingOfficial($employeeId): bool
    {
        return $this->approvingRole($employeeId) !== null;
    }

    /**
     * The title to print on the leave form for whoever signed it.
     */
    public function approvingRole($employeeId): ?string
    {
        if ($employeeId === null) {
            return null;
        }

        if ((int) $this->mayor === (int) $employeeId) {
            return 'Mayor';
        }

        if ((int) $this->vice_mayor === (int) $employeeId) {
            return 'Vice Mayor';
        }

        return null;
    }
}
