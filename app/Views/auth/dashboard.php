<?php
$firstName = session()->get('first_name') ?? 'there';
$lastName = session()->get('last_name') ?? '';
$role = session()->get('role') ?? 'Employee';
$lowStockCount = (int) ($lowStockCount ?? 0);
$displayName = trim($firstName . ' ' . $lastName);
if ($displayName === '') {
    $displayName = 'there';
}

$summary = $summary ?? [
    'total_products' => 0,
    'in_stock' => 0,
    'low_stock' => 0,
    'out_of_stock' => 0,
];
$inventoryValue = (float) ($inventoryValue ?? 0);
$salesSummary = $salesSummary ?? [
    'today' => 0,
    'week' => 0,
    'month' => 0,
    'receipts_today' => 0,
    'receipts_week' => 0,
    'receipts_month' => 0,
];
$salesTrend = $salesTrend ?? [
    'labels' => [],
    'values' => [],
];
$recentReceipts = $recentReceipts ?? [];
$lowStockItems = $lowStockItems ?? [];

$quickActions = [
    ['label' => 'Manage Products', 'icon' => 'bi-boxes', 'path' => 'products', 'roles' => ['Owner']],
    ['label' => 'Stock Levels', 'icon' => 'bi-graph-up-arrow', 'path' => 'stock-levels', 'roles' => ['Owner', 'Employee']],
    ['label' => 'Stock In', 'icon' => 'bi-receipt', 'path' => 'stockin', 'roles' => ['Owner', 'Employee']],
    ['label' => 'Cashier', 'icon' => 'bi-cash-stack', 'path' => 'stock-out/cashier', 'roles' => ['Owner', 'Employee']],
    ['label' => 'Financial Dashboard', 'icon' => 'bi-graph-up', 'path' => 'financial', 'roles' => ['Owner']],
    ['label' => 'Expense Tracking', 'icon' => 'bi-wallet2', 'path' => 'financial/expenses', 'roles' => ['Owner']],
    ['label' => 'Users Management', 'icon' => 'bi-people', 'path' => 'register', 'roles' => ['Owner']],
];

$visibleActions = array_values(array_filter(
    $quickActions,
    static fn(array $action): bool => in_array($role, $action['roles'], true)
));
?>

