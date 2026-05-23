<?php
$bills = $bills ?? [];
$productExpenses = $productExpenses ?? [];
$billFilters = array_merge(
	['status' => '', 'from_date' => '', 'to_date' => '', 'keyword' => ''],
	is_array($billFilters ?? null) ? $billFilters : []
);
$activeTab = $activeTab ?? 'bills';
?>

<div class="container-fluid px-4 px-lg-5 py-4 py-lg-5">
	<div class="row mb-4">
		<div class="col-12">
			<div class="p-4 rounded-4 border bg-white shadow-sm">
				<h2 class="h4 mb-1">Expense Tracking</h2>
				<p class="text-muted mb-0">Track bills and stock-in capital expenses.</p>
			</div>
		</div>
	</div>

	<?php if (session()->getFlashdata('success')): ?>
		<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
	<?php endif; ?>
	<?php if (session()->getFlashdata('error')): ?>
		<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
	<?php endif; ?>
	<?php $errors = session()->getFlashdata('errors') ?? []; ?>
	<?php if (!empty($errors)): ?>
		<div class="alert alert-danger">
			<ul class="mb-0">
				<?php foreach ($errors as $error): ?>
					<li><?= esc($error) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<ul class="nav nav-tabs mb-3">
		<li class="nav-item">
			<a class="nav-link <?= $activeTab === 'bills' ? 'active' : '' ?>" href="<?= base_url('financial/expenses?tab=bills') ?>">Bills</a>
		</li>
		<li class="nav-item">
			<a class="nav-link <?= $activeTab === 'product-expenses' ? 'active' : '' ?>" href="<?= base_url('financial/expenses?tab=product-expenses') ?>">Product Expenses</a>
		</li>
	</ul>

	<?php if ($activeTab === 'bills'): ?>
		<div class="card border-0 shadow-sm rounded-4 mb-4">
			<div class="card-body p-4">
				<h3 class="h6 mb-3">Bills Filters</h3>
				<form class="row g-2" method="get" action="<?= base_url('financial/expenses') ?>">
					<input type="hidden" name="tab" value="bills">
					<div class="col-md-3">
						<select class="form-select" name="status">
							<option value="" <?= $billFilters['status'] === '' ? 'selected' : '' ?>>All status</option>
							<option value="paid" <?= $billFilters['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
							<option value="unpaid" <?= $billFilters['status'] === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
						</select>
					</div>
					<div class="col-md-2">
						<input type="date" class="form-control" name="from_date" value="<?= esc((string) $billFilters['from_date']) ?>">
					</div>
					<div class="col-md-2">
						<input type="date" class="form-control" name="to_date" value="<?= esc((string) $billFilters['to_date']) ?>">
					</div>
					<div class="col-md-3">
						<input type="text" class="form-control" name="keyword" placeholder="Bill name or note" value="<?= esc((string) $billFilters['keyword']) ?>">
					</div>
					<div class="col-md-2">
						<button type="submit" class="btn btn-outline-secondary w-100">Apply</button>
					</div>
				</form>
			</div>
		</div>

		<div class="row g-4">
			<div class="col-lg-4">
				<div class="card border-0 shadow-sm rounded-4">
					<div class="card-body p-4">
						<h3 class="h6">Add Bill</h3>
						<form method="post" action="<?= base_url('financial/bills/create') ?>">
							<?= csrf_field() ?>
							<div class="mb-3">
								<label class="form-label" for="bill_name">Bill Name</label>
								<input type="text" class="form-control" id="bill_name" name="bill_name" maxlength="150" required>
							</div>
							<div class="mb-3">
								<label class="form-label" for="amount">Amount</label>
								<input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" required>
							</div>
							<div class="mb-3">
								<label class="form-label" for="bill_date">Bill Date</label>
								<input type="date" class="form-control" id="bill_date" name="bill_date" value="<?= esc(date('Y-m-d')) ?>" required>
							</div>
							<div class="mb-3">
								<label class="form-label" for="due_date">Due Date</label>
								<input type="date" class="form-control" id="due_date" name="due_date" value="<?= esc(date('Y-m-d')) ?>" required>
							</div>
							<div class="mb-3">
								<label class="form-label" for="status">Status</label>
								<select class="form-select" id="status" name="status" required>
									<option value="unpaid">Unpaid</option>
									<option value="paid">Paid</option>
								</select>
							</div>
							<div class="mb-3">
								<label class="form-label" for="notes">Notes</label>
								<input type="text" class="form-control" id="notes" name="notes" maxlength="255">
							</div>
							<button type="submit" class="btn btn-primary w-100">Save Bill</button>
						</form>
					</div>
				</div>
			</div>

			<div class="col-lg-8">
				<div class="card border-0 shadow-sm rounded-4">
					<div class="card-body p-4">
						<h3 class="h6 mb-3">Bills</h3>
						<div class="table-responsive">
							<table class="table table-hover align-middle mb-0">
								<thead>
									<tr>
										<th>Bill</th>
										<th>Bill Date</th>
										<th>Due Date</th>
										<th>Status</th>
										<th>Notes</th>
										<th class="text-end">Amount</th>
										<th class="text-end">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php if (empty($bills)): ?>
										<tr>
											<td colspan="7" class="text-center text-muted py-4">No bills found.</td>
										</tr>
									<?php else: ?>
										<?php foreach ($bills as $bill): ?>
											<tr>
												<td><?= esc($bill['bill_name']) ?></td>
												<td><?= esc((string) $bill['bill_date']) ?></td>
												<td><?= esc((string) $bill['due_date']) ?></td>
												<td>
													<span class="badge <?= ($bill['status'] ?? '') === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
														<?= esc(ucfirst((string) ($bill['status'] ?? 'unpaid'))) ?>
													</span>
												</td>
												<td><?= esc((string) ($bill['notes'] ?? '')) ?></td>
												<td class="text-end">₱<?= esc(number_format((float) ($bill['amount'] ?? 0), 2)) ?></td>
												<td class="text-end">
													<form class="d-inline" method="post" action="<?= base_url('financial/bills/update/' . (int) $bill['id']) ?>">
														<?= csrf_field() ?>
														<input type="hidden" name="bill_name" value="<?= esc((string) $bill['bill_name']) ?>">
														<input type="hidden" name="amount" value="<?= esc((string) $bill['amount']) ?>">
														<input type="hidden" name="bill_date" value="<?= esc((string) $bill['bill_date']) ?>">
														<input type="hidden" name="due_date" value="<?= esc((string) $bill['due_date']) ?>">
														<input type="hidden" name="notes" value="<?= esc((string) ($bill['notes'] ?? '')) ?>">
														<input type="hidden" name="status" value="<?= ($bill['status'] ?? '') === 'paid' ? 'unpaid' : 'paid' ?>">
														<button type="submit" class="btn btn-sm btn-outline-primary">Toggle Status</button>
													</form>
													<form class="d-inline" method="post" action="<?= base_url('financial/bills/delete/' . (int) $bill['id']) ?>" onsubmit="return confirm('Delete this bill?');">
														<?= csrf_field() ?>
														<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
													</form>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php else: ?>
		<div class="card border-0 shadow-sm rounded-4">
			<div class="card-body p-4">
				<h3 class="h6 mb-3">Product Expenses (Auto-generated from Stock-In Capital)</h3>
				<div class="table-responsive">
					<table class="table table-hover align-middle mb-0">
						<thead>
							<tr>
								<th>Date</th>
								<th>Source</th>
								<th>Stock-In ID</th>
								<th class="text-end">Amount</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($productExpenses)): ?>
								<tr>
									<td colspan="4" class="text-center text-muted py-4">No product expenses found.</td>
								</tr>
							<?php else: ?>
								<?php foreach ($productExpenses as $row): ?>
									<tr>
										<td><?= esc(date('Y-m-d', strtotime((string) $row['expense_date']))) ?></td>
										<td><?= esc((string) ($row['source_label'] ?? 'stock-in-capital')) ?></td>
										<td><?= esc((string) ($row['stock_in_id'] ?? '')) ?></td>
										<td class="text-end">₱<?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
