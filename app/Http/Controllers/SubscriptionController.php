<?php

namespace App\Http\Controllers;

use App\Repositories\AddonSubscription\AddonSubscriptionInterface;
use App\Repositories\Feature\FeatureInterface;
use App\Repositories\Package\PackageInterface;
use App\Repositories\PaymentTransaction\PaymentTransactionInterface;
use App\Repositories\School\SchoolInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\Staff\StaffInterface;
use App\Repositories\Subscription\SubscriptionInterface;
use App\Repositories\SubscriptionBill\SubscriptionBillInterface;
use App\Repositories\SubscriptionFeature\SubscriptionFeatureInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;
use Throwable;

class SubscriptionController extends Controller
{
    private PackageInterface $package;
    private FeatureInterface $feature;
    private SubscriptionInterface $subscription;
    private AddonSubscriptionInterface $addonSubscription;
    private UserInterface $user;
    private SchoolSettingInterface $schoolSettings;
    private SubscriptionBillInterface $subscriptionBill;
    private StaffInterface $staff;
    private PaymentTransactionInterface $paymentTransaction;
    private SchoolInterface $school;
    private SchoolSettingInterface $schoolSetting;
    private CachingService $cache;
    private SubscriptionFeatureInterface $subscriptionFeature;


    public function __construct(PackageInterface $package, FeatureInterface $feature, SubscriptionInterface $subscription, AddonSubscriptionInterface $addonSubscription, UserInterface $user, SchoolSettingInterface $schoolSettings, StaffInterface $staff, SubscriptionBillInterface $subscriptionBill, PaymentTransactionInterface $paymentTransaction, SchoolInterface $school, SchoolSettingInterface $schoolSetting, CachingService $cachingService, SubscriptionFeatureInterface $subscriptionFeature)
    {
        $this->package = $package;
        $this->feature = $feature;
        $this->subscription = $subscription;
        $this->addonSubscription = $addonSubscription;
        $this->user = $user;
        $this->schoolSettings = $schoolSettings;
        $this->subscriptionBill = $subscriptionBill;
        $this->staff = $staff;
        $this->paymentTransaction = $paymentTransaction;
        $this->school = $school;
        $this->schoolSetting = $schoolSetting;
        $this->cache = $cachingService;
        $this->subscriptionFeature = $subscriptionFeature;
    }

    public function index()
    {
        ResponseService::noRoleThenRedirect('School Admin');
        $packages = $this->package->builder()->with('package_feature')->where('status', 1)->orderBy('rank', 'ASC')->get();
        $features = $this->feature->builder()->get();
        $today_date = Carbon::now()->format('Y-m-d');
        $current_plan = $this->subscription->builder()->where('start_date','<=',$today_date)->where('end_date','>=',$today_date)->first();
        $settings = app(CachingService::class)->getSystemSettings();
        return view('subscription.index', compact('packages', 'features','current_plan','settings'));
    }


    /**
     * @throws ApiErrorException
     */
    public function store(Request $request)
    {
        $settings = app(CachingService::class)->getSystemSettings();
        $currency = $settings['currency_code'];
        $subscriptionBill = $this->subscriptionBill->findById($request->id);

        $stripe_secret_key = $settings['stripe_secret_key'] ?? null;
        if (empty($stripe_secret_key)) {
            return redirect()->back()->with('error',trans('No API key provided'));
        }
        Stripe::setApiKey($stripe_secret_key);
        $session = StripeSession::create([
            'line_items'  => [
                [
                    'price_data' => [
                        'currency'     => $currency,
                        'product_data' => [
                            'name' => $subscriptionBill->subscription->name,
                            'images' => [$settings['horizontal_logo'] ?? 'logo.svg'],
                        ],
                        'unit_amount'  => $subscriptionBill->amount * 100,
                    ],
                    'quantity'   => 1,
                ],
            ],
            'mode'        => 'payment',
            'success_url' => url('subscriptions/payment/success') . '/{CHECKOUT_SESSION_ID}' . '/' . $request->id,
            'cancel_url'  => url('subscriptions/payment/cancel'),
        ]);


        return redirect()->away($session->url);
    }

