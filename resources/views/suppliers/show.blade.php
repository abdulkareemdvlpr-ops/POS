@extends('layouts.app')

@section('title', 'Supplier Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Supplier Details</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                <li class="breadcrumb-item active">{{ $supplier->company_name ?? $supplier->name }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('suppliers.ledger', $supplier) }}" class="btn btn-primary">
            <i class="bi bi-journal-text me-2"></i>View Ledger
        </a>
        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-warning">
            <i class="bi bi-pencil me-2"></i>Edit
        </a>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                    style="width:80px;height:80px;background:#dcfce7;color:#166534;font-weight:700;font-size:2rem;">
                    {{ strtoupper(substr($supplier->company_name ?? $supplier->name, 0, 1)) }}
                </div>
                <h5 class="fw-bold mb-1">{{ $supplier->company_name ?? $supplier->name }}</h5>
                @if($supplier->company_name)
                    <p class="text-muted small mb-1">{{ $supplier->name }}</p>
                @endif
                <p class="text-muted small mb-3">{{ $supplier->email ?? 'No email' }}</p>
                <p class="text-muted small mb-1">
                    @if(($supplier->supplier_type ?? 'distributor') === 'company')
                        <span class="badge rounded-pill bg-primary">Direct Company (Direct Procurement)</span>
                    @else
                        <span class="badge rounded-pill bg-info text-dark">Local Distribution (Vendor Khata)</span>
                    @endif
                </p>
                @if($supplier->status ?? true)
                    <span class="badge rounded-pill badge-active">Active</span>
                @else
                    <span class="badge rounded-pill badge-inactive">Inactive</span>
                @endif
            </div>
            <div class="card-footer">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted">Phone</td>
                        <td class="fw-semibold">{{ $supplier->phone ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">NTN</td>
                        <td class="fw-semibold">{{ $supplier->ntn ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">City</td>
                        <td class="fw-semibold">{{ $supplier->city ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Address</td>
                        <td class="small">{{ $supplier->address ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Balance</td>
                        <td class="fw-semibold text-primary">Rs. {{ number_format($supplier->opening_balance ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Since</td>
                        <td class="small">{{ $supplier->created_at->format('d M Y') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-3"><i class="bi bi-box-seam me-2"></i>Products Supplied</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Product</th>
                                <th>Category</th>
                                <th>Buy Price</th>
                                <th>Sale Price</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supplier->products ?? [] as $product)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $product->name }}</td>
                                <td class="text-muted small">{{ $product->category->name ?? '—' }}</td>
                                <td>Rs. {{ number_format($product->buy_price ?? 0, 2) }}</td>
                                <td>Rs. {{ number_format($product->price, 2) }}</td>
                                <td><span class="badge {{ $product->stock < 10 ? 'bg-danger' : 'bg-success' }} rounded-pill">{{ $product->stock }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No products linked to this supplier</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @if($supplier->notes)
        <div class="card mt-4">
            <div class="card-header py-3"><i class="bi bi-sticky me-2"></i>Notes</div>
            <div class="card-body text-muted">{{ $supplier->notes }}</div>
        </div>
        @endif
    </div>
</div>
@endsection
