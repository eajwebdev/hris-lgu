<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\NoCacheMiddleware;
use App\Http\Controllers\LoginAuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TirednessController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MyAccountController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\PdsController;
use App\Http\Controllers\FamilybgController;
use App\Http\Controllers\EducBgController;
use App\Http\Controllers\EligibilityController;
use App\Http\Controllers\WorkExperienceController;
use App\Http\Controllers\VoluntaryWorkController;
use App\Http\Controllers\LearningDevController;
use App\Http\Controllers\OtherInfoController;
use App\Http\Controllers\InfoQuestionController;
use App\Http\Controllers\PdsReferencesController;
use App\Http\Controllers\GovIdController;
use App\Http\Controllers\LeaveCreditController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PendingController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\JobHiringController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\EteEvaluationController;
use App\Http\Controllers\InterviewEvaluationController;
use App\Http\Controllers\FaceRegistrationController;
use App\Http\Controllers\AttendancePortalController;
use App\Http\Controllers\AttendanceAdminController;
use App\Http\Controllers\SpmsController;

//login
Route::get('/hr-admin',[LoginAuthController::class,'getLoginAdmin'])->name('getLoginAdmin');
Route::get('/',[LoginAuthController::class,'getLogin'])->name('getLogin')->middleware([NoCacheMiddleware::class]);
Route::post('/post-login',[LoginAuthController::class,'postLogin'])->name('postLogin');
// Route::get('/update-pass', [EmployeeController::class, 'updateEmployeePasswords']);

Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
Route::get('/verify', [GoogleAuthController::class, 'verifyForm'])->name('verify');
Route::post('/verify', [GoogleAuthController::class, 'verify'])->name('verify.code');
// Route::get('/convert-esign', [PdsController::class, 'convertEsign'])->name('convertEsign');

/*
 * Employee attendance portal.
 *
 * Public on purpose — the camera is the login. It is safe to expose because the
 * browser never names an employee: it sends a face (and, in QR mode, a token it
 * cannot forge) and the server decides whose row moves. Throttled per IP so the
 * face endpoint cannot be used to grind through descriptors.
 */
Route::prefix('attendance')->group(function () {
    $limit = 'throttle:' . config('attendance.portal.rate_limit', 20) . ',1';

    Route::get('/', [AttendancePortalController::class, 'show'])->name('attendancePortal');
    Route::post('/qr-check', [AttendancePortalController::class, 'checkQr'])->middleware($limit)->name('attendanceQrCheck');
    Route::post('/challenge', [AttendancePortalController::class, 'challenge'])->middleware($limit)->name('attendanceChallenge');
    Route::post('/punch', [AttendancePortalController::class, 'punch'])->middleware($limit)->name('attendancePunch');
});

/*
 * Public careers portal.
 *
 * Applicants are not users of this system — no account, no login. They browse
 * the open positions, apply, and track their application by its number. The
 * submission and tracking endpoints live in routes/api.php and were already
 * built for this portal; this page is the front door the confirmation emails
 * have been linking to.
 */
Route::get('/careers', [JobHiringController::class, 'portal'])->name('careersPortal');

