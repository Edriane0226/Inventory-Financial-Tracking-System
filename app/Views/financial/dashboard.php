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

	<div class="row g-3 mb-4">
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

	<div class="row g-4">
		<div class="col-lg-6">
			<div class="card border-0 shadow-sm rounded-4 h-100">
				<div class="card-body">
					<h3 class="h5">Weekly Financial Report</h3>
					<table class="table table-sm align-middle mb-0">
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

