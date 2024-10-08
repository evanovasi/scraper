<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    use HasFactory;
    protected $fillable = ['scraping_id', 'sentiment', 'solution', 'lang'];
    protected $hidden = ['created_at', 'updated_at'];

    public function scraping()
    {
        return $this->belongsTo(Scraping::class);
    }
}
