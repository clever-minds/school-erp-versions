<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('holidays', function (Blueprint $table) {

            $table->string('type')->default('holiday')->after('description');
            $table->text('class_ids')->nullable()->after('type');

        });
    }

    public function down()
    {
        Schema::table('holidays', function (Blueprint $table) {

            $table->dropColumn(['type','class_ids']);

        });
    }
};