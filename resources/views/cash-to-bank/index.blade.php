@extends('layouts.app')

@section('title', 'Cash to Bank Transfer')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Cash to Bank Deposit Record</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Cash to Bank</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Galla / Cash In Hand</h6>
                <h3 class="mb-0 fw-bold">Rs. {{ number_format($settings->cash_in_hand ?? 0, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-info text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-1">Bank Balance</h6>
                <h3 class="mb-0 fw-bold">Rs. {{ number_format($settings->bank_balance ?? 0, 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3"><span class="fw-bold">Record Bank Deposit</span></div>
            <div class="card-body">
                <form action="{{ route('cash-to-bank.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Deposit Date <span class="text-danger">*</span></label>
                        <input type="date" name="deposit_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deposit Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="0.01" name="amount" class="form-control" min="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control" placeholder="e.g. HBL, Meezan, Alfalah" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deposit Slip / Ref #</label>
                        <input type="text" name="slip_number" class="form-control" placeholder="Slip #">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Remarks..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">Transfer Cash to Bank</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3"><span class="fw-bold">Deposit Log History</span></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Date</th>
                                <th>Bank</th>
                                <th>Slip #</th>
                                <th>Notes</th>
                                <th>Logged By</th>
                                <th class="text-end pe-3">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td class="ps-3">{{ $loop->iteration }}</td>
                                <td>{{ $log->deposit_date->format('d M Y') }}</td>
                                <td class="fw-semibold">{{ $log->bank_name }}</td>
                                <td>{{ $log->slip_number ?? '—' }}</td>
                                <td>{{ $log->notes ?? '—' }}</td>
                                <td>{{ $log->creator->name ?? 'System' }}</td>
                                <td class="text-end pe-3 text-info fw-bold">Rs. {{ number_format($log->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No deposit log history.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($logs->hasPages())
            <div class="card-footer bg-white">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
