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
        Schema::create('story_by_citizen_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('storyByCitizenId')->index();
            $table->foreign('storyByCitizenId')->references('id')->on('story_by_citizens')->onDelete('cascade');
            $table->uuid('viewedBy')->index();
            $table->foreign('viewedBy')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
            $table->uuid('createdBy')->index();
            $table->foreign('createdBy')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('updatedBy')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_by_citizen_views');
    }
};
