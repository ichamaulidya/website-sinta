<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    protected $table = 'dosen'; // ⬅️ WAJIB (karena default Laravel = dosens)

    public $timestamps = false;

    protected $fillable = [
        'Nama',
        'Bagian',
        'NPI',
        'NIDN',
        'NUPTK',
        'Sinta_ID'
    ];
}
