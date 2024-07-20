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
        Schema::create('page_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('pageType')->nullable();
            $table->uuid('stateId')->index()->nullable();
            $table->foreign('stateId')->references('id')->on('states')->onDelete('cascade');

            $table->uuid('assemblyId')->index()->nullable();
            $table->foreign('assemblyId')->references('id')->on('assembly_consituencies')->onDelete('cascade');

            $table->uuid('partyId')->index()->nullable();
            $table->foreign('partyId')->references('id')->on('parties')->onDelete('cascade');

            $table->uuid('loksabhaId')->index()->nullable();
            $table->foreign('loksabhaId')->references('id')->on('lok_sabha_consituencies')->onDelete('cascade');

            $table->uuid('requestedBy')->index();
            $table->foreign('requestedBy')->references('id')->on('users')->onDelete('cascade');

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
        Schema::dropIfExists('page_requests');
    }
};
