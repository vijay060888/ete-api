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
        Schema::create('manifesto_promises', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('manifestoId')->index();
            $table->foreign('manifestoId')->references('id')->on('manifestos')->onDelete('cascade');
            $table->string('manifestoPromisesDepartment')->nullable();
            $table->string('manifestoPromisesPromise')->nullable();
            $table->string('manifestoCountPositive')->nullable();
            $table->string('manifestoCountNegative')->nullable();
            $table->string('manifestoCountShare')->nullable();
            $table->integer('likesCount')->default(0);
            $table->integer('commentsCount')->default(0);
            $table->string('manifestoPromisesDescriptions')->nullable();
            $table->text('manifestoShortDescriptions')->nullable();
            $table->string('manifestoPromisesIdStatus')->nullable();
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
        Schema::dropIfExists('manifesto_promises');
    }
};
