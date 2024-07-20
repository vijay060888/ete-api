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
        Schema::create('leaders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leadersId')->index();
            $table->foreign('leadersId')->references('id')->on('users')->onDelete('cascade');
            $table->string('officialPage'); 
            $table->longText('descriptionShort')->nullable();
            $table->longText('descriptionBrief')->nullable();
            $table->longText('leaderBiography')->nullable();
            $table->longText('leaderMission')->nullable();
            $table->longText('leaderVision')->nullable();
            $table->longText('officeAddress')->nullable();
            $table->text('backgroundImage')->nullable();
            $table->string('file')->nullable();
            $table->string('timelineYear')->nullable();
            $table->string('timelineHeading')->nullable();
            $table->string('timelineDescriptions')->nullable();
            $table->string('about')->nullable();
            $table->string('timeLine')->nullable();
            $table->string('phoneNumber2')->nullable();
            $table->integer('followercount')->default(0);
            $table->integer('voluntercount')->default(0);
            $table->json('social')->nullable();
            $table->string('contactPersonName')->nullable();
            $table->string('leaderPartyRole')->nullable();
            $table->string('leaderPartyRoleLevel')->nullable();
            $table->string('leaderMinistry')->nullable();
            $table->uuid('leaderConsituencyId')->nullable();
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
        Schema::dropIfExists('leaders');
    }
};
