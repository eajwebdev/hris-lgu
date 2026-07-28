<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsurePasswordChanged;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;

class LoginAuthController extends Controller
{

    public function getLoginAdmin()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }elseif(Auth::guard('employee')->check()){
            return redirect()->route('dashboard');
        }
        
        return view('login-page');
    }

    public function getLogin()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }elseif(Auth::guard('employee')->check()){
            return redirect()->route('dashboard');
        }

        return view('login');
    }
    
    public function postLogin(Request $request)
    {
        // The field accepts either a username or an email address. Older forms
        // posted it as "username", so both names are honoured.
        $request->merge(['login' => $request->input('login', $request->input('username'))]);

        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $login = trim($request->login);
        $password = $request->password;

        // Administrators / HR users sign in with their username.
        $user = User::where('username', $login)->first();

        if ($user && auth()->guard('web')->attempt(['username' => $user->username, 'password' => $password])) {
            return $this->afterLogin($request, $user);
        }

        // Employees may use their username or their organisational email.
        $employee = Employee::where('username', $login)
            ->orWhere('org_email', $login)
            ->first();

        if ($employee) {
            if ($employee->stat_1 != 1) {
                return redirect()->back()->with('error', 'Account Suspended');
            }

            if (auth()->guard('employee')->attempt(['username' => $employee->username, 'password' => $password])) {
                return $this->afterLogin($request, $employee);
            }
        }

        return redirect()->back()->with('error', 'Invalid Credentials');
    }

    /**
     * Where a successful sign-in lands.
     *
     * The "is this still the issued password" question is answered once, here,
     * and carried in the session: asking it on every request would mean a
     * bcrypt comparison per page load. EnsurePasswordChanged reads the flag.
     */
    private function afterLogin(Request $request, $account)
    {
        $request->session()->regenerate();

        if (EnsurePasswordChanged::isDefault($account->password)) {
            $request->session()->put(EnsurePasswordChanged::SESSION_KEY, true);

            return redirect()->route('password.change');
        }

        return redirect()->route('dashboard')->with('success', 'Login Successfully');
    }
}
