<?php
$categoryNames = [];
foreach (($categories ?? []) as $category) {
	$categoryNames[$category['id']] = $category['category_name'];
}

$unitTypeNames = [];
foreach (($unitTypes ?? []) as $unitType) {
	$unitTypeNames[$unitType['id']] = $unitType['unit_type_name'];
}

$capitalByStockIn = [];
foreach (($capitals ?? []) as $capital) {
	$capitalByStockIn[$capital['stock_in_id']] = $capital['capital'];
}

$batchByStockIn = [];
foreach (($productBatches ?? []) as $batch) {
	$batchByStockIn[$batch['stock_in_id']] = [
		'batch_number' => $batch['batch_number'],
		'expiration_date' => $batch['expiration_date'],
	];
}
?>

<div class="container py-4 py-lg-5">
	<div class="row mb-4">
		<div class="col-12">
			<div class="p-4 rounded-4 border bg-white shadow-sm">
				<h2 class="h4 mb-1">Stock In</h2>
				<p class="text-muted mb-0">Create incoming stock records for Owner and Employee accounts.</p>
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
			<div class="fw-semibold mb-2">Please fix the following:</div>
			<ul class="mb-0">
				<?php foreach ($errors as $error): ?>
					<li><?= esc($error) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<div class="row g-4">
		<div class="col-lg-5">
			<div class="card border-0 shadow-sm rounded-4">
				<div class="card-body p-4">
					<h3 class="h5 mb-3">New Stock In Entry</h3>
					<form action="<?= base_url('stockin') ?>" method="post">
						<?= csrf_field() ?>

						<div class="mb-3">
							<label class="form-label" for="product_name">Product Name</label>
							<input type="text" class="form-control" id="product_name" name="product_name" value="<?= esc(old('product_name')) ?>" required>
						</div>

						<div class="row g-3">
							<div class="col-md-6">
								<label class="form-label" for="quantity">Quantity</label>
								<input type="number" min="1" class="form-control" id="quantity" name="quantity" value="<?= esc(old('quantity')) ?>" required>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="unit_type">Unit Type</label>
								<select class="form-select" id="unit_type" name="unit_type" required>
									<option value="">Select unit</option>
									<?php foreach (($unitTypes ?? []) as $unitType): ?>
										<option value="<?= esc($unitType['unit_type_name']) ?>" <?= old('unit_type') === $unitType['unit_type_name'] ? 'selected' : '' ?>>
											<?= esc($unitType['unit_type_name']) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="row g-3 mt-0">
							<div class="col-md-6">
								<label class="form-label" for="category">Category</label>
								<select class="form-select" id="category" name="category" required>
									<option value="">Select category</option>
									<?php foreach (($categories ?? []) as $category): ?>
										<option value="<?= esc($category['category_name']) ?>" <?= old('category') === $category['category_name'] ? 'selected' : '' ?>>
											<?= esc($category['category_name']) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="row g-3 mt-0">
							<div class="col-md-6">
								<label class="form-label" for="batch_number">Batch Number</label>
								<input type="text" class="form-control" id="batch_number" name="batch_number" value="<?= esc(old('batch_number')) ?>" required>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="expiration_date">Expiration Date</label>
								<input type="date" class="form-control" id="expiration_date" name="expiration_date" value="<?= esc(old('expiration_date')) ?>" required>
							</div>
						</div>

						<div class="row g-3 mt-0">
							<div class="col-md-6">
								<label class="form-label" for="capital">Capital</label>
								<input type="number" step="0.01" min="0" class="form-control" id="capital" name="capital" value="<?= esc(old('capital')) ?>" required>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="sales_price">Sales Price</label>
								<input type="number" step="0.01" min="0" class="form-control" id="sales_price" name="sales_price" value="<?= esc(old('sales_price')) ?>" required>
							</div>
						</div>

						<div class="mb-3 mt-3">
							<label class="form-label" for="stockin_date">Stock In Date</label>
							<input type="date" class="form-control" id="stockin_date" name="stockin_date" value="<?= esc(old('stockin_date', date('Y-m-d'))) ?>" required>
						</div>

						<div class="mb-3">
							<label class="form-label" for="barcode">Barcode</label>
							<input type="text" class="form-control" id="barcode" name="barcode" value="<?= esc(old('barcode')) ?>" required>
						</div>

						<button type="submit" class="btn btn-primary w-100">Save Stock In</button>
					</form>
				</div>
			</div>
		</div>

		<div class="col-lg-7">
			<div class="card border-0 shadow-sm rounded-4 h-100">
				<div class="card-body p-4">
					<h3 class="h5 mb-3">Recent Stock In Records</h3>

					<div class="table-responsive">
						<table class="table align-middle">
							<thead>
								<tr>
									<th>Product</th>
									<th>Qty</th>
									<th>Category</th>
									<th>Unit</th>
									<th>Batch</th>
									<th>Capital</th>
									<th>Date</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($stockIns)): ?>
									<tr>
										<td colspan="7" class="text-center text-muted py-4">No stock-in records yet.</td>
									</tr>
								<?php else: ?>
									<?php foreach ($stockIns as $stockIn): ?>
										<tr>
											<td class="fw-semibold"><?= esc($stockIn['product_name']) ?></td>
											<td><?= esc($stockIn['quantity']) ?></td>
											<td><?= esc($categoryNames[$stockIn['category_id'] ?? null] ?? 'N/A') ?></td>
											<td><?= esc($unitTypeNames[$stockIn['unit_type_id'] ?? null] ?? 'N/A') ?></td>
											<td><?= esc($batchByStockIn[$stockIn['id']]['batch_number'] ?? '-') ?></td>
											<td><?= esc(number_format((float) ($capitalByStockIn[$stockIn['id']] ?? 0), 2)) ?></td>
											<td><?= esc(date('Y-m-d', strtotime($stockIn['stock_in_date']))) ?></td>
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
