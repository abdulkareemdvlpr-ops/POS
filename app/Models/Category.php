<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'status', 'product_type', 'default_almari', 'default_khana', 'default_row', 'default_mfg_date', 'default_expiry_date'];

    protected $casts = ['status' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
