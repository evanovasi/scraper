<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scraping extends Model
{
    use HasFactory;
    protected $hidden = ['type', 'created_at', 'updated_at'];

    public function scopeFilter($query,  $request)
    {
        // search
        $query->when($request->query('search') ?? false, function ($query, $searchKey) {
            return $query->where('date', 'LIKE', "%{$searchKey}%")
                ->orWhere('title', 'LIKE', "%{$searchKey}%")
                ->orWhere('content', 'LIKE', "%{$searchKey}%")
                ->orWhere('hashtags', 'LIKE', "%{$searchKey}%")
                ->orWhere('url', 'LIKE', "%{$searchKey}%");
        });

        // hastags
        $query->when($request->query('tags') ?? false, function ($query, $tagsKey) {
            return $query->where('hashtags', 'LIKE', "%{$tagsKey}%");
        });
    }

    public function analyses()
    {
        return $this->hasMany(Analysis::class);
    }
}
