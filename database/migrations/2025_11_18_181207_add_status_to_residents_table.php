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
        Schema::table('residents', function (Blueprint $table) {
            $table->enum('status', ['approved', 'pending', 'reject'])
                  ->default('pending')
                  ->after('gender'); // change 'gender' to the column you want to place it after
        });
    }
    
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
