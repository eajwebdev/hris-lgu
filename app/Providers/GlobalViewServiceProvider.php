<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\InterviewEvaluation;
use App\Models\Employee;

class GlobalViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('*', function ($view) {
            $guard = guard();

            // Check if a user is authenticated
            if ($guard && auth()->guard($guard)->check()) {
                $user = auth()->guard($guard)->user();
                $role = $user->role;
                $userid = $user->id;

                $activeInterviewRatings = collect();
                $panelEmployeeId = null;

                if ($guard === 'employee') {
                    $panelEmployeeId = (int) $userid;
                } elseif ($guard === 'web') {
                    if (!empty($user->emp_ID)) {
                        $panelEmployeeId = Employee::where('emp_ID', $user->emp_ID)->value('id');
                    }

                    if (!$panelEmployeeId && !empty($user->username)) {
                        $panelEmployeeId = Employee::where('username', $user->username)
                            ->orWhere('org_email', $user->username)
                            ->value('id');
                    }

                    if (!$panelEmployeeId && !empty($user->fname) && !empty($user->lname)) {
                        $panelEmployeeId = Employee::whereRaw('LOWER(fname) = ?', [strtolower($user->fname)])
                            ->whereRaw('LOWER(lname) = ?', [strtolower($user->lname)])
                            ->value('id');
                    }
                }

                $panelEmployeeId = $panelEmployeeId ? (int) $panelEmployeeId : null;

                if ($panelEmployeeId) {
                    $activeInterviewRatings = InterviewEvaluation::with(['job', 'activeApplication'])
                        ->whereNotNull('active_application_id')
                        ->whereHas('applicants', function ($query) {
                            $query->where('is_cast', true)
                                ->whereColumn('interview_applicants.application_id', 'interview_evaluations.active_application_id');
                        })
                        ->whereHas('ratings', function ($query) use ($panelEmployeeId) {
                            $query->where('panel_employee_id', $panelEmployeeId)
                                ->whereColumn('interview_ratings.application_id', 'interview_evaluations.active_application_id');
                        })
                        ->latest()
                        ->get();
                }

                // Share variables to all views
                $view->with([
                    'role' => $role,
                    'guard' => $guard,
                    'userid' => $userid,
                    'activeInterviewRatings' => $activeInterviewRatings,
                    'activeInterviewRatingCount' => $activeInterviewRatings->count(),
                    'panelEmployeeId' => $panelEmployeeId,
                ]);
            }
        });
    }

    public function register()
    {
        //
    }
}
