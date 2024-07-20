<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('permalink');
            $table->string('pageType')->nullable();
            
            $table->uuid('parentContituencyPageId')->index()->nullable();

            $table->uuid('parentStatePageId')->index()->nullable();

            $table->uuid('parentPartyPageId')->index()->nullable();

            $table->uuid('parentLoksabhaConsituencyPageId')->index()->nullable();

            $table->uuid('parentAssemblyConsituencyPageId')->index()->nullable();

            $table->uuid('boothId')->index()->nullable();

            $table->uuid('assemblyConsituencyId')->index()->nullable();
            $table->foreign('assemblyConsituencyId')->references('id')->on('assembly_consituencies')->onDelete('cascade');

            $table->uuid('loksabhaConsituencyId')->index()->nullable();
            $table->foreign('loksabhaConsituencyId')->references('id')->on('lok_sabha_consituencies')->onDelete('cascade');

            $table->uuid('stateId')->index()->nullable();
            $table->foreign('stateId')->references('id')->on('states')->onDelete('cascade');

            $table->string('officialPage')->nullable();
            $table->longText('descriptionShort')->nullable();
            $table->longText('descriptionBrief')->nullable();
            $table->string('type')->nullable();
            $table->string('hashTags')->nullable();
            $table->string('about')->nullable();
            $table->string('timeLine')->nullable();
            $table->string('contact')->nullable();
            $table->string('social')->nullable();
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
            $table->uuid('createdBy')->index()->nullable();
            $table->foreign('createdBy')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('updatedBy')->index()->nullable();
            $table->foreign('updatedBy')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};