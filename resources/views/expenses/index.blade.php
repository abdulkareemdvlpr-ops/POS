@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted mb-0">Total Filtered: <strong class="text-danger">Rs. {{ number_format($totalAmount, 2) }}</strong></h6>
    </div>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Add Expense
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-primary me-2">Filter</button>
                <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                        <th>Title</th>
                        <th>Category</th>
                        <th>Company / Bill #</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Added By</th>
                        <th>Notes</th>
                        @if(auth()->user()->isAdmin())
                        <th class="text-end pe-3">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        <td class="ps-3 text-muted small">{{ $expense->id }}</td>
                        <td class="fw-semibold">{{ $expense->title }}</td>
                        <td><span class="badge bg-secondary rounded-pill">{{ $expense->category }}</span></td>
                        <td class="text-muted small">
                            @if($expense->supplier)
                                {{ $expense->supplier->name }}@if($expense->bill_number) &middot; {{ $expense->bill_number }} @endif
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td>{{ $expense->expense_date->format('d M Y') }}</td>
                        <td class="text-danger fw-semibold">Rs. {{ number_format($expense->amount, 2) }}</td>
                        <td class="text-muted small">{{ $expense->user->name ?? '—' }}</td>
                        <td class="text-muted small">{{ Str::limit($expense->notes, 40) }}</td>
                        @if(auth()->user()->isAdmin())
                        <td class="text-end pe-3">
                            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="d-inline"
                                  onsubmit="return confirm('Delete this expense?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="{{ auth()->user()->isAdmin() ? 9 : 8 }}" class="text-center text-muted py-4">No expenses recorded yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($expenses->hasPages())
    <div class="card-footer">{{ $expenses->links() }}</div>
    @endif
</div>
@endsection
