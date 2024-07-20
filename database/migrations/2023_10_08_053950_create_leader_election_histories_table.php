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
        Schema::create('leader_election_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leaderId')->index()->nullable();
            $table->foreign('leaderId')->references('id')->on('users')->onDelete('cascade');

            $table->uuid('partyId')->index()->nullable();
            $table->foreign('partyId')->references('id')->on('parties')->onDelete('cascade');

            $table->uuid('electionHistoryId')->index()->nullable();
            $table->foreign('electionHistoryId')->references('id')->on('election_histories')->onDelete('cascade');

            $table->uuid('assemblyId')->index()->nullable();
            $table->foreign('assemblyId')->references('id')->on('assembly_consituencies')->onDelete('cascade');

            $table->uuid('loksabhaId')->index()->nullable();
            $table->foreign('loksabhaId')->references('id')->on('lok_sabha_consituencies')->onDelete('cascade');

            $table->string('electionHistoryLeaderResult')->nullable();

            $table->boolean('isIndependent')->nullable();

            $table->string('leadInVoting')->nullable();
            
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
        Schema::dropIfExists('leader_election_histories');
    }
};
