<?php
$daily = $metrics['daily'] ?? ['revenue' => 0, 'expenses' => 0, 'net_income' => 0, 'profit_margin' => 0];
$weekly = $metrics['weekly'] ?? ['revenue' => 0, 'expenses' => 0, 'net_income' => 0, 'profit_margin' => 0];
$monthly = $metrics['monthly'] ?? ['revenue' => 0, 'expenses' => 0, 'net_income' => 0, 'profit_margin' => 0];
?>

<div class="container-fluid px-4 px-lg-5 py-4 py-lg-5">
	<div class="row mb-4">
		<div class="col-12">
			<div class="p-4 rounded-4 border bg-white shadow-sm">
				<h2 class="h4 mb-1">Financial Analytics</h2>
				<p class="text-muted mb-0">Revenue, expenses, net income, and margin tracking.</p>
			</div>
		</div>
	</div>

	<?php if (session()->getFlashdata('success')): ?>
		<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
	<?php endif; ?>
	<?php if (session()->getFlashdata('error')): ?>
		<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
	<?php endif; ?>

	<div class="row g-3 mb-3">
		<div class="col-md-6 col-lg-3">
			<div class="card border-0 shadow-sm rounded-4 h-100">
				<div class="card-body">
					<div class="text-muted small">Daily Revenue</div>
					<div class="h4 mb-0">₱<?= esc(number_format((float) $daily['revenue'], 2)) ?></div>
				</div>
			</div>
		</div>
		<div class="col-md-6 col-lg-3">
			<div class="card border-0 shadow-sm rounded-4 h-100">
				<div class="card-body">
					<div class="text-muted small">Daily Expenses</div>
					<div class="h4 mb-0">₱<?= esc(number_format((float) $daily['expenses'], 2)) ?></div>
				</div>
			</div>
		</div>
		<div class="col-md-6 col-lg-3">
			<div class="card border-0 shadow-sm rounded-4 h-100">
				<div class="card-body">
					<div class="text-muted small">Net Income</div>
					<div class="h4 mb-0">₱<?= esc(number_format((float) $daily['net_income'], 2)) ?></div>
				</div>
			</div>
		</div>
		<div class="col-md-6 col-lg-3">
			<div class="card border-0 shadow-sm rounded-4 h-100">
				<div class="card-body">
					<div class="text-muted small">Profit Margin</div>
					<div class="h4 mb-0"><?= esc(number_format((float) $daily['profit_margin'], 2)) ?>%</div>
				</div>
			</div>
		</div>
	</div>

	<div class="mb-4">
		<button
			class="btn btn-sm btn-outline-secondary js-financial-breakdown-toggle"
			type="button"
			data-bs-toggle="collapse"
			data-bs-target="#financial-breakdown-panel"
			aria-expanded="false"
			aria-controls="financial-breakdown-panel"
		>
			Show breakdown
		</button>
		<div id="financial-breakdown-panel" class="collapse mt-2">
			<div class="border rounded-3 p-3 bg-light">
				<div class="row g-2 align-items-end mb-3">
					<div class="col-12 col-md-4">
						<label class="form-label small mb-1" for="breakdown-filter">Filter</label>
						<select class="form-select form-select-sm js-breakdown-filter" id="breakdown-filter">
							<option value="all" selected>All</option>
							<option value="batch">Batch expenses</option>
							<option value="month">By month</option>
						</select>
					</div>
					<div class="col-6 col-md-3">
						<label class="form-label small mb-1" for="breakdown-month">Month (01-12)</label>
						<select class="form-select form-select-sm js-breakdown-month" id="breakdown-month" disabled>
							<option value="" selected>Month</option>
							<?php for ($month = 1; $month <= 12; $month++): ?>
								<?php $monthLabel = str_pad((string) $month, 2, '0', STR_PAD_LEFT); ?>
								<option value="<?= esc($month) ?>"><?= esc($monthLabel) ?></option>
							<?php endfor; ?>
						</select>
					</div>
					<div class="col-6 col-md-3">
						<label class="form-label small mb-1" for="breakdown-year">Year (YYYY)</label>
						<select class="form-select form-select-sm js-breakdown-year" id="breakdown-year" disabled>
							<?php $currentYear = (int) date('Y'); ?>
							<?php for ($year = $currentYear; $year >= $currentYear - 5; $year--): ?>
								<option value="<?= esc($year) ?>" <?= $year === $currentYear ? 'selected' : '' ?>><?= esc($year) ?></option>
							<?php endfor; ?>
						</select>
					</div>
					<div class="col-12 col-md-2">
						<button class="btn btn-sm btn-primary w-100 js-breakdown-search" type="button">Search</button>
					</div>
				</div>
				<div class="js-breakdown-summary text-muted small">Select a filter and click Search. Month requires a month and year.</div>
				<div class="js-breakdown-content mt-3">
					<div class="text-muted small">No data loaded yet.</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-4">
		<div class="col-lg-6">
			<div class="card border-0 shadow-sm rounded-4 h-100">
				<div class="card-body">
					<h3 class="h5">Weekly Financial Report</h3>
					<table class="table table-sm align-middle mb-3">
						<tbody>
							<tr><th>Revenue</th><td class="text-end">₱<?= esc(number_format((float) $weekly['revenue'], 2)) ?></td></tr>
							<tr><th>Expenses</th><td class="text-end">₱<?= esc(number_format((float) $weekly['expenses'], 2)) ?></td></tr>
							<tr><th>Net Income</th><td class="text-end">₱<?= esc(number_format((float) $weekly['net_income'], 2)) ?></td></tr>
							<tr><th>Profit Margin</th><td class="text-end"><?= esc(number_format((float) $weekly['profit_margin'], 2)) ?>%</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-lg-6">
			<div class="card border-0 shadow-sm rounded-4 h-100">
				<div class="card-body">
					<h3 class="h5">Monthly Financial Statement</h3>
					<table class="table table-sm align-middle mb-3">
						<tbody>
							<tr><th>Revenue</th><td class="text-end">₱<?= esc(number_format((float) $monthly['revenue'], 2)) ?></td></tr>
							<tr><th>Expenses</th><td class="text-end">₱<?= esc(number_format((float) $monthly['expenses'], 2)) ?></td></tr>
							<tr><th>Net Income</th><td class="text-end">₱<?= esc(number_format((float) $monthly['net_income'], 2)) ?></td></tr>
							<tr><th>Profit Margin</th><td class="text-end"><?= esc(number_format((float) $monthly['profit_margin'], 2)) ?>%</td></tr>
						</tbody>
					</table>
					<form method="get" action="<?= base_url('financial/export-csv') ?>" class="row g-2">
						<div class="col-4">
							<input class="form-control" type="number" min="2000" name="year" value="<?= esc(date('Y')) ?>" required>
						</div>
						<div class="col-4">
							<input class="form-control" type="number" min="1" max="12" name="month" value="<?= esc(date('n')) ?>" required>
						</div>
						<div class="col-4">
							<button type="submit" class="btn btn-primary w-100">Export CSV</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(() => {
	const toggle = document.querySelector('.js-financial-breakdown-toggle');
	const panel = document.getElementById('financial-breakdown-panel');
	if (!toggle || !panel) {
		return;
	}

	const endpointBase = '<?= base_url('financial/breakdown') ?>';
	const cache = {};

	const formatCurrency = (value) => `₱${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
	const formatPercent = (value) => `${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
	const escapeHtml = (value) => String(value ?? '')
		.replaceAll('&', '&amp;')
		.replaceAll('<', '&lt;')
		.replaceAll('>', '&gt;')
		.replaceAll('"', '&quot;')
		.replaceAll("'", '&#039;');

	const filterSelect = panel.querySelector('.js-breakdown-filter');
	const monthInput = panel.querySelector('.js-breakdown-month');
	const yearInput = panel.querySelector('.js-breakdown-year');
	const searchButton = panel.querySelector('.js-breakdown-search');
	const summaryHost = panel.querySelector('.js-breakdown-summary');
	const contentHost = panel.querySelector('.js-breakdown-content');

	const buildUrl = (params) => `${endpointBase}?${new URLSearchParams(params).toString()}`;

	const renderSummary = (payload) => {
		const label = payload?.label ? escapeHtml(payload.label) : 'Selected period';
		const totals = payload?.totals ?? {};
		return `
			<div>Period: ${label}</div>
			<div>Revenue: ${formatCurrency(totals.revenue)} | Expenses: ${formatCurrency(totals.expenses)} | Net: ${formatCurrency(totals.net_income)} | Margin: ${formatPercent(totals.profit_margin)}</div>
		`;
	};

	const renderTable = (rows) => {
		if (!Array.isArray(rows) || rows.length === 0) {
			return '<div class="text-muted small">No rows for this filter.</div>';
		}

		const bodyHtml = rows.map((row) => {
			const type = row.type === 'Revenue' ? 'success' : 'danger';
			const typeBadge = `<span class="badge bg-${type}">${escapeHtml(row.type)}</span>`;
			return `
				<tr>
					<td>${escapeHtml(row.date)}</td>
					<td>${typeBadge}</td>
					<td>${escapeHtml(row.source)}</td>
					<td>${escapeHtml(row.reference)}</td>
					<td>${escapeHtml(row.notes)}</td>
					<td class="text-end">${formatCurrency(row.amount)}</td>
				</tr>
			`;
		}).join('');

		return `
			<div class="table-responsive">
				<table class="table table-sm table-striped align-middle mb-0">
					<thead>
						<tr>
							<th>Date</th>
							<th>Type</th>
							<th>Source</th>
							<th>Reference</th>
							<th>Notes</th>
							<th class="text-end">Amount</th>
						</tr>
					</thead>
					<tbody>${bodyHtml}</tbody>
				</table>
			</div>
		`;
	};

	const setOutput = (summaryHtml, contentHtml) => {
		if (summaryHost) {
			summaryHost.innerHTML = summaryHtml;
		}
		if (contentHost) {
			contentHost.innerHTML = contentHtml;
		}
	};

	const loadBreakdown = async (params) => {
		if (!summaryHost || !contentHost) {
			return;
		}
		const cacheKey = new URLSearchParams(params).toString();
		if (cache[cacheKey]) {
			setOutput(cache[cacheKey].summary, cache[cacheKey].content);
			return;
		}

		setOutput('<div class="text-muted small">Loading summary...</div>', '<div class="text-muted small">Loading rows...</div>');

		try {
			const response = await fetch(buildUrl(params), {
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
			});
			if (!response.ok) {
				throw new Error('Request failed');
			}

			const payload = await response.json();
			const summaryHtml = renderSummary(payload);
			const contentHtml = renderTable(payload?.rows ?? []);
			cache[cacheKey] = { summary: summaryHtml, content: contentHtml };
			setOutput(summaryHtml, contentHtml);
		} catch (error) {
			setOutput('<div class="text-danger small">Unable to load summary.</div>', '<div class="text-danger small">Unable to load rows.</div>');
		}
	};

	const loadFromFilters = () => {
		const filterValue = filterSelect?.value?.trim() || 'all';
		if (filterValue === 'month') {
			const monthValue = monthInput?.value?.trim();
			const yearValue = yearInput?.value?.trim();
			if (!monthValue || !yearValue) {
				setOutput('<div class="text-muted small">Select a month and year to search.</div>', '<div class="text-muted small">No data loaded.</div>');
				return;
			}
			loadBreakdown({ period: 'month', month: monthValue, year: yearValue });
			return;
		}

		loadBreakdown({ period: filterValue });
	};

	const syncFilterState = () => {
		const isMonth = filterSelect?.value === 'month';
		if (monthInput) {
			monthInput.disabled = !isMonth;
		}
		if (yearInput) {
			yearInput.disabled = !isMonth;
		}
	};

	if (searchButton) {
		searchButton.addEventListener('click', loadFromFilters);
	}
	if (filterSelect) {
		filterSelect.addEventListener('change', syncFilterState);
	}

	let initialLoadDone = false;
	panel.addEventListener('shown.bs.collapse', () => {
		if (initialLoadDone) {
			return;
		}
		initialLoadDone = true;
		syncFilterState();
		loadBreakdown({ period: filterSelect?.value || 'all' });
	});
})();
</script>
