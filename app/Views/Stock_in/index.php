<?php
$categoriesList = isset($categories) && is_array($categories) ? $categories : [];
$unitTypesList = isset($unitTypes) && is_array($unitTypes) ? $unitTypes : [];
$capitalsList = isset($capitals) && is_array($capitals) ? $capitals : [];
$productBatchesList = isset($productBatches) && is_array($productBatches) ? $productBatches : [];
$stockInsList = isset($stockIns) && is_array($stockIns) ? $stockIns : [];

$categoryNames = [];
foreach ($categoriesList as $category) {
	$categoryId = is_array($category) ? (int) ($category['id'] ?? 0) : 0;
	$categoryName = is_array($category) ? (string) ($category['category_name'] ?? '') : '';
	$categoryNames[$categoryId] = $categoryName;
}

$unitTypeNames = [];
foreach ($unitTypesList as $unitType) {
	$unitTypeId = is_array($unitType) ? (int) ($unitType['id'] ?? 0) : 0;
	$unitTypeName = is_array($unitType) ? (string) ($unitType['unit_type_name'] ?? '') : '';
	$unitTypeNames[$unitTypeId] = $unitTypeName;
}

$capitalByStockIn = [];
foreach ($capitalsList as $capital) {
	$capitalStockInId = is_array($capital) ? (int) ($capital['stock_in_id'] ?? 0) : 0;
	$capitalValue = is_array($capital) ? (float) ($capital['capital'] ?? 0) : 0;
	$capitalByStockIn[$capitalStockInId] = $capitalValue;
}

$batchByStockIn = [];
foreach ($productBatchesList as $batch) {
	$batchStockInId = is_array($batch) ? (int) ($batch['stock_in_id'] ?? 0) : 0;
	$batchByStockIn[$batchStockInId] = [
		'batch_number' => is_array($batch) ? (string) ($batch['batch_number'] ?? '') : '',
		'expiration_date' => is_array($batch) ? (string) ($batch['expiration_date'] ?? '') : '',
	];
}

$stockInsInStock = [];
$stockInsOutOfStock = [];
foreach ($stockInsList as $stockIn) {
	if ((int) ($stockIn['quantity'] ?? 0) <= 0) {
		$stockInsOutOfStock[] = $stockIn;
		continue;
	}

	$stockInsInStock[] = $stockIn;
}

$displayStockIns = array_merge($stockInsInStock, $stockInsOutOfStock);
?>

