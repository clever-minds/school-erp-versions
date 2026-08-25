<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\SchoolProvisionProgressUpdated;
use App\Models\School;
use App\Services\SchoolDataService;
use App\Services\SubscriptionService;
use App\Services\CachingService;
use App\Repositories\SystemSetting\SystemSettingInterface;
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

final class SetupSchoolDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes timeout
    public int $backoff = 120; // 2 minutes between retries

    private array $currentProgress = [];

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int     $schoolId,
        private readonly ?int    $packageId = null,
        private readonly ?string $schoolCodePrefix = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        SchoolDataService    $schoolService,
        SubscriptionService  $subscriptionService,
        CachingService       $cache,
        SystemSettingInterface $systemSettings
    ): void {
        try {
            DB::setDefaultConnection('mysql');

            // Get school data and reset progress
            $school = School::withTrashed()->findOrFail($this->schoolId);
            $this->currentProgress = [];
            School::on('mysql')->withTrashed()->where('id', $school->id)->update(['progress' => null, 'provision_step' => 'job_started']);

            // ── Step: Job Started ─────────────────────────────────────────
            $this->broadcastStep($school, 'job_started', 'installing', 'Initializing school setup...');
            sleep(2);

            // ── Step: Create Database ─────────────────────────────────────
            $this->broadcastStep($school, 'database_created', 'installing', 'Establishing database architecture...');
            DB::statement("CREATE DATABASE IF NOT EXISTS {$school->database_name}");
            sleep(2);

            // ── Step: Run Migrations ──────────────────────────────────────
            $this->broadcastStep($school, 'migrations_completed', 'installing', 'Synchronizing core tables and schema...');
            $schoolService->createDatabaseMigration($school);
            sleep(2);

            // ── Step: Pre-settings (seeders, roles, session year) ─────────
            $this->broadcastStep($school, 'seeders_executed', 'installing', 'Configuring default settings and roles...');
            $schoolService->preSettingsSetup($school);
            sleep(2);

            // ── Step: Update School Code Prefix ───────────────────────────
            if ($this->schoolCodePrefix) {
                $settings = $cache->getSystemSettings();
                if (($settings['school_prefix'] ?? '') != $this->schoolCodePrefix) {
                    $settingsData[] = [
                        'name' => 'school_prefix',
                        'data' => $this->schoolCodePrefix,
                        'type' => 'text',
                    ];
                    $systemSettings->upsert($settingsData, ['name'], ['data']);
                    $cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
                }
            }

            // ── Step: Activate School ─────────────────────────────────────
            // (We set installed=1 at the very end to ensure everything is ready)

            DB::setDefaultConnection('school');
            Config::set('database.connections.school.database', $school->database_name);
            DB::purge('school');
            DB::connection('school')->reconnect();
            DB::setDefaultConnection('school');
            School::on('school')->withTrashed()->where('id', $this->schoolId)->update(['status' => 1, 'installed' => 1]);

            // Reload on primary connection for email
            DB::setDefaultConnection('mysql');
            $school = School::withTrashed()->with('user')->findOrFail($this->schoolId);

            $this->broadcastStep($school, 'school_activated', 'installing', 'Enabling school services...');
            sleep(2);

            // ── Step: Send Welcome Email ──────────────────────────────────
            $this->broadcastStep($school, 'welcome_email_sent', 'installing', 'Dispatching credentials to administrator...');
            $settings = $cache->getSystemSettings();
            $email_body = $this->replacePlaceholders($school, $school->user, $settings, $school->code);

            $data = [
                'subject'    => 'Welcome to ' . ($settings['system_name'] ?? 'eSchool Saas'),
                'email'      => $school->support_email,
                'email_body' => $email_body,
            ];

            Mail::send('schools.email', $data, static function ($message) use ($data) {
                $message->to($data['email'])->subject($data['subject']);
            });

            // ── Step: Send Verification Email ─────────────────────────────
            $this->broadcastStep($school, 'verification_email_sent', 'installing', 'Requesting email verification...');
            if (!$school->user->hasVerifiedEmail()) {
                sleep(5);
                $school->user->sendEmailVerificationNotification();
            }

            // ── Final Step: Success ───────────────────────────────────────
            $this->broadcastStep($school, 'provisioning_completed', 'completed', 'School provisioned successfully!');
        } catch (Throwable $e) {
            Log::error("School database setup failed for school ID: {$this->schoolId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Broadcast failure so the UI can update immediately
            try {
                $school = School::withTrashed()->find($this->schoolId);
                if ($school) {
                    $this->broadcastFailure($school, $e->getMessage());
                }
            } catch (Throwable) {
                // Swallow — don't mask the original exception
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("School database setup job failed permanently for school ID: {$this->schoolId}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Persist progress and broadcast a step update.
     */
    private function broadcastStep(School $school, string $stepKey, string $status, string $message): void
    {
        $stepInfo = School::STEPS[$stepKey] ?? ['label' => $stepKey, 'progress' => 0];

        // Build updated progress payload
        $this->currentProgress[$stepKey] = true;

        // Persist to DB
        try {
            $updateData = [
                'progress'       => json_encode($this->currentProgress),
                'provision_step' => $stepKey,
            ];

            // If this is the final step, mark as installed
            if ($stepKey === 'provisioning_completed') {
                $updateData['installed'] = 1;
                $updateData['status']    = 1; // Mark school as active
            }

            School::on('mysql')->withTrashed()->where('id', $school->id)->update($updateData);
        } catch (Throwable $e) {
            Log::warning("Could not persist provision progress for school {$school->id}: " . $e->getMessage());
        }

        event(new SchoolProvisionProgressUpdated(
            schoolId: $school->id,
            schoolName: $school->name,
            step: $stepKey,
            stepLabel: $stepInfo['label'],
            progress: $stepInfo['progress'],
            status: $status,
            message: $message,
        ));
    }

    /**
     * Broadcast a failure event.
     */
    private function broadcastFailure(School $school, string $errorMessage): void
    {
        // Determine last completed progress %
        $lastStep = array_key_last($this->currentProgress) ?? 'job_started';
        $lastProgress = School::STEPS[$lastStep]['progress'] ?? 5;

        event(new SchoolProvisionProgressUpdated(
            schoolId: $school->id,
            schoolName: $school->name,
            step: 'failed',
            stepLabel: 'Setup Failed',
            progress: $lastProgress,
            status: 'failed',
            message: $errorMessage,
        ));
    }

    private function replacePlaceholders($school, $user, $settings, $schoolCode): string
    {
        $templateContent = $settings['email_template_school_registration'] ?? '';

        $placeholders = [
            '{school_admin_name}' => $user->full_name,
            '{code}'              => $schoolCode,
            '{email}'             => $user->email,
            '{password}'          => $user->mobile,
            '{school_name}'       => $school->name ?? '',
            '{super_admin_name}'  => $settings['super_admin_name'] ?? 'Super Admin',
            '{support_email}'     => $settings['mail_username'] ?? '',
            '{contact}'           => $settings['mobile'] ?? '',
            '{system_name}'       => $settings['system_name'] ?? 'eSchool Saas',
            '{url}'               => url('/'),
        ];

        foreach ($placeholders as $placeholder => $replacement) {
            $templateContent = str_replace($placeholder, $replacement, $templateContent);
        }

        return $templateContent;
    }
}
