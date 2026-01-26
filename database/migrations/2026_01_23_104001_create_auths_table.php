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
        Schema::create('auth', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('name')->nullable();
            $table->string('user_type')->default('root');
            $table->boolean('is_email_verified')->nullable()->default(false);
            $table->string('status')->nullable()->default('active');
            $table->boolean('is_phone_verified')->nullable()->default(false);
            $table->boolean('two_factor_enabled')->nullable()->default(false);
            $table->string('two_factor_option')->default('none');
            $table->boolean('has_password')->nullable()->default(false);
            $table->string('password_hash')->nullable();
            $table->timestamp('last_login')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth');
    }
};
