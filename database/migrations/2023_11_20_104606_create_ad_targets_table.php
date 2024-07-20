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
        Schema::create('ad_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('adId')->index();
            $table->foreign('adId')->references('id')->on('ads')->onDelete('cascade');
            $table->uuid('stateId')->index();
            $table->foreign('stateId')->references('id')->on('states')->onDelete('cascade');
            $table->string('constituency')->nullable();
            $table->string('constituencyType')->nullable();
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_targets');
    }
};
