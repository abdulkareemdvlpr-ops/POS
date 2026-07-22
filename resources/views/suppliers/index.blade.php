@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Suppliers</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Suppliers</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
        <i class="bi bi-truck me-2"></i>Add Supplier
    </a>
</div>

<div class="card">
    <div class="card-header py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-truck text-muted"></i>
                <span class="fw-semibold me-3">Suppliers Register</span>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary active" id="btn-filter-all" onclick="filterSupplierType('all')">All</button>
                    <button type="button" class="btn btn-outline-primary" id="btn-filter-distributor" onclick="filterSupplierType('distributor')">Local Distributions (Vendor Khata)</button>
                    <button type="button" class="btn btn-outline-primary" id="btn-filter-company" onclick="filterSupplierType('company')">Direct Companies (Procurement)</button>
                </div>
            </div>
            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search suppliers..." style="width:220px;">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="suppliersTable">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:50px;">#</th>
                        <th>Company / Name</th>
                        <th>Type</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>City</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr data-type="{{ $supplier->supplier_type ?? 'distributor' }}">
                        <td class="ps-3 text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:36px;height:36px;background:#dcfce7;color:#166534;font-weight:700;font-size:0.85rem;">
                                    {{ strtoupper(substr($supplier->company_name ?? $supplier->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $supplier->company_name ?? $supplier->name }}</div>
                                    @if($supplier->company_name)
                                        <div class="text-muted" style="font-size:0.75rem;">{{ $supplier->name }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if(($supplier->supplier_type ?? 'distributor') === 'company')
                                <span class="badge rounded-pill bg-primary" style="font-size: 0.75rem;">Direct Company</span>
                            @else
                                <span class="badge rounded-pill bg-info text-dark" style="font-size: 0.75rem;">Local Distribution</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $supplier->contact_person ?? '—' }}</td>
                        <td>{{ $supplier->phone ?? '—' }}</td>
                        <td class="text-muted">{{ $supplier->email ?? '—' }}</td>
                        <td class="text-muted">{{ $supplier->city ?? '—' }}</td>
                        <td>
                            @if($supplier->status ?? true)
                                <span class="badge rounded-pill badge-active">Active</span>
                            @else
                                <span class="badge rounded-pill badge-inactive">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('suppliers.ledger', $supplier) }}" class="btn btn-sm btn-outline-secondary me-1">Ledger</a>
                            <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-info me-1">View</a>
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-warning me-1">Edit</a>
                            <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this supplier?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="bi bi-truck fs-1 text-muted d-block mb-2"></i>
                            <p class="text-muted mb-0">No suppliers found.</p>
                            <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm mt-2">Add First Supplier</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($suppliers->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing {{ $suppliers->firstItem() }} to {{ $suppliers->lastItem() }} of {{ $suppliers->total() }}</small>
        {{ $suppliers->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
let currentFilterType = 'all';

function filterSupplierType(type) {
    currentFilterType = type;
    
    // Update active button styling
    document.getElementById('btn-filter-all').classList.toggle('active', type === 'all');
    document.getElementById('btn-filter-distributor').classList.toggle('active', type === 'distributor');
    document.getElementById('btn-filter-company').classList.toggle('active', type === 'company');
    
    // Filter table rows
    const searchVal = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#suppliersTable tbody tr').forEach(row => {
        const rowType = row.getAttribute('data-type');
        if (!rowType) return; // Skip empty row
        
        const matchesType = (type === 'all' || rowType === type);
        const matchesSearch = row.textContent.toLowerCase().includes(searchVal);
        
        if (matchesType && matchesSearch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

document.getElementById('searchInput').addEventListener('keyup', function() {
    filterSupplierType(currentFilterType);
});
</script>
@endpush
