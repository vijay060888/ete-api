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
        Schema::create('about_parties', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('partyId')->index();
            $table->foreign('partyId')->references('id')->on('parties')->onDelete('cascade');

            $table->text('about')->nullable();
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();


            $table->uuid('createdBy')->index();
            $table->foreign('createdBy')->references('id')->on('parties')->onDelete('cascade');
            $table->uuid('updatedBy')->index();
            $table->foreign('updatedBy')->references('id')->on('parties')->onDelete('cascade');

            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_parties');
    }
};
