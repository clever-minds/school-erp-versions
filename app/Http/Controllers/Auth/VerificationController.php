<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\VerifiesEmails;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except('verify');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @param  string  $hash
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(\Illuminate\Http\Request $request, $id, $hash)
    {
        $user = \App\Models\User::find($id);

        if (! $user) {
            abort(404, 'User not found.');
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new \Illuminate\Auth\Access\AuthorizationException;
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($this->redirectPath());
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));

            // Sync to School Database if school_id is present
            if ($user->school_id) {
                try {
                    $school = \App\Models\School::find($user->school_id);
                    if ($school) {
                        \Illuminate\Support\Facades\Config::set('database.connections.school.database', $school->database_name);
                        \Illuminate\Support\Facades\DB::purge('school');
                        \Illuminate\Support\Facades\DB::connection('school')->table('users')->where('id', $user->id)->update([
                            'email_verified_at' => $user->email_verified_at
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to sync email verification to school database: " . $e->getMessage());
                }
            }
        }

        return redirect($this->redirectPath())->with('verified', true)->with('success', 'Email verified successfully.');
    }
}
