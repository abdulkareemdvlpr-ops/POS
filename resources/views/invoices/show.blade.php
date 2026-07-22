@extends('layouts.app')

@section('title', 'Bill #' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT))

@section('content')
@php
    $invoiceNumber  = str_pad($invoice->id, 4, '0', STR_PAD_LEFT);
    $receiptDate    = $invoice->invoice_date ?: $invoice->created_at;
    $cashierName    = $invoice->cashier->name ?? 'Cashier';
    $discount       = (float) ($invoice->discount ?? 0);
    $discountAmount = $invoice->discount_type === 'percent'
        ? ((float) $invoice->subtotal * $discount / 100)
        : $discount;
    $taxableAmount  = max(0, (float) $invoice->subtotal - $discountAmount);
    $salesTaxAmount = $taxableAmount * (float) ($invoice->tax ?? 0) / 100;
    $serviceFee     = (float) ($invoice->service_fee ?? 0);
    $deliveryPhone  = $businessSettings->delivery_phone ?: $businessSettings->phone;
    $backNotes      = array_values(array_filter(
        preg_split('/\r\n|\r|\n/', trim((string) ($businessSettings->receipt_back_notes ?? ''))) ?: [],
        fn($n) => trim($n) !== ''
    ));
    $backLogo       = $businessSettings->back_logo_path ?: $businessSettings->logo_path;
    $isRtl          = (bool) ($businessSettings->receipt_back_rtl ?? false);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h4 class="mb-0 fw-bold">Pharmacy Bill #{{ $invoiceNumber }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Bills</a></li>
                <li class="breadcrumb-item active">Receipt</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 flex-wrap no-print">
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i>Print
        </button>
        @if(auth()->user()->isAdmin())
        <form action="{{ route('invoices.destroy', $invoice) }}" method="POST"
              onsubmit="return confirm('Delete this invoice and restore stock?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
        </form>
        @endif
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div id="printArea" class="receipt-stack">

    {{-- ════════════ FRONT ════════════ --}}
    <section class="receipt-page">
        <div class="print-doc-title">{{ $businessSettings->business_name ?? 'Pharmacy' }} - Bill #{{ $invoiceNumber }}</div>

        {{-- Header --}}
        <div class="receipt-header text-center">
            @if($businessSettings->logo_path)
                <img src="{{ route('media.show', $businessSettings->logo_path) }}" alt="Logo" class="receipt-logo">
            @endif
            @if($businessSettings->business_name)
                <h2 class="receipt-title">{{ $businessSettings->business_name }}</h2>
            @endif
            @if($businessSettings->address)
                <div class="receipt-sub">{{ $businessSettings->address }}</div>
            @endif

            {{-- Dynamic header fields (inline on one row) --}}
            @php
                $headerParts = [];
                if($businessSettings->show_phone_on_print && $businessSettings->phone) $headerParts[] = 'Ph # '.$businessSettings->phone;
                if($businessSettings->show_license && $businessSettings->license_no)   $headerParts[] = 'Lic. # '.$businessSettings->license_no;
                if($businessSettings->show_ntn && $businessSettings->ntn_no)           $headerParts[] = 'NTN # '.$businessSettings->ntn_no;
                if($businessSettings->show_strn && $businessSettings->strn_no)         $headerParts[] = 'STRN # '.$businessSettings->strn_no;
            @endphp
            @if(count($headerParts) > 0)
                <div class="receipt-sub" style="margin-top:3px;">{{ implode('    ', $headerParts) }}</div>
            @endif
        </div>

        {{-- Invoice strip --}}
        <div class="receipt-strip">
            <div><span>Invoice #</span><strong>{{ $invoiceNumber }}</strong></div>
            <div><span>Date / Time</span><strong>{{ $receiptDate->format('d/m/Y h:i A') }}</strong></div>
            <div><span>Cashier</span><strong>{{ $cashierName }}</strong></div>
            <div><span>Payment</span><strong>{{ ucfirst(str_replace('_', ' ', $invoice->payment_method ?? 'Cash')) }}</strong></div>
        </div>

        {{-- Customer --}}
        <div class="receipt-customer">
            <span class="receipt-label">Customer:</span>
            @if($invoice->customer)
                <strong>{{ $invoice->customer->name }}</strong>
                @if($invoice->customer->phone) &nbsp; {{ $invoice->customer->phone }} @endif
            @else
                <strong>CASH SALES - WALKING CUSTOMER A/C</strong>
            @endif
            @if($invoice->notes)
                &nbsp;&nbsp; <span class="receipt-label">Remarks:</span> {{ $invoice->notes }}
            @endif
        </div>

        {{-- Items table --}}
        <table class="receipt-table">
            <thead>
                <tr>
                    <th style="width:26px;">#</th>
                    <th>Description</th>
                    <th class="ta-c" style="width:50px;">MRP</th>
                    <th class="ta-c" style="width:50px;">Price</th>
                    <th class="ta-c" style="width:36px;">Qty</th>
                    <th class="ta-c" style="width:56px;">GST%<br>Value</th>
                    <th class="ta-r" style="width:60px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $i => $item)
                @php
                    $gstPct = (float)($invoice->tax ?? 0);
                    $gstVal = round($item->price * $item->qty * $gstPct / 100, 2);
                @endphp
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>
                        <div class="fw-sb">{{ $item->product->name ?? 'Medicine' }}</div>
                        @if($item->unit_type && $item->unit_type !== 'tablet')
                        <div class="receipt-sub small">Sold as: {{ $item->unit_qty }} x {{ $item->unitLabel() }} ({{ $item->qty }} {{ $item->unitLabel() === 'Box' ? 'tablets/pcs' : 'units' }})</div>
                        @endif
                        @if($item->product->batch_number ?? false)
                        <div class="receipt-sub small">Batch: {{ $item->product->batch_number }}</div>
                        @endif
                    </td>
                    <td class="ta-c">{{ number_format($item->price, 2) }}</td>
                    <td class="ta-c">{{ number_format($item->price, 2) }}</td>
                    <td class="ta-c">{{ $item->qty }}</td>
                    <td class="ta-c">{{ number_format($gstPct, 2) }}({{ number_format($gstPct, 0) }}%)<br>{{ number_format($gstVal, 2) }}</td>
                    <td class="ta-r fw-sb">{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Summary --}}
        <div class="receipt-summary">
            <div class="receipt-summary-row">
                <span>Total Qty: {{ $invoice->items->sum('qty') }}</span>
                <span>Total Amount: <strong>{{ number_format($invoice->subtotal ?? 0, 2) }}</strong></span>
            </div>
            @if($discountAmount > 0)
            <div class="receipt-summary-row">
                <span></span>
                <span>Discount: - {{ number_format($discountAmount, 2) }}</span>
            </div>
            @endif
            <div class="receipt-summary-row">
                <span></span>
                <span>Sales Tax: {{ number_format($salesTaxAmount, 2) }}</span>
            </div>
            @if($serviceFee > 0)
            <div class="receipt-summary-row">
                <span></span>
                <span>POS Service Fee: {{ number_format($serviceFee, 2) }}</span>
            </div>
            @endif
            <div class="receipt-summary-row receipt-total-row">
                <span></span>
                <span>Payable: <strong>{{ number_format($invoice->total, 2) }}</strong></span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="receipt-footer text-center">
            @if($deliveryPhone)
                <div>Call For Home Delivery: {{ $deliveryPhone }}</div>
            @endif
        </div>

        {{-- Policy / Instructions Notes --}}
        @if(count($backNotes) > 0)
        <div class="receipt-policy-notes {{ $isRtl ? 'rtl-notes' : '' }}" style="margin-top: 10px; border-top: 1px dashed #aaa; padding-top: 8px; font-size: 0.78rem; text-align: {{ $isRtl ? 'right' : 'left' }};" @if($isRtl) dir="rtl" @endif>
            @if($businessSettings->receipt_back_heading)
                <div style="font-weight: bold; margin-bottom: 4px; text-align: center; font-size: 0.82rem;">{{ $businessSettings->receipt_back_heading }}</div>
            @endif
            <div style="padding: 0 4px; line-height: 1.4;">
                @foreach($backNotes as $note)
                    <div style="margin-bottom: 3px;">{{ $isRtl ? '•' : '*' }} {{ $note }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="software-credit" style="text-align: center; font-size: 11px; line-height: 1.4; margin-top: 15px;">
            {!! str_replace('Abdul Kareem', '<br>Abdul Kareem', \App\Models\BusinessSetting::SOFTWARE_CREDIT) !!}
        </div>

    </section>
</div>
@endsection

@push('styles')
<style>
/* ── Screen preview ──────────────────────────────────────── */
.receipt-stack { display: grid; gap: 20px; max-width: 820px; margin: 0 auto; }
.receipt-page  { position: relative; box-sizing: border-box; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; color: #111; }
.print-doc-title { display: none; }

/* Header */
.receipt-logo  { max-height: 64px; max-width: 180px; object-fit: contain; margin-bottom: 6px; }
.receipt-title { font-size: 1.3rem; font-weight: 800; margin: 0 0 2px; }
.receipt-sub   { color: #555; font-size: 0.8rem; }
.receipt-label { color: #666; font-size: 0.8rem; }
.receipt-header { padding-bottom: 10px; border-bottom: 2px solid #0f172a; margin-bottom: 10px; }

/* Invoice strip */
.receipt-strip { display: grid; grid-template-columns: repeat(4,1fr); background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 10px; }
.receipt-strip > div { padding: 7px 10px; }
.receipt-strip span   { display: block; font-size: 0.68rem; color: #888; text-transform: uppercase; }
.receipt-strip strong { display: block; font-size: 0.82rem; font-weight: 700; }

/* Customer line */
.receipt-customer { font-size: 0.82rem; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid #e2e8f0; }

/* Items table */
.receipt-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-bottom: 6px; }
.receipt-table thead tr { background: #0f172a; color: #fff; }
.receipt-table th, .receipt-table td { padding: 5px 6px; }
.receipt-table tbody tr { border-bottom: 1px solid #f1f5f9; }
.ta-c { text-align: center; }
.ta-r { text-align: right; }
.fw-sb { font-weight: 600; }

/* Summary */
.receipt-summary { font-size: 0.82rem; }
.receipt-summary-row { display: flex; justify-content: space-between; padding: 3px 0; }
.receipt-total-row { border-top: 2px solid #0f172a; font-weight: 700; font-size: 1rem; margin-top: 4px; padding-top: 6px; }

/* Footer */
.receipt-footer { border-top: 1px dashed #aaa; margin-top: 10px; padding-top: 8px; font-size: 0.78rem; color: #444; }
.software-credit { font-weight: 700; font-size: 0.75rem; margin-top: 4px; }

/* Back page */
.receipt-back { display: flex; flex-direction: column; min-height: 520px; }
.back-logo-wrap .back-logo { max-height: 60px; max-width: 200px; object-fit: contain; }
.back-brand-name { font-size: 0.9rem; font-weight: 700; margin-top: 3px; }
.back-heading-box {
    background: #0f172a; color: #fff;
    text-align: center; font-size: 1rem; font-weight: 700;
    padding: 7px 12px; margin: 12px 0;
    border-radius: 4px;
}
.back-notes { padding: 0; }
.back-note-item {
    border-bottom: 1px solid #e5e7eb;
    padding: 6px 2px;
    font-size: 0.82rem;
    color: #222;
}
.back-note-item::before { content: "* "; font-weight: 700; color: #dc2626; }
.receipt-back-rtl {
    direction: rtl;
    text-align: right;
    font-family: "Jameel Noori Nastaleeq", "Noto Nastaliq Urdu", "Urdu Typesetting", "Traditional Arabic", serif;
    letter-spacing: 0;
}
.receipt-back-rtl .back-brand-name,
.receipt-back-rtl .back-heading-box,
.receipt-back-rtl .back-notes,
.receipt-back-rtl .back-note-item {
    font-family: "Jameel Noori Nastaleeq", "Noto Nastaliq Urdu", "Urdu Typesetting", "Traditional Arabic", serif !important;
    letter-spacing: 0 !important;
}
.receipt-back-rtl .back-heading-box {
    font-size: 1.2rem;
    line-height: 1.8;
}
.receipt-back-rtl .back-notes {
    line-height: 2.15;
}
.receipt-back-rtl .back-note-item {
    font-size: 1.05rem;
    line-height: 2.15;
    text-align: right;
    padding: 7px 2px;
}
.receipt-back-rtl .back-note-item::before {
    content: "۔ ";
    color: #dc2626;
    font-weight: 700;
}

/* ── Print styles ────────────────────────────────────────── */
@page {
    size: auto;
    margin: 0;
}

@media print {
    .no-print, .sidebar, .topbar, .page-content > .alert { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
    html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; min-height: 100% !important; }
    body { background: #fff !important; font-size: 11px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .receipt-stack { display: block !important; gap: 0; max-width: none; margin: 0 !important; }
    .receipt-page  {
        border: 0;
        border-radius: 0;
        padding: 8mm 5mm;
        min-height: auto !important;
        height: auto !important;
        box-sizing: border-box;
        break-after: auto;
        page-break-after: auto;
        box-shadow: none;
        overflow: visible;
    }
    .print-doc-title {
        display: block !important;
        position: absolute;
        top: 4mm;
        right: 8mm;
        text-align: right;
        font-size: 8.5pt;
        font-weight: 700;
        line-height: 1.1;
        color: #111;
        white-space: nowrap;
    }
    .receipt-back  { break-after: auto; page-break-after: auto; }
    .receipt-table { page-break-inside: auto; }
    .receipt-table tr { page-break-inside: avoid; page-break-after: auto; }
    .receipt-header,
    .receipt-strip,
    .receipt-customer,
    .receipt-summary,
    .receipt-footer,
    .back-logo-wrap,
    .back-heading-box,
    .back-note-item { break-inside: avoid; page-break-inside: avoid; }
    .receipt-strip { grid-template-columns: repeat(4,1fr); }
    .receipt-logo  { max-height: 50px; }
    .receipt-title { font-size: 1.1rem; }
    .back-logo     { max-height: 50px; }
    .back-heading-box { font-size: 0.9rem; padding: 5px 10px; }
    .receipt-back-rtl,
    .receipt-back-rtl .back-brand-name,
    .receipt-back-rtl .back-heading-box,
    .receipt-back-rtl .back-notes,
    .receipt-back-rtl .back-note-item {
        font-family: "Jameel Noori Nastaleeq", "Noto Nastaliq Urdu", "Urdu Typesetting", "Traditional Arabic", serif !important;
        letter-spacing: 0 !important;
    }
    .receipt-back-rtl .back-heading-box {
        font-size: 13pt !important;
        line-height: 1.65 !important;
        padding: 4px 10px;
    }
    .receipt-back-rtl .back-note-item {
        font-size: 11.5pt !important;
        line-height: 1.85 !important;
        padding: 4px 2px;
    }
}

@media (max-width: 600px) {
    .receipt-strip { grid-template-columns: repeat(2,1fr); }
}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const originalTitle = document.title;
    window.addEventListener('beforeprint', () => {
        document.title = ' ';
    });
    window.addEventListener('afterprint', () => {
        document.title = originalTitle;
    });
})();
</script>
@endpush
