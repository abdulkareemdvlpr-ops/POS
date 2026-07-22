@extends('layouts.app')

@section('title', 'Shift Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Shift Reconciliation Details</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('cash-registers.index') }}">Register Logs</a></li>
                <li class="breadcrumb-item active">Shift #{{ $cashRegister->id }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('cash-registers.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Logs
    </a>
</div>

{{-- Top Summary Card --}}
<div class="card border-0 shadow-sm mb-4 text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 12px;">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-white-50 d-block">Cashier / Staff</small>
                <h5 class="fw-bold mb-0 text-info">{{ $cashRegister->user->name }}</h5>
            </div>
            <div class="col-md-3">
                <small class="text-white-50 d-block">Opened At</small>
                <span class="fw-semibold">{{ $cashRegister->opened_at->format('d M Y h:i A') }}</span>
            </div>
            <div class="col-md-3">
                <small class="text-white-50 d-block">Closed At</small>
                <span class="fw-semibold">{{ $cashRegister->closed_at ? $cashRegister->closed_at->format('d M Y h:i A') : 'Active Shift' }}</span>
            </div>
            <div class="col-md-3 text-md-end">
                <small class="text-white-50 d-block">Difference</small>
                <h4 class="fw-extrabold mb-0">
                    @if($cashRegister->status === 'open')
                        <span class="text-warning">Active</span>
                    @elseif($cashRegister->difference_amount == 0)
                        <span class="text-success">Balanced</span>
                    @elseif($cashRegister->difference_amount > 0)
                        <span class="text-success">+Rs. {{ number_format($cashRegister->difference_amount, 2) }}</span>
                    @else
                        <span class="text-danger">-Rs. {{ number_format(abs($cashRegister->difference_amount), 2) }}</span>
                    @endif
                </h4>
            </div>
        </div>

        <hr class="border-secondary my-4">

        <div class="row g-4 text-center">
            <div class="col-4 col-md-2">
                <small class="text-white-50 d-block">Opening Float</small>
                <span class="fw-bold text-light">Rs. {{ number_format($cashRegister->opening_amount, 0) }}</span>
            </div>
            <div class="col-4 col-md-2">
                <small class="text-white-50 d-block">Cash Sales</small>
                <span class="fw-bold text-success">+Rs. {{ number_format($cashRegister->cash_sales, 0) }}</span>
            </div>
            <div class="col-4 col-md-2">
                <small class="text-white-50 d-block">Collections</small>
                <span class="fw-bold text-success">+Rs. {{ number_format($cashRegister->customer_collections, 0) }}</span>
            </div>
            <div class="col-4 col-md-2">
                <small class="text-white-50 d-block">Payouts</small>
                <span class="fw-bold text-danger">-Rs. {{ number_format($cashRegister->supplier_payouts, 0) }}</span>
            </div>
            <div class="col-4 col-md-2">
                <small class="text-white-50 d-block">Expenses</small>
                <span class="fw-bold text-danger">-Rs. {{ number_format($cashRegister->expenses, 0) }}</span>
            </div>
            <div class="col-4 col-md-2 border-start border-secondary">
                <small class="text-white-50 d-block">Actual Closing Cash</small>
                <span class="fw-extrabold text-info">Rs. {{ number_format($cashRegister->actual_closing_amount, 0) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Detail Tabs --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <ul class="nav nav-tabs card-header-tabs" id="shiftDetailsTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-bold text-dark" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">
                    <i class="bi bi-receipt me-1"></i>Sales ({{ $invoices->count() }})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold text-dark" id="collections-tab" data-bs-toggle="tab" data-bs-target="#collections" type="button" role="tab">
                    <i class="bi bi-arrow-down-left-circle me-1"></i>Collections ({{ $collections->count() }})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold text-dark" id="payouts-tab" data-bs-toggle="tab" data-bs-target="#payouts" type="button" role="tab">
                    <i class="bi bi-arrow-up-right-circle me-1"></i>Payouts ({{ $payouts->count() }})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold text-dark" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses" type="button" role="tab">
                    <i class="bi bi-dash-circle me-1"></i>Expenses ({{ $expenses->count() }})
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content" id="shiftDetailsTabsContent">
            {{-- Sales --}}
            <div class="tab-pane fade show active" id="sales" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Invoice #</th>
                                <th>Customer</th>
                                <th>Items Sold</th>
                                <th>Time</th>
                                <th>Payment Method</th>
                                <th class="text-end">Paid Amount</th>
                                <th class="text-end">Due Amount</th>
                                <th class="text-end pe-3">Total Bill</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $inv)
                            <tr>
                                <td class="ps-3 fw-semibold"><a href="{{ route('invoices.show', $inv) }}">#{{ str_pad($inv->id, 4, '0', STR_PAD_LEFT) }}</a></td>
                                <td>{{ $inv->customer->name ?? 'Walk-in' }}</td>
                                <td style="min-width: 260px;">
                                    @forelse($inv->items as $item)
                                        <div class="small d-flex justify-content-between gap-2 border-bottom border-light py-1">
                                            <span>{{ $item->product->name ?? 'Deleted Product' }} <span class="text-muted">x{{ rtrim(rtrim(number_format($item->qty, 2), '0'), '.') }}</span></span>
                                            <span class="text-muted">Rs. {{ number_format($item->total, 2) }}</span>
                                        </div>
                                    @empty
                                        <span class="text-muted small">No items</span>
                                    @endforelse
                                </td>
                                <td class="text-muted small">{{ $inv->created_at->format('h:i A') }}</td>
                                <td>{{ ucfirst($inv->payment_method) }}</td>
                                <td class="text-end text-success fw-bold">Rs. {{ number_format($inv->paid_amount, 2) }}</td>
                                <td class="text-end text-danger">Rs. {{ number_format($inv->due_amount, 2) }}</td>
                                <td class="text-end pe-3 fw-bold">Rs. {{ number_format($inv->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No billing invoices recorded in this shift.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Collections --}}
            <div class="tab-pane fade" id="collections" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Collection ID</th>
                                <th>Customer</th>
                                <th>Time</th>
                                <th>Payment Method</th>
                                <th>Slip/Ref #</th>
                                <th>Notes</th>
                                <th class="text-end pe-3">Collected Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($collections as $col)
                            <tr>
                                <td class="ps-3 fw-semibold">#{{ $col->id }}</td>
                                <td>{{ $col->customer->name }}</td>
                                <td class="text-muted small">{{ $col->created_at->format('h:i A') }}</td>
                                <td>{{ ucfirst($col->payment_method) }}</td>
                                <td>{{ $col->slip_number ?? '—' }}</td>
                                <td class="text-muted small">{{ $col->notes ?? '—' }}</td>
                                <td class="text-end pe-3 text-success fw-bold">Rs. {{ number_format($col->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No customer collections logged in this shift.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Payouts --}}
            <div class="tab-pane fade" id="payouts" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Payout ID</th>
                                <th>Supplier</th>
                                <th>Time</th>
                                <th>Payment Method</th>
                                <th>Slip/Ref #</th>
                                <th>Notes</th>
                                <th class="text-end pe-3">Payout Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payouts as $pay)
                            <tr>
                                <td class="ps-3 fw-semibold">#{{ $pay->id }}</td>
                                <td>{{ $pay->supplier->name }}</td>
                                <td class="text-muted small">{{ $pay->created_at->format('h:i A') }}</td>
                                <td>{{ ucfirst($pay->payment_method) }}</td>
                                <td>{{ $pay->slip_number ?? '—' }}</td>
                                <td class="text-muted small">{{ $pay->notes ?? '—' }}</td>
                                <td class="text-end pe-3 text-danger fw-bold">Rs. {{ number_format($pay->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No supplier payouts logged in this shift.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Expenses --}}
            <div class="tab-pane fade" id="expenses" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Expense ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Time</th>
                                <th>Notes</th>
                                <th class="text-end pe-3">Expense Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $exp)
                            <tr>
                                <td class="ps-3 fw-semibold">#{{ $exp->id }}</td>
                                <td class="fw-semibold">{{ $exp->title }}</td>
                                <td>{{ $exp->category }}</td>
                                <td class="text-muted small">{{ $exp->created_at->format('h:i A') }}</td>
                                <td class="text-muted small">{{ $exp->notes ?? '—' }}</td>
                                <td class="text-end pe-3 text-danger fw-bold">Rs. {{ number_format($exp->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No expenses logged in this shift.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
