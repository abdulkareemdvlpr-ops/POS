@extends('layouts.app')
@section('title', 'Pharmacy Bills')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Pharmacy Bills</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Bills</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">+ New Bill</a>
</div>

@if(auth()->user()->isAdmin())
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Total Sales Revenue</h6>
                <h3 class="mb-0 fw-bold">Rs. {{ number_format($totalSales ?? 0, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Total Net Profit</h6>
                <h3 class="mb-0 fw-bold">Rs. {{ number_format($totalProfit ?? 0, 2) }}</h3>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('invoices.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-6 d-flex">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="{{ route('invoices.export', ['from_date' => request('from_date'), 'to_date' => request('to_date')]) }}" class="btn btn-success">Download CSV</a>
                <a href="{{ route('invoices.index') }}" class="btn btn-secondary ms-2">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover" id="invoicesTable">
            <thead>
                <tr>
                    <th>Bill #</th><th>Customer</th><th>Date</th><th>Total</th>@if(auth()->user()->isAdmin())<th>Net Profit</th>@endif<th>Status</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                <tr>
                    <td>#{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $invoice->customer->name ?? 'Walk-in' }}</td>
                    <td>{{ $invoice->invoice_date ?? $invoice->created_at->format('Y-m-d') }}</td>
                    <td>Rs. {{ number_format($invoice->total, 2) }}</td>
                    @if(auth()->user()->isAdmin())
                    <td class="text-success fw-semibold">Rs. {{ number_format($invoice->calculateProfit(), 2) }}</td>
                    @endif
                    <td><span class="badge {{ $invoice->status === 'paid' ? 'bg-success' : 'bg-warning' }}">{{ ucfirst($invoice->status) }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-info">View</a>
                        <a href="{{ route('invoices.show', $invoice) }}?print=1" target="_blank" class="btn btn-sm btn-outline-secondary">Print</a>
                        @if(auth()->user()->isAdmin())
                            <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
