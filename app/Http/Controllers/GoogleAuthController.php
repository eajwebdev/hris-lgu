<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsurePasswordChanged;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        // Without credentials Socialite still builds a redirect, and the
        // employee lands on a raw Google "invalid_request" page with no way
        // back. Catching it here keeps the failure inside the app, where the
        // sign-in form can say what is actually wrong.
        if (! $this->configured()) {
            Log::error('Google OAuth is not configured: GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET are empty.');

            return redirect()->route('getLogin')
                ->with('error', 'Google sign-in is not set up yet. Please use your HRIS username and password, or contact HR.');
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Google has already authenticated the account, so the login completes here.
     * No verification code is generated or emailed.
     */
    public function handleGoogleCallback()
    {
        if (! $this->configured()) {
            return redirect()->route('getLogin')
                ->with('error', 'Google sign-in is not set up yet. Please use your HRIS username and password, or contact HR.');
        }

        try {
            $google_user = Socialite::driver('google')->user();
            $email = trim((string) $google_user->getEmail());

            if ($email === '') {
                return redirect()->route('getLogin')
                    ->with('error', 'Google did not share an email address with us. Please contact HR for assistance.');
            }

            $user = User::where('username', $email)->first();

            // Matched the same way the password form matches them: an employee
            // added through the HR form carries their emp_ID as the username
            // and their address in org_email, so matching on username alone
            // would lock exactly those people out of Google sign-in.
            $employee = Employee::where('username', $email)
                ->orWhere('org_email', $email)
                ->first();

            if (!$user && !$employee) {
                return redirect()->route('getLogin')
                    ->with('error', 'We couldn\'t find your email. Please contact HR for assistance.');
            }

            if ($user) {
                Auth::login($user);

                return $this->afterLogin($user);
            }

            if ($employee->stat_1 != 1) {
                return redirect()->route('getLogin')->with('error', 'Account Suspended');
            }

            Auth::guard('employee')->login($employee);

            return $this->afterLogin($employee);

        } catch (\Exception $e) {
            Log::error('Google OAuth Error: ' . $e->getMessage());
            return redirect()->route('getLogin')
                ->with('error', 'There was an issue with Google OAuth. Please try again.');
        }
    }

    /**
     * Google vouched for who they are, which says nothing about the password
     * sitting on the record. An account still carrying the issued one is held
     * on the change screen exactly as it would be after a password sign-in —
     * otherwise "Continue with Google" would be a way around it.
     */
    private function afterLogin($account)
    {
        session()->regenerate();

        if (EnsurePasswordChanged::isDefault($account->password)) {
            session()->put(EnsurePasswordChanged::SESSION_KEY, true);

            return redirect()->route('password.change');
        }

        return redirect()->route('dashboard')->with('success', 'Login Successfully');
    }

    /**
     * Whether Google sign-in has actually been given credentials. The keys ship
     * in .env as empty placeholders, so their presence proves nothing.
     */
    private function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    /**
     * Retained so any bookmarked /verify link lands back on the sign-in page.
     */
    public function verifyForm(Request $request)
    {
        return redirect()->route('getLogin');
    }

    public function verify(Request $request)
    {
        return redirect()->route('getLogin');
    }
}
