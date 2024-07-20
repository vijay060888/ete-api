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
        Schema::create('donation_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('orderId');
            $table->string('trackingId');
            $table->string('bankRefNo')->nullable();
            $table->string('orderStatus');
            $table->string('failureMessage')->nullable();
            $table->string('paymentMode');
            $table->string('statusCode');
            $table->string('statusMessage');
            $table->string('currency');
            $table->decimal('amount', 10, 2);
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_transactions');
    }
};
