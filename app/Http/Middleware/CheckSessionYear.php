<?php

namespace App\Http\Middleware;

use App\Services\CachingService;
use App\Services\ResponseService;
use Closure;
use Illuminate\Http\Request;

class CheckSessionYear
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip validation for session/semester switching and inherently multi-session routes (Promotion/Transfer)
        if (
            $request->is('session-year/set-session-year')
            || $request->is('set-semester')
            || $request->is('promote-student*')
            || $request->is('getPromoteData')
            || $request->is('transfer-student*')
        ) {
            return $next($request);
        }

        // Only trigger for state-changing requests
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH') || $request->isMethod('DELETE')) {

            // Check if the request contains a session_year_id
            if ($request->has('session_year_id') && !empty($request->session_year_id)) {
                $currentSessionYear = app(CachingService::class)->getSessionYear();

                // If the submitted session year ID does not match the system's active session year ID
                if ($request->session_year_id != $currentSessionYear->id) {
                    $message = 'Your active session has changed in another tab. Please refresh the page to continue.';

                    if ($request->ajax() || $request->wantsJson()) {
                        return ResponseService::errorResponse($message);
                    }

                    return ResponseService::errorRedirectResponse(null, $message);
                }
            }
        }

        return $next($request);
    }
}
