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
        Schema::create('galleries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leader_id')->nullable();
            $table->uuid('party_id')->nullable();
            $table->uuid('party_admin_id')->nullable();
            $table->uuid('archive_by')->nullable();
            $table->string('archive_by_type')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('hashtag')->nullable();
            $table->string('status')->nullable();
            $table->string('media')->nullable();
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
        Schema::dropIfExists('galleries');
    }
};
