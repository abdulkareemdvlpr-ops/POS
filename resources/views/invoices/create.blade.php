@extends('layouts.app')

@section('title', 'New Pharmacy Bill')

@section('content')
@php
    $defaultTaxRate    = old('tax', $businessSettings->sales_tax_rate ?? 0);
    $defaultServiceFee = old('service_fee', $businessSettings->service_fee ?? 0);
    $resumeId = request('resume');
@endphp

{{-- Hold Bill Modal --}}
<div class="modal fade" id="holdBillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold"><i class="bi bi-pause-circle me-2"></i>Hold Bill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">Bill Label (optional)</label>
                <input type="text" id="holdLabelInput" class="form-control" placeholder="e.g. Patient Name, Token No...">
                <div class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>Bill will be saved and can be resumed anytime.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning text-dark fw-bold" id="confirmHoldBtn"><i class="bi bi-pause-circle me-2"></i>Hold This Bill</button>
            </div>
        </div>
    </div>
</div>

{{-- Hidden Hold Bill Form --}}
<form action="{{ route('held-invoices.store') }}" method="POST" id="holdBillForm" style="display:none">
    @csrf
    <input type="hidden" name="label"          id="holdFormLabel">
    <input type="hidden" name="customer_id"    id="holdFormCustomer">
    <input type="hidden" name="discount"       id="holdFormDiscount">
    <input type="hidden" name="discount_type"  id="holdFormDiscountType">
    <input type="hidden" name="tax"            id="holdFormTax">
    <input type="hidden" name="service_fee"    id="holdFormServiceFee">
    <input type="hidden" name="payment_method" id="holdFormPayment">
    <input type="hidden" name="notes"          id="holdFormNotes">
    <div id="holdFormItems"></div>
</form>

<form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
@csrf

{{-- ─── TOP META ROW ───────────────────────────────────────────────── --}}
<div class="bill-meta-row mb-3 p-3 bg-white rounded shadow-sm border">
    <div class="row g-2 align-items-end">
        <div class="col-lg-3 col-md-6">
            <label class="form-label small fw-bold text-muted mb-1">Customer</label>
            <div class="d-flex gap-1">
                <div class="input-group input-group-sm border rounded shadow-sm">
                    <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-person"></i></span>
                    <select name="customer_id" class="form-select form-select-sm border-0 ps-0 shadow-none fw-semibold" id="customerSelect">
                        <option value="">Walk-in Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">
                                {{ $customer->name }} ({{ $customer->phone }})
                                @if($customer->totalDue() > 0) — [Due: Rs. {{ number_format($customer->totalDue(),2) }}] @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ route('customers.index') }}" target="_blank" class="btn btn-sm btn-light border shadow-sm" title="New Customer"><i class="bi bi-plus-lg"></i></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-3 col-6">
            <label class="form-label small fw-bold text-muted mb-1">Invoice Date</label>
            <input type="date" name="invoice_date" id="invoiceDate" class="form-control form-control-sm border shadow-sm fw-semibold" value="{{ date('Y-m-d') }}">
        </div>
        <div class="col-lg-2 col-md-3 col-6">
            <label class="form-label small fw-bold text-muted mb-1">Payment Method</label>
            <select name="payment_method" id="paymentMethodSelect" class="form-select form-select-sm border shadow-sm fw-semibold">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="easypaisa">Easypaisa</option>
                <option value="jazzcash">JazzCash</option>
                <option value="due">Credit (Due)</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-3 col-6">
            <label class="form-label small fw-bold text-muted mb-1">Status</label>
            <select name="status" id="statusSelect" class="form-select form-select-sm border shadow-sm fw-semibold">
                <option value="paid">● Paid</option>
                <option value="unpaid">Unpaid</option>
                <option value="partial">Partial</option>
            </select>
        </div>
        <div class="col-lg-3 col-md-3 col-6">
            <label class="form-label small fw-bold text-muted mb-1">Bill Action / Type</label>
            <div class="btn-group w-100">
                <button type="button" class="btn btn-sm btn-outline-primary active fw-semibold" id="newBillTypeBtn" onclick="window.location.href='{{ route('invoices.create') }}';">New Bill</button>
                <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold" id="holdBillBtnTop">Hold Bill</button>
                <div class="btn-group" role="group">
                    <button id="resumeBillBtnGrp" type="button" class="btn btn-sm btn-outline-success dropdown-toggle fw-semibold" data-bs-toggle="dropdown" aria-expanded="false" style="border-top-left-radius:0; border-bottom-left-radius:0;">
                        Resume ({{ $heldInvoices->count() }})
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="resumeBillBtnGrp" style="max-height: 250px; overflow-y: auto;">
                        @forelse($heldInvoices as $held)
                            <li>
                                <a class="dropdown-item d-flex justify-content-between align-items-center py-2" href="{{ route('invoices.create', ['resume' => $held->id]) }}">
                                    <div>
                                        <strong class="text-dark">{{ $held->label }}</strong>
                                        <div class="text-muted" style="font-size:0.7rem;">{{ $held->created_at->diffForHumans() }}</div>
                                    </div>
                                    <span class="badge bg-success text-white ms-2" style="font-size: 0.72rem;">
                                        Rs. {{ number_format(collect($held->data['items'] ?? [])->sum(function($item) { return ($item['qty'] ?? 1) * ($item['price'] ?? 0); }), 0) }}
                                    </span>
                                </a>
                            </li>
                        @empty
                            <li><span class="dropdown-item text-muted text-center small py-2">No held bills found</span></li>
                        @endforelse
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center text-primary small fw-bold py-1" href="{{ route('held-invoices.index') }}">View All Held Bills</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── SEARCH BAR ─────────────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border p-3 mb-0" style="border-bottom-left-radius:0!important;border-bottom-right-radius:0!important;">
    <label class="form-label fw-bold mb-2" style="font-size:0.85rem;color:#374151;">Search Medicine (Barcode / Name / Generic / SKU)</label>
    <div class="d-flex gap-2 align-items-center">
        <div class="flex-grow-1 input-group shadow-sm border rounded-pill overflow-hidden" style="border-color:#e2e8f0!important;">
            <span class="input-group-text bg-white border-0 text-muted ps-3"><i class="bi bi-search"></i></span>
            <input type="text" id="productSearchInput" class="form-control border-0 shadow-none" style="height:42px;font-size:0.95rem;"
                placeholder="Type medicine name, generic name, barcode or SKU..." autocomplete="off" autofocus>
            <input type="text" id="barcodeScannerInput" class="form-control border-0 shadow-none d-none" placeholder="Scan barcode...">
        </div>
        <button type="button" class="btn btn-outline-secondary fw-semibold px-3" style="height:42px;white-space:nowrap;" id="barcodeToggleBtn">
            <i class="bi bi-upc-scan me-1"></i>Barcode
        </button>
        <select id="categorySelect" class="form-select form-select-sm border shadow-sm d-none d-md-block" style="width:160px;height:42px;">
            <option value="">All Categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="small text-muted mt-1" style="font-size:0.72rem;"><i class="bi bi-keyboard me-1"></i>Tip: Use <kbd>↑</kbd><kbd>↓</kbd> arrow keys to navigate results, <kbd>Enter</kbd> to add selected item</div>
