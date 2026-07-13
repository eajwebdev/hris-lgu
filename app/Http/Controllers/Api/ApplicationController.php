<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Carbon\Carbon;


class ApplicationController extends Controller
{
    function shortDecrypt($encrypted)
    {
        $key = 'fA7xB93kL0pTzWmQ';
        $cipher = 'AES-128-ECB';
        $encrypted = strtr($encrypted, '-_', '+/');
        return openssl_decrypt(base64_decode($encrypted), $cipher, $key, 0);
    }


    // public function applicationStore(Request $request)
    // {
    //     $request->validate([
    //         'jid' => 'required|integer',
    //         'first_name' => 'required|string',
    //         'last_name' => 'required|string',
    //         'age' => 'required|integer|min:18|max:65',
    //         'sex' => 'required|string',
    //         'mobile' => 'required|string',
    //         'email' => 'required|email',
    //         'address' => 'required|string',
    //         'education' => 'required|array',
    //         'elevel' => 'required|array',
    //         'eyear' => 'required|array',
    //         'eligibility' => 'nullable|array',
    //         'pds' => 'required|mimes:pdf|max:20480',
    //         'wes' => 'required|mimes:pdf|max:20480',
    //         'intent' => 'required|mimes:pdf|max:20480',
    //         'resume' => 'required|mimes:pdf|max:20480',
    //         'tor' => 'required|mimes:pdf|max:20480',
    //         'coe' => 'nullable|mimes:pdf|max:20480',
    //         'cert_training.*' => 'nullable|mimes:pdf|max:20480',
    //     ]);

    //     $exists = Application::where('email', $request->email)
    //                         ->where('jid', $request->jid)
    //                         ->exists();

    //     if ($exists) {
    //         return response()->json([
    //             'message' => 'You have already applied for this position!',
    //         ], 409);
    //     }

    //     // 📂 File Uploads
    //     $paths = [];
    //     foreach (['pds','wes','intent','resume','tor','coe'] as $fileKey) {
    //         if ($request->hasFile($fileKey)) {
    //             $paths[$fileKey] = $request->file($fileKey)->store('applications', 'public');
    //         }
    //     }

    //     if ($request->hasFile('cert_training')) {
    //         $certPaths = [];
    //         foreach ($request->file('cert_training') as $file) {
    //             $certPaths[] = $file->store('applications/cert_training', 'public');
    //         }
    //         $paths['cert_training'] = implode(',', $certPaths);
    //     }

    //     // 🧾 Combine educations
    //     $educationList = [];
    //     foreach ($request->education as $i => $desc) {
    //         $educationList[] = $desc . ' (' . ($request->elevel[$i] ?? '') . ', ' . ($request->eyear[$i] ?? '') . ')';
    //     }
    //     $educationString = implode(', ', $educationList);

    //     // 🏅 Combine eligibilities
    //     $eligibilityString = $request->eligibility ? implode(', ', $request->eligibility) : null;

    //     // 🧠 Generate unique application number: APP-2025-0143A
    //     $year = Carbon::now()->format('Y');
    //     $randomDigits = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    //     $randomLetter = strtoupper(Str::random(1));
    //     $applicationNumber = "APP-{$year}-{$randomDigits}{$randomLetter}";

    //     // 💾 Save application
    //     $application = Application::create(array_merge($request->only([
    //         'jid', 'first_name', 'middle_name', 'last_name',
    //         'age', 'sex', 'mobile', 'email', 'address',
    //     ]), [
    //         'app_number' => $applicationNumber,
    //         'education' => $educationString,
    //         'eligibility' => $eligibilityString,
    //     ], $paths));

    //     // 📧 Send email directly here
    //     try {
    //         $toEmail = 'cpsu_career@cpsu.edu.ph'; // ✅ fixed

    //         $subject = "New Job Application: {$request->first_name} {$request->last_name}";
            
