<?php

namespace App\Models\Campaigns;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $table = 'campaign_categories';
    protected $fillable = [
        'category_id',
        'name',
        'slug',
    ];
}