Route::group(['middleware' => ['login_auth', NoCacheMiddleware::class]], function() {

    /*
     * Attendance administration — the punch monitor and the station list.
     * Admin/HR only; the punch itself never touches these routes.
     */
    Route::prefix('attendance-admin')->middleware('face.registrar')->group(function () {
        Route::get('/', [AttendanceAdminController::class, 'monitor'])->name('attendanceMonitor');
        Route::post('/stations', [AttendanceAdminController::class, 'storeStation'])->name('stationStore');
        Route::post('/stations/{station}/update', [AttendanceAdminController::class, 'updateStation'])->name('stationUpdate');
        Route::post('/stations/{station}/delete', [AttendanceAdminController::class, 'deleteStation'])->name('stationDelete');
    });

    //Performance
    Route::post('/data-privacy-notice', [MasterController::class, 'dataPrivacyNotice'])->name('dataPrivacyNotice');
    //Performance
    Route::get('/system-performance', [PerformanceController::class, 'systemPerformance'])->name('systemPerformance');
    // Dashboard
    Route::get('/dashboard', [MasterController::class, 'dashboard'])->name('dashboard');
    Route::get('/data-privacy', [MasterController::class, 'dataPrivacy'])->name('dataPrivacy');

    // SPMS (Strategic Performance Management System)
    Route::prefix('spms')->group(function() {
        Route::get('/', [SpmsController::class, 'drive'])->name('spms.drive');
        Route::get('/opcr', [SpmsController::class, 'opcrList'])->name('spms.opcr');
        Route::post('/opcr/create', [SpmsController::class, 'createOpcr'])->name('spms.opcr.create');
        Route::get('/opcr/{id}', [SpmsController::class, 'opcrMatrix'])->name('spms.opcr.matrix');
        Route::post('/opcr/item/store', [SpmsController::class, 'storeOpcrItem'])->name('spms.opcr.item.store');
        Route::post('/opcr/item/{id}/delete', [SpmsController::class, 'deleteOpcrItem'])->name('spms.opcr.item.delete');
        Route::post('/opcr/item/cascade', [SpmsController::class, 'cascadeOpcrItem'])->name('spms.opcr.item.cascade');

        Route::get('/ipcr/{id?}', [SpmsController::class, 'ipcrMatrix'])->name('spms.ipcr');
        Route::post('/ipcr/accomplishment/submit', [SpmsController::class, 'submitAccomplishment'])->name('spms.ipcr.accomplishment.submit');
        Route::post('/ipcr/item/rate', [SpmsController::class, 'rateIpcrItem'])->name('spms.ipcr.item.rate');
    });





    // DTR
    Route::prefix('dtr')->group(function() {
        Route::get('/', [DtrController::class, 'dtrRead'])->name('dtr-read');
        Route::post('/', [DtrController::class, 'dtrSearch'])->name('dtrSearch');
        Route::get('/dtr-logs', [DtrController::class, 'dtrLogs'])->name('dtrLogs');
        Route::post('/dtr-logs', [DtrController::class, 'dtrLogs'])->name('dtrLogspost');
        Route::get('/dtr-log-pdf/{employeeId}/{dateFrom}/{dateTo}/{overtime?}', [DtrController::class, 'logDtrView'])->name('logDtrView');
        Route::get('/pdf', [DtrController::class, 'dtrPdf'])->name('dtr-pdf');
    });
    

    // User
    Route::prefix('user')->group(function() {
        Route::get('/', [UserController::class, 'ulist'])->name('ulist');
        Route::post('/create', [UserController::class, 'uCreate'])->name('uCreate');
        Route::get('/edit/{id}', [UserController::class, 'uEdit'])->name('uEdit');
        Route::post('/update', [UserController::class, 'uUpdate'])->name('uUpdate');
        Route::post('/delete', [UserController::class, 'uDelete'])->name('uDelete');

        Route::get('/myaccount', [MyAccountController::class, 'myAccount'])->name('myAccount');
    });

    // My Account — the account view posts to updateAccount, so it must be registered.
    Route::prefix('/myaccount')->group(function(){
        Route::post('/update-account', [MyAccountController::class, 'updateAccount'])->name('updateAccount');
    });

    Route::prefix('career')->group(function () {
        Route::get('/', [JobHiringController::class, 'jlist'])->name('jlist');
        Route::post('/create', [JobHiringController::class, 'jCreate'])->name('jCreate');
        Route::get('/edit/{id}', [JobHiringController::class, 'jEdit'])->name('jEdit');
        Route::post('/update', [JobHiringController::class, 'jUpdate'])->name('jUpdate');
        Route::post('/delete', [JobHiringController::class, 'jDelete'])->name('jDelete');

        // optional extra route if applicants can apply directly
        Route::get('/applications', [MasterController::class, 'appList'])->name('appList');
        Route::get('/applications/report', [MasterController::class, 'applicationReport'])->name('applicationReport');
        Route::post('/applications/create', [ApplicationController::class, 'appCreate'])->name('appCreate');
        Route::post('/application/setCtrlNo', [ApplicationController::class, 'setCtrlNo'])->name('setCtrlNo');
        Route::get('/application/update-status', function () {
            return redirect()->route('viewAllApplication')
                ->with('error', 'Please update applicant status using the action buttons.');
        });
        Route::post('/application/update-status', [ApplicationController::class, 'updateStatus'])->name('updateStatus');

        //Jobs
        Route::get('/view-applications', [ApplicationController::class, 'viewAllApplication'])->name('viewAllApplication');
        Route::get('/view-application/{appid}', [ApplicationController::class, 'viewApplication'])->name('viewApplication');
        Route::post('/mark-forwarded/{appid}', [ApplicationController::class, 'markForwarded'])->name('markForwarded');

        //application-manual
        Route::post('/application-store', [ApplicationController::class, 'applicationStore'])->name('applicationStore');

        //ETE-Evaluation
        
    });

    Route::prefix('ete')->group(function () {
        Route::get('/ete-evaluations',[EteEvaluationController::class, 'eteEvaluationList'])->name('eteEvaluationList');
        Route::post('/ete-evaluations/store',[EteEvaluationController::class, 'eteEvaluationStore'])->name('eteEvaluationStore');
        Route::post('/ete-evaluations/{id}/delete',[EteEvaluationController::class, 'eteEvaluationDelete'])->name('eteEvaluationDelete');
        Route::get('/ete-evaluations/{id}',[EteEvaluationController::class, 'eteEvaluationShow'])->name('eteEvaluationShow');
        Route::get('/ete-evaluations/{id}/selected-consolidated',[EteEvaluationController::class, 'selectedApplicantConsolidated'])->name('eteSelectedApplicantConsolidated');
        Route::get('/ete-evaluations/{id}/applicant/{applicationId}/pdf',[EteEvaluationController::class, 'applicantEvaluationPdf'])->name('eteApplicantEvaluationPdf');
        Route::get('/ete-evaluations/{id}/rate',[EteEvaluationController::class, 'adminRating'])->name('eteAdminRating');
        Route::post('/ete-evaluations/rating/update-ajax',[EteEvaluationController::class, 'eteRatingUpdateAjax'])->name('eteRatingUpdateAjax');
        Route::post('/ete-evaluations/{id}/rating/copy',[EteEvaluationController::class, 'copyPreviousRating'])->name('eteCopyPreviousRating');
        Route::get('/ete-evaluations/{id}/consolidated',[EteEvaluationController::class, 'consolidatedScreen'])->name('eteConsolidatedScreen');
        Route::get('/ete-evaluations/{id}/consolidated-data',[EteEvaluationController::class, 'consolidatedData'])->name('eteConsolidatedData');
    });

    Route::prefix('interview')->group(function () {
        Route::get('/my-assignments', [InterviewEvaluationController::class, 'assignments'])->name('interviewAssignments');
        Route::get('/my-assignments/status', [InterviewEvaluationController::class, 'assignmentStatus'])->name('interviewAssignmentsStatus');
        Route::get('/evaluations/{id}/rate/{applicationId}/status', [InterviewEvaluationController::class, 'ratingStatus'])->name('interviewRatingStatus');
        Route::get('/evaluations', [InterviewEvaluationController::class, 'index'])->name('interviewEvaluationList');
        Route::post('/evaluations/store', [InterviewEvaluationController::class, 'store'])->name('interviewEvaluationStore');
        Route::get('/evaluations/{id}/consolidated', [InterviewEvaluationController::class, 'consolidatedScreen'])->name('interviewConsolidatedScreen');
        Route::get('/evaluations/{id}/consolidated-data', [InterviewEvaluationController::class, 'consolidatedData'])->name('interviewConsolidatedData');
        Route::get('/evaluations/{id}/summary-rating', [InterviewEvaluationController::class, 'summaryRatingPdf'])->name('interviewSummaryRatingPdf');
        Route::get('/evaluations/{id}/panel-progress', [InterviewEvaluationController::class, 'panelProgress'])->name('interviewPanelProgress');
        Route::get('/evaluations/{id}', [InterviewEvaluationController::class, 'show'])->name('interviewEvaluationShow');
        Route::post('/evaluations/{id}/candidate/{applicationId}/cast', [InterviewEvaluationController::class, 'cast'])->name('interviewCandidateCast');
        Route::post('/evaluations/{id}/candidate/{applicationId}/uncast', [InterviewEvaluationController::class, 'uncast'])->name('interviewCandidateUncast');
        Route::post('/evaluations/{id}/candidate/{applicationId}/panel', [InterviewEvaluationController::class, 'addApplicantPanel'])->name('interviewCandidatePanelAdd');
        Route::post('/evaluations/{id}/candidate/{applicationId}/panel/{employeeId}/remove', [InterviewEvaluationController::class, 'removeApplicantPanel'])->name('interviewCandidatePanelRemove');
        Route::post('/evaluations/{id}/panel/{employeeId}/chairman', [InterviewEvaluationController::class, 'setPanelChairman'])->name('interviewPanelSetChairman');
        Route::get('/evaluations/{id}/rate/{applicationId?}', [InterviewEvaluationController::class, 'rate'])->name('interviewRatingForm');
        Route::post('/evaluations/{id}/rate/{applicationId}', [InterviewEvaluationController::class, 'saveRating'])->name('interviewRatingSave');
        Route::post('/evaluations/{id}/rate/{applicationId}/copy', [InterviewEvaluationController::class, 'copyPreviousRating'])->name('interviewRatingCopy');
        Route::post('/evaluations/{id}/delete', [InterviewEvaluationController::class, 'destroy'])->name('interviewEvaluationDelete');
    });

    // Employee
    Route::prefix('employees')->group(function() {
        Route::get('/', [EmployeeController::class, 'emp_list'])->name('emp_list');
        Route::get('/add', [EmployeeController::class, 'empAdd'])->name('empAdd');
        Route::get('/generate', [EmployeeController::class, 'genEmp'])->name('genEmp');

        Route::post('/create', [EmployeeController::class, 'empCreate'])->name('empCreate');
        Route::post('/update-profile/{id}', [EmployeeController::class, 'updateProfilePicture'])->name('updateProfilePicture');
        Route::post('/update', [EmployeeController::class, 'empUpdate'])->name('empUpdate');
        Route::post('/employee-update', [EmployeeController::class, 'employeeUpdate'])->name('employeeUpdate');
        Route::post('/toggle-acct-stat', [EmployeeController::class, 'toggleAcctStat'])->name('toggleAcctStat');
        Route::post('/official-time/{empid}', [EmployeeController::class, 'OfficialTimeRead'])->name('OfficialTimeRead');
        Route::post('/official-time-create', [EmployeeController::class, 'OfficialTimeCreate'])->name('OfficialTimeCreate');
        Route::get('/emp-qr', [EmployeeController::class, 'empQr'])->name('empQr');

        Route::post('/delete/{id}', [EmployeeController::class, 'empDelete'])->name('empDelete');

        /*
         * Face recognition — Phase 1, enrolment only.
         *
         * The middleware is the access boundary; the profile panel hiding its
         * controls is cosmetic. Admin/HR may enrol anyone; an employee may enrol
         * (and re-enrol) their own face. Removing a biometric stays Admin/HR
         * only — self-service ends at registration.
         */
        Route::prefix('face')->group(function () {
            Route::get('/{employee}', [FaceRegistrationController::class, 'status'])->middleware('face.self')->name('faceStatus');
            Route::post('/{employee}', [FaceRegistrationController::class, 'store'])->middleware('face.self')->name('faceRegister');
            Route::delete('/{employee}', [FaceRegistrationController::class, 'destroy'])->middleware('face.registrar')->name('faceRemove');
        });
    });
    
    Route::prefix('tardiness')->group(function(){
        Route::get('/data', [TirednessController::class, 'readTiredness'])->name('readTiredness');
        Route::post('/data', [TirednessController::class, 'readTiredness'])->name('tirednessSearch');
        Route::get('/pdf/{employeeId}/{month}', [TirednessController::class, 'pdfTirednes'])->name('pdfTirednes');
    });

    Route::prefix('pending')->group(function(){
        Route::get('/{type}/{cat?}', [PendingController::class, 'readPending'])->name('readPending');
    });
    
    //pds
    Route::prefix('pds')->group(function() {
        Route::get('/', [PdsController::class, 'empPDS'])->name('empPDS');  
        Route::get('/generate/{id?}', [PdsController::class, 'generatepds'])->name('generatepds');
        Route::get('/attachment/{id?}', [PdsController::class, 'genpdsAtthachment'])->name('genpdsAtthachment');
        
        //personal Info
        Route::get('personal-info/{id}', [EmployeeController::class, 'PDS'])->name('PDS');   

        //family background
        Route::get('/family-bg/{id?}', [FamilybgController::class, 'familybg'])->name('familybg');
        Route::post('/update-child', [FamilyBgController::class, 'updateChild'])->name('update-child');
        Route::post('/familybg-update', [FamilyBgController::class, 'familyBgUpdate'])->name('familyBgUpdate');
        Route::post('/familybg-update-array', [FamilyBgController::class, 'familyBgUpdateArray'])->name('familyBgUpdateArray');
        
        //Educational Background
        Route::get('/educ-bg/{id?}', [EducBgController::class, 'educbg'])->name('educbg');
        Route::post('/update-educ-child', [EducBgController::class, 'updateEducChild'])->name('updateEducChild');
        Route::post('/educbg-update', [EducBgController::class, 'educBgUpdate'])->name('educBgUpdate');
        Route::post('/educbg-update-array', [EducBgController::class, 'educBgUpdateArray'])->name('educBgUpdateArray');

        Route::post('/graduate-studies-update', [EducBgController::class, 'graduateStudiesUpdate'])->name('graduateStudiesUpdate');
        Route::post('/educbg-update-graduate-array', [EducBgController::class, 'educBgUpdateGraduateArray'])->name('educBgUpdateGraduateArray');

        //Eligibility
        Route::get('/eligibility/{id?}', [EligibilityController::class, 'eligibility'])->name('eligibility');
        Route::post('/eligibility-create', [EligibilityController::class, 'eligibilityCreate'])->name('eligibilityCreate');
        Route::get('/eligibility-edit/{id?}/{eid}', [EligibilityController::class, 'eligibilityEdit'])->name('eligibilityEdit');
        Route::post('/eligibility-update/{id}', [EligibilityController::class, 'eligibilityUpdate'])->name('eligibilityUpdate');
        Route::post('/eligibility-delete/{id}', [EligibilityController::class, 'eliDelete'])->name('eliDelete');
        Route::post('/eligibility-approve/{id}', [EligibilityController::class, 'eliApprove'])->name('eliApprove');
        Route::post('/eligibility-cancel', [EligibilityController::class, 'eliCancel'])->name('eliCancel');

        //Work-experience
        Route::get('/work-experience/{id?}', [WorkExperienceController::class, 'workexperience'])->name('work-experience');
        Route::post('/work-experience-create', [WorkExperienceController::class, 'workexperienceCreate'])->name('workexperienceCreate');
        Route::get('/work-experience-edit/{id?}/{eid}', [WorkExperienceController::class, 'workexperienceEdit'])->name('workexperienceEdit');
        Route::post('/work-experience-update/{id}', [WorkExperienceController::class, 'workexperienceUpdate'])->name('workexperienceUpdate');
        Route::post('/work-experience-delete/{id}', [WorkExperienceController::class, 'workDelete'])->name('workDelete');
        Route::post('/work-experience-approve/{id}', [WorkExperienceController::class, 'expApprove'])->name('expApprove');
        Route::post('/work-experience-cancel', [WorkExperienceController::class, 'workexperienceCancel'])->name('workexperienceCancel');

        //Voluntary-works
        Route::get('/voluntary-work/{id?}', [VoluntaryWorkController::class, 'voluntaryworks'])->name('voluntary-work');
        Route::post('/voluntary-work-create', [VoluntaryWorkController::class, 'voluntaryworksCreate'])->name('voluntaryworksCreate');
        Route::get('/voluntary-work-edit/{id?}/{eid}', [VoluntaryWorkController::class, 'voluntaryworksEdit'])->name('voluntaryworksEdit');
        Route::post('/voluntary-work-update/{id}', [VoluntaryWorkController::class, 'voluntaryworksUpdate'])->name('voluntaryworksUpdate');
        Route::post('/voluntary-work-delete/{id}', [VoluntaryWorkController::class, 'voluntaryworkDelete'])->name('voluntaryworkDelete');
        Route::post('/voluntary-work-approve/{id}', [VoluntaryWorkController::class, 'voluntaryworksApprove'])->name('voluntaryworksApprove');
        Route::post('/voluntary-work-cancel', [VoluntaryWorkController::class, 'voluntaryworksCancel'])->name('voluntaryworksCancel');

        //Learning-development
        Route::get('/learning-dev/{id?}', [LearningDevController::class, 'learningdev'])->name('learning-dev');
        Route::post('/learning-dev-create', [LearningDevController::class, 'learningdevCreate'])->name('learningdevCreate');
        Route::get('/learning-dev-edit/{id?}/{eid}', [LearningDevController::class, 'learningdevEdit'])->name('learningdevEdit');
        Route::post('/learning-dev-update/{id}', [LearningDevController::class, 'learningdevUpdate'])->name('learningdevUpdate');
        Route::post('/learning-dev-delete/{id}', [LearningDevController::class, 'learningdevDelete'])->name('learningdevDelete');
        Route::post('/learning-dev-approve/{id}', [LearningDevController::class, 'learningdevApprove'])->name('learningdevApprove');
        Route::post('/learning-dev-cancel', [LearningDevController::class, 'learningdevCancel'])->name('learningdevCancel');

        //Other Information
        Route::get('/other-info/{id?}', [OtherInfoController::class, 'otherInfo'])->name('otherInfo');
        Route::post('/update-child-oi', [OtherInfoController::class, 'updateChild'])->name('update-child-oi');
        Route::post('/otherinfo-update', [OtherInfoController::class, 'otherInfoUpdate'])->name('otherInfoUpdate');
        Route::post('/otherInfo-update-array', [OtherInfoController::class, 'otherInfoUpdateArray'])->name('otherInfoUpdateArray');

        //Other Information Question
        Route::get('/info-question/{id?}', [InfoQuestionController::class, 'infoQuestion'])->name('infoQuestion');
        Route::post('/update-info-question', [InfoQuestionController::class, 'update'])->name('update.info.question');
        
        //References
        Route::get('/references/{id?}', [PdsReferencesController::class, 'references'])->name('references');
        Route::post('/update-references', [PdsReferencesController::class, 'update'])->name('update.references');

        //Government ID
        Route::get('/government-id/{id?}', [GovIdController::class, 'govids'])->name('govids'); 
        Route::post('/update-govids', [GovIdController::class, 'update'])->name('update.govids');
        
        //Signature
        // Face Recognition — its own page in the PDS submenu, next to E-Signature.
        // Admin/HR can open anyone's; an employee only reaches their own — naming
        // another employee's id by hand gets a 403.
        Route::get('/face-recognition/{id?}', [FaceRegistrationController::class, 'page'])
            ->middleware('face.self')
            ->name('faceRecognition');

        Route::get('/signature/{id?}', [PdsController::class, 'signature'])->name('signature');
        Route::post('/upload-signature/{id?}', [PdsController::class, 'uploadSignature'])->name('uploadSignature');
    });
    
    // Office
    Route::prefix('office')->group(function() {
        Route::get('/', [OfficeController::class, 'officeList'])->name('officeList');
        Route::post('/create', [OfficeController::class, 'officeCreate'])->name('officeCreate');
        Route::get('/edit/{id}', [OfficeController::class, 'officeEdit'])->name('officeEdit');
        Route::post('/update', [OfficeController::class, 'officeUpdate'])->name('officeUpdate');
        Route::post('/delete/{id}', [OfficeController::class, 'officeDelete'])->name('officeDelete');
    });

    //Address
    Route::prefix('/address')->group(function() {
        Route::get('/provinces/{regionId}', [AddressController::class, 'getProvinces'])->name('getProvinces');
        Route::get('/cities/{provinceId}', [AddressController::class, 'getCities'])->name('getCities');
        Route::get('/barangays/{cityId}', [AddressController::class, 'getBarangays'])->name('getBarangays');
    }); 

    // Calendar
    Route::prefix('events')->group(function() {
        Route::post('/update', [CalendarController::class, 'eventUpdate'])->name('eventUpdate');
        Route::post('/delete/{id}', [CalendarController::class, 'eventDelete'])->name('eventDelete');
    });
    
    //Leave-Credits
    Route::prefix('leaves')->group(function() {
        Route::get('/{id?}', [LeaveCreditController::class, 'leavesRead'])->name('leavesRead');
        Route::post('/leaves-create', [LeaveCreditController::class, 'leavesCreate'])->name('leavesCreate');
        Route::post('/leaves-deduct', [LeaveCreditController::class, 'leavescreditDeduct'])->name('leavescreditDeduct');
        Route::post('/leaves-deduct-update', [LeaveCreditController::class, 'leavescreditDeductUpdate'])->name('leavescreditDeductUpdate');
        Route::post('/leaves-edit/{id}', [LeaveCreditController::class, 'leavesEdit'])->name('leavesEdit');
        Route::post('/leaves-update', [LeaveCreditController::class, 'leavesUpdate'])->name('leavesUpdate');
        Route::post('/delete/{id}/{empid}', [LeaveCreditController::class, 'leavesDelete'])->name('leavesDelete');  
    });

    //Notification
    Route::prefix('notification')->group(function() {
        // Route::get('/load/{page}', [NotificationController::class, 'loadMore'])->name('notificationload');
        Route::get('/load', [NotificationController::class, 'loadMore'])->name('notificationload');
        Route::get('/update-notif/{menid}/{lappid}/{menu}', [NotificationController::class, 'updateNotif'])->name('updateNotif');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    });

    // leave
    Route::prefix('leave')->group(function() {
        Route::get('/', [LeaveCreditController::class, 'leavesReadEmp'])->name('leavesReadEmp');
        Route::post('/create', [LeaveApplicationController::class, 'LeaveAppCreate'])->name('LeaveAppCreate');
        
        Route::get('/status/{id?}', [LeaveApplicationController::class, 'leaveStatus'])->name('leaveStatus');
        Route::post('/leave-wpay', [LeaveApplicationController::class, 'leaveWpay'])->name('leaveWpay');
        Route::post('/approve', [LeaveApplicationController::class, 'leaveApprove'])->name('leaveApprove');
        Route::post('/approve-pres', [LeaveApplicationController::class, 'leaveApprovePres'])->name('leaveApprovePres');
        Route::post('/dis-approve', [LeaveApplicationController::class, 'leaveDisapprove'])->name('leaveDisapprove');
        Route::get('/preview-leave/{id}', [LeaveApplicationController::class, 'previewLeave'])->name('previewLeave');   
        Route::post('/leave-live/{id?}', [LeaveApplicationController::class, 'leaveLive'])->name('leaveLive');
        Route::get('/history/{id?}', [LeaveApplicationController::class, 'historyRead'])->name('historyRead');
        Route::post('/return/{id?}', [LeaveApplicationController::class, 'leaveReturn'])->name('leaveReturn');

        Route::post('/undo/{id?}', [LeaveApplicationController::class, 'leaveUndo'])->name('leaveUndo');
        
        Route::post('/cacelLeave/{id}', [LeaveApplicationController::class, 'cancelLeave'])->name('cancelLeave');
        
        Route::post('/get-pdf-path', [LeaveApplicationController::class, 'getPdfPath'])->name('getPdfPath');
        
        Route::post('/leaves-report', [LeaveApplicationController::class, 'leaveReport'])->name('leaveReport');
    });
    
    // events — Admin / HR Administrator only (same gate as attendance admin).
    Route::prefix('event')->middleware('face.registrar')->group(function() {
        Route::get('/', [EventController::class, 'eventIndex'])->name('eventIndex');
        Route::post('/create', [EventController::class, 'eventCreate'])->name('eventCreate');
        Route::get('/event-json', [EventController::class, 'eventShow'])->name('eventJson');
        Route::post('/update', [EventController::class, 'eventUpdate'])->name('eventUpdateSave');
        Route::post('/delete/{id}', [EventController::class, 'eventDestroy'])->name('eventDestroy');
        Route::get('/reports', [EventController::class, 'showReport'])->name('showReport');
        Route::post('/reports', [EventController::class, 'searchReport'])->name('searchReport');
        Route::get('/reports-generate/{eventid}/{statusid}', [EventController::class, 'reportGenrate'])->name('reportGenrate');

        /*
         * Event QR attendance scanner. The whole event group is already gated to
         * Administrator / HR Administrator (face.registrar, above), the same gate
         * used for attendance administration, so a plain employee reaching any of
         * these URLs by hand gets a 403. The QR carries an encrypted emp_ID (the
         * same token the printed employee cards show); the server decides whose
         * row moves. First scan clocks the attendee in, the last scan clocks out.
         */
        Route::get('/scan', [EventController::class, 'scanPortal'])->name('eventScan');
        Route::post('/scan-punch', [EventController::class, 'scanPunch'])->name('eventScanPunch');
    });

    Route::get('/settings', [MasterController::class, 'systemSetting'])->name('settings');
    Route::get('/leave/disapprove', [LeaveApplicationController::class, 'leaveDisapprove']);
    Route::post('/logout', [MasterController::class, 'logout'])->name('logout');

    // SPMS Routes
    Route::prefix('spms')->group(function() {
        Route::get('/', [SpmsController::class, 'drive'])->name('spms.drive');
        Route::get('/opcr', [SpmsController::class, 'opcrList'])->name('spms.opcr');
        Route::get('/opcr/{id}', [SpmsController::class, 'opcrMatrix'])->name('spms.opcr.matrix');
        Route::post('/opcr/item/store', [SpmsController::class, 'storeOpcrItem'])->name('spms.opcr.item.store');
        Route::post('/opcr/item/delete/{id}', [SpmsController::class, 'deleteOpcrItem'])->name('spms.opcr.item.delete');
        Route::post('/opcr/item/cascade', [SpmsController::class, 'cascadeOpcrItem'])->name('spms.opcr.item.cascade');
        Route::get('/ipcr/{id?}', [SpmsController::class, 'ipcrMatrix'])->name('spms.ipcr');
        Route::post('/ipcr/accomplishment/submit', [SpmsController::class, 'submitAccomplishment'])->name('spms.ipcr.accomplishment.submit');
        Route::post('/ipcr/item/store', [SpmsController::class, 'storeIpcrItem'])->name('spms.ipcr.item.store');
        Route::post('/ipcr/item/rate', [SpmsController::class, 'rateIpcrItem'])->name('spms.ipcr.item.rate');
        Route::get('/evidence/{id}', [SpmsController::class, 'viewEvidence'])->name('spms.evidence.view');
    });
});
