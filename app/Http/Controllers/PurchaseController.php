<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with('supplier', 'creator')
            ->whereHas('supplier', fn($q) => $q->where('supplier_type', 'distributor'));

        if ($request->filled('supplier_id')) $query->where('supplier_id', $request->supplier_id);
        if ($request->filled('status')) $query->where('payment_status', $request->status);
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('purchase_date', [$request->from_date, $request->to_date]);
        }
        $totalPurchases = (clone $query)->sum('total_amount');
        $totalDue = (clone $query)->where('payment_status', '!=', 'paid')->sum('due_amount');
        $purchases = $query->latest()->paginate(20);
        $suppliers = Supplier::where('status', 1)->where('supplier_type', 'distributor')->orderBy('name')->get();
        return view('purchases.index', compact('purchases', 'suppliers', 'totalPurchases', 'totalDue'));
    }

    public function create()
    {
        // Direct Procurement now only covers local distributor purchases on credit (Udhar Khata).
        // Direct company bills are recorded under Expenses instead.
        $suppliers = Supplier::where('status', 1)->where('supplier_type', 'distributor')->orderBy('name')->get();
        $products = Product::where('status', 1)->orderBy('name')->get();
        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('supplier_type', 'distributor')],
            'purchase_date' => 'required|date',
            'payment_method' => 'required|string',
            'paid_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.boxes' => 'required|integer|min:1',
            'items.*.strips_per_box' => 'required|integer|min:1',
            'items.*.tablets_per_strip' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.retail_price' => 'nullable|numeric|min:0',
            'items.*.batch_number' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($request) {
            $totalAmount = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $boxes = max(1, (int) $item['boxes']);
                
                if ($product->product_type === 'liquid') {
                    $stripsPerBox = max(1, (int) $item['strips_per_box']); // serves as units_per_box
                    $tabletsPerStrip = 1;
                } else {
                    $stripsPerBox = $product->isGeneral() ? 1 : max(1, (int) $item['strips_per_box']);
                    $tabletsPerStrip = $product->isGeneral() ? 1 : max(1, (int) $item['tablets_per_strip']);
                }

                $unitsPerBox = $stripsPerBox * $tabletsPerStrip;
                $baseQty = (int) round($boxes * $unitsPerBox);
                $boxCost = (float) $item['cost_price'];
                $boxRetail = (float) ($item['retail_price'] ?? 0);
                $unitCost = $unitsPerBox > 0 ? round($boxCost / $unitsPerBox, 2) : $boxCost;
                $unitRetail = $boxRetail > 0 && $unitsPerBox > 0 ? round($boxRetail / $unitsPerBox, 2) : 0;

                $total = $boxes * $boxCost;
                $totalAmount += $total;
                $itemsData[] = array_merge($item, [
                    'qty' => $baseQty,
                    'boxes' => $boxes,
                    'strips_per_box' => $stripsPerBox,
                    'tablets_per_strip' => $tabletsPerStrip,
                    'cost_price' => $unitCost,
                    'retail_price' => $unitRetail,
                    'total' => $total,
                ]);
            }

            $paidAmount = (float)($request->paid_amount ?? 0);
            $dueAmount = max(0, $totalAmount - $paidAmount);
            $status = $paidAmount >= $totalAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'total_amount' => round($totalAmount, 2),
                'paid_amount' => round($paidAmount, 2),
                'due_amount' => round($dueAmount, 2),
                'payment_status' => $status,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($itemsData as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'cost_price' => $item['cost_price'],
                    'retail_price' => $item['retail_price'] ?? 0,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'total' => $item['total'],
                ]);

                // Increase product stock in internal base units.
                // Do NOT update default catalog prices to keep previous rates intact.
                $product = Product::find($item['product_id']);
                $product->increment('stock', $item['qty']);
                
                $updates = [];
                if (empty($product->batch_number) && !empty($item['batch_number'])) {
                    $updates['batch_number'] = $item['batch_number'];
                }
                if (empty($product->expiry_date) && !empty($item['expiry_date'])) {
                    $updates['expiry_date'] = $item['expiry_date'];
                }
                if (!empty($updates)) {
                    $product->update($updates);
                }
            }
        });

        return redirect()->route('purchases.index')->with('success', 'Purchase recorded successfully! Stock updated.');
    }

    public function show(Purchase $purchase)
    {
        abort_if(($purchase->supplier->supplier_type ?? 'distributor') !== 'distributor' && !auth()->user()->isAdmin(), 403);

        $purchase->load('supplier', 'items.product', 'payments', 'creator');
        return view('purchases.show', compact('purchase'));
    }

    public function destroy(Purchase $purchase)
    {
        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                Product::where('id', $item->product_id)->decrement('stock', $item->qty);
            }
            $purchase->items()->delete();
            $purchase->delete();
        });
        return redirect()->route('purchases.index')->with('success', 'Purchase deleted and stock reversed.');
    }
}
