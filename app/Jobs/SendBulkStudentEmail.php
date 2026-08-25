<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\School;
use App\Models\Students;
use App\Models\User;
use App\Services\CachingService;
use App\Services\SchoolDataService;
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

final class SendBulkStudentEmail implements ShouldQueue
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
     * @param int $studentUserId The student user ID to send email for
     */
    public function __construct(
        private readonly int $schoolId,
        private readonly int $studentUserId
    ) {}

    /**
     * Execute the job.
     *
     * @param SchoolDataService $schoolDataService
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

            // Step 3: Fetch uploaded student data from school database
            $student = Students::with(['user', 'guardian'])
                ->where('user_id', $this->studentUserId)
                ->first();

            if (!$student) {
                Log::warning("No student found for user ID: {$this->studentUserId} in school database: {$school->database_name}");
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

    
           
            $guardian = $student->guardian;
            $child = $student->user;

            if (!$guardian || !$child) {
                Log::warning("Guardian or child not found for student user ID: {$this->studentUserId}");
                $this->switchBackToMainDatabase();
                return;
            }

            // Generate passwords
            $childPassword = $this->makeStudentPassword($child->dob);
            $parentPassword = $this->makeParentPassword($guardian->mobile);

            // Prepare email content with placeholders
            $emailBody = $this->replacePlaceholders(
                $guardian,
                $child,
                $student->admission_no,
                $childPassword,
                $school,
                $schoolSettings,
                $systemSettings
            );

            $data = [
                'subject' => 'Admission Application Approved - Welcome to ' . $schoolSettings['school_name'] ?? $school->name,
                'email' => $guardian->email,
                'email_body' => $emailBody
            ];

            // Send email notification to parent
            Mail::send('students.email', $data, static function ($message) use ($data) {
                $message->to($data['email'])->subject($data['subject']);
            });

            Log::info("Email sent successfully to parent {$guardian->email} for student {$child->full_name}");

           
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
            'student_user_id' => $this->studentUserId
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
     * Generate student password from date of birth.
     *
     * @param string|null $dob
     * @return string
     */
    private function makeStudentPassword(?string $dob): string
    {
        if (!$dob) {
            return 'password123';
        }

        $date = date('dmY', strtotime($dob));
        return $date;
    }

    /**
     * Generate parent password from mobile number.
     *
     * @param string|null $mobile
     * @return string
     */
    private function makeParentPassword(?string $mobile): string
    {
        if (!$mobile) {
            return 'password123';
        }

        return $mobile;
    }

    /**
     * Replace placeholders in email template.
     *
     * @param User $guardian
     * @param User $child
     * @param string $admissionNo
     * @param string $childPassword
     * @param School $school
     * @param array $schoolSettings
     * @param array $systemSettings
     * @return string
     */
    private function replacePlaceholders(
        User $guardian,
        User $child,
        string $admissionNo,
        string $childPassword,
        School $school,
        array $schoolSettings,
        array $systemSettings
    ): string {
        $templateContent = $schoolSettings['email-template-parent'] ?? '';

        $placeholders = [
            '{parent_name}' => $guardian->full_name ?? ($guardian->first_name . ' ' . $guardian->last_name),
            '{code}' => $school->code ?? '',
            '{email}' => $guardian->email ?? '',
            '{password}' => $this->makeParentPassword($guardian->mobile),
            '{school_name}' => $schoolSettings['school_name'] ?? $school->name,
            '{child_name}' => $child->full_name ?? ($child->first_name . ' ' . $child->last_name),
            '{grno}' => $child->email ?? '',
            '{child_password}' => $childPassword,
            '{admission_no}' => $admissionNo,
            '{support_email}' => $schoolSettings['school_email'] ?? $school->support_email ?? '',
            '{support_contact}' => $schoolSettings['school_phone'] ?? $school->support_phone ?? '',
            '{android_app}' => $systemSettings['app_link'] ?? '',
            '{ios_app}' => $systemSettings['ios_app_link'] ?? '',
        ];

        foreach ($placeholders as $placeholder => $replacement) {
            $templateContent = str_replace($placeholder, $replacement, $templateContent);
        }

        return $templateContent;
    }
}

