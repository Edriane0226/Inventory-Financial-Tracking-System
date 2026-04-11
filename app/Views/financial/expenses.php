<?php
$filters = $filters ?? ['category_id' => 0, 'keyword' => '', 'from_date' => '', 'to_date' => ''];
$categories = $categories ?? [];
$expenses = $expenses ?? [];
?>

<div class="container-fluid px-4 px-lg-5 py-4 py-lg-5">
	<div class="row mb-4">
		<div class="col-12">
			<div class="p-4 rounded-4 border bg-white shadow-sm">
				<h2 class="h4 mb-1">Expense History</h2>
				<p class="text-muted mb-0">Record and filter business expenses.</p>
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

	<div class="row g-4">
		<div class="col-lg-4">
			<div class="card border-0 shadow-sm rounded-4">
				<div class="card-body p-4">
					<h3 class="h6">Add Expense</h3>
					<form method="post" action="<?= base_url('financial/expenses/create') ?>">
						<?= csrf_field() ?>
						<div class="mb-3">
							<label class="form-label" for="category_id">Category</label>
							<select class="form-select" id="category_id" name="category_id" required>
								<option value="">Select category</option>
								<?php foreach ($categories as $category): ?>
									<option value="<?= esc((string) $category['id']) ?>"><?= esc($category['name']) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label" for="amount">Amount</label>
							<input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" required>
						</div>
						<div class="mb-3">
							<label class="form-label" for="expense_date">Expense Date</label>
							<input type="date" class="form-control" id="expense_date" name="expense_date" value="<?= esc(date('Y-m-d')) ?>" required>
						</div>
						<div class="mb-3">
							<label class="form-label" for="note">Note</label>
							<input type="text" class="form-control" id="note" name="note" maxlength="255">
						</div>
						<button type="submit" class="btn btn-primary w-100">Save Expense</button>
					</form>
				</div>
			</div>

			<div class="card border-0 shadow-sm rounded-4 mt-4">
				<div class="card-body p-4">
					<h3 class="h6">Add Category</h3>
					<form method="post" action="<?= base_url('financial/categories/create') ?>">
						<?= csrf_field() ?>
						<div class="mb-3">
							<label class="form-label" for="new_category">Category Name</label>
							<input type="text" class="form-control" id="new_category" name="name" maxlength="120" required>
						</div>
						<button type="submit" class="btn btn-outline-primary w-100">Create Category</button>
					</form>
				</div>
			</div>
		</div>

		<div class="col-lg-8">
			<div class="card border-0 shadow-sm rounded-4">
				<div class="card-body p-4">
					<h3 class="h6 mb-3">Filters</h3>
					<form class="row g-2 mb-4" method="get" action="<?= base_url('financial/expenses') ?>">
						<div class="col-md-3">
							<input type="date" class="form-control" name="from_date" value="<?= esc((string) ($filters['from_date'] ?? '')) ?>">
						</div>
						<div class="col-md-3">
							<input type="date" class="form-control" name="to_date" value="<?= esc((string) ($filters['to_date'] ?? '')) ?>">
						</div>
						<div class="col-md-3">
							<select class="form-select" name="category_id">
								<option value="0">All categories</option>
								<?php foreach ($categories as $category): ?>
									<option value="<?= esc((string) $category['id']) ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
										<?= esc($category['name']) ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-3">
							<input type="text" class="form-control" name="keyword" placeholder="Keyword" value="<?= esc((string) ($filters['keyword'] ?? '')) ?>">
						</div>
						<div class="col-12">
							<button type="submit" class="btn btn-outline-secondary">Apply Filters</button>
						</div>
					</form>

					<div class="table-responsive">
						<table class="table table-hover align-middle mb-0">
							<thead>
								<tr>
									<th>Date</th>
									<th>Category</th>
									<th>Note</th>
									<th class="text-end">Amount</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($expenses)): ?>
									<tr>
										<td colspan="4" class="text-center text-muted py-4">No expenses found.</td>
									</tr>
								<?php else: ?>
									<?php foreach ($expenses as $expense): ?>
										<tr>
											<td><?= esc(date('Y-m-d', strtotime((string) $expense['expense_date']))) ?></td>
											<td><?= esc($expense['category_name'] ?? 'N/A') ?></td>
											<td><?= esc($expense['note'] ?? '') ?></td>
											<td class="text-end">₱<?= esc(number_format((float) ($expense['amount'] ?? 0), 2)) ?></td>
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
</div>

