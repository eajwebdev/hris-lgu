<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveCredit;
use App\Models\LeaveApplication;
use App\Models\Notification;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }  

    // public function loadMore(Request $request, $page)
    // {
    //     $guard = $this->getGuard();
    //     // Check if the request is AJAX
    //     if ($request->ajax()) {
    //         $notifications = Notification::query()
    //         ->select(
    //             'notifications.*',
    //             'notifications.status as notifstat',
    //             'notifications.created_at as notif_created_at'
    //         )
    //         ->where('notifications.utype', '=', 'hr')
            
    //         // Join for "leave" module
    //         ->leftJoin('leave_applications', function ($join) {
    //             $join->on('notifications.lapp_id', '=', 'leave_applications.id')
    //                  ->where('notifications.module', '=', 'leave');
    //         })
    //         ->leftJoin('employees as leave_emp', function ($join) {
    //             $join->on('leave_emp.emp_ID', '=', 'leave_applications.empid')
    //                  ->where('notifications.module', '=', 'leave');
    //         })
            
    //         // Join for "pds eligibilities" module with category 1
    //         ->leftJoin('eligibilities', function ($join) {
    //             $join->on('notifications.lapp_id', '=', 'eligibilities.id')
    //                  ->where('notifications.category', '=', 1)
    //                  ->where('notifications.module', '=', 'pds');
    //         })
    //         ->leftJoin('employees as pds_emp_eligi', function ($join) {
    //             $join->on('pds_emp_eligi.emp_ID', '=', 'eligibilities.empid')
    //                  ->where('notifications.category', '=', 1)
    //                  ->where('notifications.module', '=', 'pds');
    //         })
    
    //         // Join for "pds work_experiences" module with category 2
    //         ->leftJoin('work_experiences', function ($join) {
    //             $join->on('notifications.lapp_id', '=', 'work_experiences.id')
    //                     ->where('notifications.category', '=', 2)
    //                     ->where('notifications.module', '=', 'pds');
    //         })
    //         ->leftJoin('employees as pds_emp_workexp', function ($join) {
    //             $join->on('pds_emp_workexp.emp_ID', '=', 'work_experiences.empid')
    //                     ->where('notifications.category', '=', 2)
    //                     ->where('notifications.module', '=', 'pds');
    //         })  
    
    //         // Join for "pds voluntary_works" module with category 3
    //         ->leftJoin('voluntary_works', function ($join) {
    //             $join->on('notifications.lapp_id', '=', 'voluntary_works.id')
    //                     ->where('notifications.category', '=', 3)
    //                     ->where('notifications.module', '=', 'pds');
    //         })
    //         ->leftJoin('employees as pds_emp_volworks', function ($join) {
    //             $join->on('pds_emp_volworks.emp_ID', '=', 'voluntary_works.empid')
    //                     ->where('notifications.category', '=', 3)
    //                     ->where('notifications.module', '=', 'pds');
    //         })  
    
    //         // Join for "pds learning_devs" module with category 4
    //         ->leftJoin('learning_devs', function ($join) {
    //             $join->on('notifications.lapp_id', '=', 'learning_devs.id')
    //                     ->where('notifications.category', '=', 4)
    //                     ->where('notifications.module', '=', 'pds');
    //         })
    //         ->leftJoin('employees as pds_emp_learndev', function ($join) {
    //             $join->on('pds_emp_learndev.emp_ID', '=', 'learning_devs.empid')
    //                     ->where('notifications.category', '=', 4)
    //                     ->where('notifications.module', '=', 'pds');
    //         })  
            
    //         ->addSelect(
    //             'leave_applications.*', 'leave_emp.*', 'leave_emp.id as leave_emp_id', 'leave_emp.profile as leave_emp_profile', \DB::raw("CONCAT(leave_emp.fname, ' ', leave_emp.lname) as leave_emp_fullname"),
    //             'pds_emp_eligi.*', 'pds_emp_eligi.id as pds_emp_eligi_id', 'pds_emp_eligi.profile as pds_emp_eligi_profile', \DB::raw("CONCAT(pds_emp_eligi.fname, ' ', pds_emp_eligi.lname) as pds_emp_eligi_fullname"),
    //             'pds_emp_workexp.*', 'pds_emp_workexp.id as pds_emp_workexp_id', 'pds_emp_workexp.profile as pds_emp_workexp_profile', \DB::raw("CONCAT(pds_emp_workexp.fname, ' ', pds_emp_workexp.lname) as pds_emp_workexp_fullname"),
    //             'pds_emp_volworks.*', 'pds_emp_volworks.id as pds_emp_volworks_id', 'pds_emp_volworks.profile as pds_emp_volworks_profile', \DB::raw("CONCAT(pds_emp_volworks.fname, ' ', pds_emp_volworks.lname) as pds_emp_volworks_fullname"),
    //             'pds_emp_learndev.*', 'pds_emp_learndev.id as pds_emp_learndev_id', 'pds_emp_learndev.profile as pds_emp_learndev_profile', \DB::raw("CONCAT(pds_emp_learndev.fname, ' ', pds_emp_learndev.lname) as pds_emp_learndev_fullname")
    //         )
    //         ->orderBy('notifications.created_at', 'desc')
    //         ->paginate(10, ['*'], 'page', $page); // Paginate the results
    
    //         // Check if notifications are empty
    //         if ($notifications->isEmpty()) {
    //             return response()->json(['html' => '']);
    //         }
    
    //         // Render the notifications view
    //         $view = view('partials.notification_items', compact('notifications'))->render();
    //         return response()->json(['html' => $view]);
    //     }
    
    //     // Handle non-AJAX requests
    //     return response()->json(['html' => '']);
    // }

    public function loadMore(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(['html' => '']);
        }

        $offset = intval($request->offset ?? 0);
        $limit = 10;

        $notifications = Notification::query()
            ->select(
                'notifications.id',
                'notifications.lapp_id',
                'notifications.module',
                'notifications.category',
                'notifications.status as notifstat',
                'notifications.created_at as notif_created_at'
            )
            ->where('notifications.utype', 'hr')

            // LEAVE
            ->leftJoin('leave_applications', function ($join) {
                $join->on('notifications.lapp_id', '=', 'leave_applications.id')
                    ->where('notifications.module', 'leave');
            })
            ->leftJoin('employees as leave_emp', function ($join) {
                $join->on('leave_emp.emp_ID', '=', 'leave_applications.empid')
                    ->where('notifications.module', 'leave');
            })
            ->addSelect(
                'leave_applications.leave_type',
                'leave_applications.transnum',
                'leave_emp.id as leave_emp_id',
                'leave_emp.fname as leave_emp_fname',
                'leave_emp.lname as leave_emp_lname',
                'leave_emp.profile as leave_emp_profile',
                \DB::raw("CONCAT(leave_emp.fname, ' ', leave_emp.lname) as leave_emp_fullname")
            )

            // PDS CATEGORY 1 (eligibilities)
            ->leftJoin('eligibilities', function ($join) {
                $join->on('notifications.lapp_id', '=', 'eligibilities.id')
                    ->where('notifications.module', 'pds')
                    ->where('notifications.category', 1);
            })
            ->leftJoin('employees as pds_emp_eligi', function ($join) {
                $join->on('pds_emp_eligi.emp_ID', '=', 'eligibilities.empid')
                    ->where('notifications.module', 'pds')
                    ->where('notifications.category', 1);
            })
            ->addSelect(
                'pds_emp_eligi.id as pds_emp_eligi_id',
                'pds_emp_eligi.fname as pds_emp_eligi_fname',
                'pds_emp_eligi.lname as pds_emp_eligi_lname',
                'pds_emp_eligi.profile as pds_emp_eligi_profile',
                \DB::raw("CONCAT(pds_emp_eligi.fname, ' ', pds_emp_eligi.lname) as pds_emp_eligi_fullname")
            )

            // PDS CATEGORY 2 (work_experiences)
            ->leftJoin('work_experiences', function ($join) {
                $join->on('notifications.lapp_id', '=', 'work_experiences.id')
                    ->where('notifications.module', 'pds')
                    ->where('notifications.category', 2);
            })
            ->leftJoin('employees as pds_emp_workexp', function ($join) {
                $join->on('pds_emp_workexp.emp_ID', '=', 'work_experiences.empid')
                    ->where('notifications.module', 'pds')
                    ->where('notifications.category', 2);
            })
            ->addSelect(
                'pds_emp_workexp.id as pds_emp_workexp_id',
                'pds_emp_workexp.fname as pds_emp_workexp_fname',
                'pds_emp_workexp.lname as pds_emp_workexp_lname',
                'pds_emp_workexp.profile as pds_emp_workexp_profile',
                \DB::raw("CONCAT(pds_emp_workexp.fname, ' ', pds_emp_workexp.lname) as pds_emp_workexp_fullname")
            )

            // PDS CATEGORY 3 (voluntary_works)
            ->leftJoin('voluntary_works', function ($join) {
                $join->on('notifications.lapp_id', '=', 'voluntary_works.id')
                    ->where('notifications.module', 'pds')
                    ->where('notifications.category', 3);
            })
            ->leftJoin('employees as pds_emp_volworks', function ($join) {
                $join->on('pds_emp_volworks.emp_ID', '=', 'voluntary_works.empid')
                    ->where('notifications.module', 'pds')
                    ->where('notifications.category', 3);
            })
            ->addSelect(
                'pds_emp_volworks.id as pds_emp_volworks_id',
                'pds_emp_volworks.fname as pds_emp_volworks_fname',
                'pds_emp_volworks.lname as pds_emp_volworks_lname',
                'pds_emp_volworks.profile as pds_emp_volworks_profile',
                \DB::raw("CONCAT(pds_emp_volworks.fname, ' ', pds_emp_volworks.lname) as pds_emp_volworks_fullname")
            )

            // PDS CATEGORY 4 (learning_devs)
            ->leftJoin('learning_devs', function ($join) {
                $join->on('notifications.lapp_id', '=', 'learning_devs.id')
                    ->where('notifications.module', 'pds')
                    ->where('notifications.category', 4);
            })
            ->leftJoin('employees as pds_emp_learndev', function ($join) {
                $join->on('pds_emp_learndev.emp_ID', '=', 'learning_devs.empid')
                    ->where('notifications.module', 'pds')
                    ->where('notifications.category', 4);
            })
            ->addSelect(
                'pds_emp_learndev.id as pds_emp_learndev_id',
                'pds_emp_learndev.fname as pds_emp_learndev_fname',
                'pds_emp_learndev.lname as pds_emp_learndev_lname',
                'pds_emp_learndev.profile as pds_emp_learndev_profile',
                \DB::raw("CONCAT(pds_emp_learndev.fname, ' ', pds_emp_learndev.lname) as pds_emp_learndev_fullname")
            )

            // ATTENDANCE (out-of-range punches flagged for clarification)
            ->leftJoin('attendance_punch_logs as att_log', function ($join) {
                $join->on('notifications.lapp_id', '=', 'att_log.id')
                    ->where('notifications.module', 'attendance');
            })
            ->leftJoin('employees as att_emp', function ($join) {
                $join->on('att_emp.id', '=', 'att_log.employee_id')
                    ->where('notifications.module', 'attendance');
            })
            ->addSelect(
                'att_emp.id as att_emp_id',
                'att_emp.profile as att_emp_profile',
                'att_log.action as att_action',
                'att_log.station_name as att_station_name',
                'att_log.distance_m as att_distance_m',
                \DB::raw("CONCAT(att_emp.fname, ' ', att_emp.lname) as att_emp_fullname")
            )

            ->orderBy('notifications.created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        if ($notifications->isEmpty()) {
            return response()->json(['html' => '', 'stop' => true]);
        }

        $view = view('partials.notification_items', ['notifications' => $notifications])->render();

        return response()->json([
            'html' => $view,
            'nextOffset' => $offset + $limit
        ]);
    }

    public function updateNotif($menid,$lappid,$menu){
        $categories = [
            "eligibility" => 1,
            "work-experience" => 2,
            "voluntary-work" => 3,
            "learning-dev" => 4
        ];
        
        if (isset($categories[$menu])) {
            $category = $categories[$menu];
            $notification = Notification::where('lapp_id', $lappid)->where('category', $category)->where('module', 'pds')->update(['status' => 1]);
        }

        // $menu is a route name supplied in the URL — never trust it blindly.
        if (!\Route::has($menu)) {
            return redirect()->route('dashboard');
        }

        return redirect()->route($menu, $menid);
    }

    public function markAllRead()
    {
        $guard = $this->getGuard();

        if ($guard === 'web') {
            Notification::where('utype', 'hr')
                ->where('status', 0)
                ->update(['status' => 1]);
        }

        if ($guard === 'employee') {
            Notification::where('utype', 'employee')
                ->where('empid', auth()->guard('employee')->user()->emp_ID)
                ->whereNotIn('module', ['leavecredit', 'leavecreditadd'])
                ->where('status', 0)
                ->update(['status' => 1]);
        }

        return redirect()->back()->with('success', 'Notifications marked as read');
    }
}
