@extends('layouts.app')

@section('title', 'Cash Register Status')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Cash Register & Galla Status</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Register Status</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4">
    {{-- Left Side: Shift Statistics --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #f8fafc; border-radius: 14px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge bg-success rounded-pill px-3 py-1 mb-2">Shift Active</span>
                        <h4 class="fw-bold mb-0">Shift Summary Log</h4>
                        <small class="text-white-50">Shared galla opened on {{ $active->opened_at->format('d M Y \a\t h:i A') }}</small>
                    </div>
                    <div class="text-end">
                        <small class="text-white-50 d-block">Opened By</small>
                        <span class="fw-bold text-info">{{ $active->user->name }}</span>
                    </div>
                </div>

                <div class="mt-4" style="font-size: 0.98rem;">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                        <span class="text-white-50"><i class="bi bi-wallet2 text-info me-2"></i>Opening Float (Shared galla)</span>
                        <span class="fw-bold">Rs. {{ number_format($active->opening_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                        <span class="text-white-50"><i class="bi bi-plus-circle text-success me-2"></i>Cash Sales (Admin + Cashiers)</span>
                        <span class="fw-bold text-success">+ Rs. {{ number_format($stats['cash_sales'], 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                        <span class="text-white-50"><i class="bi bi-arrow-down-left text-success me-2"></i>Customer Collections (Udhaar Cash-In)</span>
                        <span class="fw-bold text-success">+ Rs. {{ number_format($stats['customer_collections'], 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                        <span class="text-white-50"><i class="bi bi-arrow-up-right text-danger me-2"></i>Supplier Payouts (Counter Kisht)</span>
                        <span class="fw-bold text-danger">- Rs. {{ number_format($stats['supplier_payouts'], 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                        <span class="text-white-50"><i class="bi bi-dash-circle text-danger me-2"></i>Expenses (Cash Paid Out)</span>
                        <span class="fw-bold text-danger">- Rs. {{ number_format($stats['expenses'], 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-3">
                        <span class="fw-bold fs-5">Expected Cash in Drawer</span>
                        <span class="fw-extrabold fs-4 text-info">Rs. {{ number_format($stats['expected'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Side: Closing Form --}}
    <div class="col-lg-5">
        <div class="card shadow-sm h-100 border-0" style="border-radius: 14px;">
            <div class="card-header bg-white py-3 border-0"><span class="fw-bold fs-5 text-dark"><i class="bi bi-lock-fill me-2 text-danger"></i>Close Cash Register</span></div>
            <div class="card-body">
                <form action="{{ route('cash-registers.close') }}" method="POST">
                    @csrf
                    <div class="mb-4 bg-light p-3 rounded" style="border-left: 4px solid #0ea5e9;">
                        <span class="text-muted small d-block">Expected Cash in Hand (System)</span>
                        <h3 class="fw-bold mb-0 text-dark">Rs. {{ number_format($stats['expected'], 2) }}</h3>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">Actual Cash in Drawer (Physical Count) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg shadow-sm border rounded">
                            <span class="input-group-text bg-white border-0 text-muted">Rs.</span>
                            <input type="number" step="0.01" name="actual_closing_amount" class="form-control border-0 fw-bold text-dark shadow-none" placeholder="0.00" min="0" required autofocus>
                        </div>
                        <small class="text-muted d-block mt-2">Count all physical cash coins and notes in the register drawer and enter the sum here.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">Closing Notes / Discrepancy Remarks</label>
                        <textarea name="closing_notes" class="form-control" rows="3" placeholder="Explain any cash surplus/deficit or leave shift closing remarks..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold py-3" style="border-radius: 10px;" onsubmit="return confirm('Are you sure you want to close this cash register shift?');">
                        <i class="bi bi-power me-1"></i>Reconcile & Close Register
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
