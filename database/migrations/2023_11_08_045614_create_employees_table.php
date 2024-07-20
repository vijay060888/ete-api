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
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('employeeDepartmentId')->index();
            $table->uuid('employeeToId')->index();
            $table->foreign('employeeToId')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('employeeCreatedBy')->index();
            $table->string('employeeCreatedType')->index();
            $table->string('jobRole')->nullable();
            $table->string('annualCTC')->nullable();
            $table->string('workExperince')->nullable();
            $table->date('dateOfJoining');
            $table->bigIncrements('empCode');
            $table->string('referenceName')->nullable();
            $table->string('referencePhone')->nullable();
            $table->string('maxEducation')->nullable();
            $table->text('comments')->nullable();
            $table->string('reportingManager');
            $table->string('status')->default('active');
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
        Schema::dropIfExists('employees');
    }
};
