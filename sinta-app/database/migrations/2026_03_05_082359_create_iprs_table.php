<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan variabel di sini adalah $table
        Schema::create('iprs', function (Blueprint $table) {
            $table->id();
            $table->string('sinta_id'); 
            $table->text('title');      
            $table->text('inventor')->nullable(); // Kolom inventor yang tadi kita bahas
            $table->string('category');   
            $table->string('year', 4)->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iprs');
    }
};