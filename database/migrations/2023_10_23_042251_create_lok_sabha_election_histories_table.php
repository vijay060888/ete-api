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
        Schema::create('lok_sabha_election_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('electionHistoryId')->index();
            $table->foreign('electionHistoryId')->references('id')->on('election_histories')->onDelete('cascade');

            $table->uuid('electionTypeId')->index();
            $table->foreign('electionTypeId')->references('id')->on('election_types')->onDelete('cascade');

            $table->string('percentageinParliament')->nullable();
            $table->string('majority')->nullable();
            $table->string('turnout')->nullable();

            $table->uuid('singleLargestPartyId')->index();
            $table->foreign('singleLargestPartyId')->references('id')->on('parties')->onDelete('cascade');

            $table->string('government')->index();
            $table->string('votesPercentage')->index();
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
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
        Schema::dropIfExists('lok_sabha_election_histories');
    }
};
