<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('userName')->unique();
            $table->string('password');
            $table->string('name');
            $table->string('nameAbbrevation');
            $table->string('logo');
            $table->string('officialPage')->nullable();
            $table->string('phoneNumber2')->nullable();
            $table->string('file')->nullable();
            $table->longText('descriptionShort')->nullable();
            $table->longText('descriptionBrief')->nullable();
            $table->string('type');
            $table->string('hashTags')->nullable();
            $table->text('about')->nullable();
            $table->string('timeLine')->nullable();
            $table->string('contact')->nullable();
            $table->string('social')->nullable();
            $table->string('email')->nullable();
            $table->text('vision')->nullable();
            $table->text('mission')->nullable(); 
            $table->text('timelineDescriptions')->nullable(); 
            $table->text('parentPartyId')->nullable(); 
            $table->integer('followerCount');
            $table->integer('VolunterCount');
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
        Schema::dropIfExists('parties');
    }
};