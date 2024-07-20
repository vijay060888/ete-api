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
        Schema::create('assembly_follower_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assemblyId');
            $table->foreign('assemblyId')->references('id')->on('assembly_consituencies')->onDelete('cascade');
            $table->enum('gender', ['MALE', 'FEMALE', 'TRANSGENDER']);
            $table->integer('follower_count')->default(0);
            $table->json('ageRange');
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assembly_follower_details');
    }
};
