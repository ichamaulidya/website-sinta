<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('iprs', function (Blueprint $table) {
            $table->string('nama_prodi')->nullable()->after('sinta_id');
        });
    }
};
