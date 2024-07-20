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
        Schema::create('consituency_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('consituencyType');
            $table->uuid('consituencyId');
            $table->string('developmentIndex')->nullable(); 
            $table->string('area')->nullable(); 
            $table->string('safetyIndex')->nullable();  
            $table->string('backgrouundImage')->nullable();  
            $table->string('literacyIndex')->nullable();  
            $table->string('corruptionIndex')->nullable();  
            $table->text('history')->nullable();  
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
        Schema::dropIfExists('consituency_details');
    }
};
