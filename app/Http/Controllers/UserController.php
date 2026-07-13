<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    public function getGuaard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function ulist()
    {
        $guard = $this->getGuaard();

        $users = User::select('users.id as uid', 'users.*')->get();

        return view("users.user-list", compact('users', 'guard'));
    }

    public function uCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lname' => 'required',
            'fname' => 'required',
            'mname' => 'required',
            'username' => 'required|unique:users',
            'role' => 'required',
            'gender' => 'required',
            'access' => 'array',
            'access.*' => 'in:0,1',
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        $password = Hash::make($request->input('password'));
    
        $userData = [
            'fname' => $request->input('fname'),
            'mname' => $request->input('mname'),
            'lname' => $request->input('lname'),
            'username' => $request->input('username'),
            'role' => $request->input('role'),
            'gender' => $request->input('gender'),
        ];
    
        $accessPermissions = array_fill(0, 8, '0');
        foreach ($request->input('access', []) as $index => $value) {
            $accessPermissions[$index] = '1';
        }
    
        // if ($request->input('Role') === 'Administrator') {
        //     $accessPermissions[7] = '1';
        // }else{
        //     $accessPermissions[7] = '0';
        // }
        
        $userData['access'] = implode(',', $accessPermissions);
    
        User::create($userData);
    
        return redirect()->back()->with('success', 'User created successfully.');
    }
    
    public function uEdit($id)
    {
        $guard = $this->getGuaard();
        $users = User::select('id as uid', 'users.*')->get();
        $uEdit = User::find($id);
    
        if (!$uEdit) {
            return redirect()->back()->with('error', 'User not found.');
        }
    
        return view("users.user-list", compact('users', 'uEdit', 'guard'));
    }  

    public function uUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lname' => 'required',
            'fname' => 'required',
            'mname' => 'required',
            'username' => 'required',
            'password' => 'nullable',
            'role' => 'required',
            'gender' => 'required',
            'access' => 'array',
            'access.*' => 'in:0,1',
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        $user = User::find($request->input('uid'));
    
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found']);
        }
    
        $userData = [
            'fname' => $request->input('fname'),
            'mname' => $request->input('mname'),
            'lname' => $request->input('lname'),
            'username' => $request->input('username'),
            'role' => $request->input('role'),
            'gender' => $request->input('gender'),
        ];
    
        $accessPermissions = array_fill(0, 9, '0');
    
        foreach ($request->input('access', []) as $index => $value) {
            $accessPermissions[$index] = '1';
        }
    
        $userData['access'] = implode(',', $accessPermissions);
    
        $user->update($userData);
    
        return redirect()->back()->with('success', 'User updated successfully.');
    }    

    public function uDelete(Request $request) {
        $user = User::find($request->id);
    
        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'pmt not found',
            ]);
        }
    
        $user->delete();
    
        return response()->json([
            'status' => 200,
            'id' => $user->id,
        ]);
    }

}
