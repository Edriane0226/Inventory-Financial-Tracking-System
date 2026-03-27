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

<div class="container-fluid px-4 px-lg-5 py-4 py-lg-5">
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
		<div class="col-lg-3">
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

						<div class="mb-3 p-3 bg-light rounded-3 border border-info">
							<label class="form-label fw-semibold mb-2" for="barcode">
								<i class="bi bi-barcode"></i> Barcode
							</label>
							<input type="text" class="form-control form-control-lg font-monospace" id="barcode" name="barcode" value="<?= esc(old('barcode')) ?>" readonly style="background-color: #fff; letter-spacing: 2px; font-weight: 500;">
							<div class="barcode-preview-wrap mt-2 bg-white border rounded p-2 text-center">
								<svg id="barcodePreview" class="barcode-svg" aria-label="Generated barcode preview"></svg>
							</div>
							<small class="text-muted d-block mt-2"><i class="bi bi-info-circle"></i> Automatically generated from product details</small>
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
								<?php if (empty($stockIns)): ?>
									<tr>
										<td colspan="8" class="text-center text-muted py-5">No stock-in records yet.</td>
									</tr>
								<?php else: ?>
									<?php foreach ($stockIns as $stockIn): ?>
										<tr>
											<td class="fw-semibold"><?= esc($stockIn['product_name']) ?></td>
											<td class="text-center"><?= esc($stockIn['quantity']) ?></td>
											<td><?= esc($categoryNames[$stockIn['category_id'] ?? null] ?? 'N/A') ?></td>
											<td class="text-center"><?= esc($unitTypeNames[$stockIn['unit_type_id'] ?? null] ?? 'N/A') ?></td>
											<td><?= esc($batchByStockIn[$stockIn['id']]['batch_number'] ?? '-') ?></td>
											<td class="text-center barcode-cell">
												<?php $barcodeValue = (string) ($stockIn['barcode'] ?? '-'); ?>
												<?php if ($barcodeValue !== '-'): ?>
													<svg
														class="table-barcode"
														data-barcode="<?= esc($barcodeValue) ?>"
														aria-label="Barcode <?= esc($barcodeValue) ?>"
													></svg>
												<?php else: ?>
													<span class="text-muted">-</span>
												<?php endif; ?>
											</td>
											<td class="text-end fw-semibold">₱<?= esc(number_format((float) ($capitalByStockIn[$stockIn['id']] ?? 0), 2)) ?></td>
											<td class="text-center text-muted"><?= esc(date('m/d/y', strtotime($stockIn['stock_in_date']))) ?></td>
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

	// Auto-generate barcode when key fields are filled
	const generateBarcode = () => {
		const productName = document.getElementById('product_name').value;
		const batchNumber = document.getElementById('batch_number').value;
		const quantity = document.getElementById('quantity').value;
		const barcodeField = document.getElementById('barcode');

		if (productName && batchNumber && quantity) {
			// Build a scanner-friendly alphanumeric code for CODE128 rendering.
			const productCode = productName.replace(/[^a-zA-Z0-9]/g, '').substring(0, 3).toUpperCase().padEnd(3, '0');
			const batchCode = batchNumber.replace(/[^a-zA-Z0-9]/g, '').substring(0, 4).toUpperCase().padEnd(4, '0');
			const timestamp = Date.now().toString().slice(-6);
			const barcode = `${productCode}${batchCode}${timestamp}`;
			barcodeField.value = barcode;
			renderBarcodeSvg('#barcodePreview', barcode, { width: 1.4, height: 48 });
		} else {
			document.getElementById('barcodePreview').innerHTML = '';
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

	// Listen for input changes on relevant fields
	document.getElementById('product_name').addEventListener('input', generateBarcode);
	document.getElementById('batch_number').addEventListener('input', generateBarcode);
	document.getElementById('quantity').addEventListener('input', generateBarcode);
	document.getElementById('category').addEventListener('change', generateBarcode);

	// Generate barcode on page load if form has old values
	window.addEventListener('load', () => {
		generateBarcode();
		renderTableBarcodes();
	});
</script>
