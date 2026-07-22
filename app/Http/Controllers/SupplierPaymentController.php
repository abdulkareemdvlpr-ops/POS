<?php
namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierPayment::with('supplier', 'purchase')->latest();
        if ($request->filled('supplier_id')) $query->where('supplier_id', $request->supplier_id);
        $payments = $query->paginate(20);
        $suppliers = Supplier::where('status', 1)->orderBy('name')->get();
        return view('supplier-payments.index', compact('payments', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'slip_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $supplier = Supplier::findOrFail($data['supplier_id']);
        if (!auth()->user()->isAdmin() && ($supplier->supplier_type ?? 'distributor') !== 'distributor') {
            abort(403);
        }

        if (!empty($data['purchase_id'])) {
            $purchase = Purchase::findOrFail($data['purchase_id']);
            abort_if((int) $purchase->supplier_id !== (int) $supplier->id, 422, 'Selected purchase does not belong to this supplier.');
        }

        DB::transaction(function () use ($data) {
            $data['created_by'] = auth()->id();

            if (!empty($data['purchase_id'])) {
                SupplierPayment::create($data);
                $purchase = Purchase::whereKey($data['purchase_id'])->lockForUpdate()->first();
                $this->applyAmountToPurchase($purchase, (float) $data['amount']);
                return;
            }

            $remaining = (float) $data['amount'];
            $purchases = Purchase::where('supplier_id', $data['supplier_id'])
                ->where('due_amount', '>', 0)
                ->orderBy('purchase_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($purchases as $purchase) {
                if ($remaining <= 0) {
                    break;
                }

                $amountForPurchase = min($remaining, (float) $purchase->due_amount);
                if ($amountForPurchase <= 0) {
                    continue;
                }

                SupplierPayment::create(array_merge($data, [
                    'purchase_id' => $purchase->id,
                    'amount' => round($amountForPurchase, 2),
                ]));

                $this->applyAmountToPurchase($purchase, $amountForPurchase);
                $remaining = round($remaining - $amountForPurchase, 2);
            }

            if ($remaining > 0) {
                SupplierPayment::create(array_merge($data, [
                    'amount' => round($remaining, 2),
                ]));
            }
        });

        return back()->with('success', 'Supplier payment recorded. Ledger updated.');
    }

    public function destroy(SupplierPayment $supplierPayment)
    {
        DB::transaction(function () use ($supplierPayment) {
            // Revert the purchase paid amount if there was a linked purchase
            if ($supplierPayment->purchase_id) {
                $purchase = Purchase::find($supplierPayment->purchase_id);
                if ($purchase) {
                    $purchase->paid_amount -= $supplierPayment->amount;
                    if ($purchase->paid_amount < 0) $purchase->paid_amount = 0;
                    $purchase->due_amount = max(0, $purchase->total_amount - $purchase->paid_amount);
                    $purchase->payment_status = $purchase->due_amount <= 0 ? 'paid' : ($purchase->paid_amount > 0 ? 'partial' : 'unpaid');
                    $purchase->save();
                }
            }
            $supplierPayment->delete();
        });

        return back()->with('success', 'Supplier payment deleted. Ledger reverted.');
    }

    private function applyAmountToPurchase(Purchase $purchase, float $amount): void
    {
        $purchase->paid_amount = round((float) $purchase->paid_amount + $amount, 2);
        $purchase->due_amount = max(0, round((float) $purchase->total_amount - (float) $purchase->paid_amount, 2));
        $purchase->payment_status = $purchase->due_amount <= 0
            ? 'paid'
            : ($purchase->paid_amount > 0 ? 'partial' : 'unpaid');
        $purchase->save();
    }
}