<div class="container-fluid px-4 px-lg-5 py-4 py-lg-5">

	<?php if (session()->getFlashdata('success')): ?>
		<div class="alert alert-success"><?= esc((string) session()->getFlashdata('success')) ?></div>
	<?php endif; ?>

	<?php if (session()->getFlashdata('error')): ?>
		<div class="alert alert-danger"><?= esc((string) session()->getFlashdata('error')) ?></div>
	<?php endif; ?>

	<?php $errors = session()->getFlashdata('errors') ?? []; ?>
	<?php if (!empty($errors)): ?>
		<div class="alert alert-danger">
			<div class="fw-semibold mb-2">Please fix the following:</div>
			<ul class="mb-0">
				<?php foreach ($errors as $error): ?>
						<li><?= esc((string) $error) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<div class="row g-4">
		<div class="col-lg-3">
			<div class="card border-0 shadow-sm rounded-4">
				<div class="card-body p-4">
					<h3 class="h5 mb-3">New Stock In Entry</h3>
					<form action="<?= base_url('stockin') ?>" method="post">
						<?= csrf_field() ?>

						<div class="mb-3">
							<label class="form-label" for="product_name">Product Name</label>
							<input type="text" class="form-control" id="product_name" name="product_name" value="<?= esc((string) old('product_name')) ?>" required>
						</div>

						<div class="row g-3">
							<div class="col-md-6">
								<label class="form-label" for="quantity">Quantity</label>
								<input type="number" min="1" class="form-control" id="quantity" name="quantity" value="<?= esc((string) old('quantity')) ?>" required>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="unit_type">Unit Type</label>
								<select class="form-select" id="unit_type" name="unit_type" required>
									<option value="">Select unit</option>
									<?php foreach ($unitTypesList as $unitType): ?>
														<?php $unitTypeName = is_array($unitType) ? (string) ($unitType['unit_type_name'] ?? '') : ''; ?>
														<option value="<?= esc($unitTypeName) ?>" <?= old('unit_type') === $unitTypeName ? 'selected' : '' ?>>
															<?= esc($unitTypeName) ?>
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
									<?php foreach ($categoriesList as $category): ?>
										<?php $categoryName = is_array($category) ? (string) ($category['category_name'] ?? '') : ''; ?>
										<option value="<?= esc($categoryName) ?>" <?= old('category') === $categoryName ? 'selected' : '' ?>>
											<?= esc($categoryName) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="row g-3 mt-0">
							<div class="col-md-6">
								<label class="form-label" for="batch_number">Batch Number</label>
								<input type="text" class="form-control" id="batch_number" name="batch_number" value="<?= esc((string) old('batch_number')) ?>" required>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="expiration_date">Expiration Date</label>
								<input type="date" class="form-control" id="expiration_date" name="expiration_date" value="<?= esc((string) old('expiration_date')) ?>" required>
							</div>
						</div>

						<div class="row g-3 mt-0">
							<div class="col-md-6">
								<label class="form-label" for="capital">Capital</label>
								<input type="number" step="0.01" min="0" class="form-control" id="capital" name="capital" value="<?= esc((string) old('capital')) ?>" required>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="sales_price">Sales Price</label>
								<input type="number" step="0.01" min="0" class="form-control" id="sales_price" name="sales_price" value="<?= esc((string) old('sales_price')) ?>" required>
							</div>
						</div>

						<div class="mb-3 mt-3">
							<label class="form-label" for="stockin_date">Stock In Date</label>
							<input type="date" class="form-control" id="stockin_date" name="stockin_date" value="<?= esc((string) old('stockin_date', date('Y-m-d'))) ?>" required>
						</div>

						

						<button type="submit" class="btn btn-primary w-100 btn-lg">Save Stock In</button>
					</form>
				</div>
			</div>
		</div>

		<div class="col-lg-9">
			<div class="card border-0 shadow-sm rounded-4 h-100">
				<div class="card-body p-4">
					<h3 class="h5 mb-4">Recent Stock In Records</h3>

					<div class="table-responsive">
						<table class="table align-middle table-hover stockin-records-table">
							<colgroup>
								<col style="width: 22%;">
								<col style="width: 8%;">
								<col style="width: 12%;">
								<col style="width: 8%;">
								<col style="width: 15%;">
								<col style="width: 15%;">
								<col style="width: 10%;">
								<col style="width: 10%;">
							</colgroup>
							<thead class="table-light">
								<tr>
									<th class="fw-bold">Product</th>
									<th class="fw-bold text-center">Qty</th>
									<th class="fw-bold">Cat</th>
									<th class="fw-bold text-center">Unit</th>
									<th class="fw-bold">Batch</th>
									<th class="fw-bold text-center">Barcode</th>
									<th class="fw-bold text-end">Cap</th>
									<th class="fw-bold text-center">Date</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($displayStockIns)): ?>
									<tr>
										<td colspan="8" class="text-center text-muted py-5">No stock-in records yet.</td>
									</tr>
								<?php else: ?>
									<?php foreach ($displayStockIns as $index => $stockIn): ?>
										<?php
											$stockInRow = is_array($stockIn) ? $stockIn : [];
											$stockInId = (int) ($stockInRow['id'] ?? 0);
											$stockInProductName = (string) ($stockInRow['product_name'] ?? '');
											$stockInQuantity = (int) ($stockInRow['quantity'] ?? 0);
											$stockInCategoryId = (int) ($stockInRow['category_id'] ?? 0);
											$stockInUnitTypeId = (int) ($stockInRow['unit_type_id'] ?? 0);
											$stockInBarcode = (string) ($stockInRow['barcode'] ?? '-');
											$stockInDate = (string) ($stockInRow['stock_in_date'] ?? '');
											$isOutOfStock = $stockInQuantity <= 0;
											$stockInBatch = $batchByStockIn[$stockInId] ?? [];
											$stockInBatchNumber = (string) ($stockInBatch['batch_number'] ?? '-');
											$stockInCapital = (float) ($capitalByStockIn[$stockInId] ?? 0);
											$stockInCategoryName = $categoryNames[$stockInCategoryId] ?? 'N/A';
											$stockInUnitTypeName = $unitTypeNames[$stockInUnitTypeId] ?? 'N/A';
										?>
										<tr>
											<td class="fw-semibold <?= $isOutOfStock ? 'text-danger' : '' ?>">
												<?= $isOutOfStock ? 'Out of Stock: ' : '' ?><?= esc($stockInProductName) ?>
											</td>
											<td class="text-center"><?= esc((string) $stockInQuantity) ?></td>
											<td><?= esc($stockInCategoryName) ?></td>
											<td class="text-center"><?= esc($stockInUnitTypeName) ?></td>
											<td><?= esc($stockInBatchNumber) ?></td>
											<td class="text-center barcode-cell">
												<?php if ($stockInBarcode !== '-'): ?>
													<svg
														class="table-barcode"
														data-barcode="<?= esc($stockInBarcode) ?>"
														aria-label="Barcode <?= esc($stockInBarcode) ?>"
													></svg>
												<?php else: ?>
													<span class="text-muted">-</span>
												<?php endif; ?>
											</td>
											<td class="text-end fw-semibold">₱<?= esc(number_format($stockInCapital, 2)) ?></td>
											<td class="text-center text-muted"><?= esc(date('m/d/y', strtotime($stockInDate))) ?></td>
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

