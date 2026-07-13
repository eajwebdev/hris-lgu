<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LoginAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->guard('web')->check()) {
            $userRole = auth()->guard('web')->user()->role;
    
            if ($userRole !== 'Administrator') {
                if ($request->is('user') || $request->is('user/*')) {
                    return redirect()->route('dashboard')->with('error1', 'You do not have permission to access this page');
                }
            }
        } elseif (auth()->guard('employee')->check()) {
            if ($request->is('users') || $request->is('users/*') || $request->is('office') || $request->is('office/*') || $request->is('employees') || $request->is('leave/status/*') || $request->is('leave/history/*')
            || $request->is('tardiness') || $request->is('leaves') || $request->is('pending/*') || $request->is('pds/family-bg/*') || $request->is('pds/educ-bg/*') || $request->is('pds/eligibility/*') 
            || $request->is('pds/work-experience/*') || $request->is('pds/voluntary-work/*') || $request->is('pds/learning-dev/*') || $request->is('pds/other-info/*') || $request->is('pds/info-question/*') 
            || $request->is('pds/references/*') || $request->is('pds/government-id/*')) {
                return redirect()->route('dashboard')->with('error1', 'You do not have permission to access this page');
            }
            if ($request->is('leave') && auth()->guard('employee')->user()->emp_status != 1) {
                return redirect()->route('dashboard')->with('error1', 'You do not have permission to access this page');
            }    

        }else {
            return redirect()->route('getLogin')->with('error1', 'You have to sign in first to access this page');
        }
        
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 1990 00:00:00 GMT');

        return $response;
    }
}
