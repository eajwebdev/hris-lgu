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
        
        return view('login-page', ['demoAccounts' => $this->demoAccounts()]);
    }

    public function getLogin()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }elseif(Auth::guard('employee')->check()){
            return redirect()->route('dashboard');
        }

        return view('login', ['demoAccounts' => $this->demoAccounts()]);
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
     * Demo quick access: sign in as any listed account without a password.
     *
     * Exists only while APP_DEMO is on — the route is public but the action
     * 404s otherwise, so nothing reaches it in production. It also skips the
     * "replace the issued password" hold, since a demo is rarely given by
     * somebody who wants to stop and set eighty passwords first.
     */
    public function demoLogin(Request $request)
    {
        abort_unless(config('app.demo'), 404);

        $request->validate([
            'account' => ['required', 'regex:/^(web|employee):\d+$/'],
        ]);

        [$guard, $id] = explode(':', $request->account);

        $account = $guard === 'web' ? User::find($id) : Employee::find($id);

        if (! $account) {
            return redirect()->back()->with('error', 'Invalid Credentials');
        }

        if ($guard === 'employee' && $account->stat_1 != 1) {
            return redirect()->back()->with('error', 'Account Suspended');
        }

        // One identity per session. LoginAuth checks the "web" guard first, so
        // a left-over admin login would otherwise win over the account just
        // picked — drop the other guard before logging this one in.
        Auth::guard($guard === 'web' ? 'employee' : 'web')->logout();
        Auth::guard($guard)->login($account);

        $request->session()->regenerate();
        $request->session()->forget(EnsurePasswordChanged::SESSION_KEY);

        return redirect()->route('dashboard')->with('success', 'Login Successfully');
    }

    /**
     * The accounts the demo panel offers, grouped the way it lists them.
     * Null when demo mode is off, so the views render nothing.
     */
    private function demoAccounts(): ?array
    {
        if (! config('app.demo')) {
            return null;
        }

        $label = fn ($account) => trim($account->fname . ' ' . $account->lname) ?: $account->username;

        $admins = User::orderBy('id')->get(['id', 'fname', 'lname', 'username', 'role'])
            ->map(fn ($user) => [
                'key'      => 'web:' . $user->id,
                'name'     => $label($user),
                'username' => $user->username,
                'role'     => $user->role ?: 'Administrator',
            ]);

        $employees = Employee::orderBy('lname')->orderBy('fname')
            ->get(['id', 'fname', 'lname', 'username', 'role', 'position', 'stat_1'])
            ->map(fn ($employee) => [
                'key'      => 'employee:' . $employee->id,
                'name'     => $label($employee),
                'username' => $employee->username,
                'role'     => $employee->stat_1 != 1
                    ? 'Suspended'
                    : ($employee->position ?: ucfirst((string) ($employee->role ?: 'employee'))),
            ]);

        return array_filter([
            'Administrators' => $admins->all(),
            'Employees'      => $employees->all(),
        ]);
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
