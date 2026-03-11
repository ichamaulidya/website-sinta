<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iprs', function (Blueprint $blade) {
            $blade->id();
            $blade->string('sinta_id'); // ID Dosen
            $blade->text('title');      // Judul HKI
            $blade->string('category');   // Jenis: Hak Cipta, Paten, dll
            $blade->string('year', 4)->nullable();
            $blade->string('link')->nullable();
            $blade->timestamps();
            
            // Opsional: Hubungkan dengan tabel dosen jika ada
            // $blade->foreign('sinta_id')->references('sinta_id')->on('lecturers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iprs');
    }
};