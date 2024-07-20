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
        Schema::create('leader_ministries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leaderId')->index()->nullable();
            $table->foreign('leaderId')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('ministryId')->index()->nullable();
            $table->foreign('ministryId')->references('id')->on('ministries')->onDelete('cascade');
            $table->string('status');
            $table->string('type')->nullable();
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
            $table->uuid('createdBy')->index()->nullable();
            $table->foreign('createdBy')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('updatedBy')->index()->nullable();
            $table->foreign('updatedBy')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leader_ministries');
    }
};
