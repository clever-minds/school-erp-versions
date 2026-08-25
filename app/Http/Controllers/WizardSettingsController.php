<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use App\Repositories\PaymentConfiguration\PaymentConfigurationInterface;
use App\Repositories\SystemSetting\SystemSettingInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WizardSettingsController extends Controller
{
    private SystemSettingInterface $systemSettings;
    private PaymentConfigurationInterface $paymentConfiguration;
    private CachingService $cache;

    public function __construct(SystemSettingInterface $systemSettings, PaymentConfigurationInterface $paymentConfiguration, CachingService $cachingService)
    {
        $this->systemSettings = $systemSettings;
        $this->paymentConfiguration = $paymentConfiguration;
        $this->cache = $cachingService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        ResponseService::noPermissionThenRedirect('system-setting-manage');
        $settings = $this->cache->getSystemSettings();
        $getDateFormat = getDateFormat();
        $getTimezoneList = getTimezoneList();
        $getTimeFormat = getTimeFormat();

        $get_two_factor_verification = User::where('id', Auth::user()->id)->pluck('two_factor_enabled')->toArray()[0] ? 1 : 0;

        // get firebase settings
        $name = 'firebase_project_id';
        $file = 'firebase_service_file';
        $project_id = htmlspecialchars_decode($this->cache->getSystemSettings($name));
        $serviceFile = htmlspecialchars_decode($this->cache->getSystemSettings($file));

        // get payment gateway settings
        $paymentConfiguration = $this->paymentConfiguration->all();
        $paymentGateway = [];
        foreach ($paymentConfiguration as $row) {
            $paymentGateway[$row->payment_method] = $row->toArray();
        }

        // Get wizard settings and determine current step
        $wizardSettings = $this->getWizardSettings();
        $currentStep = $this->getFirstUncompletedStep($wizardSettings);

        // If all steps are completed, redirect to server configuration check
        if ($currentStep === null) {
            return redirect()->route('server-config.index');
        }

        return view('wizard-settings.index', compact(
            'settings',
            'getDateFormat',
            'getTimezoneList',
            'getTimeFormat',
            'get_two_factor_verification',
            'project_id',
            'serviceFile',
            'paymentGateway',
            'currentStep'
        ));
    }

    /**
     * Get all wizard settings
     */
    private function getWizardSettings(): array
    {
        $wizardSettings = [
            'system_settings_wizard_checkMark',
            'notification_settings_wizard_checkMark',
            'email_settings_wizard_checkMark',
            'verify_email_wizard_checkMark',
            'email_template_settings_wizard_checkMark',
            'payment_settings_wizard_checkMark',
            'third_party_api_settings_wizard_checkMark'
        ];

        $settings = $this->systemSettings->builder()
            ->whereIn('name', $wizardSettings)
            ->get()
            ->pluck('data', 'name')
            ->toArray();

        return $settings;
    }

    /**
     * Find the first uncompleted step
     */
    private function getFirstUncompletedStep(array $wizardSettings): ?int
    {
        $steps = [
            'system_settings_wizard_checkMark',
            'notification_settings_wizard_checkMark',
            'email_settings_wizard_checkMark',
            'verify_email_wizard_checkMark',
            'email_template_settings_wizard_checkMark',
            'payment_settings_wizard_checkMark',
            'third_party_api_settings_wizard_checkMark'
        ];

        foreach ($steps as $index => $step) {
            if (!isset($wizardSettings[$step]) || $wizardSettings[$step] == 0) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        //

        return response()->json('success');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function updateWizardSystemSettings(Request $request)
    {
        $this->systemSettings->builder()->where('name', $request->name)->update(['data' => 1]); // 1 means checked

        $settings = $this->systemSettings->builder()->get();

        $WizardSystemSettings = [
            'system_settings_wizard_checkMark',
            'notification_settings_wizard_checkMark',
            'email_settings_wizard_checkMark',
            'verify_email_wizard_checkMark',
            'email_template_settings_wizard_checkMark',
            'payment_settings_wizard_checkMark',
            'third_party_api_settings_wizard_checkMark'
        ];

        // Get the 'data' column values for all the wizard system settings
        $is_checked = $this->systemSettings->builder()->whereIn('name', $WizardSystemSettings)->get()->pluck('data')->toArray();

        if (count($is_checked) === count($WizardSystemSettings) && !in_array(0, $is_checked)) {
            $this->systemSettings->builder()->where('name', 'wizard_checkMark')->update(['data' => 1]);
        }

        $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));

        return response()->json([
            'message' => 'Wizard System Settings Updated Successfully',
            'data' => $settings
        ]);
    }

    public function resetWizardSettings()
    {
        // $data[] = [
        //     'name' => 'wizard_checkMark',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'system_settings_wizard_checkMark',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'notification_settings_wizard_checkMark',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'email_settings_wizard_checkMark',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'verify_email_wizard_checkMark',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'email_template_settings_wizard_checkMark',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'payment_settings_wizard_checkMark',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'third_party_api_settings_wizard_checkMark',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'server_config_db_checkMark',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'server_config_queue_checkMark',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'server_config_wizard_checkMark',
        //     'data' => 0
        // ];

        // $data[] = [
        //     'name' => 'database_root_user',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'laravel_queue_setup',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'wildcard_domain',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'web_socket_setup',
        //     'data' => 0
        // ];
        // $data[] = [
        //     'name' => 'notification_settings',
        //     'data' => 0
        // ];

        $data = [
            ['name' => 'wizard_checkMark', 'data' => 0],
            ['name' => 'system_settings_wizard_checkMark', 'data' => 0],
            ['name' => 'notification_settings_wizard_checkMark', 'data' => 0],
            ['name' => 'email_settings_wizard_checkMark', 'data' => 0],
            ['name' => 'verify_email_wizard_checkMark', 'data' => 0],
            ['name' => 'email_template_settings_wizard_checkMark', 'data' => 0],
            ['name' => 'payment_settings_wizard_checkMark', 'data' => 0],
            ['name' => 'third_party_api_settings_wizard_checkMark', 'data' => 0],
            ['name' => 'server_config_db_checkMark', 'data' => 0],
            ['name' => 'server_config_queue_checkMark', 'data' => 0],
            ['name' => 'server_config_reverb_checkMark', 'data' => 0],
            ['name' => 'server_config_wizard_checkMark', 'data' => 0],
            ['name' => 'database_root_user', 'data' => 0],
            ['name' => 'laravel_queue_setup', 'data' => 0],
            ['name' => 'wildcard_domain', 'data' => 0],
            ['name' => 'notification_settings', 'data' => 0],
        ];

        SystemSetting::upsert($data, ['name'], ['data']);

        $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));

        return redirect()->route('wizard-settings.index');
    }
}
