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
        Schema::create('campaign_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('broadcastId')->index();
            $table->foreign('broadcastId')->references('id')->on('broadcasts')->onDelete('cascade');
            $table->string('broadcastType');
            $table->date('startDate');
            $table->time('startTime');
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_settings');
    }
};
