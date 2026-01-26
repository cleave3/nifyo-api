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
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('name');
            $table->string('description')->nullable();
            $table->float('value')->default(0.0);
            $table->json('images')->nullable();
            $table->timestamp('purchase_date')->useCurrent();
            $table->timestamp('warranty_expiry_date')->nullable();
            $table->string('asset_type')->default('equipment');
            $table->string('asset_condition')->default('new');
            $table->string('asset_status')->default('available');
            $table->string('asset_location')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