<div class="dashboard-shell">
	<div class="dashboard-main">
		<main class="content-area px-3 px-lg-4 py-4 py-lg-5">
			<?php if (session()->getFlashdata('success')): ?>
				<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
			<?php endif; ?>
			<?php if (session()->getFlashdata('error')): ?>
				<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
			<?php endif; ?>

			

			<?php if ($role === 'Owner'): ?>
			<section class="mb-5">
				<div class="row g-3">
					<div class="col-md-6 col-xl-4">
						<div class="metric-card" style="animation-delay: 0.05s;">
							<div class="d-flex justify-content-between align-items-start">
								<div>
									<div class="metric-label">Total Products</div>
									<div class="metric-value"><?= esc((string) $summary['total_products']) ?></div>
								</div>
								<div class="metric-icon"><i class="bi bi-box-seam"></i></div>
							</div>
						</div>
					</div>
					<div class="col-md-6 col-xl-4">
						<div class="metric-card" style="animation-delay: 0.1s;">
							<div class="d-flex justify-content-between align-items-start">
								<div>
									<div class="metric-label">Low Stock Items</div>
									<div class="metric-value"><?= esc((string) $summary['low_stock']) ?></div>
									<div class="metric-subtext">Needs attention</div>
								</div>
								<div class="metric-icon metric-icon-warning"><i class="bi bi-exclamation-triangle"></i></div>
							</div>
						</div>
					</div>
					<div class="col-md-6 col-xl-4">
						<div class="metric-card" style="animation-delay: 0.15s;">
							<div class="d-flex justify-content-between align-items-start">
								<div>
									<div class="metric-label">Out of Stock</div>
									<div class="metric-value"><?= esc((string) $summary['out_of_stock']) ?></div>
									<div class="metric-subtext">Restock needed</div>
								</div>
								<div class="metric-icon metric-icon-danger"><i class="bi bi-x-circle"></i></div>
							</div>
						</div>
					</div>
					<div class="col-md-6 col-xl-4">
						<div class="metric-card" style="animation-delay: 0.2s;">
							<div class="d-flex justify-content-between align-items-start">
								<div>
									<div class="metric-label">Inventory Value</div>
									<div class="metric-value">PHP <?= esc(number_format($inventoryValue, 2)) ?></div>
									<div class="metric-subtext">Estimated stock worth</div>
								</div>
								<div class="metric-icon metric-icon-accent"><i class="bi bi-bank"></i></div>
							</div>
						</div>
					</div>
					<div class="col-md-6 col-xl-4">
						<div class="metric-card" style="animation-delay: 0.25s;">
							<div class="d-flex justify-content-between align-items-start">
								<div>
									<div class="metric-label">Sales Today</div>
									<div class="metric-value">PHP <?= esc(number_format((float) $salesSummary['today'], 2)) ?></div>
									<div class="metric-subtext"><?= esc((string) $salesSummary['receipts_today']) ?> transactions</div>
								</div>
								<div class="metric-icon"><i class="bi bi-graph-up"></i></div>
							</div>
						</div>
					</div>
					<div class="col-md-6 col-xl-4">
						<div class="metric-card" style="animation-delay: 0.3s;">
							<div class="d-flex justify-content-between align-items-start">
								<div>
									<div class="metric-label">Sales This Week</div>
									<div class="metric-value">PHP <?= esc(number_format((float) $salesSummary['week'], 2)) ?></div>
									<div class="metric-subtext"><?= esc((string) $salesSummary['receipts_week']) ?> transactions</div>
								</div>
								<div class="metric-icon metric-icon-accent"><i class="bi bi-bar-chart"></i></div>
							</div>
						</div>
					</div>
				</div>
			</section>

			<section class="mb-5">
				<div class="row g-4">
					<div class="col-lg-8">
						<div class="panel-card p-3" style="animation-delay: 0.2s;">
							<div class="card-body">
								<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
									<h2 class="h5 mb-0">Sales Trend (Last 7 Days)</h2>
									<span class="text-muted small">Monthly total: PHP <?= esc(number_format((float) $salesSummary['month'], 2)) ?></span>
								</div>
								<div class="chart-shell">
									<canvas id="salesTrendChart" height="140"></canvas>
								</div>
								<?php if ((int) $salesSummary['receipts_week'] === 0): ?>
									<p class="text-muted small mt-2 mb-0">No sales recorded in the last 7 days.</p>
								<?php endif; ?>
							</div>
						</div>
					</div>
					<div class="col-lg-4">
						<div class="panel-card p-3" style="animation-delay: 0.25s;">
							<div class="card-body">
								<h2 class="h5 mb-3">Stock Status</h2>
								<div class="chart-shell">
									<canvas id="stockStatusChart" height="220"></canvas>
								</div>
								<div class="status-list">
									<div><span class="dot dot-good"></span>In Stock: <?= esc((string) $summary['in_stock']) ?></div>
									<div><span class="dot dot-warn"></span>Low Stock: <?= esc((string) $summary['low_stock']) ?></div>
									<div><span class="dot dot-bad"></span>Out of Stock: <?= esc((string) $summary['out_of_stock']) ?></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>

			<section class="mb-5">
				<div class="row g-4">
					<div class="col-lg-6">
						<div class="panel-card p-3" style="animation-delay: 0.3s;">
							<div class="card-body">
								<h2 class="h5 mb-3">Low Stock Spotlight</h2>
								<?php if ($lowStockItems === []): ?>
									<p class="text-muted mb-0">All tracked items are healthy today.</p>
								<?php else: ?>
									<div class="table-responsive">
										<table class="table table-sm align-middle mb-0">
											<thead>
												<tr>
													<th>Item</th>
													<th class="text-end">Qty</th>
													<th class="text-end">Min</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($lowStockItems as $item): ?>
													<tr>
														<td><?= esc((string) ($item['name'] ?? '')) ?></td>
														<td class="text-end"><?= esc((string) ((int) ($item['stock_quantity'] ?? 0))) ?></td>
														<td class="text-end"><?= esc((string) ((int) ($item['minimum_stock'] ?? 0))) ?></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="panel-card p-3" style="animation-delay: 0.35s;">
							<div class="card-body">
								<h2 class="h5 mb-3">Recent Receipts</h2>
								<?php if ($recentReceipts === []): ?>
									<p class="text-muted mb-0">No sales recorded yet.</p>
								<?php else: ?>
									<div class="table-responsive">
										<table class="table table-sm align-middle mb-0">
											<thead>
												<tr>
													<th>Receipt</th>
													<th class="text-end">Amount</th>
													<th class="text-end">Date</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($recentReceipts as $receipt): ?>
													<?php
														$receiptDate = (string) ($receipt['created_at'] ?? '');
														$timestamp = $receiptDate !== '' ? strtotime($receiptDate) : false;
														$receiptLabel = $timestamp ? date('M j, Y g:i A', $timestamp) : '-';
													?>
													<tr>
														<td><?= esc((string) ($receipt['receipt_number'] ?? '')) ?></td>
														<td class="text-end">PHP <?= esc(number_format((float) ($receipt['total_amount'] ?? 0), 2)) ?></td>
														<td class="text-end text-muted small"><?= esc($receiptLabel) ?></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</section>

			<?php endif; ?>
			<section class="mb-5">
				<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
					<h2 class="h5 mb-0">Quick Actions</h2>
					<span class="text-muted small">Only tools that are ready to use</span>
				</div>
				<div class="row g-3">
					<?php foreach ($visibleActions as $action): ?>
						<div class="col-md-6 col-lg-4">
							<a href="<?= esc(base_url((string) $action['path'])) ?>" class="action-card">
								<div class="action-icon"><i class="bi <?= esc($action['icon']) ?>"></i></div>
								<div>
									<div class="action-title"><?= esc($action['label']) ?></div>
									<div class="action-subtext">Open module</div>
								</div>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		</main>
	</div>
