<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->latest()->paginate(1000);
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:255|unique:categories,name',
            'description'         => 'nullable|string|max:500',
            'status'              => 'nullable|boolean',
            'product_type'        => 'required|string|in:medicine,liquid,general',
            'default_almari'      => 'nullable|string|max:100',
            'default_khana'       => 'nullable|string|max:100',
            'default_row'         => 'nullable|string|max:100',
            'default_mfg_date'    => 'nullable|date',
            'default_expiry_date' => 'nullable|date',
        ]);

        Category::create([
            'name'                => $request->name,
            'description'         => $request->description,
            'status'              => $request->status ?? 1,
            'product_type'        => $request->product_type,
            'default_almari'      => $request->default_almari,
            'default_khana'       => $request->default_khana,
            'default_row'         => $request->default_row,
            'default_mfg_date'    => $request->default_mfg_date,
            'default_expiry_date' => $request->default_expiry_date,
        ]);

        return redirect()->route('categories.index')->with('success', 'Category added successfully!');
    }

    public function show(Category $category)
    {
        $category->load('products');
        return view('categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name'                => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description'         => 'nullable|string|max:500',
            'status'              => 'nullable|boolean',
            'product_type'        => 'required|string|in:medicine,liquid,general',
            'default_almari'      => 'nullable|string|max:100',
            'default_khana'       => 'nullable|string|max:100',
            'default_row'         => 'nullable|string|max:100',
            'default_mfg_date'    => 'nullable|date',
            'default_expiry_date' => 'nullable|date',
        ]);

        $category->update([
            'name'                => $request->name,
            'description'         => $request->description,
            'status'              => $request->status ?? 1,
            'product_type'        => $request->product_type,
            'default_almari'      => $request->default_almari,
            'default_khana'       => $request->default_khana,
            'default_row'         => $request->default_row,
            'default_mfg_date'    => $request->default_mfg_date,
            'default_expiry_date' => $request->default_expiry_date,
        ]);

        // Bulk update dates & locations for all existing products in this category
        $updateData = [];
        if ($request->filled('default_almari'))      $updateData['almari']      = $request->default_almari;
        if ($request->filled('default_khana'))       $updateData['khana']       = $request->default_khana;
        if ($request->filled('default_row'))         $updateData['row']         = $request->default_row;
        if ($request->filled('default_mfg_date'))    $updateData['mfg_date']    = $request->default_mfg_date;
        if ($request->filled('default_expiry_date')) $updateData['expiry_date'] = $request->default_expiry_date;

        if (count($updateData) > 0) {
            $category->products()->update($updateData);
        }

        return redirect()->route('categories.index')->with('success', 'Category and its products updated successfully!');
    }

    public function destroy(Category $category)
    {
        try {
            // Delete all products inside this category first
            $products = $category->products;
            foreach ($products as $product) {
                if ($product->image) \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
                $product->delete();
            }
            $category->delete();
            return redirect()->route('categories.index')->with('success', 'Category and all its medicines deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('categories.index')->with('error', 'Cannot delete this category because some of its medicines are referenced in past bills/invoices. Consider keeping it active or reassigning those medicines.');
        }
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:categories,id',
        ]);

        $deletedCount = 0;
        $failedCount = 0;

        foreach ($request->ids as $id) {
            $category = Category::find($id);
            if ($category) {
                try {
                    $products = $category->products;
                    foreach ($products as $product) {
                        if ($product->image) \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
                        $product->delete();
                    }
                    $category->delete();
                    $deletedCount++;
                } catch (\Illuminate\Database\QueryException $e) {
                    $failedCount++;
                }
            }
        }

        if ($failedCount > 0) {
            return redirect()->route('categories.index')->with('warning', "Deleted {$deletedCount} categories and their medicines. {$failedCount} categories could not be deleted because they (or their medicines) are referenced in past bills/invoices.");
        }

        return redirect()->route('categories.index')->with('success', "Successfully deleted {$deletedCount} categories and their medicines.");
    }
}
