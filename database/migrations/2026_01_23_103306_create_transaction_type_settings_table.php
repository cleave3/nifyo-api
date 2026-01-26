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
        Schema::create('transaction_types_setting', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_id')->nullable();
            $table->string('type')->default('customer_deposit');
            $table->boolean('requires_approval')->default(false);
            $table->integer('number_of_required_approval')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_types_setting');
    }
};
