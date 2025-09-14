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
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('document_type');       // indigency, clearance, etc.
            $table->string('full_name');           // applicant’s name
            $table->string('address');             // complete address
            $table->string('contact_number');      // phone number
            $table->string('email');               // applicant email
            $table->text('purpose');               // purpose of request
            $table->string('reference_number')->unique()->nullable(); // DOC-XXXX-XXX
            $table->enum('status', ['pending', 'processing', 'ready', 'claimed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
