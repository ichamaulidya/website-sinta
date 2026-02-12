<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    use HasFactory;

    // Tambahkan baris ini untuk mengizinkan kolom diisi secara otomatis
    protected $fillable = [
        'sinta_id',
        'source',
        'title',
        'journal',
        'year',
        'cited',
        'type'
    ];
}