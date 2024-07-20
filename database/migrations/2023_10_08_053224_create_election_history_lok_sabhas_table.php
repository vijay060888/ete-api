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
        Schema::create('election_history_lok_sabhas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('electionTypeId');
            $table->foreign('electionTypeId')->references('id')->on('election_types')->onDelete('cascade');
            $table->string('rulingParty')->nullable();
            $table->string('oppositionParty')->nullable();
            $table->string('isAssociation')->nullable();
            $table->uuid('loksabhaId')->index();
            $table->foreign('loksabhaId')->references('id')->on('lok_sabha_consituencies')->onDelete('cascade');
            $table->string('electionDate')->nullable();
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
        Schema::dropIfExists('election_history_lok_sabhas');
    }
};
