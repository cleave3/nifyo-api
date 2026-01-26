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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->nullable();
            $table->float('amount');
            $table->float('charges');
            $table->string('transaction_type')->default('customer_deposit');
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->json('meta_data')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->integer('number_of_required_approval')->default(0);
            $table->timestamps();
            $table->uuid('initiator_id')->nullable();
            $table->uuid('updated_by_id')->nullable();

            $table->foreign('business_id')->references('id')->on('businesses');
            $table->foreign('initiator_id')->references('uid')->on('users');
            $table->foreign('updated_by_id')->references('uid')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
