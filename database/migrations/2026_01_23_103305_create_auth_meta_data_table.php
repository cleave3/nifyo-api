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
        Schema::create('auth_meta_data', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('device_ip');
            $table->string('device_name');
            $table->string('device_os');
            $table->string('device_browser');
            $table->string('timezone');
            $table->timestamp('login_time')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_meta_data');
    }
};
