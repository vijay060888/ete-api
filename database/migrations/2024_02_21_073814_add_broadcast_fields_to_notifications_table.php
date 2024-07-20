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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('broadcast_title')->nullable();
            $table->text('broadcast_message')->nullable();
            $table->string('broadcast_image')->nullable();
            $table->string('broadcast_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('broadcast_title');
            $table->dropColumn('broadcast_message');
            $table->dropColumn('broadcast_image');
            $table->dropColumn('broadcast_url');
        });
    }
};
