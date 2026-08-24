<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('student_pickup_requests');
        Schema::create('student_pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('student_id')->unsigned();
            $table->bigInteger('parent_id')->unsigned();
            $table->string('pickup_person_name');
            $table->string('otp', 6);
            $table->tinyInteger('status')->default(0)->comment('0: Pending, 1: Verified, 2: Expired');
            $table->bigInteger('verified_by')->unsigned()->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->bigInteger('school_id')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_pickup_requests');
    }
};
