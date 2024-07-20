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
        Schema::create('leader_core_parties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leaderId')->index();
            $table->foreign('leaderId')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('corePartyId')->index();
            $table->foreign('corePartyId')->references('id')->on('parties')->onDelete('cascade');
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
            $table->uuid('createdBy')->index()->nullable();
            $table->foreign('createdBy')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('updatedBy')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leader_core_parties');
    }
};
