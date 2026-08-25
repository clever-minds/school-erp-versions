<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessSystemUpdateJob;
use App\Models\SystemSetting;
use App\Models\SystemUpdateRun;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SystemUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class SystemUpdateController extends Controller
{
    private string $destinationPath;

    public function __construct(
        private readonly CachingService $cache,
        private readonly SystemUpdateService $updateService
    ) {
        $this->destinationPath = base_path() . '/update/tmp/';
    }

    /**
     * Display the system update page.
     */
    public function index()
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            return redirect(route('home'))->withErrors([
                'message' => trans('no_permission_message'),
            ]);
        }

        $systemVersion = SystemSetting::where('name', 'system_version')->first();
        $maintenanceMode = $this->updateService->isMaintenanceModeActive();
        $preUpdateChecks = $this->updateService->getPreUpdateChecks();

        // Get the latest run
        $latestRun = $this->updateService->getLatestRun();
        $runData = null;
        if ($latestRun) {
            $runData = $this->updateService->getRunStatus($latestRun->run_id);
        }

        return view('system-update.index', [
            'system_version' => $systemVersion,
            'maintenanceMode' => $maintenanceMode,
            'preUpdateChecks' => $preUpdateChecks,
            'latestRun' => $runData,
            'reverb' => [
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => config('broadcasting.connections.reverb.options.port'),
                'scheme' => config('broadcasting.connections.reverb.options.scheme', 'http'),
            ],
        ]);
    }

    /**
     * Trigger the system update process.
     */
    public function update(Request $request): void
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            ResponseService::errorResponse(trans('no_permission_message'));
        }

        // Check maintenance mode
        if (!$this->updateService->isMaintenanceModeActive()) {
            ResponseService::errorResponse('Please enable maintenance mode first before updating.');
        }

        $validator = Validator::make($request->all(), [
            'purchase_code' => 'required|string',
            'file' => 'required|file|mimes:zip',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            // ── Verify purchase code FIRST ──
            $appUrl = (string) url('/');
            $appUrl = preg_replace('#^https?://#i', '', $appUrl);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://validator.wrteam.in/eschoolsaas_validator?purchase_code=' . urlencode($request->purchase_code) . '&domain_url=' . urlencode($appUrl),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ]);
            $licenseResponse = curl_exec($curl);
            $curlError = curl_error($curl);
            curl_close($curl);

            if ($licenseResponse === false) {
                ResponseService::errorResponse('Unable to verify purchase code. Please check your internet connection. ' . $curlError);
            }

            $licenseData = json_decode($licenseResponse, true);
            if (empty($licenseData) || !is_array($licenseData)) {
                ResponseService::errorResponse('Invalid response from license server. Please try again.');
            }
            if (!empty($licenseData['error'])) {
                ResponseService::errorResponse($licenseData['message'] ?? 'Purchase code verification failed.');
            }

            // Check for an active run
            $activeRun = SystemUpdateRun::whereNotIn('status', ['completed', 'failed'])->first();
            if ($activeRun) {
                ResponseService::errorResponse('An update is already in progress. Please wait for it to complete.');
            }

            // Create temp directory
            if (!is_dir($this->destinationPath) && !mkdir($this->destinationPath, 0777, true) && !is_dir($this->destinationPath)) {
                ResponseService::errorResponse('Permission Error while creating Temp Directory');
            }

            // Move uploaded file
            $zipFile = $request->file('file');
            $fileName = time() . '_' . Str::random(10) . '.zip';
            $zipFile->move($this->destinationPath, $fileName);
            $filePath = $this->destinationPath . $fileName;

            // Validate zip file exists and is openable before dispatching async job
            if (!file_exists($filePath)) {
                ResponseService::errorResponse('Failed to save uploaded file. Please check directory permissions.');
            }

            $testZip = new \ZipArchive();
            $zipResult = $testZip->open($filePath);
            if ($zipResult !== true) {
                @unlink($filePath);
                ResponseService::errorResponse('Uploaded file is not a valid zip archive. Please re-download and try again.');
            }
            $testZip->close();

            // Get current version
            $currentVersion = SystemSetting::where('name', 'system_version')->first()?->data ?? '1.0.0';

            // Create run record
            $runId = (string) Str::uuid();
            SystemUpdateRun::create([
                'run_id' => $runId,
                'version_from' => $currentVersion,
                'status' => 'pending',
            ]);

            // Dispatch the orchestrator job
            ProcessSystemUpdateJob::dispatch($runId, $filePath);

            ResponseService::successResponse('System update initiated successfully.', [
                'run_id' => $runId,
            ]);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /**
     * Get the status of a specific run.
     */
    public function status(string $runId): JsonResponse
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')], 403);
        }

        $data = $this->updateService->getRunStatus($runId);

        if (!$data) {
            return response()->json(['error' => true, 'message' => 'Run not found.'], 404);
        }

        return response()->json([
            'error' => false,
            'data' => $data,
        ]);
    }

    /**
     * Get paginated school tracks for a specific run.
     */
    public function schoolTracks(Request $request): JsonResponse
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')], 403);
        }

        $runId = $request->input('run_id');
        if (!$runId) {
            return response()->json(['error' => true, 'message' => 'Run ID is required.'], 422);
        }

        $perPage = (int) $request->input('per_page', 10);
        $search = $request->input('search');
        $status = $request->input('status');

        $paginator = $this->updateService->getPaginatedTracks($runId, $perPage, $search, $status);

        return response()->json([
            'error' => false,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Retry a failed school update.
     */
    public function retrySchool(Request $request): void
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            ResponseService::errorResponse(trans('no_permission_message'));
        }

        $validator = Validator::make($request->all(), [
            'run_id' => 'required|string|exists:system_update_runs,run_id',
            'school_id' => 'required|integer|exists:schools,id',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        $result = $this->updateService->retrySchool($request->run_id, (int) $request->school_id);

        if ($result) {
            ResponseService::successResponse('Retry initiated for the school.');
        } else {
            ResponseService::errorResponse('School not found or not in failed state.');
        }
    }

    /**
     * Execute a post-update task.
     */
    public function postUpdateTask(Request $request): void
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            ResponseService::errorResponse(trans('no_permission_message'));
        }

        $validator = Validator::make($request->all(), [
            'task' => 'required|string|in:restart_supervisor,disable_maintenance,clear_cache,verify_queue',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        $result = $this->updateService->runPostUpdateTask($request->task);

        if ($result['success']) {
            ResponseService::successResponse($result['message']);
        } else {
            ResponseService::errorResponse($result['message']);
        }
    }

    /**
     * Toggle maintenance mode.
     */
    public function toggleMaintenance(Request $request): void
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            ResponseService::errorResponse(trans('no_permission_message'));
        }

        $enable = filter_var($request->input('enable', true), FILTER_VALIDATE_BOOLEAN);
        $this->updateService->toggleMaintenance($enable);

        $message = $enable ? 'Maintenance mode enabled.' : 'Maintenance mode disabled.';
        ResponseService::successResponse($message);
    }
}