    //         $body = '
    //             <div style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px;">
    //                 <div style="max-width: 600px; margin: auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
    //                     <div style="background-color: #004aad; color: white; padding: 16px 24px;">
    //                         <h2 style="margin: 0; font-size: 20px;">New Job Application Received</h2>
    //                     </div>
    //                     <div style="padding: 24px; color: #333;">
    //                         <p style="font-size: 16px; margin-bottom: 10px;">Dear HR Team,</p>
    //                         <p style="margin-bottom: 20px;">A new job application has been submitted via the CPSU Career Portal. Here are the details:</p>

    //                         <table style="width: 100%; border-collapse: collapse;">
    //                             <tr><td style="padding: 6px 0; font-weight: bold;">Application No:</td><td>' . $applicationNumber . '</td></tr>
    //                             <tr><td style="padding: 6px 0; font-weight: bold;">Name:</td><td>' . $request->first_name . ' ' . $request->last_name . '</td></tr>
    //                             <tr><td style="padding: 6px 0; font-weight: bold;">Email:</td><td>' . $request->email . '</td></tr>
    //                             <tr><td style="padding: 6px 0; font-weight: bold;">Mobile:</td><td>' . $request->mobile . '</td></tr>
    //                             <tr><td style="padding: 6px 0; font-weight: bold;">Address:</td><td>' . $request->address . '</td></tr>
    //                             <tr><td style="padding: 6px 0; font-weight: bold;">Age:</td><td>' . $request->age . '</td></tr>
    //                             <tr><td style="padding: 6px 0; font-weight: bold;">Sex:</td><td>' . $request->sex . '</td></tr>
    //                             <tr><td style="padding: 6px 0; font-weight: bold;">Education:</td><td>' . $educationString . '</td></tr>' .
    //                             ($eligibilityString ? '<tr><td style="padding: 6px 0; font-weight: bold;">Eligibility:</td><td>' . $eligibilityString . '</td></tr>' : '') . '
    //                         </table>

    //                         <div style="margin-top: 24px; padding: 12px; background: #f0f4ff; border-left: 4px solid #004aad;">
    //                             <p style="margin: 0;">📎 The applicant’s <strong>Intent Letter (PDF)</strong> is attached to this email.</p>
    //                         </div>

    //                         <p style="margin-top: 24px;">Best regards,<br><strong>CPSU Online Career Portal</strong></p>
    //                     </div>
    //                     <div style="background: #f1f1f1; text-align: center; padding: 10px; font-size: 12px; color: #555;">
    //                         © ' . date('Y') . ' Central Philippines State University | HRIS
    //                     </div>
    //                 </div>
    //             </div>
    //         ';

    //         Mail::send([], [], function ($message) use ($toEmail, $subject, $body, $paths) {
    //             $message->to($toEmail)
    //                     ->from(config('mail.from.address'), config('mail.from.name'))
    //                     ->subject($subject)
    //                     ->html($body);

    //             if (isset($paths['intent'])) {
    //                 $message->attach(storage_path('app/public/' . $paths['intent']));
    //             }
    //         });

    //     } catch (\Exception $e) {
    //         \Log::error('❌ Failed to send email: ' . $e->getMessage());
    //     }

    //     return response()->json([
    //         'message' => 'Application submitted successfully!',
    //         'data' => [
    //             'id' => shortDecrypt($application->id),
    //             'app_number' => $application->app_number,
    //             'first_name' => $application->first_name,
    //             'last_name' => $application->last_name,
    //         ]
    //     ], 201);
    // }

