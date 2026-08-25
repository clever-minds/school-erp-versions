<?php

namespace App\Http\Controllers;

use App\Repositories\SystemSetting\SystemSettingInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SystemSettingsController extends Controller
{
    // Initializing the Settings Repository
    private SystemSettingInterface $systemSettings;
    private CachingService $cache;

    public function __construct(SystemSettingInterface $systemSettings, CachingService $cachingService)
    {
        $this->systemSettings = $systemSettings;
        $this->cache = $cachingService;
    }

    public function index()
    {
        ResponseService::noPermissionThenRedirect('system-setting-manage');
        $settings = $this->cache->getSystemSettings();
        $getDateFormat = getDateFormat();
        $getTimezoneList = getTimezoneList();
        $getTimeFormat = getTimeFormat();
        return view('settings.system-settings', compact('settings', 'getDateFormat', 'getTimezoneList', 'getTimeFormat'));
    }


    public function store(Request $request)
    {
        ResponseService::noPermissionThenRedirect('system-setting-manage');

        $request->validate([
            'favicon' => 'nullable|mimes:jpg,png,jpeg,svg,icon',
            'horizontal_logo' => 'nullable|mimes:jpg,png,jpeg,svg',
            'vertical_logo' => 'nullable|mimes:jpg,png,jpeg,svg'
        ]);

        $settings = array(
            'time_zone', 'date_format', 'time_format', 'theme_color',
            'horizontal_logo', 'vertical_logo', 'favicon', 'additional_billing_days', 'system_name', 'address', 'billing_cycle_in_days','current_plan_expiry_warning_days','tag_line','mobile'
        );
        try {
            $data = array();
            foreach ($settings as $row) {
                if ($row == 'horizontal_logo' || $row == 'vertical_logo' || $row == 'favicon') {
                    if ($request->hasFile($row)) {
                        // TODO : Remove the old files from server
                        $data[] = [
                            "name" => $row,
                            "data" => $request->file($row),
                            "type" => "file"
                        ];
                    }
                } else {
                    $data[] = [
                        "name" => $row,
                        "data" => $request->$row,
                        "type" => "string"
                    ];
                }
            }

            $this->systemSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse('Data Updated Successfully');

        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> Store method");
            ResponseService::errorResponse();
        }
    }
    public function update(Request $request) {
        ResponseService::noPermissionThenRedirect('system-setting-manage');
        $request->validate([
            'name' => 'required',
            'data' => 'required'
        ]);
        try {
            $OtherSettingsData[] = array(
                'name' => $request->name,
                'data' => htmlspecialchars($request->data),
                'type' => 'string'
            );
            $this->systemSettings->upsert($OtherSettingsData, ["name"], ["data"]);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse("Data Stored Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> otherSystemSettings method");
            ResponseService::errorResponse();
        }
    }

    public function fcmIndex()
    {
        ResponseService::noPermissionThenRedirect('fcm-setting-manage');
        $name = 'fcm_server_key';
        $data = htmlspecialchars_decode($this->cache->getSystemSettings($name));
        return view('settings.fcm', compact('name', 'data'));
    }

    public function privacyPolicy()
    {
        ResponseService::noPermissionThenRedirect('privacy-policy');
        $name = 'privacy_policy';
        $data = htmlspecialchars_decode($this->cache->getSystemSettings($name));
        return view('settings.privacy-policy', compact('name', 'data'));
    }

    public function contactUs()
    {
        ResponseService::noPermissionThenRedirect('contact-us');
        $name = 'contact_us';
        $data = htmlspecialchars_decode($this->cache->getSystemSettings($name));
        return view('settings.contact-us', compact('name', 'data'));
    }

    public function aboutUs()
    {
        ResponseService::noPermissionThenRedirect('about-us');
        $name = 'about_us';
        $data = htmlspecialchars_decode($this->cache->getSystemSettings($name));
        return view('settings.about-us', compact('name', 'data'));
    }

    public function termsConditions()
    {
        ResponseService::noPermissionThenRedirect('terms-condition');
        $name = 'terms_condition';
        $data = htmlspecialchars_decode($this->cache->getSystemSettings($name));
        return view('settings.terms-condition', compact('name', 'data'));
    }

    public function appSettingsIndex()
    {
        ResponseService::noPermissionThenRedirect('app-settings');

        // List of the names to be fetched
        $names = array('app_link', 'ios_app_link', 'app_version', 'ios_app_version', 'force_app_update', 'app_maintenance', 'teacher_app_link', 'teacher_ios_app_link', 'teacher_app_version', 'teacher_ios_app_version', 'teacher_force_app_update', 'teacher_app_maintenance');

        // Passing the array of names and gets the array of data
        $settings = $this->systemSettings->getBulkData($names);
        return view('settings.app', compact('settings'));
    }

    public function appSettingsUpdate(Request $request)
    {
        ResponseService::noPermissionThenRedirect('app-settings');
        $request->validate([
            'app_link'                 => 'required',
            'ios_app_link'             => 'required',
            'app_version'              => 'required',
            'ios_app_version'          => 'required',
            'force_app_update'         => 'required',
            'app_maintenance'          => 'required',
            // 'teacher_app_link'         => 'required',
            // 'teacher_ios_app_link'     => 'required',
            // 'teacher_app_version'      => 'required',
            // 'teacher_ios_app_version'  => 'required',
            // 'teacher_force_app_update' => 'required',
            // 'teacher_app_maintenance'  => 'required',
        ]);

        $settings = [
            'app_link',
            'ios_app_link',
            'app_version',
            'ios_app_version',
            'force_app_update',
            'app_maintenance',
            // 'teacher_app_link',
            // 'teacher_ios_app_link',
            // 'teacher_app_version',
            // 'teacher_ios_app_version',
            // 'teacher_force_app_update',
            // 'teacher_app_maintenance',
        ];

        try {
            $request->validate([
                'app_link'         => 'required',
                'ios_app_link'     => 'required',
                'app_version'      => 'required',
                'ios_app_version'  => 'required',
                'force_app_update' => 'required',
                'app_maintenance'  => 'required',
                //            'teacher_app_link'         => 'required',
                //            'teacher_ios_app_link'     => 'required',
                //            'teacher_app_version'      => 'required',
                //            'teacher_ios_app_version'  => 'required',
                //            'teacher_force_app_update' => 'required',
                //            'teacher_app_maintenance'  => 'required',
            ]);

            $settings = [
                'app_link',
                'ios_app_link',
                'app_version',
                'ios_app_version',
                'force_app_update',
                'app_maintenance',
                //            'teacher_app_link',
                //            'teacher_ios_app_link',
                //            'teacher_app_version',
                //            'teacher_ios_app_version',
                //            'teacher_force_app_update',
                //            'teacher_app_maintenance',
            ];
            foreach ($settings as $row) {
                $data[] = [
                    'name' => $row,
                    'data' => $request->$row,
                    'type' => 'string'
                ];
                // Call storeOrUpdate function of Setting upsert
            }
            $this->systemSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> appSettingsUpdate method");
            ResponseService::errorResponse();
        }
    }

    public function emailIndex()
    {
        ResponseService::noPermissionThenRedirect('email-setting-create');
        // List of the names to be fetched
        $names = array('mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_send_from', 'email_verified');
        // Passing the array of names and gets the array of data
        $settings = $this->cache->getSystemSettings($names);
        return view('settings.email', compact('settings'));
    }

    public function emailUpdate(Request $request)
    {
        ResponseService::noPermissionThenRedirect('email-setting-create');
        $request->validate([
            'mail_mailer'     => 'required',
            'mail_host'       => 'required',
            'mail_port'       => 'required',
            'mail_username'   => 'required',
            'mail_password'   => 'required',
            'mail_encryption' => 'required',
            'mail_send_from'  => 'required|email',
        ]);

        $settings = [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_send_from',
            'email_verified'
        ];

        try {
            foreach ($settings as $row) {
                $data[] = [
                    'name' => $row,
                    'data' => ($row == 'email_verified' ? 0 : $request->$row),
                    'type' => $row == 'email_verified' ? 'boolean' : 'string'
                ];
            }
            // Call Upsert function of Setting Upsert
            $this->systemSettings->upsert($data, ["name"], ["data"]);
            Cache::flush();

            // Update ENV
            $env_update = changeEnv([
                'MAIL_MAILER'       => $request->mail_mailer,
                'MAIL_HOST'         => $request->mail_host,
                'MAIL_PORT'         => $request->mail_port,
                'MAIL_USERNAME'     => $request->mail_username,
                'MAIL_PASSWORD'     => $request->mail_password,
                'MAIL_ENCRYPTION'   => $request->mail_encryption,
                'MAIL_FROM_ADDRESS' => $request->mail_send_from

            ]);
            if ($env_update) {
                ResponseService::successResponse("Data Updated Successfully");
            } else {
                ResponseService::errorResponse();
            }
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> emailUpdate method");
            ResponseService::errorResponse();
        }
    }

    public function verifyEmailConfiguration(Request $request)
    {
        ResponseService::noPermissionThenRedirect('email-setting-create');
        $validator = Validator::make($request->all(), [
            'verify_email' => 'required|email',
        ]);
        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }
        try {
            $data_email = [
                'email' => $request->verify_email,
            ];
            $admin_mail = $this->cache->getSystemSettings()['mail_send_from'];
            if (!filter_var($request->verify_email, FILTER_VALIDATE_EMAIL)) {
                $response = array(
                    'error'   => true,
                    'message' => trans('invalid_email'),
                );
                return response()->json($response);
            }
            Mail::send('mail', $data_email, static function ($message) use ($data_email, $admin_mail) {
                $message->to($data_email['email'])->subject('Connection Verified successfully');
                $message->from($admin_mail, 'Eschool Admin');
            });

            $data[] = [
                'name' => 'email_verified',
                'data' => 1,
                'type' => 'string'
            ];

            // Call storeOrUpdate function of Setting upsert
            $this->systemSettings->upsert($data, ["name"], ["data"]);
            //TODO: Remove Specific Cache instead of Cache::flush
            Cache::flush();

            ResponseService::successResponse("Email Sent Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> verifyEmailConfiguration method");
            ResponseService::errorResponse();
        }
    }

    public function paymentIndex()
    {
        ResponseService::noRoleThenRedirect('Super Admin');
        $names = array('currency_code', 'currency_symbol','stripe_publishable_key', 'stripe_secret_key', 'stripe_webhook_secret', 'stripe_webhook_url',);
        // Passing the array of names and gets the array of data
        $settings = $this->systemSettings->getBulkData($names);
        return view('settings.payment', compact('settings'));
    }

    public function paymentUpdate(Request $request)
    {
        ResponseService::noRoleThenRedirect('Super Admin');
        $request->validate([
            'stripe_publishable_key' => 'required',
            'stripe_secret_key'      => 'required',
            'stripe_webhook_secret'  => 'required',
            'currency_code'          => 'required',
            'currency_symbol'        => 'required',
        ]);

        $settings = [
            'stripe_publishable_key',
            'stripe_secret_key',
            'stripe_webhook_secret',
            'currency_code',
            'currency_symbol',
        ];

        try {
            foreach ($settings as $row) {
                $data[] = [
                    'name' => $row,
                    'data' => $request->$row,
                    'type' => 'string'
                ];
            }
            // Call Upsert function of Setting Upsert
            $this->systemSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));

            ResponseService::successResponse("Data Updated Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> paymentUpdate method");
            ResponseService::errorResponse();
        }
    }

    public function front_site_settings()
    {

        ResponseService::noPermissionThenRedirect('front-site-setting');
        $names = array('front_site_theme_color', 'primary_color', 'secondary_color', 'home_image', 'facebook', 'instagram', 'linkedin', 'footer_text', 'short_description');

        // Passing the array of names and gets the array of data
        $settings = $this->systemSettings->getBulkData($names);

        return view('settings.front_site_settings',compact('settings'));
    }

    public function front_site_settings_update(Request $request)
    {
        ResponseService::noPermissionThenRedirect('front-site-setting');

        $request->validate([
            'home_image' => 'nullable|mimes:jpg,png,jpeg,svg,icon|max:2048',
        ]);
        $settings = array(
            'front_site_theme_color', 'primary_color', 'secondary_color', 'short_description', 'home_image', 'facebook', 'instagram', 'linkedin', 'footer_text'
        );
        try {
            $data = array();
            foreach ($settings as $row) {
                if ($row == 'home_image') {
                    if ($request->hasFile($row)) {
                        // TODO : Remove the old files from server
                        $data[] = [
                            "name" => $row,
                            "data" => $request->file($row),
                            "type" => "file"
                        ];
                    }
                } else {
                    $data[] = [
                        "name" => $row,
                        "data" => $request->$row,
                        "type" => "text"
                    ];
                }
            }

            $this->systemSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse('Data Updated Successfully');

        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> Front Site Settings Update method");
            ResponseService::errorResponse();
        }
    }
}
