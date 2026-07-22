@extends('layouts.app')

@section('title', 'Edit Medicine')

@section('content')
@php
    $productType = old('product_type', $product->product_type);
    
    $boxMrp = 0;
    $boxBuyPrice = 0;
    
    if ($product->product_type === 'medicine') {
        $multiplier = ($product->tablets_per_strip ?? 1) * ($product->strips_per_box ?? 1);
        $boxMrp = $product->price * $multiplier;
        $boxBuyPrice = $product->buy_price * $multiplier;
    } elseif ($product->product_type === 'liquid') {
        $multiplier = $product->units_per_box ?? 1;
        $boxMrp = $product->price * $multiplier;
        $boxBuyPrice = $product->buy_price * $multiplier;
    } else {
        $boxMrp = $product->price;
        $boxBuyPrice = $product->buy_price;
    }
    
    $tradeDiscount = 15;
    if ($boxMrp > 0) {
        $tradeDiscount = (($boxMrp - $boxBuyPrice) / $boxMrp) * 100;
        if ($tradeDiscount < 0) $tradeDiscount = 0;
    }
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Edit Medicine</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Medicines</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">
        @if($product->isExpired())
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
            <div><strong>Locked: This batch is expired ({{ $product->expiry_date->format('d M Y') }}).</strong>
            It is hard-locked from billing. Update expiry date or create a stock return.</div>
        </div>
        @endif

        <div class="card">
            <div class="card-header py-3"><i class="bi bi-pencil me-2"></i>Edit: {{ $product->name }}</div>
            <div class="card-body">
                <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Medicine Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $product->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Generic / Formula Name</label>
                            <input type="text" name="generic_name" class="form-control @error('generic_name') is-invalid @enderror"
                                value="{{ old('generic_name', $product->generic_name) }}" placeholder="e.g. Paracetamol 500mg">
                            @error('generic_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">SKU</label>
                            <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Barcode</label>
                            <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror"
                                value="{{ old('barcode', $product->barcode) }}">
                            @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Batch #</label>
                            <input type="text" name="batch_number" class="form-control @error('batch_number') is-invalid @enderror"
                                value="{{ old('batch_number', $product->batch_number) }}" placeholder="e.g. BT-2024-001">
                            @error('batch_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Mfg Date</label>
                            <input type="date" name="mfg_date" class="form-control @error('mfg_date') is-invalid @enderror"
                                value="{{ old('mfg_date', $product->mfg_date?->format('Y-m-d')) }}">
                            @error('mfg_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror"
                                value="{{ old('expiry_date', $product->expiry_date?->format('Y-m-d')) }}" id="expiryDateInput">
                            @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div id="expiryPreview" class="p-2 rounded w-100 text-center small" style="background:#f8fafc;min-height:38px;">
                                @if($product->expiry_date)
                                    {!! $product->expiryBadgeHtml() !!}
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="categorySelect" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">Select category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" data-product-type="{{ $cat->product_type }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">No supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->company_name ?? $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Product Type (Hidden field, set automatically via Category) --}}
                        <input type="hidden" name="product_type" id="productTypeInput" value="{{ old('product_type', $product->product_type) }}">
                        <div class="col-md-4 packaging-field">
                            <label class="form-label fw-semibold">Tablets / Units per Strip</label>
                            <input type="number" name="tablets_per_strip" id="tabletsPerStripInput" class="form-control" min="1"
                                value="{{ old('tablets_per_strip', $product->tablets_per_strip ?? 1) }}">
                            <div class="form-text">e.g. 10 tablets in one strip.</div>
                        </div>
                        <div class="col-md-4 packaging-field">
                            <label class="form-label fw-semibold">Strips per Box</label>
                            <input type="number" name="strips_per_box" id="stripsPerBoxInput" class="form-control" min="1"
                                value="{{ old('strips_per_box', $product->strips_per_box ?? 1) }}">
                            <div class="form-text">e.g. 10 strips in one box.</div>
                        </div>

                        {{-- Liquid Fields --}}
                        <div class="col-md-4 liquid-field">
                            <label class="form-label fw-semibold">Units per Box <span class="text-danger">*</span></label>
                            <input type="number" name="units_per_box" id="unitsPerBoxInput" class="form-control" min="1"
                                value="{{ old('units_per_box', $product->units_per_box ?? 1) }}">
                            <div class="form-text">e.g. 12 bottles in one carton/box.</div>
                        </div>
                        <div class="col-md-4 liquid-field">
                            <label class="form-label fw-semibold">Volume (ml/grams)</label>
                            <input type="text" name="volume" id="volumeInput" class="form-control" placeholder="e.g. 120ml"
                                value="{{ old('volume', $product->volume) }}">
                            <div class="form-text">e.g. 120ml, 50g, 5ml.</div>
                        </div>

                        {{-- Box/MRP Calculator --}}
                        <div class="col-md-4 box-pricing-field">
                            <label class="form-label fw-semibold">Box Retail Price (MRP) (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" id="boxMrpInput" step="0.01" class="form-control" value="{{ number_format($boxMrp, 2, '.', '') }}" placeholder="0.00" min="0">
                            <div class="form-text">Total Retail Price of the entire Box.</div>
                        </div>
                        <div class="col-md-4 box-pricing-field">
                            <label class="form-label fw-semibold">Trade Discount (%)</label>
                            <input type="number" id="tradeDiscountInput" step="0.01" class="form-control" value="{{ number_format($tradeDiscount, 2, '.', '') }}" min="0" max="100">
                            <div class="form-text">Pharma standard trade discount (default 15%).</div>
                        </div>
                        <div class="col-md-4 box-pricing-field">
                            <label class="form-label fw-semibold">Box Buy Price (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" id="boxBuyPriceInput" step="0.01" class="form-control" value="{{ number_format($boxBuyPrice, 2, '.', '') }}" placeholder="0.00" min="0">
                            <div class="form-text">Actual purchase cost of the entire Box.</div>
                        </div>

                        {{-- Pricing --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Buy Price — per Tablet/Unit (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" name="buy_price" id="buyPriceInput" step="0.01" class="form-control @error('buy_price') is-invalid @enderror"
                                value="{{ old('buy_price', $product->buy_price) }}" placeholder="0.00" min="0" required>
                            @error('buy_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" id="buyPriceHelp">Auto-calculated single unit cost.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sale Price — per Tablet/Unit (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="salePriceInput" step="0.01" class="form-control @error('price') is-invalid @enderror"
                                value="{{ old('price', $product->price) }}" placeholder="0.00" min="0" required>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" id="salePriceHelp">Auto-calculated single unit retail price.</div>
                        </div>
                        <div class="col-12 packaging-field liquid-field box-pricing-field">
                            <div id="pricePreview" class="p-2 rounded small" style="background:#f8fafc;"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Total Boxes / Pieces <span class="text-danger">*</span>
                                <span class="text-muted small">(boxes for medicine)</span>
                            </label>
                            <input type="number" step="0.01" name="stock" class="form-control @error('stock') is-invalid @enderror"
                                value="{{ old('stock', $product->stockBoxes()) }}" min="0" required>
                            @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Low Stock Alert (Boxes)</label>
                            <input type="number" step="0.01" name="low_stock_threshold" class="form-control"
                                value="{{ old('low_stock_threshold', $product->lowStockBoxes()) }}" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unit</label>
                            <select name="unit" class="form-select">
                                @foreach(['pcs','kg','g','l','ml','box','dozen','strip','vial','ampoule'] as $u)
                                <option value="{{ $u }}" {{ old('unit', $product->unit) === $u ? 'selected' : '' }}>{{ strtoupper($u) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" {{ old('status', $product->status) == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status', $product->status) == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Almari (Cupboard)</label>
                            <input type="text" name="almari" class="form-control" value="{{ old('almari', $product->almari) }}" placeholder="e.g. Almari 3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Khana (Drawer/Box)</label>
                            <input type="text" name="khana" class="form-control" value="{{ old('khana', $product->khana) }}" placeholder="e.g. Khana 2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Row (Shelf Row)</label>
                            <input type="text" name="row" class="form-control" value="{{ old('row', $product->row) }}" placeholder="e.g. Row 1">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" rows="2" class="form-control">{{ old('description', $product->description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Medicine Image</label>
                            @if($product->image)
                                <div class="mb-2">
                                    <img src="{{ route('media.show', $product->image) }}" style="height:80px;border-radius:8px;" alt="Current">
                                    <small class="text-muted ms-2">Current image</small>
                                </div>
                            @endif
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Update Medicine
                        </button>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Dynamic Price Logic: calculate unit price from Box Price / Multipliers and apply trade discount.
function refreshPackagingUI(e) {
    const type = document.getElementById('productTypeInput').value;

    const isMedicine = type === 'medicine';
    const isLiquid   = type === 'liquid';
    const isGeneral  = type === 'general';

    // Show/hide fields
    document.querySelectorAll('.packaging-field').forEach(el => el.classList.toggle('d-none', !isMedicine));
    document.querySelectorAll('.liquid-field').forEach(el => el.classList.toggle('d-none', !isLiquid));
    document.querySelectorAll('.box-pricing-field').forEach(el => el.classList.toggle('d-none', isGeneral));

    const buyInput = document.getElementById('buyPriceInput');
    const saleInput = document.getElementById('salePriceInput');

    if (isGeneral) {
        // Make fields editable
        buyInput.removeAttribute('readonly');
        saleInput.removeAttribute('readonly');
        buyInput.style.backgroundColor = '';
        saleInput.style.backgroundColor = '';

        document.getElementById('tabletsPerStripInput').value = 1;
        document.getElementById('stripsPerBoxInput').value = 1;
        document.getElementById('unitsPerBoxInput').value = 1;

        updatePricePreview();
    } else {
        // Make fields readonly
        buyInput.setAttribute('readonly', 'readonly');
        saleInput.setAttribute('readonly', 'readonly');
        buyInput.style.backgroundColor = '#f1f5f9';
        saleInput.style.backgroundColor = '#f1f5f9';

        // Perform calculation
        calculateFromBoxPricing(e);
    }
}

function calculateFromBoxPricing(e) {
    const type = document.getElementById('productTypeInput').value;
    if (type === 'general') return;

    const boxMrp = parseFloat(document.getElementById('boxMrpInput').value) || 0;
    const discount = parseFloat(document.getElementById('tradeDiscountInput').value) || 0;
    const boxBuyInput = document.getElementById('boxBuyPriceInput');
    let boxBuy = parseFloat(boxBuyInput.value) || 0;

    // Determine what triggered the event
    const triggerId = e ? e.target.id : '';

    if (triggerId === 'boxMrpInput' || triggerId === 'tradeDiscountInput' || !boxBuy || triggerId === 'productTypeInput') {
        // Recalculate Box Buy Price from MRP and Trade Discount
        boxBuy = boxMrp * (1 - discount / 100);
        boxBuyInput.value = boxBuy.toFixed(2);
    } else if (triggerId === 'boxBuyPriceInput') {
        // Recalculate Trade Discount from MRP and Box Buy Price
        if (boxMrp > 0) {
            const calculatedDiscount = ((boxMrp - boxBuy) / boxMrp) * 100;
            document.getElementById('tradeDiscountInput').value = Math.max(0, calculatedDiscount).toFixed(2);
        }
    }

    let totalUnits = 1;
    if (type === 'medicine') {
        const tablets = Math.max(1, parseInt(document.getElementById('tabletsPerStripInput').value) || 1);
        const strips = Math.max(1, parseInt(document.getElementById('stripsPerBoxInput').value) || 1);
        totalUnits = tablets * strips;
    } else if (type === 'liquid') {
        totalUnits = Math.max(1, parseInt(document.getElementById('unitsPerBoxInput').value) || 1);
    }

    const unitBuy = boxBuy / totalUnits;
    const unitSale = boxMrp / totalUnits;

    document.getElementById('buyPriceInput').value = unitBuy.toFixed(2);
    document.getElementById('salePriceInput').value = unitSale.toFixed(2);

    updatePricePreview();
}

function updatePricePreview() {
    const type = document.getElementById('productTypeInput').value;
    const buyPrice = parseFloat(document.getElementById('buyPriceInput').value) || 0;
    const salePrice = parseFloat(document.getElementById('salePriceInput').value) || 0;

    let html = `<strong><i class="bi bi-calculator me-1"></i>Auto-calculated prices:</strong><div class="d-flex gap-4 mt-1 flex-wrap">`;

    if (type === 'medicine') {
        const tablets = Math.max(1, parseInt(document.getElementById('tabletsPerStripInput').value) || 1);
        const strips = Math.max(1, parseInt(document.getElementById('stripsPerBoxInput').value) || 1);
        const stripBuy = (buyPrice * tablets).toFixed(2);
        const stripSale = (salePrice * tablets).toFixed(2);
        const boxBuy = (buyPrice * tablets * strips).toFixed(2);
        const boxSale = (salePrice * tablets * strips).toFixed(2);

        html += `<span>💊 Tablet: Buy Rs ${buyPrice.toFixed(2)} / Sale Rs ${salePrice.toFixed(2)}</span>
                 <span>📦 Strip (${tablets} tab): Buy Rs ${stripBuy} / Sale Rs ${stripSale}</span>
                 <span>🗃️ Box (${strips} strips): Buy Rs ${boxBuy} / Sale Rs ${boxSale}</span>`;
    } else if (type === 'liquid') {
        const units = Math.max(1, parseInt(document.getElementById('unitsPerBoxInput').value) || 1);
        const boxBuy = (buyPrice * units).toFixed(2);
        const boxSale = (salePrice * units).toFixed(2);

        html += `<span>🧴 Piece/Bottle: Buy Rs ${buyPrice.toFixed(2)} / Sale Rs ${salePrice.toFixed(2)}</span>
                 <span>🗃️ Box (${units} units): Buy Rs ${boxBuy} / Sale Rs ${boxSale}</span>`;
    } else {
        html += `<span>🛍️ Item: Buy Rs ${buyPrice.toFixed(2)} / Sale Rs ${salePrice.toFixed(2)}</span>`;
    }

    html += `</div>`;
    document.getElementById('pricePreview').innerHTML = html;
}

const categorySelect = document.getElementById('categorySelect');
if (categorySelect) {
    categorySelect.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const type = opt ? opt.getAttribute('data-product-type') : 'medicine';
        document.getElementById('productTypeInput').value = type || 'medicine';
        refreshPackagingUI();
    });
}

['productTypeInput', 'tabletsPerStripInput', 'stripsPerBoxInput', 'unitsPerBoxInput', 'boxMrpInput', 'tradeDiscountInput', 'boxBuyPriceInput', 'buyPriceInput', 'salePriceInput'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('input', refreshPackagingUI);
        el.addEventListener('change', refreshPackagingUI);
    }
});

// Trigger change listener on load to set up the form based on current category
if (categorySelect) {
    const opt = categorySelect.options[categorySelect.selectedIndex];
    const type = opt ? opt.getAttribute('data-product-type') : 'medicine';
    document.getElementById('productTypeInput').value = type || 'medicine';
}
refreshPackagingUI();

document.getElementById('expiryDateInput').addEventListener('change', function() {
    const preview = document.getElementById('expiryPreview');
    if (!this.value) { preview.innerHTML = ''; return; }
    const expiry = new Date(this.value);
    const today  = new Date();
    today.setHours(0,0,0,0);
    const days = Math.floor((expiry - today) / 86400000);
    let cls, icon, label;
    if (days < 0)        { cls='danger';            icon='🔒'; label='EXPIRED'; }
    else if (days < 30)  { cls='danger';            icon='🔴'; label=days+' days — CRITICAL'; }
    else if (days < 90)  { cls='warning text-dark'; icon='🟡'; label=days+' days — Caution'; }
    else if (days < 180) { cls='warning text-dark'; icon='🟠'; label=days+' days — Monitor'; }
    else                 { cls='success';            icon='🟢'; label=days+' days — Safe'; }
    preview.innerHTML = `<span class="badge bg-${cls}">${icon} ${label}</span>`;
});
</script>
@endpush