    public function applicationStore(Request $request)
    {
        $request->validate([
            'jid' => 'required|integer',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'age' => 'required|integer|min:18|max:65',
            'sex' => 'required|string',
            'mobile' => 'required|string',
            'email' => 'required|email',
            'address' => 'required|string',
            'education' => 'required|array',
            'elevel' => 'required|array',
            'eyear' => 'required|array',
            'eligibility' => 'nullable|array',
            'pds' => 'required|mimes:pdf|max:20480',
            'wes' => 'required|mimes:pdf|max:20480',
            'intent' => 'required|mimes:pdf|max:20480',
            'resume' => 'required|mimes:pdf|max:20480',
            'tor' => 'required|mimes:pdf|max:20480',
            'coe' => 'nullable|mimes:pdf|max:20480',
            'cert_training.*' => 'nullable|mimes:pdf|max:20480',
        ]);

        // 🧩 Prevent duplicate application
        $exists = Application::where('email', $request->email)
                            ->where('jid', $request->jid)
                            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You have already applied for this position!',
            ], 409);
        }

        // 📂 File Uploads
        $paths = [];
        foreach (['pds','wes','intent','resume','tor','coe'] as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $paths[$fileKey] = $request->file($fileKey)->store('applications', 'public');
            }
        }

        if ($request->hasFile('cert_training')) {
            $certPaths = [];
            foreach ($request->file('cert_training') as $file) {
                $certPaths[] = $file->store('applications/cert_training', 'public');
            }
            $paths['cert_training'] = implode(',', $certPaths);
        }

        // 🎓 Combine education info
        $educationList = [];
        foreach ($request->education as $i => $desc) {
            $educationList[] = $desc . ' (' . ($request->elevel[$i] ?? '') . ', ' . ($request->eyear[$i] ?? '') . ')';
        }
        $educationString = implode(', ', $educationList);

        // 🏅 Combine eligibilities
        $eligibilityString = $request->eligibility ? implode(', ', $request->eligibility) : null;

        // 🧠 Generate unique application number: APP-2025-0143A
        $year = Carbon::now()->format('Y');
        $randomDigits = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $randomLetter = strtoupper(Str::random(1));
        $applicationNumber = "APP-{$year}-{$randomDigits}{$randomLetter}";

        // 💾 Save application
        $application = Application::create(array_merge($request->only([
            'jid', 'first_name', 'middle_name', 'last_name',
            'age', 'sex', 'mobile', 'email', 'address',
        ]), [
            'app_number' => $applicationNumber,
            'education' => $educationString,
            'eligibility' => $eligibilityString,
        ], $paths));

        // 📧 Send email to HR and applicant
        try {
            $toEmail = optional(\App\Models\Setting::first())->records_office_email ?: config('mail.from.address');
            $green = '#187744';
            $trackingUrl = url('/careers');

            // ===== 📩 Email to Records =====
            $subjectHR = "New Job Application: {$request->first_name} {$request->last_name}";
            $bodyHR = '
                <div style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px;">
                    <div style="max-width: 600px; margin: auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                        <div style="background-color: '.$green.'; color: white; padding: 16px 24px;">
                            <h2 style="margin: 0; font-size: 20px;">New Job Application Received</h2>
                        </div>
                        <div style="padding: 24px; color: #333;">
                            <p style="font-size: 16px; margin-bottom: 10px;">Dear Records Office Team,</p>
                            <p style="margin-bottom: 20px;">A new job application has been submitted via the CPSU Career Portal. Here are the details:</p>

                            <table style="width: 100%; border-collapse: collapse;">
                                <tr><td style="padding: 6px 0; font-weight: bold;">Application No:</td><td>' . $applicationNumber . '</td></tr>
                                <tr><td style="padding: 6px 0; font-weight: bold;">Name:</td><td>' . $request->first_name . ' ' . $request->last_name . '</td></tr>
                                <tr><td style="padding: 6px 0; font-weight: bold;">Email:</td><td>' . $request->email . '</td></tr>
                                <tr><td style="padding: 6px 0; font-weight: bold;">Mobile:</td><td>' . $request->mobile . '</td></tr>
                                <tr><td style="padding: 6px 0; font-weight: bold;">Address:</td><td>' . $request->address . '</td></tr>
                                <tr><td style="padding: 6px 0; font-weight: bold;">Age:</td><td>' . $request->age . '</td></tr>
                                <tr><td style="padding: 6px 0; font-weight: bold;">Sex:</td><td>' . $request->sex . '</td></tr>
                                <tr><td style="padding: 6px 0; font-weight: bold;">Education:</td><td>' . $educationString . '</td></tr>' .
                                ($eligibilityString ? '<tr><td style="padding: 6px 0; font-weight: bold;">Eligibility:</td><td>' . $eligibilityString . '</td></tr>' : '') . '
                            </table>

                            <div style="margin-top: 24px; padding: 12px; background: #f0fdf4; border-left: 4px solid '.$green.';">
                                <p style="margin: 0;">📎 The applicant’s <strong>Intent Letter (PDF)</strong> is attached to this email.</p>
                            </div>

                            <p style="margin-top: 24px;">Best regards,<br><strong>CPSU Online Career Portal</strong></p>
                        </div>
                        <div style="background: #f1f1f1; text-align: center; padding: 10px; font-size: 12px; color: #555;">
                            © ' . date('Y') . ' Central Philippines State University | HRIS
                        </div>
                    </div>
                </div>
            '; 

            Mail::send([], [], function ($message) use ($toEmail, $subjectHR, $bodyHR, $paths) {
                $message->to($toEmail)
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->subject($subjectHR)
                        ->html($bodyHR);

                if (isset($paths['intent'])) {
                    $message->attach(storage_path('app/public/' . $paths['intent']));
                }
            });

            // ===== 📩 Email to Applicant =====
            $subjectApplicant = "CPSU Career Portal - Application Confirmation (#{$applicationNumber})";
            $bodyApplicant = '
                <div style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px;">
                    <div style="max-width: 600px; margin: auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                        <div style="background-color: '.$green.'; color: white; padding: 16px 24px;">
                            <h2 style="margin: 0; font-size: 20px;">Application Successfully Submitted</h2>
                        </div>
                        <div style="padding: 24px; color: #333;">
                            <p>Dear <strong>' . $request->first_name . ' ' . $request->last_name . '</strong>,</p>
                            <p>Thank you for submitting your application to <strong>Central Philippines State University (CPSU)</strong>.</p>
                            <p>Your Application Number is:</p>
                            <div style="background: #f0fdf4; border-left: 4px solid '.$green.'; padding: 10px 16px; margin: 16px 0; font-size: 18px; font-weight: bold; color: '.$green.';">
                                ' . $applicationNumber . '
                            </div>
                            <p>You can track your application status anytime using the link below:</p>
                            <p><a href="' . $trackingUrl . '" style="display: inline-block; background-color: '.$green.'; color: white; text-decoration: none; padding: 10px 18px; border-radius: 6px;">Track My Application</a></p>

                            <p style="margin-top: 24px;">Best regards,<br><strong>CPSU Career Portal</strong></p>
                        </div>
                        <div style="background: #f1f1f1; text-align: center; padding: 10px; font-size: 12px; color: #555;">
                            © ' . date('Y') . ' Central Philippines State University | HRIS
                        </div>
                    </div>
                </div>
            ';

            Mail::send([], [], function ($message) use ($request, $subjectApplicant, $bodyApplicant, $toEmail) {
                $message->to($request->email)
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->subject($subjectApplicant)
                        ->html($bodyApplicant);
            });

        } catch (\Exception $e) {
            \Log::error('❌ Failed to send email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Application submitted successfully!',
            'data' => [
                'id' => shortDecrypt($application->id),
                'app_number' => $application->app_number,
                'first_name' => $application->first_name,
                'last_name' => $application->last_name,
            ]
        ], 201);
    }

    public function applicationStatus(Request $request)
    {
        $application = Application::join('job_hirings', 'applications.jid', '=', 'job_hirings.id')
            ->select('applications.*', 'job_hirings.title as position')
            ->where('app_number', $request->appnumber)->first();

        if ($application) {
            return response()->json([
                'status' => 'success',
                'data' => $application, 
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Application not found'
            ], 404);
        }
    }

    public function applicationCheck($jid, $email)
    {
        $exists = Application::where('jid', $jid)
                            ->where('email', $email)
                            ->exists();
        
        return response()->json([
            'exists' => $exists,
            'app_number' => $exists ? Application::where('jid', $jid)->where('email', $email)->value('app_number') : null
        ]);
    }
}
