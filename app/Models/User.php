<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fname',
        'mname',
        'lname',
        'gender',
        'username',
        'verification_code',
        'password',
        'role',
        'access',
        'emp_ID',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * May this account sign at the HRMO stop of a leave application?
     *
     * The eighth access flag is the Leave right. An account whose flags were
     * never set (access NULL, as for the seeded Administrator and HR
     * Administrator) falls back on its role; otherwise HR could never approve
     * anything and every application stopped after the employee signed.
     */
    public function canApproveLeave(): bool
    {
        if (blank($this->access)) {
            return in_array($this->role, ['Administrator', 'HR Administrator'], true);
        }

        return (explode(',', (string) $this->access)[7] ?? '0') == '1';
    }
}
