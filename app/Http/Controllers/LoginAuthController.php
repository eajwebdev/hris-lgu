<?php

namespace App\Http\Controllers;

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
    
    protected function postLogin(Request $request)
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
            return redirect()->route('dashboard')->with('success', 'Login Successfully');
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
                return redirect()->route('dashboard')->with('success', 'Login Successfully');
            }
        }

        return redirect()->back()->with('error', 'Invalid Credentials');
    }
    
}