<style>
	.stockin-records-table {
		table-layout: fixed;
		width: 100%;
	}

	.stockin-records-table th,
	.stockin-records-table td {
		padding: 0.5rem 0.45rem;
		white-space: normal;
		overflow-wrap: anywhere;
	}

	.stockin-records-table thead th {
		font-size: 0.86rem;
		letter-spacing: 0.04em;
		white-space: nowrap;
	}

	.stockin-records-table tbody td {
		font-size: 1rem;
	}

	.stockin-records-table code {
		display: inline-block;
		max-width: 7.2rem;
		font-size: 0.94rem;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		text-align: center;
	}

	.barcode-preview-wrap {
		min-height: 64px;
	}

	.barcode-svg {
		max-width: 100%;
		height: 68px;
	}

	.table-barcode {
		width: 120px;
		height: 48px;
		display: block;
		margin: 0 auto;
	}

	.stockin-records-table td.barcode-cell {
		vertical-align: middle;
	}

	.stockin-records-table td:nth-child(2),
	.stockin-records-table td:nth-child(4),
	.stockin-records-table td:nth-child(7),
	.stockin-records-table td:nth-child(8) {
		white-space: nowrap;
	}
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
	const renderBarcodeSvg = (selector, value, options = {}) => {
		const target = document.querySelector(selector);
		if (!target || !value) {
			return;
		}

		try {
			JsBarcode(target, value, {
				format: 'CODE128',
				displayValue: true,
				fontSize: 12,
				textMargin: 2,
				margin: 2,
				lineColor: '#111827',
				...options,
			});
		} catch (error) {
			target.innerHTML = '';
		}
	};

	const renderTableBarcodes = () => {
		document.querySelectorAll('.table-barcode').forEach((svgEl) => {
			const value = svgEl.getAttribute('data-barcode');
			if (!value) {
				return;
			}

			try {
				JsBarcode(svgEl, value, {
					format: 'CODE128',
					displayValue: true,
					fontSize: 10,
					textMargin: 1,
					margin: 1,
					width: 1.1,
					height: 24,
					lineColor: '#111827',
				});
			} catch (error) {
				svgEl.outerHTML = '<span class="text-muted">Invalid</span>';
			}
		});
	};

	// Render barcode preview on page load when a value is already present.
	window.addEventListener('load', () => {
		const barcodeField = document.getElementById('barcode');
		const barcodeValue = (barcodeField?.value ?? '').trim();
		if (barcodeValue !== '') {
			renderBarcodeSvg('#barcodePreview', barcodeValue, { width: 1.4, height: 48 });
		}
		renderTableBarcodes();
	});
</script>
