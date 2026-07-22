@extends('layouts.app')

@section('title', 'Direct Procurement (Purchases)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Direct Procurement & Purchases (Maal Khareedna)</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Direct Procurement</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('purchases.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Record Purchase (Procurement)
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Total Purchased</h6>
                <h3 class="mb-0 fw-bold">Rs. {{ number_format($totalPurchases ?? 0, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-danger text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Outstanding Dues</h6>
                <h3 class="mb-0 fw-bold">Rs. {{ number_format($totalDue ?? 0, 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-light border w-100">Clear</a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Supplier</th>
                        <th>Purchase Date</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                    <tr>
                        <td class="ps-3">{{ $loop->iteration }}</td>
                        <td>
                            <div class="fw-semibold">{{ $purchase->supplier->name ?? '—' }}</div>
                            @if(isset($purchase->supplier))
                                @if(($purchase->supplier->supplier_type ?? 'distributor') === 'company')
                                    <span class="badge bg-primary text-white rounded-pill" style="font-size: 0.65rem;">Direct Company</span>
                                @else
                                    <span class="badge bg-info text-dark rounded-pill" style="font-size: 0.65rem;">Local Distribution</span>
                                @endif
                            @endif
                        </td>
                        <td>{{ $purchase->purchase_date->format('d M Y') }}</td>
                        <td class="fw-semibold">Rs. {{ number_format($purchase->total_amount, 2) }}</td>
                        <td class="text-success">Rs. {{ number_format($purchase->paid_amount, 2) }}</td>
                        <td class="text-danger">Rs. {{ number_format($purchase->due_amount, 2) }}</td>
                        <td>
                            @if($purchase->payment_status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif($purchase->payment_status === 'partial')
                                <span class="badge bg-warning text-dark">Partial</span>
                            @else
                                <span class="badge bg-danger">Unpaid</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-outline-info me-1">View</a>
                            @if($purchase->supplier)
                            <a href="{{ route('suppliers.ledger', $purchase->supplier) }}" class="btn btn-sm btn-outline-secondary me-1">Ledger</a>
                            @endif
                            @if(auth()->user()->isAdmin())
                            <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this purchase? Item quantities will be decremented from stock.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No purchases logged.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($purchases->hasPages())
    <div class="card-footer bg-white">
        {{ $purchases->links() }}
    </div>
    @endif
</div>
@endsection
