<?php

namespace App\Http\Controllers;

use App\Repositories\Announcement\AnnouncementInterface;
use App\Repositories\School\SchoolInterface;
use App\Repositories\Subscription\SubscriptionInterface;
use App\Repositories\User\UserInterface;
use App\Services\CachingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private UserInterface $user;
    private AnnouncementInterface $announcement;
    private SubscriptionInterface $subscription;
    private SchoolInterface $school;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserInterface $user, AnnouncementInterface $announcement, SubscriptionInterface $subscription, SchoolInterface $school)
    {
        $this->middleware('auth');
        $this->user = $user;
        $this->announcement = $announcement;
        $this->subscription = $subscription;
        $this->school = $school;
    }

    public function index()
    {
        $teacher = $student = $parent = $teachers = $subscription = null;
        $boys = $girls = $license_expire = 0;
        $previous_subscriptions = array();
        $announcement = array();
        $settings = app(CachingService::class)->getSystemSettings();
        // School Admin Dashboard
        if (Auth::user()->hasRole('School Admin') || Auth::user()->school_id) {
            $teacher = $this->user->builder()->role("Teacher")->count();
            $student = $this->user->builder()->role("Student")->count();
            $parent = $this->user->builder()->role('Guardian')->count();
            $teachers = $this->user->builder()->role("Teacher")->with('teacher')->get();
            if ($student > 0) {
                $boys_count = $this->user->builder()->role('Student')->where('gender', 'male')->count();
                $girls_count = $this->user->builder()->role('Student')->where('gender', 'female')->count();
                $boys = round((($boys_count * 100) / $student), 2);
                $girls = round(($girls_count * 100) / $student, 2);
            }

            $subscription = $this->subscription->default()->with('subscription_bill')->first();
            if ($subscription) {
                $license_expire = Carbon::now()->diffInDays(Carbon::parse($subscription->end_date)) + 1;
            }
            $previous_subscriptions = $this->subscription->builder()->with('subscription_bill')->get()->whereIn('status', [3, 4, 5]);


            $announcement = $this->announcement->builder()->whereHas('announcement_class', function ($q) {
                $q->where('class_subject_id', null);
            })->limit(5)->get();
        }

        // Super admin dashboard
        $super_admin = [
            'total_school' => 0,
            'active_school' => 0,
            'deactive_school' => 0,
        ];
        if (Auth::user()->hasRole('Super Admin') || !Auth::user()->school_id) {
            $school = $this->school->builder()->get();
            $total_school = $school->count();
            $active_school = $school->where('status', 1)->count();
            $deactive_school = $school->where('status', 0)->count();

            $super_admin = [
                'total_school' => $total_school,
                'active_school' => $active_school,
                'deactive_school' => $deactive_school,
            ];
        }


        return view('dashboard', compact('teacher', 'parent', 'student', 'announcement', 'teachers', 'boys', 'girls', 'license_expire', 'subscription', 'previous_subscriptions', 'settings','super_admin'));
    }
}
