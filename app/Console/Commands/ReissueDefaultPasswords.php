<?php

namespace App\Console\Commands;

use App\Http\Middleware\EnsurePasswordChanged;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Move accounts still holding a superseded default onto the current one.
 *
 * Only exists because the default changed. EnsurePasswordChanged recognises
 * exactly one password — config('auth.default_password') — so an account left
 * on a previous default sails past the change-password hold: the system has no
 * way to tell that password apart from one the employee chose themselves.
 *
 * Accounts with a password that is neither the current default nor one of the
 * superseded ones are left alone. Those are the people who already chose their
 * own, and overwriting them would be locking out the only users who did the
 * right thing.
 */
class ReissueDefaultPasswords extends Command
{
    protected $signature = 'hris:reissue-passwords
                            {--old=password123 : Superseded default to look for; repeatable}
                            {--apply : Actually write the changes (otherwise a dry run)}';

    protected $description = 'Re-issue the current default password to accounts still on a superseded one';

    public function handle(): int
    {
        $current = (string) config('auth.default_password');

        if ($current === '') {
            $this->error('auth.default_password is empty — nothing to issue.');

            return self::FAILURE;
        }

        $olds  = (array) $this->option('old');
        $apply = (bool) $this->option('apply');

        $this->info(($apply ? 'Re-issuing' : 'DRY RUN — would re-issue') . " \"{$current}\"");
        $this->line('Superseded defaults searched: ' . implode(', ', $olds));
        $this->newLine();

        $moved = $onCurrent = $ownPassword = 0;

        foreach (Employee::select('id', 'emp_ID', 'password')->cursor() as $employee) {
            if (EnsurePasswordChanged::isDefault($employee->password)) {
                $onCurrent++;   // already on the current default (or blank) — the hold catches these
                continue;
            }

            $isOld = false;

            foreach ($olds as $old) {
                if ($old !== '' && Hash::check($old, (string) $employee->password)) {
                    $isOld = true;
                    break;
                }
            }

            if (! $isOld) {
                $ownPassword++; // chose their own — leave it alone
                continue;
            }

            if ($apply) {
                // Straight to the table: Employee::boot() hashes on `creating`,
                // so a hash put on the model would be mangled on the way out.
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update(['password' => Hash::make($current)]);
            }

            $moved++;
        }

        $this->table(['Outcome', 'Accounts'], [
            [$apply ? 're-issued the current default' : 'would be re-issued', $moved],
            ['already on the current default', $onCurrent],
            ['left alone (own password)', $ownPassword],
        ]);

        if (! $apply && $moved > 0) {
            $this->newLine();
            $this->warn('Nothing was written. Re-run with --apply to make these changes.');
        }

        return self::SUCCESS;
    }
}
