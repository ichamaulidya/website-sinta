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
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('sinta_id'); // Foreign key ke dosen (logic)
            $table->string('source');   // scopus, scholar, garuda, wos
            $table->text('title');
            $table->string('journal')->nullable();
            $table->string('year')->nullable();
            $table->integer('cited')->default(0);
            $table->string('type')->nullable(); // quartile atau badge
            $table->timestamps(); // Digunakan untuk filter "Bulan" pengumpulan data
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
