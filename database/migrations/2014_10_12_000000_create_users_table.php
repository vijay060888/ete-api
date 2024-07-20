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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('userName');
            $table->string('firstName');
            $table->string('lastName');
            $table->string('aadharNumber');
            $table->string('voterId')->nullable();
            $table->string('gender');
            $table->string('DOB')->nullable();
            $table->string('phoneNumber')->nullable();
            $table->boolean('tokenVerify')->default(0);
            $table->string('email')->unique();
            $table->string('password');
            $table->string('address')->nullable();
            $table->string('state')->nullable();
            $table->string('district')->nullable();
            $table->string('cityTown')->nullable();;
            $table->string('pinCode')->nullable();
            $table->string('educationPG')->nullable();
            $table->string('educationUG')->nullable();
            $table->string('profesionalExperience')->nullable();
            $table->string('profesionalDepartment')->nullable();
            $table->string('salary')->nullable();
            $table->string('status')->nullable();
            $table->string('forgotPassword')->nullable();
            $table->integer('loginCount')->nullable();
            $table->timestamp('lastLogin')->nullable();
            $table->string('privacy')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('createdAt', 0)->nullable();
            $table->timestamp('updatedAt', 0)->nullable();
            $table->rememberToken();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
