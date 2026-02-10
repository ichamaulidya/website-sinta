<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    // Beritahu Laravel kalau nama tabelnya 'dosen', bukan 'dosens'
    protected $table = 'dosen';

    // Jika tabel kamu tidak punya kolom created_at dan updated_at, tambahkan ini:
    public $timestamps = false;
    
    // Daftarkan kolom yang boleh diisi (sesuai gambar phpMyAdmin kamu)
    protected $fillable = [
        'Nama', 
        'Bagian', 
        'NPI', 
        'NIDN', 
        'NUPTK', 
        'Sinta_ID'
    ];
}