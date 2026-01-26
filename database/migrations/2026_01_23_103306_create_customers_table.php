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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('image_url')->nullable();
            $table->string('address')->nullable();
            $table->string('payment_frequency')->default('monthly');
            $table->string('sms_alert')->nullable()->default('YES');
            $table->string('status')->nullable()->default('active');
            $table->timestamp('next_payment_date')->nullable();
            $table->string('customer_type')->nullable();
            $table->string('customer_code')->nullable();
            $table->timestamps();
            $table->uuid('creator_id')->nullable();

            $table->foreign('business_id')->references('id')->on('businesses');
            $table->foreign('creator_id')->references('uid')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
