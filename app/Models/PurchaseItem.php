<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;
    protected $fillable = ['purchase_id','product_id','qty','cost_price','retail_price','batch_number','expiry_date','total'];
    protected $casts = ['expiry_date'=>'date','cost_price'=>'decimal:2','retail_price'=>'decimal:2','total'=>'decimal:2'];

    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
