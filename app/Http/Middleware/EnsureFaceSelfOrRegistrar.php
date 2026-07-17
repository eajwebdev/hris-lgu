<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;

/**
 * Face enrolment: Admin/HR may manage anyone; an employee may enrol themselves.
 *
 * The registrar rule is unchanged from EnsureFaceRegistrar. What this adds is
 * the self-service case: a user on the employee guard reaching a face route
 * whose target is their own record (or no target at all, which the controller
 * resolves to "me"). An employee naming anybody else's id still gets a 403 —
 * being able to enrol your own face is not being able to enrol a face for
 * somebody else.
 *
 * Removal stays registrar-only and never routes through here.
 */
class EnsureFaceSelfOrRegistrar
{
    public function handle(Request $request, Closure $next)
    {
        $target = $request->route('employee') ?? $request->route('id');

        if (! self::allowsFor($target)) {
            abort(403, 'You are not authorised to manage face recognition data.');
        }

        return $next($request);
    }

    /**
     * May the current user manage face data for this employee?
     *
     * Shared by the middleware and the views, so the panel and the routes it
     * posts to can never disagree about who is allowed. `null` means "no
     * explicit target": for a registrar that is fine (the controller will pick
     * one), and for an employee it means their own record.
     */
    public static function allowsFor(Employee|int|string|null $target): bool
    {
        if (EnsureFaceRegistrar::allows()) {
            return true;
        }

        $self = auth()->guard('employee')->user();

        if ($self === null) {
            return false;
        }

        $targetId = $target instanceof Employee ? $target->id : $target;

        return $targetId === null || (int) $targetId === (int) $self->id;
    }
}
