<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ipr extends Model
{
    // Tambahkan ini di sini
    protected $fillable = ['sinta_id', 'title', 'category', 'year', 'link'];
}