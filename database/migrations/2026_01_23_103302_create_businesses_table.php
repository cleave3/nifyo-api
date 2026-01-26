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
        Schema::create('businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('business_name');
            $table->string('business_address')->nullable();
            $table->string('business_phone');
            $table->string('business_email')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('business_type')->nullable();
            $table->string('business_nature')->nullable();
            $table->string('business_website')->nullable();
            $table->string('business_reg_no')->nullable();
            $table->string('certificate_url')->nullable();
            $table->json('modules')->nullable();
            $table->string('business_kyc_status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
