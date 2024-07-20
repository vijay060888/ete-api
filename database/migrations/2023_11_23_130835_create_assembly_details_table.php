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
        Schema::create('assembly_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assemblyId');
            $table->foreign('assemblyId')->references('id')->on('assembly_consituencies')->onDelete('cascade');
            $table->enum('gender', ['male', 'female', 'transgender']);
            $table->json('ageRange');
            $table->integer('user_count')->default(0);
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assembly_details');
    }
};
