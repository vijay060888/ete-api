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
        Schema::create('post_by_citizens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('authorType');
            $table->uuid('citizenId')->index()->nullable();
            $table->foreign('citizenId')->references('id')->on('users')->onDelete('cascade');
            $table->longText('postType')->nullable();
            $table->longText('postTitle')->nullable();
            $table->integer('likesCount')->nullable();
            $table->integer('commentsCount')->nullable();
            $table->integer('shareCount')->nullable();
            $table->boolean('anonymous');
            $table->boolean('isPublished')->default(true);
            $table->longText('hashTags')->nullable();
            $table->longText('mention')->nullable();
            $table->boolean('isAds')->default(0);
            $table->timestamp('createdAt', 0);
            $table->timestamp('updatedAt', 0);
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
        Schema::dropIfExists('post_by_citizens');
    }
};
