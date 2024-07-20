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
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_id');
            $table->string('tracking_id');
            $table->string('order_status');
            $table->text('failure_message')->nullable();
            $table->string('payment_mode');
            $table->string('card_name');
            $table->string('status_code');
            $table->string('status_message');
            $table->string('currency');
            $table->decimal('amount', 10, 2);
            $table->string('billing_name');
            $table->string('billing_tel');
            $table->string('billing_email');
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
