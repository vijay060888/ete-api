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
        Schema::create('leader_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leadersId')->index();
            $table->foreign('leadersId')->references('id')->on('users')->onDelete('cascade');
            $table->string('acchievements')->nullable();
            $table->string('descriptions')->nullable();
            $table->string('durations')->nullable();
            $table->string('expenses')->nullable();
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
        Schema::dropIfExists('leader_achievements');
    }
};
