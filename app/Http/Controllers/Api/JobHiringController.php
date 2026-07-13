<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobHiring;

class JobHiringController extends Controller
{
    public function jobList(){
        $jobs = JobHiring::where('status', 'Open')->get();

        return response()->json($jobs);
    }
}
