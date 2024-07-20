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
        Schema::create('user_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('userId')->index();
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            $table->string('voterImage')->nullable();
            $table->string('profileImage')->nullable();
            $table->uuid('loksabhaId')->nullable()->index();
            $table->foreign('loksabhaId')->references('id')->on('lok_sabha_consituencies')->onDelete('cascade');
            $table->uuid('assemblyId')->nullable()->index();
            $table->foreign('assemblyId')->references('id')->on('assembly_consituencies')->onDelete('cascade');
            $table->uuid('boothId')->nullable()->index();
            $table->foreign('boothId')->references('id')->on('booths')->onDelete('cascade');
            $table->boolean('isLeader')->nullable()->default(0);
            $table->longText('pageAccess')->nullable();
            $table->uuid('createdBy')->index();
            $table->foreign('createdBy')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('updatedBy')->index();
            $table->foreign('updatedBy')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
