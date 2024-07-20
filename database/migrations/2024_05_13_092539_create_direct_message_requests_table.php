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
        Schema::create('direct_message_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('senderId')->nullable();
            $table->string('senderType')->nullable();
            $table->uuid('receiverId')->nullable();
            $table->string('receiverType')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_message_requests');
    }
};
