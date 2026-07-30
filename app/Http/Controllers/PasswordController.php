<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsurePasswordChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Replacing the password HR issued.
 *
 * Reachable by anyone signed in, but the account being held by
 * EnsurePasswordChanged has nowhere else to go until it posts here.
 */
class PasswordController extends Controller
{
    public function edit()
    {
        return view('change-password', [
            // Drives the wording only: "you must" reads very differently from
            // "you may", and the same screen serves both.
            'forced' => (bool) session(EnsurePasswordChanged::SESSION_KEY),
        ]);
    }

    public function update(Request $request)
    {
        $account = $this->account();

        if (! $account) {
            return redirect()->route('getLogin')->with('error', 'Please sign in first.');
        }

        // The current password is no longer asked for.
        //
        // For the case this screen exists to serve it proved nothing: an
        // account held here by EnsurePasswordChanged is still on the password
        // HR issued, and every account is issued the SAME one, so retyping it
        // demonstrated no knowledge an attacker lacked.
        //
        // WHAT IT DID STILL COVER, so this is a deliberate trade and not an
        // oversight: a voluntary change made from an already-authenticated
        // session. Someone at an unattended signed-in machine can now set a new
        // password without knowing the old one and lock the owner out. The
        // session is what guards this now — session()->regenerate() below, the
        // auth middleware, and whoever walks away from a logged-in terminal.
        //
        // To restore that protection for voluntary changes only, require the
        // field when the forced hold is NOT in play:
        //
        //     if (! session(EnsurePasswordChanged::SESSION_KEY)) { ...check... }
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'The two new passwords do not match.',
        ]);

        // Refusing the issued password here is the whole point — without this
        // the screen could be satisfied by typing it straight back in.
        if ($validated['password'] === (string) config('auth.default_password')) {
            throw ValidationException::withMessages([
                'password' => 'Please choose a password other than the one that was issued to you.',
            ]);
        }

        // Written with the query builder, not save(): Employee::boot() hashes
        // the password on `creating`, so a hash assigned to the model and saved
        // would be stored as-is on insert and double-hashed on update.
        $account->newQuery()
            ->where($account->getKeyName(), $account->getKey())
            ->update(['password' => Hash::make($validated['password'])]);

        // The account is no longer on the issued password, so the hold ends.
        $request->session()->forget(EnsurePasswordChanged::SESSION_KEY);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Your password has been changed.');
    }

    /** Whichever of the two guards is actually signed in. */
    private function account()
    {
        return Auth::guard('web')->user() ?: Auth::guard('employee')->user();
    }
}
