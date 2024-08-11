<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scraping extends Model
{
    use HasFactory;
    protected $hidden = ['type', 'created_at', 'updated_at'];

    // Scope untuk pencarian
    public function scopeSearch($query, $searchKey)
    {
        return $query->where('date', 'LIKE', "%{$searchKey}%")
            ->orWhere('title', 'LIKE', "%{$searchKey}%")
            ->orWhere('content', 'LIKE', "%{$searchKey}%")
            ->orWhere('hashtags', 'LIKE', "%{$searchKey}%")
            ->orWhere('url', 'LIKE', "%{$searchKey}%");
    }
}
