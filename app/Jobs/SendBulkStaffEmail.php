<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\School;
use App\Models\Staff;
use App\Models\User;
use App\Services\CachingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendBulkStaffEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 300; // 5 minutes timeout

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 120; // 2 minutes between retries

    /**
     * Create a new job instance.
     *
     * @param int $schoolId The school ID from the main database (stored in job payload)
     * @param int $staffUserId The staff user ID to send email for
     */
    public function __construct(
        private readonly int $schoolId,
        private readonly int $staffUserId
    ) {}

    /**
     * Execute the job.
     *
     * @param CachingService $cache
     * @return void
     */
    public function handle(
        CachingService $cache
    ): void {
        // Ensure we start with main database connection
        DB::setDefaultConnection('mysql');

        try {
            // Step 1: Get school information from main database using school_id
            $school = School::on('mysql')->find($this->schoolId);

            if (!$school) {
                Log::error("School not found for ID: {$this->schoolId}");
                throw new \Exception("School not found for ID: {$this->schoolId}");
            }

            // Step 2: Dynamically switch to school-specific database
            DB::setDefaultConnection('school');
            Config::set('database.connections.school.database', $school->database_name);
            DB::purge('school');
            DB::connection('school')->reconnect();
            DB::setDefaultConnection('school');

            // Step 3: Fetch uploaded staff data from school database
            $staff = Staff::with('user')
                ->where('user_id', $this->staffUserId)
                ->first();

            if (!$staff) {
                Log::warning("No staff found for user ID: {$this->staffUserId} in school database: {$school->database_name}");
                $this->switchBackToMainDatabase();
                return;
            }

            $staffUser = $staff->user;

            if (!$staffUser) {
                Log::warning("User not found for staff user ID: {$this->staffUserId}");
                $this->switchBackToMainDatabase();
                return;
            }

            // Get settings and convert Collection to array for type compatibility
            $schoolSettings = $cache->getSchoolSettings('*', $school->id);
            $systemSettings = $cache->getSystemSettings();
            
            // Convert Collections to arrays if needed
            if ($schoolSettings instanceof \Illuminate\Support\Collection) {
                $schoolSettings = $schoolSettings->toArray();
            }
            if ($systemSettings instanceof \Illuminate\Support\Collection) {
                $systemSettings = $systemSettings->toArray();
            }

            // Generate password from mobile number
            $password = $this->makeStaffPassword($staffUser->mobile);

            // Prepare email content with placeholders
            $emailBody = $this->replacePlaceholders(
                $staffUser,
                $password,
                $school,
                $schoolSettings,
                $systemSettings
            );

            $data = [
                'subject' => 'Welcome to ' . ($schoolSettings['school_name'] ?? $school->name),
                'email' => $staffUser->email,
                'email_body' => $emailBody
            ];

            // Send email notification to staff
            Mail::send('teacher.email', $data, static function ($message) use ($data) {
                $message->to($data['email'])->subject($data['subject']);
            });

            Log::info("Email sent successfully to staff {$staffUser->email} for user {$staffUser->full_name}");

           
        } catch (Throwable $e) {
            Log::error("Bulk email job failed for school ID: {$this->schoolId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        } finally {
            // Always switch back to main database connection
            $this->switchBackToMainDatabase();
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::error("Bulk email job permanently failed for school ID: {$this->schoolId}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'staff_user_id' => $this->staffUserId
        ]);

        // Ensure we're back on main database
        $this->switchBackToMainDatabase();
    }

    /**
     * Switch back to main database connection.
     *
     * @return void
     */
    private function switchBackToMainDatabase(): void
    {
        try {
            DB::setDefaultConnection('mysql');
        } catch (Throwable $e) {
            Log::error("Failed to switch back to main database: " . $e->getMessage());
        }
    }

    /**
     * Generate staff password from mobile number.
     *
     * @param string|null $mobile
     * @return string
     */
    private function makeStaffPassword(?string $mobile): string
    {
        if (!$mobile) {
            return 'password123';
        }

        return $mobile;
    }

    /**
     * Replace placeholders in email template.
     *
     * @param User $staffUser
     * @param string $password
     * @param School $school
     * @param array $schoolSettings
     * @param array $systemSettings
     * @return string
     */
    private function replacePlaceholders(
        User $staffUser,
        string $password,
        School $school,
        array $schoolSettings,
        array $systemSettings
    ): string {
        $templateContent = $schoolSettings['email-template-staff'] ?? '';

        $placeholders = [
            '{full_name}' => $staffUser->full_name ?? ($staffUser->first_name . ' ' . $staffUser->last_name),
            '{code}' => $school->code ?? '',
            '{email}' => $staffUser->email ?? '',
            '{password}' => $password,
            '{school_name}' => $schoolSettings['school_name'] ?? $school->name,
            '{support_email}' => $schoolSettings['school_email'] ?? $school->support_email ?? '',
            '{support_contact}' => $schoolSettings['school_phone'] ?? $school->support_phone ?? '',
            '{url}' => url('/'),
            '{android_app}' => $systemSettings['app_link'] ?? '',
            '{ios_app}' => $systemSettings['ios_app_link'] ?? '',
        ];

        foreach ($placeholders as $placeholder => $replacement) {
            $templateContent = str_replace($placeholder, $replacement, $templateContent);
        }

        return $templateContent;
    }
}

