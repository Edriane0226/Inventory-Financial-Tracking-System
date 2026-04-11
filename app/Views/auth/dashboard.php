<?php
$firstName = session()->get('first_name') ?? 'there';
$role = session()->get('role') ?? 'Employee';
$lowStockCount = (int) ($lowStockCount ?? 0);
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
					<a href="<?= base_url('stockin') ?>" class="text-decoration-none text-reset d-block h-100">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-arrow-down-circle fs-3 text-success mb-3"></i>
							<h6 class="card-title">Stock In</h6>
							<p class="card-text text-muted small">Record incoming inventory</p>
						</div>
					</div>
					</a>
				</div>
				<div class="col-md-6 col-lg-4">
					<a href="<?= base_url('stock-out/inventory') ?>" class="text-decoration-none text-reset d-block h-100">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-arrow-up-circle fs-3 text-danger mb-3"></i>
							<h6 class="card-title">Stock Out</h6>
							<p class="card-text text-muted small">Automatic deduction per sale</p>
						</div>
					</div>
					</a>
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
					<a href="<?= base_url('stock-levels?status=low_stock') ?>" class="text-decoration-none text-reset d-block h-100">
					<div class="card h-100 border-0 shadow-sm position-relative">
						<div class="card-body text-center position-relative">
							<?php if ($lowStockCount > 0): ?>
								<span class="badge bg-danger low-stock-card-badge"><?= esc((string) $lowStockCount) ?></span>
							<?php endif; ?>
							<i class="bi bi-exclamation-triangle fs-3 text-danger mb-3"></i>
							<h6 class="card-title">Low Stock Alert</h6>
							<p class="card-text text-muted small">Monitor stock levels</p>
						</div>
					</div>
					</a>
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
					<a href="<?= base_url('financial') ?>" class="text-decoration-none text-reset d-block h-100">
						<div class="card h-100 border-0 shadow-sm">
							<div class="card-body text-center">
								<i class="bi bi-trending-up fs-3 text-success mb-3"></i>
								<h6 class="card-title">Revenue Tracking</h6>
								<p class="card-text text-muted small">Monitor total revenue</p>
							</div>
						</div>
					</a>
				</div>
				<div class="col-md-6 col-lg-3">
					<a href="<?= base_url('financial/expenses') ?>" class="text-decoration-none text-reset d-block h-100">
						<div class="card h-100 border-0 shadow-sm">
							<div class="card-body text-center">
								<i class="bi bi-trending-down fs-3 text-danger mb-3"></i>
								<h6 class="card-title">Expenses Tracking</h6>
								<p class="card-text text-muted small">Track business expenses</p>
							</div>
						</div>
					</a>
				</div>
				<div class="col-md-6 col-lg-3">
					<a href="<?= base_url('financial') ?>" class="text-decoration-none text-reset d-block h-100">
						<div class="card h-100 border-0 shadow-sm">
							<div class="card-body text-center">
								<i class="bi bi-bar-chart fs-3 text-info mb-3"></i>
								<h6 class="card-title">Net Income</h6>
								<p class="card-text text-muted small">Revenue - Expenses</p>
							</div>
						</div>
					</a>
				</div>
				<div class="col-md-6 col-lg-3">
					<a href="<?= base_url('financial') ?>" class="text-decoration-none text-reset d-block h-100">
						<div class="card h-100 border-0 shadow-sm">
							<div class="card-body text-center">
								<i class="bi bi-pie-chart fs-3 text-warning mb-3"></i>
								<h6 class="card-title">Financial Reports</h6>
								<p class="card-text text-muted small">Comprehensive reports</p>
							</div>
						</div>
					</a>
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
					<a href="<?= base_url('stock-out/cashier') ?>" class="text-decoration-none text-reset d-block h-100">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-cart-plus fs-3 text-success mb-3"></i>
							<h6 class="card-title">Process Sales</h6>
							<p class="card-text text-muted small">Handle customer transactions</p>
						</div>
					</div>
					</a>
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
					<a href="<?= base_url('stockin') ?>" class="text-decoration-none text-reset d-block h-100">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-arrow-down-circle fs-3 text-success mb-3"></i>
							<h6 class="card-title">Stock In</h6>
							<p class="card-text text-muted small">Record incoming inventory</p>
						</div>
					</div>
					</a>
				</div>
				<div class="col-md-6 col-lg-4">
					<a href="<?= base_url('stock-levels') ?>" class="text-decoration-none text-reset d-block h-100">
					<div class="card h-100 border-0 shadow-sm">
						<div class="card-body text-center">
							<i class="bi bi-eye fs-3 text-primary mb-3"></i>
							<h6 class="card-title">Check Stock Levels</h6>
							<p class="card-text text-muted small">Monitor product availability</p>
						</div>
					</div>
					</a>
				</div>
				<div class="col-md-6 col-lg-4">
					<a href="<?= base_url('stock-levels?status=low_stock') ?>" class="text-decoration-none text-reset d-block h-100">
					<div class="card h-100 border-0 shadow-sm position-relative">
						<div class="card-body text-center position-relative">
							<?php if ($lowStockCount > 0): ?>
								<span class="badge bg-danger low-stock-card-badge"><?= esc((string) $lowStockCount) ?></span>
							<?php endif; ?>
							<i class="bi bi-exclamation-triangle fs-3 text-warning mb-3"></i>
							<h6 class="card-title">Low Stock Alerts</h6>
							<p class="card-text text-muted small">Check items running low</p>
						</div>
					</div>
					</a>
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

	<?php if ($lowStockCount > 0): ?>
		<div class="position-fixed end-0 p-3" style="z-index: 1090; bottom: 3.75rem;">
			<div id="lowStockToast" class="toast align-items-center text-bg-warning border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
				<div class="d-flex">
					<div class="toast-body text-dark fw-semibold">
						Low Stock Alert: <?= esc((string) $lowStockCount) ?> product<?= $lowStockCount > 1 ? 's are' : ' is' ?> currently low on stock.
					</div>
					<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const body = document.body;
			const toggleBtn = document.getElementById('sidebarToggle');
			const overlay = document.querySelector('.sidebar-overlay');
			const sidebarLinks = document.querySelectorAll('.sidebar .sidebar-link');
			const hasSidebar = document.querySelector('.sidebar') !== null;

			const closeSidebar = () => body.classList.remove('sidebar-open');

			if (toggleBtn && hasSidebar) {
				toggleBtn.addEventListener('click', () => body.classList.toggle('sidebar-open'));
			}

			if (overlay && hasSidebar) {
				overlay.addEventListener('click', closeSidebar);
			}

			const lowStockToastEl = document.getElementById('lowStockToast');
			if (lowStockToastEl && window.bootstrap && window.bootstrap.Toast) {
				const lowStockToast = new window.bootstrap.Toast(lowStockToastEl);
				lowStockToast.show();
			}

			if (hasSidebar) {
				sidebarLinks.forEach(link => link.addEventListener('click', closeSidebar));
			}
		});
	</script>
	<style>
		.low-stock-card-badge {
			position: absolute;
			top: 0.65rem;
			right: 0.75rem;
		}
	</style>
