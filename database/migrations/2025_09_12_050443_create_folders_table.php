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
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->string('folder_name'); // user-provided folder name
            $table->string('zip_name'); // stored zip file name
            $table->json('original_files'); // original uploaded file names
            $table->text('description')->nullable(); // optional description
            $table->date('date_created')->nullable(); // date created by user
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
