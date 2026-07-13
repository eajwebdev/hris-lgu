<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        /**
         * A sign-in page that has been sitting open — restored from the back
         * button or the browser cache, or left idle past SESSION_LIFETIME —
         * posts a CSRF token that no longer matches the session. Send the user
         * back to a fresh form with a plain message instead of the 419 page.
         *
         * Laravel converts TokenMismatchException into a 419 HttpException
         * before render callbacks run, so the 419 is what must be caught here.
         */
        $this->renderable(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            $message = 'Your session expired. Please sign in again.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 419);
            }

            if ($request->is('post-login')) {
                $onAdminPage = str_contains((string) url()->previous(), 'hr-admin');

                return redirect()
                    ->route($onAdminPage ? 'getLoginAdmin' : 'getLogin')
                    ->withInput($request->except('password', '_token'))
                    ->with('error', $message);
            }

            return redirect()
                ->back()
                ->withInput($request->except('password', '_token'))
                ->with('error', 'Your session expired. Please try again.');
        });
    }
}