</div>

<?php if ($role === 'Owner' && $lowStockCount > 0): ?>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
	document.addEventListener('DOMContentLoaded', () => {
		if (window.Chart) {
			Chart.defaults.font.family = 'Manrope, Segoe UI, sans-serif';
			Chart.defaults.color = '#475569';
		}

		const salesTrend = <?= json_encode($salesTrend, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
		const salesLabels = Array.isArray(salesTrend.labels) ? salesTrend.labels : [];
		const salesValues = Array.isArray(salesTrend.values) ? salesTrend.values : [];
		const salesCanvas = document.getElementById('salesTrendChart');

		if (salesCanvas && window.Chart) {
			const ctx = salesCanvas.getContext('2d');
			const gradient = ctx.createLinearGradient(0, 0, 0, 160);
			gradient.addColorStop(0, 'rgba(14, 165, 233, 0.35)');
			gradient.addColorStop(1, 'rgba(14, 165, 233, 0.02)');

			new Chart(salesCanvas, {
				type: 'line',
				data: {
					labels: salesLabels,
					datasets: [
						{
							label: 'Revenue',
							data: salesValues,
							borderColor: '#0ea5e9',
							backgroundColor: gradient,
							fill: true,
							tension: 0.35,
							pointRadius: 3,
							pointBackgroundColor: '#0ea5e9',
						}
					]
				},
				options: {
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							callbacks: {
								label: (ctx) => `PHP ${Number(ctx.parsed.y || 0).toLocaleString()}`
							}
						}
					},
					scales: {
						y: {
							ticks: {
								callback: (value) => `PHP ${Number(value).toLocaleString()}`
							},
							grid: {
								color: 'rgba(148, 163, 184, 0.2)'
							}
						},
						x: {
							grid: {
								display: false
							}
						}
					}
				}
			});
		}

		const stockCanvas = document.getElementById('stockStatusChart');
		const stockStatus = [
			<?= (int) ($summary['in_stock'] ?? 0) ?>,
			<?= (int) ($summary['low_stock'] ?? 0) ?>,
			<?= (int) ($summary['out_of_stock'] ?? 0) ?>
		];

		if (stockCanvas && window.Chart) {
			new Chart(stockCanvas, {
				type: 'doughnut',
				data: {
					labels: ['In Stock', 'Low Stock', 'Out of Stock'],
					datasets: [
						{
							data: stockStatus,
							backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'],
							borderWidth: 0,
						}
					]
				},
				options: {
					cutout: '70%',
					plugins: {
						legend: {
							display: false
						}
					}
				}
			});
		}

		const lowStockToastEl = document.getElementById('lowStockToast');
		if (lowStockToastEl && window.bootstrap && window.bootstrap.Toast) {
			const lowStockToast = new window.bootstrap.Toast(lowStockToastEl);
			lowStockToast.show();
		}
	});
</script>

