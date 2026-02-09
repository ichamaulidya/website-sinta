<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    use HasFactory;
    protected $table = 'dosen'; // ⬅️ WAJIB (karena default Laravel = dosens)
    protected $primaryKey = 'id';
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
