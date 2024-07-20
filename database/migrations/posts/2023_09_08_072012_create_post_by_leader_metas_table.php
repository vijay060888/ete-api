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
        Schema::create('post_by_leader_metas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('postByLeaderId')->index()->nullable();
            $table->foreign('postByLeaderId')->references('id')->on('post_by_leaders')->onDelete('cascade');
            $table->string('postDescriptions')->nullable();
            $table->longText('imageUrl1')->nullable();
            $table->longText('imageUrl2')->nullable();
            $table->longText('imageUrl3')->nullable();
            $table->longText('imageUrl4')->nullable();
            $table->string('ideaDepartment')->nullable();
            $table->date('PollendDate');
            $table->time('pollendTime');
            $table->string('complaintLocation')->nullable();
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
        Schema::dropIfExists('post_by_leader_metas');
    }
};
