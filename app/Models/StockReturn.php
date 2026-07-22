<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReturn extends Model
{
    protected $fillable = [
        'product_id', 'supplier_id', 'user_id',
        'qty', 'reason', 'notes', 'return_date', 'status',
    ];

    protected $casts = ['return_date' => 'date'];

    public function product()  { return $this->belongsTo(Product::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function user()     { return $this->belongsTo(User::class); }
}
