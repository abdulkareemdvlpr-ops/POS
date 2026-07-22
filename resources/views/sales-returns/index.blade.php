@extends('layouts.app')

@section('title', 'Sales Returns')

@push('styles')
<style>
#itemsBody .return-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; }
#itemsBody .return-item:last-child { margin-bottom: 0; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Sales Returns</h4>
        <p class="text-muted small mb-0">Process customer returns — stock is automatically restored.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newReturnModal">
        <i class="bi bi-plus-circle me-1"></i>New Return
    </button>
</div>

{{-- Returns Table --}}
<div class="card">
    <div class="card-header py-3 fw-bold">Return History</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Items</th>
                        <th>Refund Amount</th>
                        <th>Reason</th>
                        <th>Date</th>
                        @if(auth()->user()->isAdmin())<th class="pe-3">Action</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                    <tr>
                        <td class="ps-3 fw-semibold">#{{ $ret->id }}</td>
                        <td>{{ $ret->invoice_id ? '#'.$ret->invoice_id : '—' }}</td>
                        <td>{{ $ret->customer->name ?? 'Walk-in' }}</td>
                        <td class="text-muted small">{{ $ret->cashier->name ?? '—' }}</td>
                        <td>
                            <ul class="mb-0 ps-3 small">
                                @foreach($ret->items as $item)
                                <li>{{ $item['name'] }} &times; {{ $item['qty'] }}</li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="fw-bold text-success">Rs. {{ number_format($ret->total_refund, 2) }}</td>
                        <td class="text-muted small">{{ $ret->reason ?: '—' }}</td>
                        <td class="text-muted small">{{ $ret->created_at->format('d M Y h:i A') }}</td>
                        @if(auth()->user()->isAdmin())
                        <td class="pe-3">
                            <form action="{{ route('sales-returns.destroy', $ret) }}" method="POST"
                                  onsubmit="return confirm('Undo this return? Stock will be deducted again.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">Undo</button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">No returns yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($returns->hasPages())
    <div class="card-footer">{{ $returns->links() }}</div>
    @endif
</div>

