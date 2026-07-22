<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\StockReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = StockReturn::with('product', 'supplier', 'user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('return_date', [$request->from_date, $request->to_date]);
        }

        $returns   = $query->latest()->paginate(20);
        $products  = Product::where('status', 1)->orderBy('name')->get(['id','name','stock']);
        $suppliers = Supplier::where('status', 1)->orderBy('name')->get(['id','name','company_name']);

        return view('stock-returns.index', compact('returns', 'products', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'  => 'required|exists:products,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'qty'         => 'required|integer|min:1',
            'reason'      => 'required|in:damaged,expired,wrong_item,other',
            'notes'       => 'nullable|string|max:1000',
            'return_date' => 'required|date',
            'status'      => 'required|in:pending,approved,rejected',
        ]);

        DB::transaction(function () use ($data) {
            $data['user_id'] = auth()->id();
            $ret = StockReturn::create($data);

            // Deduct stock from product when approved
            if ($data['status'] === 'approved') {
                Product::where('id', $data['product_id'])->decrement('stock', $data['qty']);
            }
        });

        return redirect()->route('stock-returns.index')->with('success', 'Stock return recorded successfully.');
    }

    public function update(Request $request, StockReturn $stockReturn)
    {
        $oldStatus = $stockReturn->status;
        $newStatus = $request->validate(['status' => 'required|in:pending,approved,rejected'])['status'];

        DB::transaction(function () use ($stockReturn, $oldStatus, $newStatus) {
            // If newly approved, deduct stock
            if ($oldStatus !== 'approved' && $newStatus === 'approved') {
                Product::where('id', $stockReturn->product_id)->decrement('stock', $stockReturn->qty);
            }
            // If un-approved (from approved back to something else), restore stock
            if ($oldStatus === 'approved' && $newStatus !== 'approved') {
                Product::where('id', $stockReturn->product_id)->increment('stock', $stockReturn->qty);
            }
            $stockReturn->update(['status' => $newStatus]);
        });

        return redirect()->route('stock-returns.index')->with('success', 'Return status updated.');
    }

    public function destroy(StockReturn $stockReturn)
    {
        if ($stockReturn->status === 'approved') {
            // Restore stock on deletion of approved return
            Product::where('id', $stockReturn->product_id)->increment('stock', $stockReturn->qty);
        }
        $stockReturn->delete();
        return redirect()->route('stock-returns.index')->with('success', 'Return deleted.');
    }
}
