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
        Schema::create('donation_bank_account_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('userId')->index();            
            $table->string('accountHolderName')->nullable(); 
            $table->string('type')->nullable();
            $table->string('ifsc')->nullable();
            $table->string('accountNumber')->nullable();
            $table->string('bankName')->nullable();
            $table->string('branch')->nullable();
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_bank_account_details');
    }
};
