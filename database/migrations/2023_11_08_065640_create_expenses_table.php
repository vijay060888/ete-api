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
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->decimal('totalExpense', 10, 2);
            $table->string('expenseBy');
            $table->string('expenseTowards');
            $table->string('paymentMode');
            $table->string('nameOfVendor');
            $table->text('Description')->nullable();
            $table->date('expenseDate');
            $table->string('transaction')->nullable();
            $table->string('hashTag')->nullable();
            $table->string('invoice')->nullable();
            $table->uuid('expenseCreatedBy')->index();
            $table->foreign('expenseCreatedBy')->references('id')->on('users')->onDelete('cascade');
            $table->string('expenseCreatedByType');
            $table->string('remarks')->nullable();
            $table->string('authorize')->nullable();
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
            $table->uuid('createdBy')->index();
            $table->foreign('createdBy')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('updatedBy')->index();
            $table->foreign('updatedBy')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
