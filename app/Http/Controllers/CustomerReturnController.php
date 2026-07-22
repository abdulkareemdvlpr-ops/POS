<?php

namespace App\Http\Controllers;

use App\Models\CustomerReturn;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerReturn::with('cashier', 'customer', 'invoice')->latest();

        // Cashier sees only their own returns
        if (!auth()->user()->isAdmin()) {
            $query->where('cashier_id', auth()->id());
        }

        $returns = $query->paginate(20);
        $invoices = Invoice::with('customer')
            ->where('status', 'paid')
            ->latest()
            ->limit(50)
            ->get();

        return view('sales-returns.index', compact('returns', 'invoices'));
    }

    public function loadInvoice(Request $request)
    {
        $invoice = Invoice::with(['items.product', 'customer'])
            ->findOrFail($request->invoice_id);

        return response()->json([
            'invoice'  => [
                'id'       => $invoice->id,
                'customer' => $invoice->customer?->name ?? 'Walk-in',
                'date'     => $invoice->created_at->format('d M Y'),
                'total'    => $invoice->total,
            ],
            'items' => $invoice->items->map(fn($item) => [
                'product_id'   => $item->product_id,
                'product_name' => $item->product->name ?? 'Unknown',
                'qty_sold'     => $item->qty,
                'price'        => $item->price,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id'  => 'nullable|exists:invoices,id',
            'customer_id' => 'nullable|exists:customers,id',
            'reason'      => 'nullable|string|max:500',
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.price'      => 'required|numeric|min:0',
        ]);

        $items       = [];
        $totalRefund = 0;

        DB::beginTransaction();
        try {
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty     = (int) $item['qty'];
                $price   = (float) $item['price'];
                $total   = round($qty * $price, 2);

                // Restore stock
                $product->increment('stock', $qty);

                $items[] = [
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'qty'        => $qty,
                    'price'      => $price,
                    'total'      => $total,
                ];
                $totalRefund += $total;
            }

            CustomerReturn::create([
                'invoice_id'   => $data['invoice_id'] ?? null,
                'cashier_id'   => auth()->id(),
                'customer_id'  => $data['customer_id'] ?? null,
                'items'        => $items,
                'total_refund' => round($totalRefund, 2),
                'reason'       => $data['reason'] ?? null,
                'status'       => 'approved',
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Return failed: ' . $e->getMessage());
        }

        return back()->with('success', "Return processed. Refund amount: Rs. " . number_format($totalRefund, 2));
    }

    public function destroy(CustomerReturn $customerReturn)
    {
        // Admin only: undo a return (deduct stock back)
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            foreach ($customerReturn->items as $item) {
                Product::where('id', $item['product_id'])->decrement('stock', $item['qty']);
            }
            $customerReturn->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Could not undo return: ' . $e->getMessage());
        }

        return back()->with('success', 'Return undone and stock restored.');
    }
}
