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
        Schema::create('states', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->string('map');
            $table->string('officialPage');
            $table->longText('descriptionShort')->nullable();
            $table->longText('descriptionBrief')->nullable();
            $table->string('population');
            $table->string('populationMale');
            $table->string('populationFemale');
            $table->string('populationElectors');
            $table->string('populationElectorsMale');
            $table->string('populationElectorsFemale');
            $table->string('gdp')->nullable();
            $table->string('languages');
            $table->string('hashTags')->nullable();
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
        Schema::dropIfExists('states');
    }
};
