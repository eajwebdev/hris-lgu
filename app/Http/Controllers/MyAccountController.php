<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Enployee;
use App\Models\User;

class MyAccountController extends Controller
{

    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function myAccount(){
        $guard = $this->getGuard();
        return view('account.my-account', compact('guard'));
    } 

    public function updateAccount(Request $request)
    {
        $guard = $this->getGuard();

        $request->validate([
            'username' => 'required|string|max:255',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);
    
        $user = Auth::guard($guard)->user();
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect'])->withInput();
        }
    
        $user->username = $request->input('username');
        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        Auth::guard($guard)->logout();

        $credentials = [
            'username' => $request->input('username'),
            'password' => $request->input('new_password'),
        ];

        if (Auth::guard($guard)->attempt($credentials)) {
            return redirect()->back()->with('success', 'Updated successfully');
        }

        return redirect()->back()->withErrors(['login' => 'Could not log in with new credentials. Please try again.']);
    }
    
}