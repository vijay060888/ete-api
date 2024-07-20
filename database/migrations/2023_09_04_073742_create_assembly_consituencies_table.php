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
        Schema::create('assembly_consituencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('map');
            $table->string('logo');
            $table->string('officialPage'); 
            $table->longText('descriptionShort');
            $table->longText('descriptionBrief');
            $table->string('population');
            $table->string('populationMale');
            $table->string('populationFemale');
            $table->integer('followercount')->default(0);
            $table->integer('volunteerscount')->default(0);
            $table->string('populationElectors');
            $table->string('populationElectorsMale');
            $table->string('populationElectorsFemale');
            $table->string('languages');
            $table->string('hashTags');
            $table->uuid('districId')->index();
            $table->foreign('districId')->references('id')->on('districts')->onDelete('cascade');
            $table->timestamp('createdAt', 0);
            $table->timestamp('updatedAt', 0);
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
        Schema::dropIfExists('assembly_consituencies');
    }
};
