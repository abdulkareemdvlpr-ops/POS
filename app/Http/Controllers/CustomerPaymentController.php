<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerPayment::with('customer')->latest();
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        $payments = $query->paginate(20);
        $customers = Customer::where('status', 1)->orderBy('name')->get();
        return view('customer-payments.index', compact('payments', 'customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'slip_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($data) {
            $data['created_by'] = auth()->id();
            CustomerPayment::create($data);
            // Auto-settle oldest unpaid invoices
            $remaining = $data['amount'];
            $invoices = Invoice::where('customer_id', $data['customer_id'])->where('due_amount', '>', 0)->orderBy('id')->get();
            foreach ($invoices as $inv) {
                if ($remaining <= 0) break;
                $pay = min($remaining, $inv->due_amount);
                $inv->paid_amount += $pay;
                $inv->due_amount -= $pay;
                if ($inv->due_amount <= 0) $inv->status = 'paid';
                $inv->save();
                $remaining -= $pay;
            }
        });

        return back()->with('success', 'Customer payment recorded. Credit balance updated.');
    }

    public function destroy(CustomerPayment $customerPayment)
    {
        DB::transaction(function () use ($customerPayment) {
            // Revert auto-settlement (LIFO)
            $remaining = $customerPayment->amount;
            $invoices = Invoice::where('customer_id', $customerPayment->customer_id)
                ->where('paid_amount', '>', 0)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($invoices as $inv) {
                if ($remaining <= 0) break;
                $revert = min($remaining, $inv->paid_amount);
                $inv->paid_amount -= $revert;
                $inv->due_amount += $revert;
                if ($inv->due_amount > 0) {
                    $inv->status = ($inv->paid_amount > 0) ? 'partial' : 'pending';
                }
                $inv->save();
                $remaining -= $revert;
            }
            $customerPayment->delete();
        });

        return back()->with('success', 'Customer payment deleted. Invoices reverted.');
    }
}
