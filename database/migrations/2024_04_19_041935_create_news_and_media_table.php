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
        Schema::create('news_and_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('newsttitle')->nullable();
            $table->text('newsdescription')->nullable();
            $table->integer('costincured')->nullable();
            $table->string('hashtags')->nullable();
            $table->string('mediaupload')->nullable();
            $table->uuid('leader_id')->nullable();
            $table->uuid('party_id')->nullable();
            $table->string('status')->nullable();
            $table->string('url')->nullable();
            $table->uuid('archieveby')->nullable();
            $table->string('archieveType')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->nullable();
            $table->foreign('leader_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('party_id')->references('id')->on('parties')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_and_media');
    }
};
