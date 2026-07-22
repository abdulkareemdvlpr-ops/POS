<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category', 'supplier');

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(fn($q) => $q->where('name','like',$term)
                ->orWhere('generic_name','like',$term)
                ->orWhere('sku','like',$term)
                ->orWhere('barcode','like',$term));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('expiry_filter')) {
            match($request->expiry_filter) {
                'expired'  => $query->whereNotNull('expiry_date')
                                    ->where('expiry_date', '<', today()),
                'critical' => $query->whereNotNull('expiry_date')
                                    ->where('expiry_date', '>=', today())
                                    ->where('expiry_date', '<', today()->addMonth()),
                'warning'  => $query->whereNotNull('expiry_date')
                                    ->where('expiry_date', '>=', today())
                                    ->where('expiry_date', '<', today()->addMonths(3)),
                'ok'       => $query->where(fn($q) => $q->whereNull('expiry_date')
                                    ->orWhere('expiry_date', '>=', today()->addMonths(3))),
                default    => null,
            };
        }

        $products   = $query->orderBy('name')->paginate(20)->withQueryString();
        $categories = Category::where('status', 1)->orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $suppliers  = Supplier::where('status', 1)->orderBy('name')->get();
        return view('products.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'generic_name'       => 'nullable|string|max:255',
            'sku'                => 'nullable|string|max:100',
            'barcode'            => 'nullable|string|max:100',
            'batch_number'       => 'nullable|string|max:100',
            'mfg_date'           => 'nullable|date',
            'expiry_date'        => 'nullable|date',
            'category_id'        => 'required|exists:categories,id',
            'supplier_id'        => 'nullable|exists:suppliers,id',
            'buy_price'          => 'required|numeric|min:0',
            'price'              => 'required|numeric|min:0',
            'stock'              => 'required|numeric|min:0',
            'low_stock_threshold'=> 'nullable|numeric|min:0',
            'unit'               => 'nullable|string|max:20',
            'strips_per_box'     => 'nullable|integer|min:1',
            'tablets_per_strip'  => 'nullable|integer|min:1',
            'units_per_box'      => 'nullable|integer|min:1',
            'volume'             => 'nullable|string|max:100',
            'description'        => 'nullable|string|max:1000',
            'status'             => 'nullable|boolean',
            'image'              => 'nullable|image|max:2048',
            'almari'             => 'nullable|string|max:100',
            'khana'              => 'nullable|string|max:100',
            'row'                => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $category = \App\Models\Category::findOrFail($request->category_id);
        $data['product_type'] = $category->product_type;

        $data['status'] = $request->boolean('status');
        $data = $this->normalizeStockInput($data);
        Product::create($data);

        return redirect()->route('products.index')->with('success', 'Medicine added successfully!');
    }

    public function show(Product $product)
    {
        $product->load('category', 'supplier');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $suppliers  = Supplier::where('status', 1)->orderBy('name')->get();
        return view('products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'generic_name'       => 'nullable|string|max:255',
            'sku'                => 'nullable|string|max:100',
            'barcode'            => 'nullable|string|max:100',
            'batch_number'       => 'nullable|string|max:100',
            'mfg_date'           => 'nullable|date',
            'expiry_date'        => 'nullable|date',
            'category_id'        => 'required|exists:categories,id',
            'supplier_id'        => 'nullable|exists:suppliers,id',
            'buy_price'          => 'required|numeric|min:0',
            'price'              => 'required|numeric|min:0',
            'stock'              => 'required|numeric|min:0',
            'low_stock_threshold'=> 'nullable|numeric|min:0',
            'unit'               => 'nullable|string|max:20',
            'strips_per_box'     => 'nullable|integer|min:1',
            'tablets_per_strip'  => 'nullable|integer|min:1',
            'units_per_box'      => 'nullable|integer|min:1',
            'volume'             => 'nullable|string|max:100',
            'description'        => 'nullable|string|max:1000',
            'status'             => 'nullable|boolean',
            'image'              => 'nullable|image|max:2048',
            'almari'             => 'nullable|string|max:100',
            'khana'              => 'nullable|string|max:100',
            'row'                => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) Storage::disk('public')->delete($product->image);
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $category = \App\Models\Category::findOrFail($request->category_id);
        $data['product_type'] = $category->product_type;

        $data['status'] = $request->boolean('status');
        $data = $this->normalizeStockInput($data);
        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Medicine updated successfully!');
    }

    public function destroy(Product $product)
    {
        try {
            if ($product->image) Storage::disk('public')->delete($product->image);
            $product->delete();
            return redirect()->route('products.index')->with('success', 'Medicine deleted.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Error 1451 means constraint violation
            if ($e->getCode() == "23000") {
                return redirect()->route('products.index')->with('error', 'Cannot delete this medicine because it exists in past invoices/bills. Consider marking it as inactive instead.');
            }
            return redirect()->route('products.index')->with('error', 'An error occurred while deleting the medicine.');
        }
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:products,id',
        ]);

        $deletedCount = 0;
        $failedCount = 0;

        foreach ($request->ids as $id) {
            $product = Product::find($id);
            if ($product) {
                try {
                    if ($product->image) Storage::disk('public')->delete($product->image);
                    $product->delete();
                    $deletedCount++;
                } catch (\Illuminate\Database\QueryException $e) {
                    $failedCount++;
                }
            }
        }

        if ($failedCount > 0) {
            return redirect()->route('products.index')->with('warning', "Deleted {$deletedCount} medicines. {$failedCount} could not be deleted because they are referenced in past bills/invoices.");
        }

        return redirect()->route('products.index')->with('success', "Successfully deleted {$deletedCount} medicines.");
    }

    private function normalizeStockInput(array $data): array
    {
        $data['stock'] = max(0, (float) ($data['stock'] ?? 0));
        $data['low_stock_threshold'] = max(0, (float) ($data['low_stock_threshold'] ?? 0));

        if ($data['product_type'] === Product::TYPE_GENERAL) {
            $data['strips_per_box'] = 1;
            $data['tablets_per_strip'] = 1;
            $data['units_per_box'] = 1;
            $data['volume'] = null;
            $data['stock'] = (int) round($data['stock']);
            $data['low_stock_threshold'] = (int) round($data['low_stock_threshold']);
            return $data;
        }

        if ($data['product_type'] === Product::TYPE_LIQUID) {
            $data['strips_per_box'] = 1;
            $data['tablets_per_strip'] = 1;
            $data['units_per_box'] = max(1, (int) ($data['units_per_box'] ?? 1));
            // $data['volume'] stays as is
            $unitsPerBox = $data['units_per_box'];
            $data['stock'] = (int) round($data['stock'] * $unitsPerBox);
            $data['low_stock_threshold'] = (int) round($data['low_stock_threshold'] * $unitsPerBox);
            return $data;
        }

        $data['strips_per_box'] = max(1, (int) ($data['strips_per_box'] ?? 1));
        $data['tablets_per_strip'] = max(1, (int) ($data['tablets_per_strip'] ?? 1));
        $data['units_per_box'] = 1;
        $data['volume'] = null;

        $unitsPerBox = $data['strips_per_box'] * $data['tablets_per_strip'];
        $data['stock'] = (int) round($data['stock'] * $unitsPerBox);
        $data['low_stock_threshold'] = (int) round($data['low_stock_threshold'] * $unitsPerBox);

        return $data;
    }
}
