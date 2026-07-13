<?php

namespace App\Http\Controllers;

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
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google has already authenticated the account, so the login completes here.
     * No verification code is generated or emailed.
     */
    public function handleGoogleCallback()
    {
        try {
            $google_user = Socialite::driver('google')->user();
            $email = $google_user->getEmail();

            $user = User::where('username', $email)->first();
            $employee = Employee::where('username', $email)->first();

            if (!$user && !$employee) {
                return redirect()->route('getLogin')
                    ->with('error', 'We couldn\'t find your email. Please contact HR for assistance.');
            }

            if ($user) {
                Auth::login($user);
                return redirect()->route('dashboard')->with('success', 'Login Successfully');
            }

            if ($employee->stat_1 != 1) {
                return redirect()->route('getLogin')->with('error', 'Account Suspended');
            }

            Auth::guard('employee')->login($employee);
            return redirect()->route('dashboard')->with('success', 'Login Successfully');

        } catch (\Exception $e) {
            Log::error('Google OAuth Error: ' . $e->getMessage());
            return redirect()->route('getLogin')
                ->with('error', 'There was an issue with Google OAuth. Please try again.');
        }
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
