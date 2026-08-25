<?php

namespace App\Http\Middleware;

use App\Models\SchoolSetting;
use App\Services\CachingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AcademySetupWizard
{
    public function __construct(private readonly CachingService $cache) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (!Auth::check() || !Auth::user()->hasRole('School Admin') || !Auth::user()->school_id) {
                return $next($request);
            }

            // check if super admin enable this feature
            $isAcademySetupWizardEnabled = (int) $this->cache->getSystemSettings('academy_master_status') === 1;
            if (!$isAcademySetupWizardEnabled) {
                SchoolSetting::upsert([
                    'school_id' => Auth::user()->school_id,
                    'name' => 'academy_setup_status',
                    'data' => 1,
                ], ['school_id', 'name'], ['data']);
                $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'), Auth::user()->school_id);
                return $next($request);
            }

            $currentRoute = $request->route()?->getName() ?? '';
            $wizardCompleted = (int) $this->cache->getSchoolSettings('academy_setup_status') === 1;
            if (!$wizardCompleted) {
                $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'), Auth::user()->school_id);
            }


            $allowedWhenIncomplete = [
                'academy-setup-wizard.*',
                'auth.logout',
                'auth.change-password.*',
                'auth.profile.*',
                'auth.2fa.code',
            ];

            $isAllowedIncompleteRoute = false;
            foreach ($allowedWhenIncomplete as $pattern) {
                if (fnmatch($pattern, $currentRoute)) {
                    $isAllowedIncompleteRoute = true;
                    break;
                }
            }

            if (!$wizardCompleted && !$isAllowedIncompleteRoute) {
                return redirect()->route('academy-setup-wizard.index');
            }

            if ($wizardCompleted && fnmatch('academy-setup-wizard.*', $currentRoute)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => false,
                        'data' => [
                            'progress' => [
                                'status' => 'completed'
                            ]
                        ]
                    ]);
                }
                return redirect()->route('dashboard');
            }

            return $next($request);
        } catch (\Throwable) {
            return $next($request);
        }
    }
}
