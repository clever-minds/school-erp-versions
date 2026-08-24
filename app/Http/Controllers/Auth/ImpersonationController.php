<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function loginAsStaff($staffId)
    {
        $admin = Auth::user();
        // Spatie permission check
        abort_unless($admin->can('login-as-staff'), 403);

        // 🔒 SAME SCHOOL / TENANT DATABASE
        $schoolDb = Session::get('school_database_name');

        if (!$schoolDb) {
            abort(403, 'School database not selected');
        }

        // Switch to school DB
        Config::set('database.connections.school.database', $schoolDb);
        DB::purge('school');
        DB::connection('school')->reconnect();
        DB::setDefaultConnection('school');

        // Staff fetch
        $staff = User::findOrFail($staffId);
         $staff->update([
        'impersonated_by' => $admin->id,
    ]);
        // Save admin id only for EXIT
        session(['impersonated_by' => $admin->id,'impersonation_return_url' => url()->previous(),"impersonation_admin"=>true]);

        // Login as staff (NO PASSWORD, NO 2FA)
        Auth::loginUsingId($staff->id);

        return redirect('/dashboard');
    }

    public function loginAsSchool($schoolId)
    {
        $admin = Auth::user();
        abort_unless($admin->hasRole('Super Admin'), 403);

        $school = \App\Models\School::findOrFail($schoolId);

        if (!$school->database_name) {
            abort(403, 'School database not found');
        }

        // Switch to school DB
        Session::put('school_database_name', $school->database_name);
        Config::set('database.connections.school.database', $school->database_name);
        DB::purge('school');
        DB::connection('school')->reconnect();
        DB::setDefaultConnection('school');

        // Get tenant admin ID
        $tenantAdminId = $school->admin_id;
        $schoolAdmin = User::find($tenantAdminId) ?? User::role('School Admin')->first();

        if (!$schoolAdmin) {
            abort(403, 'School Admin not found in tenant database');
        }

        // Skip updating 'impersonated_by' in the database for super admin
        // because the super admin's ID doesn't exist in the tenant's users table.
        // The session variables below are sufficient to track impersonation.

        // Save admin id only for EXIT
        session([
            'super_admin_impersonated_by' => $admin->id,
            'is_super_admin_impersonating' => true,
            'impersonation_admin' => true
        ]);

        // Login as school admin (NO PASSWORD, NO 2FA)
        Auth::loginUsingId($schoolAdmin->id);

        return redirect('/dashboard');
    }

    public function exit(Request $request)
    {
        // First check if we are exiting a staff impersonation (started by a school admin)
        if (session()->has('impersonated_by')) {
            $adminId = session('impersonated_by');
            $returnUrl = session('impersonation_return_url', '/dashboard');

            Auth::logout();
            Auth::loginUsingId($adminId);

            session()->forget([
                'impersonated_by',
                'impersonation_return_url',
            ]);

            if (!session('is_super_admin_impersonating')) {
                session()->forget('impersonation_admin');
            }

            if (!str_starts_with($returnUrl, url('/'))) {
                $returnUrl = '/dashboard';
            }

            return redirect($returnUrl);
        }

        // Otherwise, check if we are exiting a school impersonation
        if (session('is_super_admin_impersonating')) {
            $superAdminId = session('super_admin_impersonated_by');
            
            Auth::logout();

            session()->forget('school_database_name');
            Session::put('school_database_name', null);
            
            DB::purge('school');
            DB::connection('mysql')->reconnect();
            DB::setDefaultConnection('mysql');

            if ($superAdminId) {
                Auth::loginUsingId($superAdminId);
            }

            session()->forget([
                'super_admin_impersonated_by',
                'is_super_admin_impersonating',
                'impersonation_admin'
            ]);

            return redirect('/dashboard');
        }

        abort(403);
    }
}
