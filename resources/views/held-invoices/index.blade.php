@extends('layouts.app')

@section('title', 'Held Bills')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Held Bills</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Held Bills</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>New Bill
    </a>
</div>

<div class="card">
    <div class="card-header py-3"><i class="bi bi-pause-circle me-2 text-warning"></i>Bills on Hold</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Label</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Held At</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($heldInvoices as $held)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $held->label }}</td>
                        <td>{{ $held->customer->name ?? 'Walk-in' }}</td>
                        <td>{{ count($held->data['items'] ?? []) }} item(s)</td>
                        <td>{{ $held->created_at->format('d M Y H:i') }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('invoices.create') }}?resume={{ $held->id }}" class="btn btn-sm btn-success me-1">
                                Resume
                            </a>
                            <form action="{{ route('held-invoices.destroy', $held) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this held bill?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            No bills on hold
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