{{-- New Return Modal --}}
<div class="modal fade" id="newReturnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Process Sales Return</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('sales-returns.store') }}" method="POST" id="returnForm">
                @csrf
                <div class="modal-body">

                    {{-- Load from Invoice --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Load from Invoice (optional)</label>
                        <div class="input-group">
                            <input type="number" id="invoiceSearch" class="form-control" placeholder="Invoice ID e.g. 42" min="1">
                            <button type="button" class="btn btn-outline-primary" onclick="loadInvoice()">Load</button>
                        </div>
                        <input type="hidden" name="invoice_id" id="invoiceIdField">
                        <div id="invoiceInfo" class="alert alert-info mt-2 py-2 small d-none"></div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reason</label>
                            <input type="text" name="reason" class="form-control" placeholder="e.g. Wrong medicine given, Duplicate entry">
                        </div>
                    </div>

                    {{-- Return Items --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Return Items</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">+ Add Item</button>
                    </div>
                    <div id="itemsBody"></div>

                    <div class="d-flex justify-content-end mt-3">
                        <span class="fw-bold fs-5" id="totalDisplay">Total Refund: Rs. 0.00</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Process Return &amp; Restore Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var rowIndex = 0;

function addRow(productId, productName, qty, price) {
    productId   = productId   || '';
    productName = productName || '';
    qty         = qty         || 1;
    price       = price       || '';

    var html = '<div class="return-item" id="row-' + rowIndex + '">' +
        '<div class="row g-2 align-items-end">' +
            '<div class="col-md-5">' +
                '<label class="form-label fw-semibold small mb-1">Medicine Name</label>' +
                '<input type="hidden" name="items[' + rowIndex + '][product_id]" value="' + productId + '" id="pid-' + rowIndex + '">' +
                '<input type="text" class="form-control form-control-sm" placeholder="Search medicine..." ' +
                    'value="' + productName + '" ' +
                    'oninput="searchProduct(this, ' + rowIndex + ')" autocomplete="off">' +
                '<div class="product-dropdown list-group mt-1" id="dd-' + rowIndex + '" style="position:absolute;z-index:999;width:280px;display:none;"></div>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label class="form-label fw-semibold small mb-1">Qty</label>' +
                '<input type="number" name="items[' + rowIndex + '][qty]" class="form-control form-control-sm" ' +
                    'value="' + qty + '" min="1" oninput="recalcTotal()">' +
            '</div>' +
            '<div class="col-md-3">' +
                '<label class="form-label fw-semibold small mb-1">Price (Rs.)</label>' +
                '<input type="number" name="items[' + rowIndex + '][price]" class="form-control form-control-sm" ' +
                    'value="' + price + '" step="0.01" min="0" oninput="recalcTotal()">' +
            '</div>' +
            '<div class="col-md-2">' +
                '<button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeRow(' + rowIndex + ')">Remove</button>' +
            '</div>' +
        '</div>' +
    '</div>';
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', html);
    rowIndex++;
    recalcTotal();
}

function removeRow(i) {
    var el = document.getElementById('row-' + i);
    if (el) el.remove();
    recalcTotal();
}

function recalcTotal() {
    var items  = document.querySelectorAll('#returnForm [name$="[qty]"]');
    var prices = document.querySelectorAll('#returnForm [name$="[price]"]');
    var total  = 0;
    for (var i = 0; i < items.length; i++) {
        total += (parseFloat(items[i].value) || 0) * (parseFloat(prices[i].value) || 0);
    }
    document.getElementById('totalDisplay').textContent = 'Total Refund: Rs. ' + total.toFixed(2);
}

function loadInvoice() {
    var id = document.getElementById('invoiceSearch').value;
    if (!id) return;
    fetch('{{ route('sales-returns.load-invoice') }}?invoice_id=' + id, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.invoice) {
            document.getElementById('invoiceIdField').value = data.invoice.id;
            document.getElementById('invoiceInfo').textContent =
                'Invoice #' + data.invoice.id + ' | Customer: ' + data.invoice.customer +
                ' | Date: ' + data.invoice.date + ' | Total: Rs. ' + parseFloat(data.invoice.total).toFixed(2);
            document.getElementById('invoiceInfo').classList.remove('d-none');
            document.getElementById('itemsBody').innerHTML = '';
            rowIndex = 0;
            data.items.forEach(item => addRow(item.product_id, item.product_name, item.qty_sold, item.price));
        }
    })
    .catch(() => alert('Invoice not found.'));
}

var searchTimeout;
function searchProduct(input, idx) {
    clearTimeout(searchTimeout);
    document.getElementById('pid-' + idx).value = '';
    var q = input.value;
    if (q.length < 2) { document.getElementById('dd-' + idx).style.display = 'none'; return; }
    searchTimeout = setTimeout(function () {
        fetch('{{ route('invoice-products.search') }}?search=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            var dd = document.getElementById('dd-' + idx);
            dd.innerHTML = '';
            var products = data.products || data;
            if (!Array.isArray(products)) products = [];
            products.slice(0, 8).forEach(function (p) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action py-1 small';
                btn.textContent = p.name + ' — Rs.' + p.price;
                btn.onclick = function () {
                    input.value = p.name;
                    document.getElementById('pid-' + idx).value = p.id;
                    // set price
                    var priceInput = input.closest('.return-item').querySelector('[name$="[price]"]');
                    if (priceInput) priceInput.value = p.price;
                    dd.style.display = 'none';
                    recalcTotal();
                };
                dd.appendChild(btn);
            });
            dd.style.display = products.length ? 'block' : 'none';
        });
    }, 300);
}

document.addEventListener('click', function (e) {
    document.querySelectorAll('.product-dropdown').forEach(function (dd) {
        if (!dd.contains(e.target)) dd.style.display = 'none';
    });
});

// Start with one empty row
addRow();
</script>
@endpush
