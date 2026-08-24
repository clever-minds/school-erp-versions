<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TeacherOnboardingCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if (Auth::check() && Auth::user()->hasRole('Teacher')) {
        //     $staff = Auth::user()->staff;
        //     if ($staff) {
        //         // 1. Mandatory Onboarding (JD & Test)
        //         if (!$staff->onboarding_completed) {
        //             if ($request->expectsJson()) {
        //                 if (!$request->is('api/onboarding/*') && !$request->is('api/logout')) {
        //                     return response()->json([
        //                         'error' => true, 
        //                         'message' => 'Onboarding (JD & Test) is mandatory before full access.', 
        //                         'onboarding_required' => true
        //                     ], 403);
        //                 }
        //             }
        //         }

        //         // 2. Mandatory KYC
        //         if (!$staff->kyc_completed) {
        //             if ($request->expectsJson()) {
        //                 if (!$request->is('api/onboarding/*') && !$request->is('api/kyc/*') && !$request->is('api/get-kyc-status') && !$request->is('api/upload-kyc-document') && !$request->is('api/logout') && !$request->is('api/staff/profile')) {
        //                     return response()->json([
        //                         'error' => true, 
        //                         'message' => 'KYC documentation is mandatory before full access.', 
        //                         'kyc_required' => true
        //                     ], 403);
        //                 }
        //             } else {
        //                 // Web redirect
        //                 if (!$request->is('kyc-documents') && !$request->is('kyc-documents/upload') && !$request->is('logout') && !$request->is('profile') && !str_contains($request->url(), 'kyc-documents')) {
        //                     return redirect()->route('teacher.kyc.index')->with('error', 'Please complete your KYC first.');
        //                 }
        //             } // CLOSED else
        //         } // CLOSED kyc check

        //         // 3. Mandatory Policies
        //         if (!$staff->policy_completed) {
        //             if ($request->expectsJson()) {
        //                 if (!$request->is('api/onboarding/*') && !$request->is('api/kyc/*') && !$request->is('api/policies/*') && !$request->is('api/logout')) {
        //                     return response()->json([
        //                         'error' => true, 
        //                         'message' => 'Acknowledging policies is mandatory before full access.', 
        //                         'policy_required' => true
        //                     ], 403);
        //                 }
        //             }
        //         }
        //     }
        // }
        return $next($request);
    }
}
