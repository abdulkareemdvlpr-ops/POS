<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;
    protected $fillable = ['supplier_id','purchase_date','total_amount','paid_amount','due_amount','payment_status','payment_method','notes','created_by'];
    protected $casts = ['purchase_date'=>'date','total_amount'=>'decimal:2','paid_amount'=>'decimal:2','due_amount'=>'decimal:2'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function items() { return $this->hasMany(PurchaseItem::class); }
    public function payments() { return $this->hasMany(SupplierPayment::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
