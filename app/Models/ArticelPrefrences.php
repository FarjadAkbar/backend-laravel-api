<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticelPrefrences extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'categories', 'sources', 'authors'];
}
