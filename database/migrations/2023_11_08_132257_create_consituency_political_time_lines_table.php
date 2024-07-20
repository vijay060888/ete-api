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
        Schema::create('consituency_political_time_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('consituencyId')->index();
            $table->string('consituencyType')->index();
            $table->uuid('leaderId')->index();
            $table->foreign('leaderId')->references('id')->on('users')->onDelete('cascade');
            $table->date('inPowerDate')->nullable();
            $table->uuid('rullingPartyId')->index();
            $table->foreign('rullingPartyId')->references('id')->on('parties')->onDelete('cascade');
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
        Schema::dropIfExists('consituency_political_time_lines');
    }
};
