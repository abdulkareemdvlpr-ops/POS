@extends('layouts.app')

@section('title', 'Category Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Category Details</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
                <li class="breadcrumb-item active">{{ $category->name }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('categories.edit', $category) }}" class="btn btn-warning">
            <i class="bi bi-pencil me-2"></i>Edit
        </a>
        <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header py-3"><i class="bi bi-info-circle me-2"></i>Category Info</div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted fw-semibold" style="width:40%">Name</td>
                        <td class="fw-bold">{{ $category->name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Status</td>
                        <td>
                            @if($category->status ?? true)
                                <span class="badge rounded-pill badge-active">Active</span>
                            @else
                                <span class="badge rounded-pill badge-inactive">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Products</td>
                        <td><span class="badge bg-primary rounded-pill">{{ $category->products->count() }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Created</td>
                        <td class="small">{{ $category->created_at->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Description</td>
                        <td class="small text-muted">{{ $category->description ?? '—' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-3"><i class="bi bi-box-seam me-2"></i>Products in this Category</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Product</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($category->products as $product)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $product->name }}</td>
                                <td class="text-muted small">{{ $product->sku ?? '—' }}</td>
                                <td>Rs. {{ number_format($product->price, 2) }}</td>
                                <td><span class="badge {{ $product->stock < 10 ? 'bg-danger' : 'bg-success' }} rounded-pill">{{ $product->stock }}</span></td>
                                <td>
                                    <span class="badge rounded-pill {{ $product->status ? 'badge-active' : 'badge-inactive' }}">
                                        {{ $product->status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No products in this category</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
