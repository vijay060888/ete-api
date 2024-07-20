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
        Schema::table('user_follower_tags', function (Blueprint $table) {
            $table->string('userType')->nullable();
            $table->uuid('assembly_id')->nullable();
            $table->uuid('loksabha_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_follower_tags', function (Blueprint $table) {
            $table->dropColumn('userType');
            $table->dropColumn('assembly_id');
            $table->dropColumn('loksabha_id');
        });
    }
};
