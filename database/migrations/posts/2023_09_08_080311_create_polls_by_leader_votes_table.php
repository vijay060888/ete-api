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
        Schema::create('polls_by_leader_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('postByLeaderId')->index();
            $table->foreign('postByLeaderId')->references('id')->on('post_by_leaders')->onDelete('cascade');
            $table->uuid('pollsByLeaderDetailsId')->index();
            $table->foreign('pollsByLeaderDetailsId')->references('id')->on('polls_by_leader_details')->onDelete('cascade');
            $table->string('userId')->nullable();
            $table->string('selectedOption')->nullable();
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
        Schema::dropIfExists('polls_by_leader_votes');
    }
};
