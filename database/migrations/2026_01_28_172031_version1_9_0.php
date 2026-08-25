<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $envFile = base_path('.env');
        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);

            if (strpos($content, 'SESSION_COOKIE=') === false) {
                file_put_contents($envFile, $content . "\nSESSION_COOKIE=eschool_saas_session");
                $content .= "\nSESSION_COOKIE=eschool_saas_session";
            }

            if (strpos($content, 'DB_PREFIX=') === false) {
                file_put_contents($envFile, $content . "\nDB_PREFIX=\"eschool_saas\"  #don't use special characters and spaces in the prefix");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert changes made in the up method
    }
};
