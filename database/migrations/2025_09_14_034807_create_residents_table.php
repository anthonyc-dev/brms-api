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
        Schema::create('residents', function (Blueprint $table) {
             $table->id();     
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); 
            // Personal Information
            $table->string('first_name');
            $table->string('middle_name');
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->date('birth_date');
            $table->enum('gender', ['Male', 'Female']);
            $table->string('place_of_birth');
            $table->enum('civil_status', ['Single', 'Married', 'Widowed', 'Divorced', 'Separated']);
            $table->string('nationality');
            $table->string('religion');
            $table->string('occupation');

            
            // Address Information
            $table->string('house_number');
            $table->string('street');
            $table->string('zone');
            $table->string('city');
            $table->string('province');
            
            // Contact Information
            $table->string('contact_number');
            $table->string('email')->unique();
        
            
            // Parents Information
            $table->string('father_first_name');
            $table->string('father_middle_name')->nullable();
            $table->string('father_last_name');
            $table->string('mother_first_name');
            $table->string('mother_middle_name')->nullable();
            $table->string('mother_maiden_name');
            
            // Valid ID Upload Information
            $table->string('valid_id_path')->nullable();
            $table->string('upload_id')->nullable();
            $table->timestamp('upload_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};
