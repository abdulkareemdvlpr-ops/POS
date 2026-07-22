<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\BusinessSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('customer', 'cashier', 'items.product');
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('invoice_date', [$request->from_date, $request->to_date]);
        }

        // Clone query to calculate totals before pagination
        $allInvoices = (clone $query)->get();
        $totalSales = $allInvoices->sum('total');
        $totalProfit = $allInvoices->sum(fn($inv) => $inv->calculateProfit());

        $invoices = $query->latest()->paginate(20);
        return view('invoices.index', compact('invoices', 'totalSales', 'totalProfit'));
    }

    public function create()
    {
        $customers = Customer::where('status', 1)->orderBy('name')->get();
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $products = Product::where('status', 1)
            ->where('stock', '>', 0)
            ->notExpired()
            ->with('category')
            ->orderBy('name')
            ->limit(30)
            ->get();
        $heldInvoices = \App\Models\HeldInvoice::where('cashier_id', auth()->id())->latest()->get();

        return view('invoices.create', compact('customers', 'categories', 'products', 'heldInvoices'));
    }

    public function searchProducts(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'q'           => 'nullable|string|max:255',
        ]);

        $term = $request->filled('q') ? '%' . $request->q . '%' : null;

        $products = Product::where('status', 1)
            ->where('stock', '>', 0)
            ->notExpired()
            ->with('category:id,name')
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            ->when($term, fn($q) => $q->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('sku', 'like', $term)
                  ->orWhere('barcode', 'like', $term)
                  ->orWhere('generic_name', 'like', $term);
            }))
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn($p) => $this->productPayload($p));

        // If searching by name/generic and nothing found, suggest substitutes
        $substitutes = [];
        if ($products->isEmpty() && $request->filled('q')) {
            $q = $request->q;
            $substitutes = Product::where('status', 1)
                ->where('stock', '>', 0)
                ->notExpired()
                ->whereNotNull('generic_name')
                ->where('generic_name', 'like', '%' . $q . '%')
                ->with('category:id,name')
                ->limit(10)
                ->get()
                ->map(fn($p) => $this->productPayload($p));
        }

        return response()->json([
            'products'    => $products,
            'substitutes' => $substitutes,
        ]);
    }

    private function productPayload(Product $p): array
    {
        return [
            'id'              => $p->id,
            'name'            => $p->name,
            'generic_name'    => $p->generic_name,
            'sku'             => $p->sku,
            'barcode'         => $p->barcode,
            'batch_number'    => $p->batch_number,
            'expiry_date'     => $p->expiry_date?->format('d M Y'),
            'expiry_status'   => $p->expiryStatus(),
            'price'           => $p->price,
            'stock'           => $p->stock,
            'stock_boxes'     => $p->stockBoxes(),
            'stock_label'     => $p->formattedStock(),
            'low_stock_threshold' => (int) ($p->low_stock_threshold ?? 0),
            'category_id'     => $p->category_id,
            'category_name'   => $p->category->name ?? 'Uncategorized',
            'almari'          => $p->almari,
            'khana'           => $p->khana,
            'row'             => $p->row,
            'product_type'      => $p->product_type,
            'tablets_per_strip' => (int) $p->tablets_per_strip,
            'strips_per_box'    => (int) $p->strips_per_box,
            'units_per_box'     => (int) $p->units_per_box,
            'volume'            => $p->volume,
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'          => 'required|array|min:1',
            'customer_id'    => 'nullable|exists:customers,id',
            'subtotal'       => 'required|numeric',
            'total'          => 'required|numeric',
            'service_fee'    => 'nullable|numeric|min:0',
            'payment_method' => 'required',
            'status'         => 'required',
        ]);

        // Hard-lock: block expired products
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->isExpired()) {
                return back()->withErrors(['items' => 'Cannot bill expired product: ' . $product->name])->withInput();
            }
        }

        $invoice = DB::transaction(function () use ($request) {
            $businessSetting = BusinessSetting::current();
            $subtotal        = collect($request->items)->sum(fn($i) => ((float) $i['qty']) * ((float) $i['price']));
            $discount        = (float) ($request->discount ?? 0);
            $discountType    = $request->discount_type ?? 'flat';
            $discountAmount  = $discountType === 'percent' ? ($subtotal * $discount / 100) : $discount;
            $afterDiscount   = max(0, $subtotal - $discountAmount);
            $taxRate         = (float) ($request->tax ?? $businessSetting->sales_tax_rate ?? 0);
            $taxAmount       = $afterDiscount * $taxRate / 100;
            $serviceFee      = (float) ($request->service_fee ?? $businessSetting->service_fee ?? 0);

            $status = $request->status;
            $total = round($afterDiscount + $taxAmount + $serviceFee, 2);
            $paidAmount = 0;
            $dueAmount = 0;

            if ($status === 'paid') {
                $paidAmount = $total;
                $dueAmount = 0;
            } elseif ($status === 'unpaid') {
                $paidAmount = 0;
                $dueAmount = $total;
            } elseif ($status === 'partial') {
                $paidAmount = (float)($request->amount_received ?? 0);
                $dueAmount = max(0, $total - $paidAmount);
            }

            $invoice = Invoice::create(array_merge($request->except('items', 'amount_received'), [
                'cashier_id'    => auth()->id(),
                'subtotal'      => round($subtotal, 2),
                'discount'      => round($discount, 2),
                'discount_type' => $discountType,
                'tax'           => round($taxRate, 2),
                'service_fee'   => round($serviceFee, 2),
                'total'         => $total,
                'paid_amount'   => $paidAmount,
                'due_amount'    => $dueAmount,
                'invoice_date'  => $request->invoice_date ?? now(),
            ]));

            foreach ($request->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'qty'        => $item['qty'],
                    'price'      => $item['price'],
                    'total'      => $item['qty'] * $item['price'],
                    'unit_type'  => $item['unit_type'] ?? 'tablet',
                    'unit_qty'   => $item['unit_qty'] ?? $item['qty'],
                ]);
                Product::where('id', $item['product_id'])->decrement('stock', $item['qty']);
            }

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created successfully!');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('customer', 'cashier', 'items.product');
        return view('invoices.show', compact('invoice'));
    }

    public function destroy(Invoice $invoice)
    {
        DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {
                Product::where('id', $item->product_id)->increment('stock', $item->qty);
            }
            $invoice->items()->delete();
            $invoice->delete();
        });
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function export(Request $request)
    {
        $query = Invoice::with('customer');
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('invoice_date', [$request->from_date, $request->to_date]);
        }
        $invoices = $query->get();

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Invoice #', 'Customer', 'Date', 'Total', 'Status']);
            foreach ($invoices as $row) {
                fputcsv($file, [
                    '#' . str_pad($row->id, 4, '0', STR_PAD_LEFT),
                    $row->customer->name ?? 'Walk-in',
                    $row->invoice_date,
                    $row->total,
                    $row->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=invoices_export_' . date('Y-m-d') . '.csv',
        ]);
    }
}
