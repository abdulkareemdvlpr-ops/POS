@extends('layouts.app')

@section('title', 'Returns & Damaged Stock')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Returns & Damaged Stock</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Returns</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReturnModal">
        <i class="bi bi-plus-circle me-2"></i>Log Return
    </button>
</div>

{{-- Filter --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending"  {{ request('status')==='pending'  ? 'selected':'' }}>Pending</option>
                    <option value="approved" {{ request('status')==='approved' ? 'selected':'' }}>Approved</option>
                    <option value="rejected" {{ request('status')==='rejected' ? 'selected':'' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">From</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">To</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Medicine</th>
                        <th>Qty</th>
                        <th>Reason</th>
                        <th>Supplier</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                    <tr>
                        <td class="ps-3">{{ $ret->id }}</td>
                        <td class="fw-semibold">{{ $ret->product->name ?? '—' }}</td>
                        <td>{{ $ret->qty }}</td>
                        <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_',' ',$ret->reason)) }}</span></td>
                        <td>{{ $ret->supplier->company_name ?? $ret->supplier->name ?? '—' }}</td>
                        <td>{{ $ret->return_date->format('d M Y') }}</td>
                        <td>
                            <span class="badge {{ $ret->status==='approved'?'bg-success':($ret->status==='rejected'?'bg-danger':'bg-warning text-dark') }}">
                                {{ ucfirst($ret->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            @if($ret->status === 'pending')
                            <form action="{{ route('stock-returns.update', $ret) }}" method="POST" class="d-inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="status" value="approved">
                                <button class="btn btn-sm btn-success me-1">✓ Approve</button>
                            </form>
                            <form action="{{ route('stock-returns.update', $ret) }}" method="POST" class="d-inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="status" value="rejected">
                                <button class="btn btn-sm btn-outline-danger">✗ Reject</button>
                            </form>
                            @endif
                            <form action="{{ route('stock-returns.destroy', $ret) }}" method="POST" class="d-inline ms-1"
                                  onsubmit="return confirm('Delete this return?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-secondary">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">No returns logged yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($returns->hasPages())
    <div class="card-footer">{{ $returns->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Add Return Modal --}}
<div class="modal fade" id="addReturnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-return-left me-2"></i>Log Stock Return</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('stock-returns.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Medicine <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-select" required>
                                <option value="">Select medicine</option>
                                @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (Stock: {{ $p->stock }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">No supplier</option>
                                @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->company_name ?? $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="qty" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                            <select name="reason" class="form-select" required>
                                <option value="damaged">Damaged</option>
                                <option value="expired">Expired</option>
                                <option value="wrong_item">Wrong Item</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Return Date <span class="text-danger">*</span></label>
                            <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending">Pending Approval</option>
                                <option value="approved">Approved (deduct stock now)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Log Return</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
