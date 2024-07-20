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
        Schema::create('leader_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leadersId')->index();
            $table->foreign('leadersId')->references('id')->on('users')->onDelete('cascade');
            $table->integer('followersCount')->default(0);
            $table->integer('youngUsers')->default(0);
            $table->integer('middledAgeUsers')->default(0);
            $table->integer('maleFollowers')->default(0);
            $table->integer('femaleFollowers')->default(0);
            $table->integer('transgenderFollowers')->default(0);
            $table->integer('appreciatePostCount')->default(0);
            $table->integer('likePostCount')->default(0);
            $table->integer('carePostCount')->default(0);
            $table->integer('unlikesPostCount')->default(0);
            $table->integer('sadPostCount')->default(0);
            $table->integer('issuedResolvedCount')->default(0);
            $table->string('postFrequency')->nullable();
            $table->string('sentiments')->nullable();
            $table->integer('responseTime')->default(0);
            
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
        Schema::dropIfExists('leader_details');
    }
};
