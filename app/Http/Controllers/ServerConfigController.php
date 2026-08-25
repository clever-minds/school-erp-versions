<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Repositories\SystemSetting\SystemSettingInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDO;
use PDOException;
use Throwable;

class ServerConfigController extends Controller
{
    private SystemSettingInterface $systemSettings;
    private CachingService $cache;

    public function __construct(SystemSettingInterface $systemSettings, CachingService $cachingService)
    {
        $this->systemSettings = $systemSettings;
        $this->cache = $cachingService;
    }

    /**
     * Display the Server Configuration Check page.
     */
    public function index()
    {
        ResponseService::noPermissionThenRedirect('system-setting-manage');

        $dbCheck = $this->systemSettings->builder()
            ->where('name', 'server_config_db_checkMark')
            ->value('data');

        $queueCheck = $this->systemSettings->builder()
            ->where('name', 'server_config_queue_checkMark')
            ->value('data');

        $reverbCheck = $this->systemSettings->builder()
            ->where('name', 'server_config_reverb_checkMark')
            ->value('data');

        $checksPassedCount = (int)($dbCheck == 1) + (int)($queueCheck == 1) + (int)($reverbCheck == 1);

        if ($checksPassedCount == 3) {
            $this->saveCheckMark('server_config_wizard_checkMark', 1);
            return redirect()->route('dashboard');
        }

        $DBPassword = env('DB_PASSWORD');
        if (env('DEMO_MODE')) {
            $DBPassword = '**********';
        }

        $data = [
            'db_host' => env('DB_HOST'),
            'db_port' => env('DB_PORT'),
            'db_username' => env('DB_USERNAME'),
            'db_password' => $DBPassword,
        ];

        return view('server-config.index', compact('dbCheck', 'queueCheck', 'reverbCheck', 'checksPassedCount', 'data'));
    }

    /**
     * Test raw database connection + CREATE / DROP privileges.
     */
    public function testDatabasePrivileges(Request $request): JsonResponse
    {
        $request->validate([
            'db_host'     => ['required', 'string'],
            'db_port'     => ['required', 'numeric'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        $host     = $request->input('db_host');
        $port     = (int)$request->input('db_port');
        $username = $request->input('db_username');
        $password = $request->input('db_password', '');

        $logs = [];

        try {
            $logs[] = "[INFO] Connecting to {$host}:{$port} as '{$username}'...";

            $pdo = new PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
            );

            $logs[] = "[OK]   Connection established.";

            // Test CREATE privilege
            $testDb = 'eschool_privilege_test_' . time();
            $pdo->exec("CREATE DATABASE `{$testDb}`");
            $logs[] = "[OK]   CREATE DATABASE privilege confirmed.";

            // Test DROP privilege
            $pdo->exec("DROP DATABASE `{$testDb}`");
            $logs[] = "[OK]   DROP DATABASE privilege confirmed.";

            // Save check mark
            $this->saveCheckMark('server_config_db_checkMark', 1);
            $logs[] = "[OK]   Database root privileges check PASSED.";

            // update .env file
            changeEnv([
                'DB_HOST'     => $host,
                'DB_PORT'     => $port,
                'DB_USERNAME' => $username,
                'DB_PASSWORD' => $password,
            ]);

            while (ob_get_level()) {
                ob_end_clean();
            }
            return response()->json([
                'success' => true,
                'message' => 'Database root privileges verified successfully.',
                'logs'    => $logs,
            ]);
        } catch (PDOException $e) {
            $logs[] = "[ERROR] " . $e->getMessage();
            $this->saveCheckMark('server_config_db_checkMark', 0);

            while (ob_get_level()) {
                ob_end_clean();
            }
            return response()->json([
                'success' => false,
                'message' => 'Database connection or privilege check failed.',
                'logs'    => $logs,
            ]);
        } catch (Throwable $e) {
            $logs[] = "[ERROR] Unexpected error: " . $e->getMessage();
            $this->saveCheckMark('server_config_db_checkMark', 0);

            while (ob_get_level()) {
                ob_end_clean();
            }
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'logs'    => $logs,
            ]);
        }
    }

