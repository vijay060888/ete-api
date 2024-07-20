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
        Schema::create('broadcast_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('broadcastId')->index();
            $table->foreign('broadcastId')->references('id')->on('broadcasts')->onDelete('cascade');
            $table->uuid('stateId')->index();
            $table->foreign('stateId')->references('id')->on('states')->onDelete('cascade');
            $table->string('constituency')->nullable();
            $table->string('constituencyType')->nullable();
            $table->string('gender')->nullable();
            $table->integer('minAge')->nullable();
            $table->integer('maxAge')->nullable();
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_targets');
    }
};
