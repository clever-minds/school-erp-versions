<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
    }

    function render($request, Throwable $exception)
    {
        // if (!config('app.debug')) {
        //     \Log::error($exception);
        //     return response()->view('errors.503', [
        //         'message' => 'We’re currently experiencing technical issues. Please try again later.'
        //     ], 503);
        // }
        if (str_contains($exception->getMessage(), 'Unknown database')) {
            \Log::error('Database not found: ' . $exception->getMessage());
            return response()->view('errors.503', [
                'message' => 'School database not found or under maintenance. Please try again later.'
            ], 503);
        }

        if ($this->isHttpException($exception)) {
            switch ($exception->getStatusCode()) {

                // not found
                case '404':
                    return \Response::view('errors.404',[], 404);
                    break;

                default:
                    return $this->renderHttpException($exception);
                    break;
            }
        } else {
            return parent::render($request, $exception);
        }
    }
    
}
