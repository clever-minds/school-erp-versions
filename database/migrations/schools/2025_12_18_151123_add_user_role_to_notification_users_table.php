<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('notification_users', function (Blueprint $table) {
            $table->string('user_role', 50)
                  ->nullable()
                  ->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('notification_users', function (Blueprint $table) {
            $table->dropColumn('user_role');
        });
    }
};