</div>

{{-- ─── SEARCH RESULTS TABLE ───────────────────────────────────────── --}}
<div id="searchResultsWrap" class="bg-white rounded-bottom shadow-sm border border-top-0 mb-3 d-none">
    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center" style="background:#f8fafc;">
        <span id="productSearchStatus" class="small text-muted fw-semibold"></span>
        <button type="button" class="btn-close btn-sm" id="closeSearchResults" title="Close search (Esc)"></button>
    </div>
    <div class="table-responsive" style="max-height:300px;overflow-y:auto;" id="searchScrollArea">
        <table class="table table-hover table-sm mb-0 align-middle" id="searchResultsTable">
            <thead class="table-light" style="font-size:0.72rem;position:sticky;top:0;z-index:1;">
                <tr>
                    <th class="ps-3" style="width:20%">MEDICINE</th>
                    <th style="width:13%">GENERIC / FORMULA</th>
                    <th style="width:8%">PACK</th>
                    <th style="width:9%">CATEGORY</th>
                    <th style="width:12%">LOCATION</th>
                    <th style="width:8%">MFG DATE</th>
                    <th style="width:8%">EXPIRY</th>
                    <th style="width:8%">STOCK</th>
                    <th style="width:7%">PRICE</th>
                    <th style="width:7%" class="text-center">ACTION</th>
                </tr>
            </thead>
            <tbody id="searchResultsBody"></tbody>
        </table>
    </div>
    <div class="px-3 py-2 border-top d-flex justify-content-between align-items-center small" style="background:#fafafa;">
        <span id="viewAllResultsLink" class="text-muted"></span>
        <span id="moreResultsCount" class="text-muted"></span>
    </div>
    {{-- Substitute section --}}
    <div id="substituteSection" class="d-none border-top">
        <div class="px-3 py-2" style="background:#eff6ff;">
            <span class="small fw-semibold text-primary"><i class="bi bi-arrow-left-right me-1"></i>Formula-based Substitutes — no exact match found</span>
        </div>
        <div class="table-responsive" style="max-height:200px;overflow-y:auto;">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light" style="font-size:0.72rem;">
                    <tr>
                        <th class="ps-3">MEDICINE</th><th>GENERIC</th><th>PACK</th><th>CATEGORY</th><th>LOCATION</th><th>MFG DATE</th><th>EXPIRY</th><th>STOCK</th><th>PRICE</th><th class="text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody id="substituteResults"></tbody>
            </table>
        </div>
    </div>
</div>

