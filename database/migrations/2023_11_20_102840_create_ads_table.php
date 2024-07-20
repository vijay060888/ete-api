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
        Schema::create('ads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('campaignName');
            $table->string('adTitle');
            $table->text('adMessage');
            $table->string('url');
            $table->string('hashtags');
            $table->string('image')->nullable();
            $table->string('createdBy');
            $table->string('startDate');
            $table->string('endDate');
            $table->string('createdByType');
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
