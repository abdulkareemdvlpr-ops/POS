<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashToBankLog extends Model
{
    use HasFactory;
    protected $fillable = ['amount','bank_name','slip_number','deposit_date','notes','created_by'];
    protected $casts = ['deposit_date'=>'date','amount'=>'decimal:2'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
