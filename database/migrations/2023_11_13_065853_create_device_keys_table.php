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
        Schema::create('device_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('userId')->index();
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            $table->string('userdeviceKey')->nullable();
            $table->string('webdeviceKey')->nullable();
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
        Schema::dropIfExists('device_keys');
    }
};
