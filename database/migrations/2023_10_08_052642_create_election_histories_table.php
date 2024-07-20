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
        Schema::create('election_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('electionTypeId');
            $table->foreign('electionTypeId')->references('id')->on('election_types')->onDelete('cascade');
            $table->string('electionHistoryYear');
            $table->string('electionHistoryMonth');
            $table->string('rulingParty');
            $table->string('oppositionParty');
            $table->string('isAssociation');
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
        Schema::dropIfExists('election_histories');
    }
    
};