    public function plan($id)
    {
        // Store subscription plan
        ResponseService::noRoleThenRedirect('School Admin');
        try {
            DB::beginTransaction();

            $today_date = Carbon::now()->format('Y-m-d');
            $settings = app(CachingService::class)->getSystemSettings();

            $subscriptionBill = $this->subscriptionBill->builder()->whereHas('transaction',function($q){
                $q->whereNot('payment_status',"1");
            })->orWhereNull('payment_transaction_id')->where('school_id',Auth::user()->school_id)->first();


            if ($subscriptionBill) {
                ResponseService::errorResponse('Kindly settle any outstanding payments from before');
            }


            $package_id = $id;
            $subscription = $this->subscription->default()->first();

            // Check current active subscription
            if ($subscription) {
                $data = [
                    'package_id' => $package_id
                ];
                $response = [
                    'error' => false,
                    'message' => trans('data_fetch_successfully'),
                    'data' => $data,
                ];
                return response()->json($response);
            }

            $settings = app(CachingService::class)->getSystemSettings();
            $package = $this->package->builder()->with('package_feature')->where('id',$package_id)->first();
            $subscription_data = [
                'package_id' => $package->id,
                'name' => $package->name,
                'student_charge' => $package->student_charge,
                'staff_charge' => $package->staff_charge,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(($settings['billing_cycle_in_days'] - 1))->format('Y-m-d'),
            ];

            $subscription = $this->subscription->create($subscription_data);

            $subscription_features = array();
            foreach ($package->package_feature as $key => $feature) {
                $subscription_features[] = [
                    'subscription_id' => $subscription->id,
                    'feature_id' => $feature->feature_id
                ];
            }

            $this->subscriptionFeature->upsert($subscription_features,['subscription_id','feature_id'],['subscription_id','feature_id']);

            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.FEATURES'));
            DB::commit();
            ResponseService::successResponse(trans('Package Subscription Successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'Subscription Controller -> Plan method');
            ResponseService::errorResponse();
        }
    }

    public function show()
    {
        ResponseService::noRoleThenRedirect('School Admin');

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = $_GET['search'];

        $sql = $this->subscriptionBill->builder()
            ->where(function ($query) use ($search) {
                $query->when($search, function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")->Owner();
            });
            });

        $total = $sql->count();

        $sql = $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        $settings = app(CachingService::class)->getSystemSettings();
        foreach ($res as $row) {

            $payment_status = null;
            $operate = BootstrapTableService::button('fa fa-dollar', '#', ['btn', 'btn-xs', 'btn-gradient-success', 'btn-rounded', 'btn-icon', 'edit-data'], ['data-id' => $row->id, 'title' => 'Pay Bill', "data-toggle" => "modal", "data-target" => "#editModal"]);
            $operate .= BootstrapTableService::button('fa fa-file-pdf-o', url('subscriptions/bill/receipt', $row->id), ['btn-gradient-info'], ['title' => 'Receipt','target' => '_blank']);

            if (isset($row->transaction)) {
                $payment_status = $row->transaction->payment_status;
            }
            $addons = $this->addonSubscription->builder()->where('end_date', $row->subscription->end_date)->with('feature')->get()->append('days');

            $tempRow['no'] = $no++;
            $tempRow['id'] = $row->id;
            $tempRow['date'] = Carbon::parse($row->subscription->end_date)->addDay()->format('Y-m-d');
            $tempRow['due_date'] = $row->due_date;
            $tempRow['name'] = $row->subscription->name;
            $tempRow['description'] = $row->description;
            $tempRow['total_student'] = $row->total_student;
            $tempRow['total_staff'] = $row->total_staff;
            $tempRow['amount'] = number_format($row->amount, 2);
            $tempRow['subscription'] = $row->subscription;
            $tempRow['addons'] = $addons;
            $tempRow['payment_status'] = $payment_status;
            $tempRow['currency_symbol'] = $settings['currency_symbol'];


            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function history()
    {
        ResponseService::noRoleThenRedirect('School Admin');
        try {
            $data = [
                'students' => 0,
                'staffs' => 0
            ];

            $active_package = $this->subscription->default()->with('subscription_feature.feature','package.package_feature.feature')->first();
            $addons = $this->addonSubscription->default()->with('feature')->get()->append('days');
            $upcoming_package = '';
            if ($active_package) {
                $upcoming_package = $this->subscription->builder()->with('package.package_feature.feature')->where('start_date', '>=', $active_package->end_date)->first();
                if (!$upcoming_package) {
                    $upcoming_package = $active_package;
                }

                $students = $this->user->builder()->withTrashed()->where(function ($q) use ($active_package) {
                    $q->whereBetween('deleted_at', [$active_package->start_date, $active_package->end_date]);
                })->orWhereNull('deleted_at')->Owner()->role('Student')->count();

                $staffs = $this->staff->builder()->whereHas('user', function ($q) use ($active_package) {
                    $q->where(function ($q) use ($active_package) {
                        $q->withTrashed()->whereBetween('deleted_at', [$active_package->start_date, $active_package->end_date])
                            ->orWhereNull('deleted_at');
                    })->Owner();
                })->count();

                $data = [
                    'students' => $students,
                    'staffs' => $staffs
                ];
            }
            $system_settings = app(CachingService::class)->getSystemSettings()->toArray();
            $school_settings = app(CachingService::class)->getSchoolSettings()->toArray();
            $settings = array_merge($system_settings, $school_settings);

            return view('subscription.subscription', compact('active_package', 'addons', 'upcoming_package', 'data', 'settings'));
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'Subscription Controller -> History method');
            ResponseService::errorResponse();
        }
    }

    public function cancel_upcoming($id = null)
    {
        ResponseService::noRoleThenRedirect('School Admin');
        try {

            if ($id) {
                $subscription = $this->subscription->findById($id);
                // Remove addons first
                $this->addonSubscription->builder()->where('start_date', $subscription->start_date)->where('end_date', $subscription->end_date)->delete();
                // Remove subscription
                $this->subscription->deleteById($id);
            } else {

                $data[] = [
                    'name' => 'auto_renewal_plan',
                    'data' => 0,
                    'type' => 'integer'
                ];
                $this->schoolSettings->upsert($data, ["name"], ["data"]);
            }
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'));

            ResponseService::successResponse('Your upcoming plan has been canceled successfully');
        } catch (\Throwable $e) {
            ResponseService::logErrorResponse($e, 'Subscription Controller -> Cancel Upcoming method');
            ResponseService::errorResponse();
        }
    }

    public function confirm_upcoming_plan($id)
    {
        ResponseService::noRoleThenRedirect('School Admin');
        try {
            DB::beginTransaction();
            $current_subscription = $this->subscription->default()->first();
            $subscription = $this->subscription->builder()->where('start_date', '>', $current_subscription->end_date)->first();
            if ($subscription) {
                $response = [
                    'error' => true,
                    'message' => trans('already_added')
                ];
                return response()->json($response);
            }
            $package = $this->package->findById($id);
            $settings = app(CachingService::class)->getSystemSettings();
            $start_date = Carbon::parse($current_subscription->end_date)->addDays()->format('Y-m-d');
            $end_date = Carbon::parse($start_date)->addDays(($settings['billing_cycle_in_days'] - 1))->format('Y-m-d');
            $subscription_data = [
                'package_id' => $id,
                'name' => $package->name,
                'student_charge' => $package->student_charge,
                'staff_charge' => $package->staff_charge,
                'start_date' => $start_date,
                'end_date' => $end_date
            ];

            $this->subscription->create($subscription_data);

            $current_addons = $this->addonSubscription->default()->with('addon')->where('status', 1)->has('addon')->get();
            $addon_data = array();
            foreach ($current_addons as $current_addon) {
                if (!in_array($current_addon->addon->feature_id, $package->package_feature->pluck('feature_id')->toArray())) {
                    $addon_data[] = [
                        'feature_id' => $current_addon->feature_id,
                        'price' => $current_addon->addon->price,
                        'start_date' => $start_date,
                        'end_date' => $end_date
                    ];
                } else {
                    $this->addonSubscription->update($current_addon->id, ['status' => 0]);
                }
            }
            $this->addonSubscription->createBulk($addon_data);

            $data[] = [
                'name' => 'auto_renewal_plan',
                'data' => 1,
                'type' => 'integer'
            ];
            $this->schoolSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'));

            DB::commit();
            ResponseService::successResponse('Your Upcoming Billing Cycle Plan Has Been Added Successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'Subscription Controller -> Confirm Upcoming Plan method');
            ResponseService::errorResponse();
        }
    }

    /**
     * @throws ApiErrorException
     */
    public function payment_success($check_out_session_id, $id)
    {
        $settings = app(CachingService::class)->getSystemSettings();
//        $currency = $settings['currency_code'];
        $stripe_secret_key = $settings['stripe_secret_key'];

        $this->subscriptionBill->findById($id);

        Stripe::setApiKey($stripe_secret_key);

        $session = StripeSession::retrieve($check_out_session_id);

        $status = "0";
        if ($session->payment_status == 'paid') {
            $status = "1";
        }

        $payment_data = [
            'user_id' => Auth::user()->id,
            'amount' => ($session->amount_total / 100),
            'payment_gateway' => 2,
            'order_id' => $session->payment_intent,
            'payment_id' => $session->id,
            'payment_status' => $status,
        ];

        $paymentTransaction = $this->paymentTransaction->create($payment_data);
        $this->subscriptionBill->update($id,['payment_transaction_id' => $paymentTransaction->id]);

        $stripe = new StripeClient($stripe_secret_key);
        $stripeData = $stripe->paymentIntents->create(
            [
                'metadata' => [
                    'transaction_id'       => $paymentTransaction->id,
                    'order_id'             => $paymentTransaction->order_id,
                    'payment_id'           => $paymentTransaction->payment_id,
                    'payment_status'       => $paymentTransaction->payment_status,
                ],
            ]
        );

        return redirect()->route('subscriptions.history')->with('success',trans('the_payment_has_been_completed_successfully'));
    }

    public function payment_cancel()
    {
        return redirect()->route('subscriptions.history')->with('error',trans('the_payment_has_been_cancelled'));
    }

    public function bill_receipt($id)
    {

        $settings = app(CachingService::class)->getSystemSettings()->toArray();
        $school_settings = app(CachingService::class)->getSchoolSettings()->toArray();

        $settings['horizontal_logo'] = basename($settings['horizontal_logo'] ?? 'no_image_available.jpg');
        $subscriptionBill = $this->subscriptionBill->findById($id);
        $addons = $this->addonSubscription->builder()->whereDate('end_date',$subscriptionBill->subscription->end_date)->get();

        // 0 => Failed, 1 => Success, 2 => Pending, 3 => Unpaid
        $status = 3;
        if ($subscriptionBill->transaction) {
            $status = $subscriptionBill->transaction->payment_status;
        }


        $pdf = Pdf::loadView('subscription.subscription_receipt',compact('settings','subscriptionBill','school_settings','addons','status'));
        return $pdf->stream('subscription.pdf');

    }

    public function subscription_report()
    {
        // return Carbon::yesterday()->format('Y-m-d');
        ResponseService::noPermissionThenRedirect('subscription-view');
        $school = $this->school->builder();
        $schoolSetting = $this->schoolSetting->builder()->select(DB::raw('count(data) as data_count, data as value'))->where('name','auto_renewal_plan')->groupBy('data')->get()->makeHidden('data');

        $settings = app(CachingService::class)->getSystemSettings();

        $schoolSetting[0]['value'] == 0 ? $schoolSetting[0]['data_count'] ?? 0 : $schoolSetting[1]['data_count'] ?? 0;
        $over_due = $this->subscription->builder()->with('subscription_bill')->get()->where('status',3)->count();
        $unpaid = $this->subscription->builder()->with('subscription_bill')->get()->whereIn('status',[3,4,5,0,7])->count();
        $paid = $this->subscription->builder()->with('subscription_bill')->get()->where('status',2)->count();
        $data = [
            'registration' => $school->count(),
            'active' => $school->where('status',1)->count(),
            'deactivate' => $school->where('status',0)->count(),
            'over_due' => $over_due,
            'unpaid' => $unpaid,
            'paid' => $paid,
        ];

        return view('schools.subscription',compact('data','settings'));
    }

    public function subscription_report_show(Request $request, $status = null)
    {
        ResponseService::noPermissionThenRedirect('schools-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'start_date');
        $order = request('order', 'ASC');
        $search = request('search');

        $sql = $this->subscription->builder()->with('subscription_bill')->has('school')
            //search query
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->orwhere('name','LIKE',"%$search%")
                    ->orwhereHas('school',function($q) use ($search) {
                        $q->where('name','LIKE',"%$search%");
                    });
                });
            });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        if ($status) {
            $res = $res->whereIn('status',[3,4,5,7]);
            $total = $res->count();
            $res = $res->skip($offset)->take($limit);
        } else {
            if ($request->status) {
                $res = $res->where('status',$request->status);
            }
        }

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['logo'] = $row->school->logo;
            $tempRow['school_name'] = $row->school->name;
            $tempRow['plan'] = $row->name;
            $tempRow['billing_cycle'] = $row->start_date .' To '.$row->end_date;
            if ($row->subscription_bill) {
                $tempRow['amount'] = number_format($row->subscription_bill->amount, 2);
            }

            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
}
