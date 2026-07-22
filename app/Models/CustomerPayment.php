<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    use HasFactory;
    protected $fillable = ['customer_id','amount','payment_method','payment_date','slip_number','notes','created_by'];
    protected $casts = ['payment_date'=>'date','amount'=>'decimal:2'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
