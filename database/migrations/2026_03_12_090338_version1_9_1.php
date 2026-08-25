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

        if (!file_exists($envFile)) {
            return;
        }

        $content = file_get_contents($envFile);

        $variables = [
            'BROADCAST_DRIVER'     => 'reverb',
            'REVERB_APP_ID'        => '413804',
            'REVERB_APP_KEY'       => 'e2mhe9gu4tb2x2vkncxa',
            'REVERB_APP_SECRET'    => 'tfpqsy4pwluxohhvbjig',
            'REVERB_HOST'          => 'localhost',
            'REVERB_PORT'          => '9090',
            'REVERB_SCHEME'        => 'http',
            'VITE_REVERB_APP_KEY'  => '"${REVERB_APP_KEY}"',
            'VITE_REVERB_HOST'     => '"${REVERB_HOST}"',
            'VITE_REVERB_PORT'     => '"${REVERB_PORT}"',
            'VITE_REVERB_SCHEME'   => '"${REVERB_SCHEME}"',
        ];

        $appended = false;

        foreach ($variables as $key => $defaultValue) {
            // Check if the key already exists (handles KEY= anywhere in the file)
            if (!preg_match('/^' . preg_quote($key, '/') . '=/m', $content)) {
                $content  .= "\n{$key}={$defaultValue}";
                $appended  = true;
            }
        }

        if ($appended) {
            file_put_contents($envFile, $content);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty — .env changes are not reverted
    }
};