<style>
	@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600&family=Space+Grotesk:wght@500;600&display=swap');

	.analytics-dashboard {
		--ink: #0f172a;
		--muted: #64748b;
		--brand: #0ea5e9;
		--accent: #10b981;
		--card: #ffffff;
		--stroke: rgba(15, 23, 42, 0.08);
		--glow: rgba(14, 165, 233, 0.15);
		font-family: 'Manrope', 'Segoe UI', sans-serif;
		color: var(--ink);
	}

	.analytics-dashboard h1,
	.analytics-dashboard h2,
	.analytics-dashboard h3,
	.analytics-dashboard .metric-label,
	.analytics-dashboard .action-title {
		font-family: 'Space Grotesk', 'Manrope', sans-serif;
	}

	.analytics-dashboard .content-area {
		position: relative;
	}

	.analytics-dashboard .content-area::before {
		content: '';
		position: absolute;
		inset: -2rem -2rem auto -2rem;
		height: 45vh;
		background:
			radial-gradient(circle at 8% 10%, rgba(14, 165, 233, 0.18), transparent 55%),
			radial-gradient(circle at 78% 0%, rgba(16, 185, 129, 0.18), transparent 55%);
		pointer-events: none;
		z-index: 0;
	}

	.analytics-dashboard .content-area > * {
		position: relative;
		z-index: 1;
	}

	.analytics-dashboard .dashboard-hero {
		background: linear-gradient(120deg, rgba(14, 165, 233, 0.2), rgba(16, 185, 129, 0.2));
		border: 1px solid rgba(14, 165, 233, 0.2);
		border-radius: 26px;
		padding: 2rem;
		box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
	}

	.role-pill {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.6rem 1rem;
		border-radius: 999px;
		background: rgba(255, 255, 255, 0.8);
		border: 1px solid rgba(148, 163, 184, 0.2);
		font-weight: 600;
		color: var(--ink);
		backdrop-filter: blur(8px);
	}

	.metric-card {
		background: var(--card);
		border-radius: 22px;
		padding: 1.5rem;
		border: 1px solid var(--stroke);
		box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
		position: relative;
		overflow: hidden;
		animation: rise 0.7s ease both;
	}

	.metric-card::after {
		content: '';
		position: absolute;
		inset: 60% -20% auto auto;
		width: 140px;
		height: 140px;
		background: radial-gradient(circle, var(--glow), transparent 70%);
		opacity: 0.7;
		pointer-events: none;
	}

	.metric-label {
		color: var(--muted);
		font-size: 0.9rem;
		text-transform: uppercase;
		letter-spacing: 0.08em;
		margin-bottom: 0.35rem;
	}

	.metric-value {
		font-size: 1.6rem;
		font-weight: 600;
		color: var(--ink);
	}

	.metric-subtext {
		color: var(--muted);
		font-size: 0.85rem;
	}

	.metric-icon {
		width: 46px;
		height: 46px;
		border-radius: 16px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		background: rgba(14, 165, 233, 0.12);
		color: #0284c7;
		font-size: 1.2rem;
	}

	.metric-icon-warning {
		background: rgba(245, 158, 11, 0.15);
		color: #d97706;
	}

	.metric-icon-danger {
		background: rgba(244, 63, 94, 0.15);
		color: #e11d48;
	}

	.metric-icon-accent {
		background: rgba(16, 185, 129, 0.15);
		color: #059669;
	}

	.panel-card {
		border-radius: 22px;
		border: 1px solid var(--stroke);
		box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
		background: var(--card);
		animation: rise 0.7s ease both;
	}

	.chart-shell {
		height: 220px;
		position: relative;
	}

	.status-list {
		display: grid;
		gap: 0.35rem;
		font-size: 0.85rem;
		color: var(--muted);
		margin-top: 1rem;
	}

	.status-list .dot {
		display: inline-block;
		width: 10px;
		height: 10px;
		border-radius: 50%;
		margin-right: 0.4rem;
	}

	.dot-good {
		background: #10b981;
	}

	.dot-warn {
		background: #f59e0b;
	}

	.dot-bad {
		background: #f43f5e;
	}

	.action-card {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding: 1.25rem;
		border-radius: 18px;
		border: 1px solid rgba(148, 163, 184, 0.2);
		background: #ffffff;
		box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
		text-decoration: none;
		color: inherit;
		transition: transform 0.2s ease, box-shadow 0.2s ease;
		animation: rise 0.7s ease both;
	}

	.action-card:hover {
		transform: translateY(-4px);
		box-shadow: 0 18px 30px rgba(15, 23, 42, 0.12);
	}

	.action-icon {
		width: 48px;
		height: 48px;
		border-radius: 16px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		background: rgba(14, 165, 233, 0.12);
		color: #0284c7;
		font-size: 1.2rem;
	}

	.action-title {
		font-size: 1.05rem;
		font-weight: 600;
		margin-bottom: 0.2rem;
	}

	.action-subtext {
		font-size: 0.85rem;
		color: var(--muted);
	}

	.low-stock-card-badge {
		position: absolute;
		top: 0.65rem;
		right: 0.75rem;
	}

	@keyframes rise {
		from {
			opacity: 0;
			transform: translateY(12px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	@media (max-width: 991px) {
		.chart-shell {
			height: 200px;
		}
	}
</style>
