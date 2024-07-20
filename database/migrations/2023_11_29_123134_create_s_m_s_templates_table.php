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
        Schema::create('s_m_s_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('SMSType');
            $table->string('parameter');
            $table->string('SMSTitle');
            $table->text('template');
            $table->string('status')->default(1);
            $table->string('tempid');
            $table->string('entityid');
            $table->string('source');
            $table->boolean('isbroadcastingtemplate')->default(false);
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
        Schema::dropIfExists('s_m_s_templates');
    }
};
