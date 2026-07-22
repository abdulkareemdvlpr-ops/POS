@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Customers</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Customers</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('customers.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-2"></i>Add Customer
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <span><i class="bi bi-people me-2"></i>All Customers ({{ $customers->total() }})</span>
        <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search customers..." style="width:220px;">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="customersTable">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:50px;">#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Total Orders</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td class="ps-3 text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:36px;height:36px;background:#dbeafe;color:#1d4ed8;font-weight:700;font-size:0.85rem;">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </div>
                                <span class="fw-semibold">{{ $customer->name }}</span>
                            </div>
                        </td>
                        <td class="text-muted">{{ $customer->email ?? '—' }}</td>
                        <td>{{ $customer->phone ?? '—' }}</td>
                        <td class="text-muted">{{ $customer->city ?? '—' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $customer->invoices_count ?? 0 }}</span></td>
                        <td>
                            @if($customer->status ?? true)
                                <span class="badge rounded-pill badge-active">Active</span>
                            @else
                                <span class="badge rounded-pill badge-inactive">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('customers.ledger', $customer) }}" class="btn btn-sm btn-outline-secondary me-1">Ledger</a>
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-info me-1">View</a>
                            @if(auth()->user()->isAdmin())
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-warning me-1">Edit</a>
                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this customer?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="bi bi-people fs-1 text-muted d-block mb-2"></i>
                            <p class="text-muted mb-0">No customers found.</p>
                            <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm mt-2">Add First Customer</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($customers->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ $customers->total() }}</small>
        {{ $customers->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#customersTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
});
</script>
@endpush
