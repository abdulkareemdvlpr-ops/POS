@extends('layouts.app')

@section('title', 'Record Purchase / Procurement')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Record Purchase / Direct Procurement</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Direct Procurement</a></li>
                <li class="breadcrumb-item active">Record</li>
            </ol>
        </nav>
    </div>
</div>

<form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm">
    @csrf
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white py-3"><span class="fw-bold"><i class="bi bi-info-circle me-2 text-muted"></i>Purchase Details</span></div>
                <div class="card-body">
                    <!-- Billing Destination / Khata (Local distributor credit purchases only) -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase mb-2">Billing Destination (Khata)</label>
                        <div class="alert alert-success py-2 px-3 mb-0 fw-semibold small">
                            <i class="bi bi-journal-text me-1"></i>Udhar Khata (Local Distributor)
                        </div>
                        <input type="hidden" name="billing_destination" value="distributor">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Local Supplier / Distributor <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="credit" selected>Credit (Qist / Udhar)</option>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank / Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount Paid</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Batch details, remarks..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Save Purchase & Add Stock</button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <span class="fw-bold">Purchased Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
                        <i class="bi bi-plus-circle me-1"></i>Add Row
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="itemsTable" style="min-width: 1100px;">
                            <thead>
                                <tr class="bg-light">
                                    <th class="ps-3" style="width: 20%;">Product / Medicine</th>
                                    <th style="width: 8%;">Total Dabba</th>
                                    <th style="width: 10%;">Strips/Box (Units)</th>
                                    <th style="width: 10%;">Tablets / Strip</th>
                                    <th style="width: 11%;">Box MRP</th>
                                    <th style="width: 9%;">Discount %</th>
                                    <th style="width: 11%;">Buy Price / Box</th>
                                    <th style="width: 16%;">Batch # / Expiry</th>
                                    <th class="text-end pe-3" style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="item-row">
                                    <td class="ps-3">
                                        <select name="items[0][product_id]" class="form-select form-select-sm select-product" required>
                                            <option value="">Select Product</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}"
                                                    data-buy-price="{{ $product->isGeneral() ? $product->buy_price : $product->boxBuyPrice() }}"
                                                    data-price="{{ $product->isGeneral() ? $product->price : $product->boxSalePrice() }}"
                                                    data-strips-per-box="{{ $product->strips_per_box ?? 1 }}"
                                                    data-tablets-per-strip="{{ $product->tablets_per_strip ?? 1 }}"
                                                    data-units-per-box="{{ $product->units_per_box ?? 1 }}"
                                                    data-product-type="{{ $product->product_type ?? 'medicine' }}">
                                                    {{ $product->name }} (Stock: {{ $product->formattedStock() }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][boxes]" class="form-control form-control-sm item-boxes" min="1" value="1" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][strips_per_box]" class="form-control form-control-sm item-strips" min="1" value="1" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][tablets_per_strip]" class="form-control form-control-sm item-tablets" min="1" value="1" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="items[0][retail_price]" class="form-control form-control-sm item-mrp" min="0" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm item-discount" min="0" max="100" value="15.00">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="items[0][cost_price]" class="form-control form-control-sm item-cost" min="0" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][batch_number]" class="form-control form-control-sm mb-1" placeholder="Batch">
                                        <input type="date" name="items[0][expiry_date]" class="form-control form-control-sm" placeholder="Expiry">
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
                    <span class="fs-5 fw-semibold text-muted">Grand Total:</span>
                    <span class="fs-4 fw-bold text-primary" id="grandTotal">Rs. 0.00</span>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let rowIndex = 1;
    const itemsTableBody = document.querySelector('#itemsTable tbody');
    const addItemBtn = document.getElementById('addItemBtn');
    const grandTotalSpan = document.getElementById('grandTotal');

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-boxes').value) || 0;
            const cost = parseFloat(row.querySelector('.item-cost').value) || 0;
            total += qty * cost;
        });
        grandTotalSpan.textContent = 'Rs. ' + total.toFixed(2);
    }
    
    window.triggerCalculateTotal = calculateTotal;

    // Load current prices when product is selected
    itemsTableBody.addEventListener('change', function (e) {
        if (e.target.classList.contains('select-product')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const row = e.target.closest('.item-row');
            if (selectedOpt && selectedOpt.value) {
                const buy = parseFloat(selectedOpt.getAttribute('data-buy-price')) || 0;
                const sale = parseFloat(selectedOpt.getAttribute('data-price')) || 0;
                const strips = selectedOpt.getAttribute('data-strips-per-box') || 1;
                const tablets = selectedOpt.getAttribute('data-tablets-per-strip') || 1;
                const units = selectedOpt.getAttribute('data-units-per-box') || 1;
                const type = selectedOpt.getAttribute('data-product-type') || 'medicine';

                row.querySelector('.item-mrp').value = sale.toFixed(2);
                row.querySelector('.item-cost').value = buy.toFixed(2);
                
                const calculatedDiscount = sale > 0 ? ((sale - buy) / sale * 100) : 15.0;
                row.querySelector('.item-discount').value = Math.max(0, calculatedDiscount).toFixed(2);

                if (type === 'general') {
                    row.querySelector('.item-strips').value = 1;
                    row.querySelector('.item-tablets').value = 1;
                    row.querySelector('.item-strips').readOnly = true;
                    row.querySelector('.item-tablets').readOnly = true;
                    row.querySelector('.item-tablets').style.backgroundColor = '#f1f5f9';
                    row.querySelector('.item-strips').style.backgroundColor = '#f1f5f9';
                } else if (type === 'liquid') {
                    row.querySelector('.item-strips').value = units;
                    row.querySelector('.item-tablets').value = 1;
                    row.querySelector('.item-strips').readOnly = false;
                    row.querySelector('.item-tablets').readOnly = true;
                    row.querySelector('.item-tablets').style.backgroundColor = '#f1f5f9';
                    row.querySelector('.item-strips').style.backgroundColor = '';
                } else { // medicine
                    row.querySelector('.item-strips').value = strips;
                    row.querySelector('.item-tablets').value = tablets;
                    row.querySelector('.item-strips').readOnly = false;
                    row.querySelector('.item-tablets').readOnly = false;
                    row.querySelector('.item-tablets').style.backgroundColor = '';
                    row.querySelector('.item-strips').style.backgroundColor = '';
                }
            }
            calculateTotal();
        }
    });

    // Handle dynamic MRP/Discount/Cost recalculations per row
    itemsTableBody.addEventListener('input', function (e) {
        const target = e.target;
        const row = target.closest('.item-row');
        if (!row) return;

        if (target.classList.contains('item-mrp') || target.classList.contains('item-discount')) {
            const mrp = parseFloat(row.querySelector('.item-mrp').value) || 0;
            const discount = parseFloat(row.querySelector('.item-discount').value) || 0;
            const cost = mrp * (1 - discount / 100);
            row.querySelector('.item-cost').value = cost.toFixed(2);
            calculateTotal();
        } else if (target.classList.contains('item-cost')) {
            const cost = parseFloat(row.querySelector('.item-cost').value) || 0;
            const mrp = parseFloat(row.querySelector('.item-mrp').value) || 0;
            if (mrp > 0) {
                const discount = ((mrp - cost) / mrp) * 100;
                row.querySelector('.item-discount').value = Math.max(0, discount).toFixed(2);
            }
            calculateTotal();
        } else if (target.classList.contains('item-boxes')) {
            calculateTotal();
        }
    });

    addItemBtn.addEventListener('click', function () {
        const firstRow = document.querySelector('.item-row');
        const newRow = firstRow.cloneNode(true);

        // Reset values
        newRow.querySelectorAll('input').forEach(input => {
            if (input.type === 'number') {
                if (input.classList.contains('item-boxes') || input.classList.contains('item-strips') || input.classList.contains('item-tablets')) {
                    input.value = 1;
                } else if (input.classList.contains('item-discount')) {
                    input.value = '15.00';
                } else {
                    input.value = '';
                }
            } else {
                input.value = '';
            }
        });
        newRow.querySelector('select').selectedIndex = 0;
        newRow.querySelector('.item-strips').readOnly = false;
        newRow.querySelector('.item-tablets').readOnly = false;
        newRow.querySelector('.item-strips').style.backgroundColor = '';
        newRow.querySelector('.item-tablets').style.backgroundColor = '';

        // Update inputs names with index
        const currentIdx = window.scannedRowIndex || rowIndex;
        newRow.querySelector('.select-product').name = `items[${currentIdx}][product_id]`;
        newRow.querySelector('.item-boxes').name = `items[${currentIdx}][boxes]`;
        newRow.querySelector('.item-strips').name = `items[${currentIdx}][strips_per_box]`;
        newRow.querySelector('.item-tablets').name = `items[${currentIdx}][tablets_per_strip]`;
        newRow.querySelector('.item-cost').name = `items[${currentIdx}][cost_price]`;
        newRow.querySelector('.item-mrp').name = `items[${currentIdx}][retail_price]`;
        newRow.querySelector('input[placeholder="Batch"]').name = `items[${currentIdx}][batch_number]`;
        newRow.querySelector('input[type="date"]').name = `items[${currentIdx}][expiry_date]`;

        newRow.querySelector('.remove-row-btn').disabled = false;
        itemsTableBody.appendChild(newRow);
        
        if (window.scannedRowIndex) {
            window.scannedRowIndex++;
        } else {
            rowIndex++;
        }
        calculateTotal();
    });

    itemsTableBody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row-btn');
        if (btn && !btn.disabled) {
            btn.closest('.item-row').remove();
            calculateTotal();
        }
    });
});
</script>
@endpush
