<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory;
    protected $fillable = ['supplier_id','purchase_id','amount','payment_method','payment_date','slip_number','notes','created_by'];
    protected $casts = ['payment_date'=>'date','amount'=>'decimal:2'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
