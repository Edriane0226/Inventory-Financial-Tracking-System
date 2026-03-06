<?php
$firstName = session()->get('first_name') ?? 'there';
$role = session()->get('role') ?? 'Employee';
?>
<?php if(session()->get('role') == 'Owner'): ?>
	<div class="dashboard-shell">
		<div class="sidebar-overlay d-lg-none"></div>
		<div class="dashboard-main">
			<main class="content-area px-3 px-lg-4 py-4 py-lg-5">
				<button class="btn btn-outline-primary mb-3 d-lg-none sidebar-toggle" id="sidebarToggle">
					<i class="bi bi-list"></i> Menu
				</button>
		<section class="dashboard-hero mb-5">
			<div class="row align-items-center">
				<div class="col-lg-8">
					<p class="text-uppercase small text-muted mb-1">Today • <?= esc(date('l, F j')) ?></p>
					<h1 class="display-6 fw-semibold mb-3">Welcome back, <?= esc($firstName) ?></h1>
			</div>
		</section>

		<!-- Product Management Section -->
		<section class="mb-5">
			<h2 class="h4 fw-semibold mb-4"><i class="bi bi-box-seam text-primary me-2"></i>Product Management</h2>
			<div class="row g-3">
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-tags fs-3 text-primary mb-3"></i>
							<h6 class="card-title">Product Category</h6>
							<p class="card-text text-muted small">Manage product categories</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-upc-scan fs-3 text-primary mb-3"></i>
							<h6 class="card-title">Barcode Support</h6>
							<p class="card-text text-muted small">Barcode scanning & management</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-calendar-x fs-3 text-warning mb-3"></i>
							<h6 class="card-title">Expiration Tracking</h6>
							<p class="card-text text-muted small">Track product expiration dates</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-rulers fs-3 text-success mb-3"></i>
							<h6 class="card-title">Unit Types</h6>
							<p class="card-text text-muted small">kg, pack, piece, box units</p>
						</div>
					</div>
				</div>
			</div>
			<div class="row g-3 mt-2">
				<div class="col-md-6">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-currency-dollar fs-3 text-info mb-3"></i>
							<h6 class="card-title">Capital vs Selling Price</h6>
							<p class="card-text text-muted small">Profit margin tracking</p>
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-eye fs-3 text-secondary mb-3"></i>
							<h6 class="card-title">Stock Overview</h6>
							<p class="card-text text-muted small">Product inventory summary</p>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Inventory Control Section -->
		<section class="mb-5">
			<h2 class="h4 fw-semibold mb-4"><i class="bi bi-boxes text-primary me-2"></i>Inventory Control</h2>
			<div class="row g-3">
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-arrow-down-circle fs-3 text-success mb-3"></i>
							<h6 class="card-title">Stock In</h6>
							<p class="card-text text-muted small">Record incoming inventory</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-arrow-up-circle fs-3 text-danger mb-3"></i>
							<h6 class="card-title">Stock Out</h6>
							<p class="card-text text-muted small">Automatic deduction per sale</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-gear fs-3 text-warning mb-3"></i>
							<h6 class="card-title">Stock Adjustments</h6>
							<p class="card-text text-muted small">Lost, damaged, expired items</p>
						</div>
					</div>
				</div>
			</div>
			<div class="row g-3 mt-2">
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-exclamation-triangle fs-3 text-danger mb-3"></i>
							<h6 class="card-title">Low Stock Alert</h6>
							<p class="card-text text-muted small">Monitor stock levels</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-calculator fs-3 text-info mb-3"></i>
							<h6 class="card-title">Total Stock Value</h6>
							<p class="card-text text-muted small">Inventory valuation</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-clock-history fs-3 text-secondary mb-3"></i>
							<h6 class="card-title">Inventory History</h6>
							<p class="card-text text-muted small">Track all inventory changes</p>
						</div>
					</div>
				</div>
			</div>
			<div class="row g-3 mt-2">
				<div class="col-md-12">
					<div class="card border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-layers fs-3 text-primary mb-3"></i>
							<h6 class="card-title">Batch Tracking</h6>
							<p class="card-text text-muted small">Track batches for perishable products</p>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Sales Management Section -->
		<section class="mb-5">
			<h2 class="h4 fw-semibold mb-4"><i class="bi bi-cart-check text-primary me-2"></i>Sales Management</h2>
			<div class="row g-3">
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-receipt fs-3 text-success mb-3"></i>
							<h6 class="card-title">Sales Receipt</h6>
							<p class="card-text text-muted small">Generate sales receipts</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-credit-card fs-3 text-warning mb-3"></i>
							<h6 class="card-title">Credit Tracking</h6>
							<p class="card-text text-muted small">Monitor credit sales</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-currency-exchange fs-3 text-info mb-3"></i>
							<h6 class="card-title">Payment Tracking</h6>
							<p class="card-text text-muted small">Track payments received</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-arrow-counterclockwise fs-3 text-danger mb-3"></i>
							<h6 class="card-title">Refund & Returns</h6>
							<p class="card-text text-muted small">Process refunds and returns</p>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Financial Tracking Section -->
		<section class="mb-5">
			<h2 class="h4 fw-semibold mb-4"><i class="bi bi-graph-up text-primary me-2"></i>Financial Tracking</h2>
			<div class="row g-3">
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-trending-up fs-3 text-success mb-3"></i>
							<h6 class="card-title">Revenue Tracking</h6>
							<p class="card-text text-muted small">Monitor total revenue</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-trending-down fs-3 text-danger mb-3"></i>
							<h6 class="card-title">Expenses Tracking</h6>
							<p class="card-text text-muted small">Track business expenses</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-bar-chart fs-3 text-info mb-3"></i>
							<h6 class="card-title">Net Income</h6>
							<p class="card-text text-muted small">Revenue - Expenses</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-3">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-pie-chart fs-3 text-warning mb-3"></i>
							<h6 class="card-title">Financial Reports</h6>
							<p class="card-text text-muted small">Comprehensive reports</p>
						</div>
					</div>
				</div>
			</div>
		</section>
			</main>
		</div>
	</div>
    <?php elseif(session()->get('role') == 'Employee'): ?>
        <div class="dashboard-shell">
		<div class="sidebar-overlay d-lg-none"></div>
		<div class="dashboard-main">
			<main class="content-area px-3 px-lg-4 py-4 py-lg-5">
				<button class="btn btn-outline-primary mb-3 d-lg-none sidebar-toggle" id="sidebarToggle">
					<i class="bi bi-list"></i> Menu
				</button>
		<section class="dashboard-hero mb-5">
			<div class="row align-items-center">
				<div class="col-lg-8">
					<p class="text-uppercase small text-muted mb-1">Today • <?= esc(date('l, F j')) ?></p>
					<h1 class="display-6 fw-semibold mb-3">Welcome back, <?= esc($firstName) ?></h1>
				</div>
			</div>
		</section>

		<!-- Daily Operations Section -->
		<section class="mb-5">
			<h2 class="h4 fw-semibold mb-4"><i class="bi bi-clipboard-check text-primary me-2"></i>Daily Operations</h2>
			<div class="row g-3">
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-cart-plus fs-3 text-success mb-3"></i>
							<h6 class="card-title">Process Sales</h6>
							<p class="card-text text-muted small">Handle customer transactions</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-receipt fs-3 text-primary mb-3"></i>
							<h6 class="card-title">Generate Receipts</h6>
							<p class="card-text text-muted small">Print customer receipts</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-credit-card fs-3 text-info mb-3"></i>
							<h6 class="card-title">Payment Processing</h6>
							<p class="card-text text-muted small">Accept various payment methods</p>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Inventory Tasks Section -->
		<section class="mb-5">
			<h2 class="h4 fw-semibold mb-4"><i class="bi bi-boxes text-primary me-2"></i>Inventory Tasks</h2>
			<div class="row g-3">
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-arrow-down-circle fs-3 text-success mb-3"></i>
							<h6 class="card-title">Stock In</h6>
							<p class="card-text text-muted small">Record incoming inventory</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-eye fs-3 text-primary mb-3"></i>
							<h6 class="card-title">Check Stock Levels</h6>
							<p class="card-text text-muted small">Monitor product availability</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-exclamation-triangle fs-3 text-warning mb-3"></i>
							<h6 class="card-title">Low Stock Alerts</h6>
							<p class="card-text text-muted small">Check items running low</p>
						</div>
					</div>
				</div>
			</div>
			<div class="row g-3 mt-2">
				<div class="col-md-6">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-gear fs-3 text-danger mb-3"></i>
							<h6 class="card-title">Stock Adjustments</h6>
							<p class="card-text text-muted small">Report damaged or expired items</p>
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-upc-scan fs-3 text-info mb-3"></i>
							<h6 class="card-title">Barcode Scanner</h6>
							<p class="card-text text-muted small">Quick product lookup</p>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Customer Service Section -->
		<section class="mb-5">
			<h2 class="h4 fw-semibold mb-4"><i class="bi bi-people text-primary me-2"></i>Customer Service</h2>
			<div class="row g-3">
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-arrow-counterclockwise fs-3 text-warning mb-3"></i>
							<h6 class="card-title">Returns & Refunds</h6>
							<p class="card-text text-muted small">Process customer returns</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-clock-history fs-3 text-info mb-3"></i>
							<h6 class="card-title">Credit Tracking</h6>
							<p class="card-text text-muted small">Monitor credit sales</p>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-question-circle fs-3 text-secondary mb-3"></i>
							<h6 class="card-title">Product Info</h6>
							<p class="card-text text-muted small">Help customers find products</p>
						</div>
					</div>
				</div>
			</div>
		</section>
			</main>
		</div>
	</div>
    <?php endif; ?>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const body = document.body;
			const toggleBtn = document.getElementById('sidebarToggle');
			const overlay = document.querySelector('.sidebar-overlay');
			const sidebarLinks = document.querySelectorAll('.sidebar .sidebar-link');

			const closeSidebar = () => body.classList.remove('sidebar-open');

			if (toggleBtn) {
				toggleBtn.addEventListener('click', () => body.classList.toggle('sidebar-open'));
			}

			if (overlay) {
				overlay.addEventListener('click', closeSidebar);
			}

			sidebarLinks.forEach(link => link.addEventListener('click', closeSidebar));
		});
	</script>