{{-- ─── BILL ITEMS TABLE ───────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border mb-3">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" id="billItemsTable" style="min-width:750px;">
            <thead class="table-light" style="font-size:0.72rem;">
                <tr>
                    <th class="ps-3" style="width:30px;">#</th>
                    <th style="width:24%">MEDICINE</th>
                    <th style="width:8%">BATCH</th>
                    <th style="width:8%">EXPIRY</th>
                    <th style="width:14%">LOCATION</th>
                    <th style="width:14%" class="text-center">QTY</th>
                    <th style="width:9%" class="text-end pe-2">UNIT PRICE</th>
                    <th style="width:6%;min-width:52px;" class="text-end pe-2">DISC%</th>
                    <th style="width:9%;min-width:70px;" class="text-end pe-2">TOTAL</th>
                    <th style="min-width:48px;" class="text-center">DEL</th>
                </tr>
            </thead>
            <tbody id="productRows">
                <tr id="emptyRow">
                    <td colspan="10" class="text-center text-muted py-5">
                        <i class="bi bi-cart3 fs-1 text-light d-block mb-1"></i>
                        <span class="small">Search medicines above and add them to the bill</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-3 py-2 border-top d-flex justify-content-between align-items-center" style="background:#fafafa;">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllBtn"><i class="bi bi-trash me-1"></i>Clear All</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="addNoteBtn"><i class="bi bi-pencil me-1"></i>Add Note</button>
        </div>
        <span id="itemCountDisplay" class="small fw-semibold text-muted">Total items: 0</span>
    </div>
    <div id="noteRow" class="px-3 pb-2 d-none">
        <textarea id="noteTextarea" class="form-control form-control-sm" rows="2" placeholder="Bill note (optional)..."></textarea>
        <input type="hidden" name="notes" id="notesInput" value="">
    </div>
</div>

{{-- ─── BOTTOM SUMMARY + ACTIONS ──────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border p-0">
    <div class="d-flex align-items-stretch" style="min-height:80px;overflow-x:auto;flex-wrap:nowrap;">

        {{-- Subtotal --}}
        <div class="summary-cell border-end px-3 py-3 d-flex flex-column justify-content-center" style="min-width:130px;">
            <div class="small text-muted fw-semibold mb-1">Subtotal</div>
            <div id="subtotalDisplay" class="fw-bold" style="font-size:1rem;">Rs. 0</div>
        </div>

        {{-- Discount --}}
        <div class="summary-cell border-end px-3 py-3 d-flex flex-column justify-content-center" style="min-width:160px;">
            <div class="small text-muted fw-semibold mb-1">Discount</div>
            <div class="input-group input-group-sm" style="width:150px;">
                <select name="discount_type" id="discountType" class="form-select border" style="max-width:52px;">
                    <option value="percent">%</option>
                    <option value="flat">Rs</option>
                </select>
                <input type="number" name="discount" id="discountInput" class="form-control text-end border" value="0" min="0" step="0.01">
            </div>
        </div>

        {{-- Tax --}}
        <div class="summary-cell border-end px-3 py-3 d-flex flex-column justify-content-center" style="min-width:130px;">
            <div class="small text-muted fw-semibold mb-1">Tax (%)</div>
            <div class="input-group input-group-sm" style="width:110px;">
                <input type="number" name="tax" id="taxInput" class="form-control text-end border" value="{{ $defaultTaxRate }}" min="0" step="0.01">
                <span class="input-group-text border text-muted">%</span>
            </div>
            <div id="taxAmountDisplay" class="text-muted mt-1" style="font-size:0.65rem;"></div>
        </div>

        {{-- TOTAL --}}
        <div class="summary-cell border-end px-4 py-3 d-flex flex-column justify-content-center text-center" style="min-width:150px;background:#f0fdf4;">
            <div class="small fw-bold text-muted mb-1">TOTAL</div>
            <div id="totalDisplay" class="fw-bold" style="font-size:1.6rem;color:#10b981;line-height:1.1;">Rs. 0</div>
        </div>

        {{-- Amount Received / Change --}}
        <div class="summary-cell border-end px-3 py-3 d-flex flex-column justify-content-center" style="min-width:160px;">
            <div class="small text-muted fw-semibold mb-1">Amount Received</div>
            <input type="number" name="amount_received" id="amountReceived" class="form-control form-control-sm text-end border shadow-sm fw-bold mb-2" placeholder="0.00" min="0" step="0.01" style="width:140px;">
            <div class="d-flex justify-content-between align-items-center" style="width:140px;">
                <span class="small text-muted fw-semibold">Change</span>
                <span id="changeDisplay" class="fw-bold text-success small">Rs. 0</span>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="summary-cell flex-grow-1 px-3 py-3 d-flex flex-column justify-content-center gap-2" style="min-width:240px;">
            <input type="hidden" name="subtotal"    id="subtotalInput">
            <input type="hidden" name="total"       id="totalInput">
            <input type="hidden" name="service_fee" id="serviceFeeInput" value="{{ $defaultServiceFee }}">
            <button type="submit" class="btn fw-bold text-white w-100" style="background:#10b981;border-color:#10b981;padding:10px;font-size:0.95rem;">
                <i class="bi bi-check-circle me-1"></i>COMPLETE SALE
            </button>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-warning fw-semibold flex-fill btn-sm" id="holdBillBtnAlt">
                    <i class="bi bi-pause-circle me-1"></i>HOLD BILL
                </button>
                <button type="button" class="btn btn-outline-secondary fw-semibold flex-fill btn-sm" id="printBillBtn">
                    <i class="bi bi-printer me-1"></i>PRINT BILL
                </button>
            </div>
        </div>

    </div>
</div>

</form>

@push('styles')
<style>
    .bill-meta-row .form-select, .bill-meta-row .form-control { font-size:0.85rem; }

    /* Search results */
    #searchResultsBody tr { cursor:pointer; transition:background 0.1s; }
    #searchResultsBody tr:hover > td { background-color:#dcfce7 !important; }
    #searchResultsBody tr.kb-active > td {
        background-color: #bfdbfe !important;
        border-top: 1px solid #93c5fd !important;
        border-bottom: 1px solid #93c5fd !important;
    }
    #searchResultsBody tr.kb-active > td:first-child {
        border-left: 4px solid #3b82f6 !important;
    }
    #substituteResults tr { cursor:pointer; }
    #substituteResults tr:hover > td { background-color:#dcfce7 !important; }
    #searchResultsBody td, #substituteResults td { font-size:0.8rem; padding:6px 5px; }
    #searchResultsTable th, .table.align-middle thead th { font-size:0.71rem; letter-spacing:0.3px; }

    /* Bill items */
    #billItemsTable th { font-size:0.71rem; letter-spacing:0.3px; background:#f8fafc; color:#64748b; text-transform:uppercase; padding:8px 6px; }
    #billItemsTable td { font-size:0.82rem; vertical-align:middle; padding:5px 6px; border-bottom:1px solid #f1f5f9; }

    /* Qty toggle */
    .qty-toggle { display:flex;align-items:center;justify-content:center;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;background:#fff;height:28px; }
    .qty-toggle button { background:none;border:none;padding:0 8px;color:#64748b;font-weight:bold;cursor:pointer;height:100%;display:flex;align-items:center;font-size:1rem; }
    .qty-toggle button:hover { background:#f1f5f9;color:#0f172a; }
    .qty-toggle input { width:38px;text-align:center;border:none;padding:0;font-size:0.85rem;font-weight:600;height:100%; }
    .qty-toggle input:focus { outline:none; }

    /* Delete button - always visible */
    .del-btn { display:inline-flex !important;align-items:center;justify-content:center;background:none;border:1px solid #fca5a5;color:#dc2626;cursor:pointer;padding:4px 7px;border-radius:5px;font-size:0.9rem;line-height:1; }
    .del-btn:hover { background:#fee2e2;border-color:#dc2626; }
    #billItemsTable td:last-child { text-align:center !important; }

    /* Hide spinners */
    input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance:none;margin:0; }
    input[type=number] { -moz-appearance:textfield; }

    /* Expiry */
    .exp-red    { color:#dc2626;font-weight:700; }
    .exp-orange { color:#ea580c;font-weight:700; }
    .exp-yellow { color:#d97706;font-weight:600; }
    .exp-green  { color:#16a34a; }

    /* Stock badges */
    .badge-soft-warning { background:#fef3c7;color:#92400e;font-weight:600;font-size:0.7rem; }
    .badge-soft-danger  { background:#fee2e2;color:#991b1b;font-weight:600;font-size:0.7rem; }
    .badge-soft-success { background:#d1fae5;color:#065f46;font-weight:600;font-size:0.7rem; }

    .unit-select { height:26px;font-size:0.76rem;padding:0 0.3rem;min-width:88px; }
    .item-row-num { color:#94a3b8;font-size:0.73rem;font-weight:700; }
    .cart-medicine-name { font-weight:700;font-size:0.84rem;color:#0f172a; }
    .cart-medicine-sub  { font-size:0.71rem;color:#64748b; }
    .row-disc-input { width:48px;font-size:0.8rem;text-align:right;padding:2px 4px;border:1px solid #e2e8f0;border-radius:4px;background:#fff; }

    /* Per-item tax toggle */
    .btn-add-tax { background:none;border:none;padding:0;font-size:0.7rem;font-weight:600;color:#3b82f6;cursor:pointer;line-height:1; }
    .btn-add-tax:hover { text-decoration:underline; }

    .summary-cell { flex-shrink:0; }
    kbd { background:#e2e8f0;border-radius:3px;padding:1px 5px;font-size:0.7rem;color:#374151; }
</style>
@endpush

@push('scripts')
<script>
const initialProducts  = @json($products);
const productSearchUrl = @json(route('invoice-products.search'));
const resumeId         = @json($resumeId);
let rowIndex    = 0;
let searchTimer = null;
const productCache = new Map();
let kbIndex = -1; // keyboard nav index in search results

function escapeHtml(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}
function formatQty(v) { const n=parseFloat(v)||0; return Number.isInteger(n)?String(n):n.toFixed(2); }
function money(v) { return 'Rs. '+(parseFloat(v)||0).toFixed(0); }

function normalizeProduct(p) {
    const n = {
        id: Number(p.id), name: p.name||'Product',
        generic_name: p.generic_name||'',
        sku: p.sku||'', barcode: p.barcode||'',
        batch_number: p.batch_number||'',
        mfg_date: p.mfg_date||'',
        expiry_date: p.expiry_date||'',
        expiry_status: p.expiry_status||null,
        price: parseFloat(p.price)||0, stock: parseInt(p.stock)||0,
        category_id: p.category_id||p.category?.id||'',
        category_name: p.category_name||p.category?.name||'',
        almari: p.almari||'', khana: p.khana||'', row: p.row||'',
        product_type: p.product_type||'medicine',
        tablets_per_strip: Math.max(1, parseInt(p.tablets_per_strip)||1),
        strips_per_box: Math.max(1, parseInt(p.strips_per_box)||1),
        units_per_box: Math.max(1, parseInt(p.units_per_box)||1),
        volume: p.volume||'',
    };
    n.low_stock_threshold = parseInt(p.low_stock_threshold)||0;
    n.stock_boxes = p.stock_boxes !== undefined
        ? parseFloat(p.stock_boxes)||0
        : (n.product_type==='general' ? n.stock : (n.product_type==='liquid' ? n.stock/n.units_per_box : n.stock/Math.max(1,n.tablets_per_strip*n.strips_per_box)));
    n.stock_label = p.stock_label || `${formatQty(n.stock_boxes)} ${n.product_type==='general'?'pcs':'Box'}`;
    productCache.set(String(n.id), n);
    return n;
}

function unitMultiplier(product, unitType) {
    if (unitType==='box')   return product.product_type==='liquid' ? product.units_per_box : (product.tablets_per_strip*product.strips_per_box);
    if (unitType==='strip') return product.tablets_per_strip;
    return 1;
}
function unitOptionsFor(product) {
    const opts = [{ value:'tablet', label: product.product_type==='liquid'?'Piece/Bottle':'Tablet/Pcs' }];
    if (product.product_type==='general') return opts;
    if (product.product_type==='liquid') {
        if (product.units_per_box>1) opts.push({ value:'box', label:`Box (${product.units_per_box})` });
        return opts;
    }
    if (product.tablets_per_strip>1) opts.push({ value:'strip', label:`Strip (${product.tablets_per_strip})` });
    if (product.tablets_per_strip>1 && product.strips_per_box>1)
        opts.push({ value:'box', label:`Box (${product.tablets_per_strip*product.strips_per_box})` });
    return opts;
}

initialProducts.map(normalizeProduct);

// ── Helpers ───────────────────────────────────────────────────────────
function getExpiryText(p, field='expiry_date') {
    const dateStr = field==='mfg_date' ? p.mfg_date : p.expiry_date;
    if (!dateStr) return '<span class="text-muted">—</span>';
    const d = new Date(dateStr);
    const mon = d.toLocaleString('default',{month:'short'});
    const yr  = d.getFullYear();
    if (field==='mfg_date') return `<span class="text-muted">${mon} ${yr}</span>`;
    let cls='exp-green';
    if (p.expiry_status==='expired'||p.expiry_status==='red') cls='exp-red';
    else if (p.expiry_status==='orange') cls='exp-orange';
    else if (p.expiry_status==='yellow') cls='exp-yellow';
    return `<span class="${cls}">${mon} ${yr}</span>`;
}

function getStockBadge(p) {
    let cls='badge-soft-success';
    if (p.low_stock_threshold && p.stock<=p.low_stock_threshold) cls='badge-soft-danger';
    else if (p.low_stock_threshold && p.stock<=(p.low_stock_threshold*2)) cls='badge-soft-warning';
    return `<span class="badge ${cls} rounded-pill">${escapeHtml(p.stock_label)}</span>`;
}

function getPackLabel(p) {
    if (p.product_type==='liquid') return p.volume||'Bottle';
    if (p.product_type==='general') return 'Piece';
    const ts=p.tablets_per_strip, sb=p.strips_per_box;
    if (ts>1 && sb>1) return `${ts*sb} Tab`;
    if (ts>1) return `${ts} Tab`;
    return 'Tablet';
}

function getLocationText(p) {
    const parts=[];
    if (p.almari) parts.push(p.almari);
    if (p.khana)  parts.push(p.khana);
    if (p.row)    parts.push(p.row);
    return parts.length ? escapeHtml(parts.join(' > ')) : '<span class="text-muted">—</span>';
}

// ── Build one search-result <tr> HTML ────────────────────────────────
function buildSearchRow(p, colspan=10) {
    return `
    <tr data-product-id="${p.id}">
        <td class="ps-3">
            <div class="cart-medicine-name">${escapeHtml(p.name)}</div>
            <div class="cart-medicine-sub">${escapeHtml(p.generic_name||'—')}</div>
        </td>
        <td class="text-muted small">${escapeHtml(p.generic_name||'—')}</td>
        <td class="text-muted small">${escapeHtml(getPackLabel(p))}</td>
        <td class="text-muted small" style="font-size:0.75rem;">${escapeHtml(p.category_name||'—')}</td>
        <td style="font-size:0.75rem;">${getLocationText(p)}</td>
        <td>${getExpiryText(p,'mfg_date')}</td>
        <td>${getExpiryText(p,'expiry_date')}</td>
        <td>${getStockBadge(p)}</td>
        <td class="fw-semibold">Rs.${p.price}</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-success px-2 py-0 fw-semibold add-from-search" data-id="${p.id}" onclick="event.stopPropagation();handleAddClick(${p.id})">+&nbsp;Add</button>
        </td>
    </tr>`;
}

// ── Render search results ─────────────────────────────────────────────
function renderSearchResultsTable(items, substitutes=[]) {
    const wrap    = document.getElementById('searchResultsWrap');
    const body    = document.getElementById('searchResultsBody');
    const status  = document.getElementById('productSearchStatus');
    const subSec  = document.getElementById('substituteSection');
    const subBody = document.getElementById('substituteResults');
    const moreEl  = document.getElementById('moreResultsCount');
    const viewAll = document.getElementById('viewAllResultsLink');

    subSec.classList.add('d-none');
    kbIndex = -1;

    if (!items.length && !substitutes.length) {
        body.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-3 small">No medicines found for this search.</td></tr>';
        status.textContent = 'No results';
        wrap.classList.remove('d-none');
        return;
    }

    const visible = items.slice(0, 12);
    status.textContent = items.length
        ? `Showing ${visible.length} of ${items.length} results`
        : 'No exact match — see substitutes below';

    body.innerHTML = visible.map(p => buildSearchRow(p)).join('');

    // Click on row (not on button) to add
    body.querySelectorAll('tr[data-product-id]').forEach(tr => {
        tr.addEventListener('click', function() {
            handleAddClick(Number(this.dataset.productId));
        });
    });

    viewAll.textContent = items.length > 12 ? `View all ${items.length} results` : '';
    moreEl.textContent  = items.length > 12 ? `+${items.length-12} more` : '';

    if (substitutes.length) {
        subSec.classList.remove('d-none');
        subBody.innerHTML = substitutes.map(p => buildSearchRow(p)).join('');
        subBody.querySelectorAll('tr[data-product-id]').forEach(tr => {
            tr.addEventListener('click', function() { handleAddClick(Number(this.dataset.productId)); });
        });
    }

    wrap.classList.remove('d-none');
}

// ── Keyboard navigation in search results ─────────────────────────────
document.getElementById('productSearchInput').addEventListener('keydown', function(e) {
    const rows = document.querySelectorAll('#searchResultsBody tr[data-product-id]');
    if (!rows.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        kbIndex = Math.min(kbIndex + 1, rows.length - 1);
        updateKbHighlight(rows);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        kbIndex = Math.max(kbIndex - 1, 0);
        updateKbHighlight(rows);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (kbIndex >= 0 && rows[kbIndex]) {
            const id = Number(rows[kbIndex].dataset.productId);
            handleAddClick(id);
            // flash the row
            rows[kbIndex].style.background = '#bbf7d0';
            setTimeout(() => { rows[kbIndex] && (rows[kbIndex].style.background = ''); }, 400);
        }
    } else if (e.key === 'Escape') {
        closeSearch();
    }
});

function updateKbHighlight(rows) {
    rows.forEach((r, i) => r.classList.toggle('kb-active', i === kbIndex));
    if (rows[kbIndex]) {
        rows[kbIndex].scrollIntoView({ block:'nearest' });
    }
}

function closeSearch() {
    document.getElementById('searchResultsWrap').classList.add('d-none');
    document.getElementById('productSearchInput').value = '';
    kbIndex = -1;
}

document.getElementById('closeSearchResults').addEventListener('click', closeSearch);

function loadProducts() {
    const params     = new URLSearchParams();
    const categoryId = document.getElementById('categorySelect').value;
    const search     = document.getElementById('productSearchInput').value.trim();
    const status     = document.getElementById('productSearchStatus');

    if (!search && !categoryId) {
        document.getElementById('searchResultsWrap').classList.add('d-none');
        return;
    }
    if (categoryId) params.set('category_id', categoryId);
    if (search)     params.set('q', search);

    status.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Searching...';
    document.getElementById('searchResultsWrap').classList.remove('d-none');

    fetch(`${productSearchUrl}?${params.toString()}`, { headers:{ 'Accept':'application/json' } })
        .then(r => r.json())
        .then(data => {
            const products    = (data.products    || data).map(normalizeProduct);
            const substitutes = (data.substitutes || []).map(normalizeProduct);
            renderSearchResultsTable(products, substitutes);
        })
        .catch(() => {
            document.getElementById('productSearchStatus').innerHTML = '<span class="text-danger">Search failed.</span>';
        });
}

document.getElementById('productSearchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    if (this.value.trim().length >= 1) {
        searchTimer = setTimeout(loadProducts, 280);
    } else {
        document.getElementById('searchResultsWrap').classList.add('d-none');
        kbIndex = -1;
    }
});
document.getElementById('categorySelect').addEventListener('change', loadProducts);

// Barcode toggle
document.getElementById('barcodeScannerInput').addEventListener('keypress', function(e) {
    if (e.which==13) { barcodeSearch(); e.preventDefault(); }
});
document.getElementById('barcodeToggleBtn').addEventListener('click', () => {
    const bi = document.getElementById('barcodeScannerInput');
    const si = document.getElementById('productSearchInput');
    if (bi.classList.contains('d-none')) {
        bi.classList.remove('d-none'); si.classList.add('d-none'); bi.focus();
        document.getElementById('searchResultsWrap').classList.add('d-none');
    } else {
        bi.classList.add('d-none'); si.classList.remove('d-none'); si.focus();
    }
});

window.handleAddClick = function(id) {
    const product = productCache.get(String(id));
    if (product) addProductRow(product);
}

// ── Add Product Row ───────────────────────────────────────────────────
function addProductRow(product, qty=1) {
    product = normalizeProduct(product);

    // If already in bill, just increment qty
    for (const input of document.querySelectorAll('#productRows .product-id-input')) {
        if (input.value == product.id) {
            const row          = input.closest('tr');
            const idx          = parseInt(row.id.replace('row_',''));
            const unitQtyInput = row.querySelector('.unit-qty-input');
            const multiplier   = unitMultiplier(product, row.querySelector('.unit-type-hidden').value);
            const newUnitQty   = (parseInt(unitQtyInput.value)||0) + qty;
            if (newUnitQty * multiplier <= product.stock) {
                unitQtyInput.value = newUnitQty;
                updateRow(idx); calcTotal();
                row.style.transition = 'background 0.15s';
                row.style.background = '#f0fdf4';
                setTimeout(() => row.style.background = '', 400);
            } else {
                alert(`Cannot add more. Only ${product.stock} in stock.`);
            }
            return;
        }
    }

    document.getElementById('emptyRow')?.remove();
    const idx = rowIndex;
    const tr  = document.createElement('tr');
    tr.id = 'row_'+idx;
    tr.dataset.stock           = product.stock;
    tr.dataset.basePrice       = product.price;
    tr.dataset.tabletsPerStrip = product.tablets_per_strip;
    tr.dataset.stripsPerBox    = product.strips_per_box;
    tr.dataset.unitsPerBox     = product.units_per_box;
    tr.dataset.productType     = product.product_type;

    const unitOptions    = unitOptionsFor(product);
    const unitSelectHtml = unitOptions.length > 1
        ? `<select class="unit-select form-select form-select-sm mb-1" onchange="updateRow(${idx});updateQtyLabel(${idx});calcTotal();">${unitOptions.map(o=>`<option value="${o.value}">${o.label}</option>`).join('')}</select>`
        : `<div class="qty-unit-label small fw-semibold mb-1" style="font-size:0.74rem;color:#475569;">${unitOptions[0].label}</div>`;

    const locParts = [];
    if (product.almari) locParts.push(product.almari);
    if (product.khana)  locParts.push(product.khana);
    if (product.row)    locParts.push(product.row);
    const locationText = locParts.join(' > ') || '—';

    tr.innerHTML = `
        <td class="ps-3 item-row-num"></td>
        <td>
            <input type="hidden" name="items[${idx}][product_id]" class="product-id-input" value="${product.id}">
            <div class="cart-medicine-name text-truncate" title="${escapeHtml(product.name)}" style="max-width:175px;">${escapeHtml(product.name)}</div>
            <div class="cart-medicine-sub">${escapeHtml(product.generic_name||'—')}</div>
        </td>
        <td><span class="text-muted small">${escapeHtml(product.batch_number||'—')}</span></td>
        <td>${getExpiryText(product,'expiry_date')}</td>
        <td><span class="text-secondary" style="font-size:0.73rem;">${escapeHtml(locationText)}</span></td>
        <td class="text-center">
            ${unitSelectHtml}
            <input type="hidden" class="unit-type-hidden" name="items[${idx}][unit_type]" value="tablet">
            <div class="qty-toggle mx-auto" style="width:96px;">
                <button type="button" onclick="changeQty(${idx},-1)">−</button>
                <input type="number" class="unit-qty-input" value="${qty}" min="1" onchange="updateRow(${idx});calcTotal();" required>
                <button type="button" onclick="changeQty(${idx},1)">+</button>
            </div>
            <input type="hidden" name="items[${idx}][qty]"      class="qty-input"      value="${qty}">
            <input type="hidden" name="items[${idx}][unit_qty]" class="unit-qty-hidden" value="${qty}">
        </td>
        <td class="text-end pe-2">
            <input type="hidden" name="items[${idx}][price]" class="price-input" value="${product.price}">
            <span class="small fw-semibold unit-price-display">Rs.${product.price}</span>
        </td>
        <td class="text-end pe-1" style="white-space:nowrap;">
            <input type="number" name="items[${idx}][discount]" class="row-disc-input" value="0" min="0" max="100" step="0.01" onchange="updateRow(${idx});calcTotal()">
            <div class="mt-1" style="text-align:right;">
                <button type="button" class="btn-add-tax" id="taxToggleBtn_${idx}" onclick="toggleTaxInput(${idx})" title="Add item tax">+Tax%</button>
            </div>
            <div id="taxInputWrap_${idx}" class="d-none mt-1" style="text-align:right;">
                <input type="number" name="items[${idx}][tax]" class="row-tax-input" id="taxInput_${idx}" value="0" min="0" step="0.01" placeholder="Tax%" onchange="updateRow(${idx});calcTotal()" style="width:52px;font-size:0.78rem;padding:2px 4px;border:1px solid #93c5fd;border-radius:4px;text-align:right;">
                <span style="font-size:0.72rem;color:#64748b;">%</span>
            </div>
        </td>
        <td class="text-end pe-2 fw-bold" id="rowTotal_${idx}">Rs.${(product.price*qty).toFixed(0)}</td>
        <td class="text-center">
            <button type="button" class="del-btn" onclick="removeRow(${idx})" title="Remove item">
                <i class="bi bi-trash-fill"></i>
            </button>
        </td>`;

    document.getElementById('productRows').appendChild(tr);
    rowIndex++;
    updateRow(idx); calcTotal(); updateRowNumbers();
}

window.changeQty = function(idx, delta) {
    const row = document.getElementById(`row_${idx}`);
    if (!row) return;
    const unitType  = row.querySelector('.unit-type-hidden').value;
    const tps       = parseInt(row.dataset.tabletsPerStrip)||1;
    const spb       = parseInt(row.dataset.stripsPerBox)||1;
    const upb       = parseInt(row.dataset.unitsPerBox)||1;
    const ptype     = row.dataset.productType;
    const multiplier= unitType==='box' ? (ptype==='liquid' ? upb : tps*spb) : (unitType==='strip' ? tps : 1);
    const unitQtyInput = row.querySelector('.unit-qty-input');
    const maxStock  = parseInt(row.dataset.stock)||0;
    const maxUnitQty= maxStock && multiplier ? Math.floor(maxStock/multiplier) : Infinity;
    let newQty = (parseInt(unitQtyInput.value)||0) + delta;
    if (newQty<1) newQty=1;
    if (maxUnitQty && newQty>maxUnitQty) newQty=maxUnitQty;
    unitQtyInput.value=newQty;
    updateRow(idx); calcTotal();
}

function barcodeSearch() {
    const code = document.getElementById('barcodeScannerInput').value.trim();
    if (!code) return;
    let found = false;
    for (const [, p] of productCache.entries()) {
        if (p.barcode===code || p.sku===code || p.name.toLowerCase()===code.toLowerCase()) {
            addProductRow(p); document.getElementById('barcodeScannerInput').value=''; found=true; break;
        }
    }
    if (found) return;
    fetch(`/products/barcode/${encodeURIComponent(code)}`, { headers:{ 'Accept':'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (data.found) { addProductRow(normalizeProduct(data.product)); }
            else if (data.reason==='expired') alert(`Expired product: ${data.name}`);
            else alert('Product not found.');
            document.getElementById('barcodeScannerInput').value='';
            document.getElementById('barcodeScannerInput').focus();
        });
}

function updateRow(idx) {
    const row = document.getElementById(`row_${idx}`);
    if (!row) return;
    const unitSelect  = row.querySelector('.unit-select');
    const unitType    = unitSelect ? unitSelect.value : 'tablet';
    const tps         = parseInt(row.dataset.tabletsPerStrip)||1;
    const spb         = parseInt(row.dataset.stripsPerBox)||1;
    const upb         = parseInt(row.dataset.unitsPerBox)||1;
    const ptype       = row.dataset.productType;
    const multiplier  = unitType==='box' ? (ptype==='liquid' ? upb : tps*spb) : (unitType==='strip' ? tps : 1);
    const unitQtyInput= row.querySelector('.unit-qty-input');
    const maxStock    = parseInt(row.dataset.stock)||0;
    let unitQty       = parseInt(unitQtyInput?.value)||1;
    const maxUnitQty  = maxStock ? Math.max(1, Math.floor(maxStock/multiplier)) : unitQty;
    if (unitQty>maxUnitQty) { unitQty=maxUnitQty; unitQtyInput.value=unitQty; }
    const baseQty   = unitQty * multiplier;
    const basePrice = parseFloat(row.dataset.basePrice)||0;
    const unitPrice = basePrice * multiplier;
    row.querySelector('.qty-input').value        = baseQty;
    row.querySelector('.unit-qty-hidden').value  = unitQty;
    row.querySelector('.unit-type-hidden').value = unitType;
    const pd = row.querySelector('.unit-price-display');
    if (pd) {
        let unitLabel = '';
        if (unitType === 'strip')   unitLabel = ' <span style="font-size:0.68rem;color:#94a3b8;font-weight:400;">/strip</span>';
        else if (unitType === 'box') unitLabel = ' <span style="font-size:0.68rem;color:#94a3b8;font-weight:400;">/box</span>';
        pd.innerHTML = 'Rs.' + unitPrice.toFixed(0) + unitLabel;
    }
    const el = document.getElementById(`rowTotal_${idx}`);
    if (el) {
        const disc = parseFloat(row.querySelector('.row-disc-input')?.value)||0;
        const tax  = parseFloat(row.querySelector('.row-tax-input')?.value)||0;
        const total= baseQty * basePrice * (1 - disc/100) * (1 + tax/100);
        el.textContent = 'Rs.'+total.toFixed(0);
    }
}


function removeRow(idx) {
    document.getElementById('row_'+idx)?.remove();
    if (!document.querySelectorAll('#productRows tr[id^="row_"]').length) {
        const e=document.createElement('tr'); e.id='emptyRow';
        e.innerHTML='<td colspan="10" class="text-center text-muted py-5"><i class="bi bi-cart3 fs-1 text-light d-block mb-1"></i><span class="small">Search medicines above and add them to the bill</span></td>';
        document.getElementById('productRows').appendChild(e);
    }
    updateRowNumbers(); calcTotal();
}

function updateRowNumbers() {
    document.querySelectorAll('#productRows tr[id^="row_"]').forEach((tr,i) => {
        const el=tr.querySelector('.item-row-num');
        if (el) el.textContent=i+1;
    });
    const cnt=document.querySelectorAll('#productRows tr[id^="row_"]').length;
    document.getElementById('itemCountDisplay').textContent=`Total items: ${cnt}`;
}

// ── Toggle per-item tax input ────────────────────────
window.toggleTaxInput = function(idx) {
    const wrap = document.getElementById(`taxInputWrap_${idx}`);
    const btn  = document.getElementById(`taxToggleBtn_${idx}`);
    if (!wrap) return;
    const isOpen = !wrap.classList.contains('d-none');
    if (isOpen) {
        // Close — reset tax to 0
        wrap.classList.add('d-none');
        btn.textContent = '+Tax%';
        btn.style.color = '#3b82f6';
        const inp = document.getElementById(`taxInput_${idx}`);
        if (inp) { inp.value = 0; }
        updateRow(idx); calcTotal();
    } else {
        // Open
        wrap.classList.remove('d-none');
        btn.textContent = '✕Tax%';
        btn.style.color = '#dc2626';
        const inp = document.getElementById(`taxInput_${idx}`);
        if (inp) inp.focus();
    }
}
window.updateQtyLabel = function(idx) {
    const row      = document.getElementById(`row_${idx}`);
    if (!row) return;
    const unitSelect = row.querySelector('.unit-select');
    if (!unitSelect) return;
    const unitType = unitSelect.value;
    const label    = row.querySelector('.qty-unit-label');
    if (label) {
        const tps  = parseInt(row.dataset.tabletsPerStrip)||1;
        const spb  = parseInt(row.dataset.stripsPerBox)||1;
        const upb  = parseInt(row.dataset.unitsPerBox)||1;
        const ptype= row.dataset.productType;
        if (unitType === 'strip')       label.textContent = `Strip (${tps} tab)`;
        else if (unitType === 'box')    label.textContent = ptype==='liquid' ? `Box (${upb} btl)` : `Box (${tps*spb} tab)`;
        else                            label.textContent = ptype==='liquid' ? 'Piece/Bottle' : 'Tablet/Pcs';
    }
}

function calcTotal() {
    let subtotal=0;
    document.querySelectorAll('#productRows tr[id^="row_"]').forEach(row => {
        const baseQty   = parseFloat(row.querySelector('.qty-input')?.value)||0;
        const basePrice = parseFloat(row.querySelector('.price-input')?.value)||0;
        const disc      = parseFloat(row.querySelector('.row-disc-input')?.value)||0;
        const tax       = parseFloat(row.querySelector('.row-tax-input')?.value)||0;
        const lineTotal = baseQty * basePrice * (1 - disc/100) * (1 + tax/100);
        subtotal += lineTotal;
        const el=document.getElementById(`rowTotal_${parseInt(row.id.replace('row_',''))}`);
        if (el) el.textContent='Rs.'+lineTotal.toFixed(0);
    });
    const discountVal  = parseFloat(document.getElementById('discountInput').value)||0;
    const discountType = document.getElementById('discountType').value;
    const taxPct       = parseFloat(document.getElementById('taxInput').value)||0;
    const serviceFee   = parseFloat(document.getElementById('serviceFeeInput').value)||0;
    const discountAmt  = discountType==='percent' ? (subtotal*discountVal/100) : discountVal;
    const afterDiscount= Math.max(0, subtotal - discountAmt);
    const taxAmt       = afterDiscount * taxPct / 100;
    const total        = afterDiscount + taxAmt + serviceFee;

    document.getElementById('subtotalDisplay').textContent  = money(subtotal);
    document.getElementById('taxAmountDisplay').textContent = taxAmt>0 ? `Rs.${taxAmt.toFixed(0)} tax` : '';
    document.getElementById('totalDisplay').textContent     = money(total);
    document.getElementById('subtotalInput').value          = subtotal.toFixed(2);
    document.getElementById('totalInput').value             = total.toFixed(2);

    const received = parseFloat(document.getElementById('amountReceived').value)||0;
    document.getElementById('changeDisplay').textContent = money(Math.max(0, received-total));
}

document.getElementById('discountInput').addEventListener('input', calcTotal);
document.getElementById('discountType').addEventListener('change', calcTotal);
document.getElementById('taxInput').addEventListener('input', calcTotal);
document.getElementById('amountReceived').addEventListener('input', calcTotal);

// ── Clear All ─────────────────────────────────────────────────────────
document.getElementById('clearAllBtn').addEventListener('click', () => {
    if (!document.querySelectorAll('#productRows tr[id^="row_"]').length) return;
    if (!confirm('Clear all items from the bill?')) return;
    document.querySelectorAll('#productRows tr[id^="row_"]').forEach(tr=>tr.remove());
    const e=document.createElement('tr'); e.id='emptyRow';
    e.innerHTML='<td colspan="11" class="text-center text-muted py-5"><i class="bi bi-cart3 fs-1 text-light d-block mb-1"></i><span class="small">Search medicines above and add them to the bill</span></td>';
    document.getElementById('productRows').appendChild(e);
    updateRowNumbers(); calcTotal();
});

// ── Note toggle ───────────────────────────────────────────────────────
document.getElementById('addNoteBtn').addEventListener('click', () => {
    document.getElementById('noteRow').classList.toggle('d-none');
    if (!document.getElementById('noteRow').classList.contains('d-none'))
        document.getElementById('noteTextarea').focus();
});
document.getElementById('noteTextarea').addEventListener('input', function() {
    document.getElementById('notesInput').value = this.value;
});

// ── Hold Bill ─────────────────────────────────────────────────────────
function triggerHoldBill() {
    if (!document.querySelectorAll('#productRows tr[id^="row_"]').length) {
        alert('Pehle koi item add karein.'); return;
    }
    const modal = new bootstrap.Modal(document.getElementById('holdBillModal'));
    document.getElementById('holdLabelInput').value='';
    modal.show();
    setTimeout(()=>document.getElementById('holdLabelInput').focus(), 400);
}
document.getElementById('holdBillBtnTop').addEventListener('click', triggerHoldBill);
document.getElementById('holdBillBtnAlt').addEventListener('click', triggerHoldBill);

document.getElementById('confirmHoldBtn').addEventListener('click', () => {
    const rows = document.querySelectorAll('#productRows tr[id^="row_"]');
    if (!rows.length) return;
    const label   = document.getElementById('holdLabelInput').value.trim();
    const now     = new Date();
    const timeStr = now.getHours().toString().padStart(2,'0')+':'+now.getMinutes().toString().padStart(2,'0');
    document.getElementById('holdFormLabel').value        = label||('Hold #'+timeStr);
    document.getElementById('holdFormCustomer').value     = document.getElementById('customerSelect').value;
    document.getElementById('holdFormDiscount').value     = document.getElementById('discountInput').value;
    document.getElementById('holdFormDiscountType').value = document.getElementById('discountType').value;
    document.getElementById('holdFormTax').value          = document.getElementById('taxInput').value;
    document.getElementById('holdFormServiceFee').value   = document.getElementById('serviceFeeInput').value;
    document.getElementById('holdFormPayment').value      = document.getElementById('paymentMethodSelect').value;
    document.getElementById('holdFormNotes').value        = document.getElementById('notesInput').value;
    const container=document.getElementById('holdFormItems'); container.innerHTML='';
    rows.forEach((row,idx) => {
        [['product_id',row.querySelector('.product-id-input')?.value||''],
         ['qty',row.querySelector('.qty-input')?.value||'1'],
         ['price',row.querySelector('.price-input')?.value||'0'],
         ['unit_type',row.querySelector('.unit-type-hidden')?.value||'tablet'],
         ['unit_qty',row.querySelector('.unit-qty-hidden')?.value||'1'],
        ].forEach(([name,val]) => {
            const inp=document.createElement('input');
            inp.type='hidden'; inp.name=`items[${idx}][${name}]`; inp.value=val;
            container.appendChild(inp);
        });
    });
    const modal=bootstrap.Modal.getInstance(document.getElementById('holdBillModal'));
    if (modal) modal.hide();
    setTimeout(()=>document.getElementById('holdBillForm').submit(), 200);
});
document.getElementById('holdLabelInput').addEventListener('keydown', e => {
    if (e.key==='Enter') document.getElementById('confirmHoldBtn').click();
});

// ── Print ─────────────────────────────────────────────────────────────
document.getElementById('printBillBtn').addEventListener('click', () => {
    alert('Complete the sale first, then print from the invoice page.');
});

// ── Submit guard ──────────────────────────────────────────────────────
document.getElementById('invoiceForm').addEventListener('submit', event => {
    if (!document.querySelectorAll('#productRows tr[id^="row_"]').length) {
        event.preventDefault();
        alert('Please add at least one product before completing the sale.');
        document.getElementById('productSearchInput').focus();
    }
});

// ── Resume held bill ──────────────────────────────────────────────────
if (resumeId) {
    fetch(`/held-invoices/${resumeId}`, { headers:{ 'Accept':'application/json' } })
        .then(r=>r.json())
        .then(data => {
            if (data.customer_id) {
                const sel=document.getElementById('customerSelect');
                for (const opt of sel.options) { if (opt.value==data.customer_id) { sel.value=data.customer_id; break; } }
            }
            if (data.discount)      document.getElementById('discountInput').value=data.discount;
            if (data.discount_type) document.getElementById('discountType').value=data.discount_type;
            if (data.tax)           document.getElementById('taxInput').value=data.tax;
            if (data.service_fee)   document.getElementById('serviceFeeInput').value=data.service_fee;
            (data.items||[]).forEach(item => {
                const cached=productCache.get(String(item.product_id));
                const unitQty=parseInt(item.unit_qty)||parseInt(item.qty)||1;
                const base=cached ? {...cached, price:item.price}
                    : {id:item.product_id,name:'Product #'+item.product_id,generic_name:'',sku:'',barcode:'',batch_number:'',mfg_date:'',expiry_date:'',expiry_status:null,price:item.price,stock:9999,category_name:'',almari:'',khana:'',row:'',product_type:'medicine',tablets_per_strip:1,strips_per_box:1,units_per_box:1};
                addProductRow(base, unitQty);
                if (item.unit_type && item.unit_type!=='tablet') {
                    const row=document.querySelector('#productRows tr[id^="row_"]:last-child');
                    const select=row?.querySelector('.unit-select');
                    if (select) { select.value=item.unit_type; updateRow(parseInt(row.id.replace('row_',''))); calcTotal(); }
                }
            });
            fetch(`/held-invoices/${resumeId}`,{method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'}});
        });
}
</script>
@endpush
@endsection
