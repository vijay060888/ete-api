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
        Schema::create('assign_party_to_leaders', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('leaderId')->index();
            $table->foreign('leaderId')->references('id')->on('users')->onDelete('cascade');

            $table->uuid('partyId')->index();
            $table->foreign('partyId')->references('id')->on('parties')->onDelete('cascade');
            $table->string('status')->default("Pending");
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
        Schema::dropIfExists('assign_party_to_leaders');
    }
};
