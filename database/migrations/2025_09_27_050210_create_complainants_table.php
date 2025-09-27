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
        Schema::create('complainant_report', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('report_type'); // e.g. Noise Complaint, Theft, etc.
            $table->string('title');
            $table->text('description');
            $table->string('location');
            $table->dateTime('date_time');
            
            // complainant info
            $table->string('complainant_name')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_anonymous')->default(false);

            // report metadata
            $table->enum('urgency_level', ['low', 'medium', 'high', 'emergency']);
            $table->text('witnesses')->nullable();
            $table->text('additional_info')->nullable();

            // tracking
            $table->enum('status', ['pending', 'under_investigation', 'resolved', 'rejected'])->default('pending');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complainant_report');
    }
};
 


