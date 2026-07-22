<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $businessSettings->business_name ?? 'POS System' }} - @yield('title', 'Dashboard')</title>
    <link href="{{ asset('offline/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('offline/icons.css') }}" rel="stylesheet">
    <style>
@media print {
    /* Pure software mein jahan jahan 'no-print' class hogi, woh print ke waqt chup jayegi */
    .no-print, 
    .sidebar, 
    nav, 
    header, 
    footer, 
    .btn {
        display: none !important;
    }
    
    /* Layout ko clean printing ke liye stretch karein */
    body {
        width: 100%;
        margin: 0;
        padding: 0;
        background: white !important;
    }
}
  :root {
            --sidebar-w: 230px;
            --brand-color: #0ea5e9;
        }
        body { background: #f0f4f8; font-family: "Segoe UI", Arial, sans-serif; }

        /* ── Sidebar ─────────────────────────────────────── */
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            width: var(--sidebar-w);
            position: fixed; top: 0; left: 0;
            z-index: 200;
            box-shadow: 3px 0 16px rgba(0,0,0,0.25);
            overflow-y: auto; overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.2) transparent;
            transition: transform .25s ease;
        }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 6px; }

        .sidebar .brand {
            padding: 18px 16px;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; align-items: center; gap: 10px;
        }
        .sidebar .brand img {
            width: 34px; height: 34px; object-fit: contain;
            border-radius: 6px; background: #fff; padding: 3px;
            flex-shrink: 0;
        }
        .sidebar .brand span {
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }

        .nav-section {
            padding: 14px 16px 4px;
            color: rgba(255,255,255,0.35);
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 600;
        }

        .sidebar nav { padding-bottom: 28px; }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.72);
            padding: 10px 16px;
            font-size: 0.875rem;
            display: block;
            transition: all .18s;
            border-left: 3px solid transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.07);
            border-left-color: rgba(14,165,233,0.5);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(14,165,233,0.18);
            border-left-color: var(--brand-color);
            font-weight: 600;
        }

        /* ── Main content ──────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        .topbar {
            background: #fff;
            padding: 13px 24px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.08);
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 1050;
        }
        .topbar h5 { font-size: 1rem; font-weight: 700; color: #0f172a; }

        .page-content { padding: 22px 24px; flex: 1; }

        /* ── Cards ─────────────────────────────────────────── */
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.07); border-radius: 10px; }
        .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; font-weight: 600; font-size: 0.9rem; }

        /* ── Buttons ───────────────────────────────────────── */
        .btn-primary { background: var(--brand-color); border-color: var(--brand-color); }
        .btn-primary:hover { background: #0284c7; border-color: #0284c7; }

        /* ── Tables ────────────────────────────────────────── */
        .table th {
            background: #f8fafc; font-weight: 600;
            font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.4px;
            color: #64748b;
        }

        /* ── Badges ────────────────────────────────────────── */
        .badge-active   { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-pending  { background: #fef3c7; color: #92400e; }
        .role-badge { font-size: 0.62rem; padding: 2px 7px; border-radius: 20px; margin-left: 5px; }
        .role-admin   { background: var(--brand-color); color: #fff; }
        .role-cashier { background: #f59e0b; color: #fff; }

        /* Pagination guard: this app uses Bootstrap, not Tailwind pagination styles. */
        .pagination { margin-bottom: 0; gap: 2px; }
        .pagination svg,
        nav[role="navigation"] svg {
            width: 16px !important;
            height: 16px !important;
            max-width: 16px !important;
            max-height: 16px !important;
        }
        nav[role="navigation"] .hidden { display: none !important; }
        nav[role="navigation"] p { margin-bottom: 0; }
        .card-footer nav[role="navigation"] { margin-left: auto; }

        /* ── Mobile hamburger ──────────────────────────────── */
        .hamburger-btn { display: none; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 190; }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .main-content { margin-left: 0; }
            .hamburger-btn { display: inline-flex; }
            .page-content { padding: 14px; }
        }
        @media (max-width: 576px) {
            .topbar { padding: 10px 14px; }
            .topbar h5 { font-size: 0.9rem; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar overlay (mobile) --}}
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="brand">
        @if($businessSettings->logo_path)
            <img src="{{ route('media.show', $businessSettings->logo_path) }}" alt="logo">
        @else
            <div style="width:34px;height:34px;background:var(--brand-color);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-plus-circle" style="color:#fff;font-size:1.1rem;"></i>
            </div>
        @endif
        <span>{{ $businessSettings->business_name ?? 'POS System' }}</span>
    </div>

    <nav class="mt-1">
        {{-- 1. QUICK OPERATIONS --}}
        <div class="nav-section">⚡ QUICK OPERATIONS</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            Dashboard
        </a>
        <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
            Pharmacy Bills
        </a>
        <a href="{{ route('held-invoices.index') }}" class="nav-link {{ request()->routeIs('held-invoices.*') ? 'active' : '' }}">
            Held Bills
        </a>
        <a href="{{ route('sales-returns.index') }}" class="nav-link {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}">
            Sales Return
        </a>

        {{-- 2. INVENTORY & SUPPLIERS --}}
        <div class="nav-section">📦 INVENTORY & SUPPLIERS</div>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.index') || request()->routeIs('products.show') || request()->routeIs('products.create') || request()->routeIs('products.edit') ? 'active' : '' }}">
            Medicines (Stock)
        </a>
        <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
            Categories
        </a>
        <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
            Suppliers & Companies
        </a>
        @endif
        <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
            Local Distributors (Khata)
        </a>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('stock-returns.index') }}" class="nav-link {{ request()->routeIs('stock-returns.*') ? 'active' : '' }}">
            Returns / Damaged
        </a>
        <a href="{{ route('products.import') }}" class="nav-link {{ request()->routeIs('products.import*') ? 'active' : '' }}">
            Bulk Bill Import
        </a>
        @endif

        {{-- 3. FINANCE & CASH --}}
        <div class="nav-section">💰 FINANCE & CASH</div>
        <a href="{{ route('cash-registers.status') }}" class="nav-link {{ request()->routeIs('cash-registers.status') || request()->routeIs('cash-registers.open') ? 'active' : '' }}">
            Galla / Register Status
        </a>
        <a href="{{ route('reports.cashier-eod') }}" class="nav-link {{ request()->routeIs('reports.cashier-eod') ? 'active' : '' }}">
            My Daily Report
        </a>
        <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
            Expenses
        </a>
        
        <a href="{{ route('customer-payments.index') }}" class="nav-link {{ request()->routeIs('customer-payments.*') ? 'active' : '' }}">
            Customer Collections
        </a>
        {{-- 4. REPORTS & ADMIN --}}
        <div class="nav-section">📊 REPORTS & ADMIN</div>
        @if(auth()->user()->isAdmin())
        <a href="#reportsSubmenu" class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('reports.*') ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}">
            <span><i class="bi bi-bar-chart-line me-1"></i>Reports</span>
            <i class="bi bi-chevron-down small"></i>
        </a>
        <div class="collapse {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="reportsSubmenu">
            <a href="{{ route('reports.customer-register') }}" class="nav-link ps-4 {{ request()->routeIs('reports.customer-register') ? 'active' : '' }}">Customer Register</a>
            <a href="{{ route('reports.customer-sale-products') }}" class="nav-link ps-4 {{ request()->routeIs('reports.customer-sale-products') ? 'active' : '' }}">Customer Sale Products</a>
            <a href="{{ route('reports.purchase-history') }}" class="nav-link ps-4 {{ request()->routeIs('reports.purchase-history') ? 'active' : '' }}">Purchase History</a>
            <a href="{{ route('reports.purchase-return-history') }}" class="nav-link ps-4 {{ request()->routeIs('reports.purchase-return-history') ? 'active' : '' }}">Purchase Return History</a>
            <a href="{{ route('reports.product-purchase-history') }}" class="nav-link ps-4 {{ request()->routeIs('reports.product-purchase-history') ? 'active' : '' }}">Product Purchase History</a>
            <a href="{{ route('reports.sale-history') }}" class="nav-link ps-4 {{ request()->routeIs('reports.sale-history') ? 'active' : '' }}">Sale History</a>
            <a href="{{ route('reports.user-sale-history') }}" class="nav-link ps-4 {{ request()->routeIs('reports.user-sale-history') ? 'active' : '' }}">User Sale History</a>
            <a href="{{ route('reports.shift-sale-history') }}" class="nav-link ps-4 {{ request()->routeIs('reports.shift-sale-history') ? 'active' : '' }}">Shift Sale History</a>
            <a href="{{ route('reports.category-sale-history') }}" class="nav-link ps-4 {{ request()->routeIs('reports.category-sale-history') ? 'active' : '' }}">Category Sale History</a>
            <a href="{{ route('reports.product-sale-history') }}" class="nav-link ps-4 {{ request()->routeIs('reports.product-sale-history') ? 'active' : '' }}">Product Sale History</a>
            <a href="{{ route('reports.sale-history-category-pro') }}" class="nav-link ps-4 {{ request()->routeIs('reports.sale-history-category-pro') ? 'active' : '' }}">Category / Product Sale Detail</a>
            <a href="{{ route('reports.customer-sale-history') }}" class="nav-link ps-4 {{ request()->routeIs('reports.customer-sale-history') ? 'active' : '' }}">Customer Sale History</a>
            <a href="{{ route('reports.sale-summary') }}" class="nav-link ps-4 {{ request()->routeIs('reports.sale-summary') ? 'active' : '' }}">Sale Summary</a>
            <a href="{{ route('reports.sale-return-history') }}" class="nav-link ps-4 {{ request()->routeIs('reports.sale-return-history') ? 'active' : '' }}">Sale Return History</a>
            <a href="{{ route('reports.cash-states') }}" class="nav-link ps-4 {{ request()->routeIs('reports.cash-states') ? 'active' : '' }}">Cash Register States</a>
            <a href="{{ route('reports.products-low-stock') }}" class="nav-link ps-4 {{ request()->routeIs('reports.products-low-stock') ? 'active' : '' }}">Products Low Stock</a>
            <a href="{{ route('reports.profit-loss') }}" class="nav-link ps-4 {{ request()->routeIs('reports.profit-loss') ? 'active' : '' }}">Profit / Loss</a>
            <a href="{{ route('reports.tax') }}" class="nav-link ps-4 {{ request()->routeIs('reports.tax') ? 'active' : '' }}">Tax / GST</a>
            <a href="{{ route('reports.eod') }}" class="nav-link ps-4 {{ request()->routeIs('reports.eod') ? 'active' : '' }}">End of Day (EOD)</a>
        </div>
        <a href="{{ route('cash-registers.index') }}" class="nav-link {{ request()->routeIs('cash-registers.index') ? 'active' : '' }}">
            Register Shifts Log
        </a>
        @endif
        
        <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
            Customers List
        </a>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
            Staff Users
        </a>
        <a href="{{ route('business-settings.edit') }}" class="nav-link {{ request()->routeIs('business-settings.*') ? 'active' : '' }}">
            Business Settings
        </a>
        <a href="{{ route('backup.download') }}" class="nav-link">
            Download Backup
        </a>
        @endif

        {{-- 5. EXIT --}}
        <div class="nav-section">🛑 EXIT</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link w-100 text-start border-0 bg-transparent" style="color:rgba(255,100,100,0.8);">
                Logout
            </button>
        </form>
    </nav>
</div>

<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light hamburger-btn" onclick="openSidebar()" aria-label="Open menu">
                <span style="font-weight:700;">Menu</span>
            </button>
            <h5 class="mb-0">@yield('title', 'Dashboard')</h5>
        </div>
        
          <div class="d-flex align-items-center gap-2 gap-sm-3 flex-wrap justify-content-end">
            @php
                $topbarDistributors = \App\Models\Supplier::where('status', 1)
                    ->where('supplier_type', 'distributor')
                    ->orderBy('name')
                    ->get();
            @endphp
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">+ New Bill</a>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#topbarKishtModal">
                Pay Kisht
            </button>
            <span class="text-muted small d-none d-sm-inline">{{ now()->format('d M Y') }}</span>
            <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle px-3" data-bs-toggle="dropdown">
                    {{ auth()->user()->name ?? 'User' }}
                    @if(auth()->user()->isAdmin())
                        <span class="role-badge role-admin">Admin</span>
                    @else
                        <span class="role-badge role-cashier">Cashier</span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->email }}</span></li>
                    @if(auth()->user()->isAdmin())
                    <li><a class="dropdown-item" href="{{ route('business-settings.edit') }}">Business Settings</a></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="page-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                @foreach($errors->all() as $error) {{ $error }}<br> @endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<div class="modal fade" id="topbarKishtModal" tabindex="-1" aria-labelledby="topbarKishtModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('supplier-payments.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="topbarKishtModalLabel">Pay Kisht / Installment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Local Distributor <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">Select distributor</option>
                        @foreach($topbarDistributors as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->company_name ?: $supplier->name }}</option>
                        @endforeach
                    </select>
                    @if($topbarDistributors->isEmpty())
                        <div class="form-text text-danger">No active local distributor found.</div>
                    @else
                        <div class="form-text">Payment will auto-adjust against oldest unpaid khata bills.</div>
                    @endif
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Amount Paid <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rs.</span>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Slip / Reference #</label>
                    <input type="text" name="slip_number" class="form-control" placeholder="Optional">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional details..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success" {{ $topbarDistributors->isEmpty() ? 'disabled' : '' }}>Save Kisht</button>
            </div>
        </form>
    </div>
</div>

<script src="{{ asset('offline/bootstrap.bundle.min.js') }}"></script>
<script>
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('sidebarOverlay').classList.add('open'); }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('open'); }

document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        // Restore scroll position
        const scrollPos = localStorage.getItem('sidebar-scroll');
        if (scrollPos) {
            sidebar.scrollTop = parseInt(scrollPos, 10);
        }
        // Save scroll position on scroll
        sidebar.addEventListener('scroll', function() {
            localStorage.setItem('sidebar-scroll', sidebar.scrollTop);
        });
    }
});
</script>
@stack('scripts')
</body>
</html>
