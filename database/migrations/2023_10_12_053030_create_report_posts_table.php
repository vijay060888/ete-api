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
        Schema::create('report_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('postId')->index()->nullable();
            $table->string('postByType')->index()->nullable();
            $table->text('reportText')->index()->nullable();

            $table->uuid('reportedBy')->index()->nullable();
            $table->foreign('reportedBy')->references('id')->on('users')->onDelete('cascade');

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
        Schema::dropIfExists('report_posts');
    }
};