    /**
     * Check if a Laravel queue worker is running.
     */
    public function testQueueWorker(Request $request): JsonResponse
    {
        $logs = [];
        $logs[] = "[INFO] Checking for active queue worker process...";

        try {
            $workerRunning = $this->isProcessRunning('queue:work', $logs);

            if ($workerRunning) {
                $logs[] = "[OK]   Queue worker process is active.";
                $logs[] = "[OK]   Queue Worker (Supervisor) check PASSED.";
                $this->saveCheckMark('server_config_queue_checkMark', 1);

                while (ob_get_level()) {
                    ob_end_clean();
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Queue worker is running.',
                    'logs'    => $logs,
                ]);
            } else {
                $logs[] = "[WARN] No active 'queue:work' process found.";
                $logs[] = "[INFO] On production, ensure Supervisor is configured to run: php artisan queue:work";
                $logs[] = "[WARN] Queue Worker check: NOT RUNNING (configure Supervisor on production).";
                $this->saveCheckMark('server_config_queue_checkMark', 0);

                while (ob_get_level()) {
                    ob_end_clean();
                }
                return response()->json([
                    'success' => false,
                    'message' => 'No active queue worker detected. Configure Supervisor on production.',
                    'logs'    => $logs,
                ]);
            }
        } catch (Throwable $e) {
            $logs[] = "[ERROR] " . $e->getMessage();
            $this->saveCheckMark('server_config_queue_checkMark', 0);

            while (ob_get_level()) {
                ob_end_clean();
            }
            return response()->json([
                'success' => false,
                'message' => 'Could not determine queue worker status.',
                'logs'    => $logs,
            ]);
        }
    }

    /**
     * Check if a Laravel Reverb WebSocket server is running.
     */
    public function testReverbWorker(Request $request): JsonResponse
    {
        $logs = [];
        $logs[] = "[INFO] Checking for active Reverb (reverb:start) process...";

        try {
            $reverbRunning = $this->isProcessRunning('reverb:start', $logs);

            if ($reverbRunning) {
                $logs[] = "[OK]   Reverb process is active.";
                $logs[] = "[OK]   Laravel Reverb (Supervisor) check PASSED.";
                $this->saveCheckMark('server_config_reverb_checkMark', 1);

                while (ob_get_level()) {
                    ob_end_clean();
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Reverb WebSocket server is running.',
                    'logs'    => $logs,
                ]);
            } else {
                $logs[] = "[WARN] No active 'reverb:start' process found.";
                $logs[] = "[INFO] On production, ensure Supervisor is configured to run: php artisan reverb:start";
                $logs[] = "[WARN] Reverb check: NOT RUNNING (configure Supervisor on production).";
                $this->saveCheckMark('server_config_reverb_checkMark', 0);

                while (ob_get_level()) {
                    ob_end_clean();
                }
                return response()->json([
                    'success' => false,
                    'message' => 'No active Reverb process detected. Configure Supervisor on production.',
                    'logs'    => $logs,
                ]);
            }
        } catch (Throwable $e) {
            $logs[] = "[ERROR] " . $e->getMessage();
            $this->saveCheckMark('server_config_reverb_checkMark', 0);

            while (ob_get_level()) {
                ob_end_clean();
            }
            return response()->json([
                'success' => false,
                'message' => 'Could not determine Reverb worker status.',
                'logs'    => $logs,
            ]);
        }
    }

    /**
     * Mark server configuration as fully complete and redirect to dashboard.
     */
    public function markComplete(): JsonResponse
    {
        $this->saveCheckMark('server_config_wizard_checkMark', 1);

        while (ob_get_level()) {
            ob_end_clean();
        }
        return response()->json([
            'success'  => true,
            'redirect' => route('dashboard'),
        ]);
    }

    /**
     * Run a system command safely using proc_open (avoids shell_exec restrictions).
     * Returns the combined stdout+stderr output, or null on failure.
     */
    private function runCommand(string $command): ?string
    {
        if (!function_exists('proc_open')) {
            return null;
        }

        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $process = @proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            return null;
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $stderr  = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return ($output !== false ? $output : '') . ($stderr !== false ? $stderr : '');
    }

    /**
     * Check if a given artisan command keyword is running as a process.
     * Works on Windows (via WMIC/tasklist) and Linux/Mac (via ps).
     */
    private function isProcessRunning(string $keyword, array &$logs): bool
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            // First check if any php.exe process exists
            $taskOutput = $this->runCommand('tasklist /FI "IMAGENAME eq php.exe" /FO CSV /NH');
            $logs[] = "[INFO] tasklist output received.";

            if ($taskOutput === null || stripos($taskOutput, 'php.exe') === false) {
                $logs[] = "[INFO] No php.exe processes found.";
                return false;
            }

            // Use WMIC to get command lines — falls back to false if unavailable
            $wmicOutput = $this->runCommand('wmic process where "name=\'php.exe\'" get commandline /format:csv');
            if ($wmicOutput !== null && stripos($wmicOutput, $keyword) !== false) {
                return true;
            }

            // Fallback: try PowerShell Get-Process
            $psOutput = $this->runCommand(
                'powershell -NoProfile -Command "Get-WmiObject Win32_Process -Filter \"name=\'php.exe\'\" | Select-Object -ExpandProperty CommandLine"'
            );
            if ($psOutput !== null && stripos($psOutput, $keyword) !== false) {
                return true;
            }

            return false;
        } else {
            // Linux / Mac
            //
            // aaPanel Supervisor daemons use a *relative* start command
            // (e.g. "php artisan queue:work") with "Process directory" set to
            // the app root. The process command line therefore does NOT contain
            // the app path — only the process working directory (cwd) does.
            //
            // Strategy:
            //   1. Find all PIDs whose command line contains the keyword (pgrep -f).
            //   2. Resolve each PID's cwd:
            //        Linux → readlink /proc/<pid>/cwd   (fast, no extra tools)
            //        macOS → lsof -a -d cwd -p <pid>   (/proc does not exist on Mac)
            //   3. Accept the process only if its cwd matches base_path().
            //
            // This isolates per-domain regardless of absolute vs relative commands.
            $appBasePath = rtrim(base_path(), '/');
            $isLinux     = strtolower(PHP_OS) === 'linux';

            // Step 1: get matching PIDs
            $pgrepOutput = $this->runCommand('pgrep -f ' . escapeshellarg($keyword) . ' 2>/dev/null');

            if ($pgrepOutput !== null && trim($pgrepOutput) !== '') {
                foreach (explode("\n", trim($pgrepOutput)) as $pid) {
                    $pid = trim($pid);
                    if (!ctype_digit($pid)) {
                        continue;
                    }

                    // Step 2: resolve cwd — method differs by OS
                    if ($isLinux) {
                        // Linux: /proc/<pid>/cwd is a symlink to the cwd
                        $cwd = $this->runCommand("readlink /proc/{$pid}/cwd 2>/dev/null");
                    } else {
                        // macOS: lsof reports the cwd; last column of the data row is the path
                        $cwd = $this->runCommand(
                            "lsof -a -d cwd -p {$pid} 2>/dev/null | awk 'NR==2{print \$NF}'"
                        );
                    }

                    if ($cwd === null) {
                        continue;
                    }

                    $cwd = rtrim(trim($cwd), '/');

                    // Step 3: cwd must match this app's base path
                    if ($cwd === $appBasePath) {
                        return true;
                    }
                }
            }

            return false;
        }
    }

    /**
     * Upsert a check mark value in system_settings.
     */
    private function saveCheckMark(string $name, int $value): void
    {
        SystemSetting::upsert(
            [['name' => $name, 'data' => $value]],
            ['name'],
            ['data']
        );
        $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
    }
}
