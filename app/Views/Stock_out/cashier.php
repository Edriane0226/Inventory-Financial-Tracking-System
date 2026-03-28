<div class="container-fluid px-4 px-lg-5 py-4 py-lg-5">
	<div class="row mb-4">
		<div class="col-12">
			<div class="p-4 rounded-4 border bg-white shadow-sm">
				<h2 class="h4 mb-1">Stock Out - Cashier</h2>
				<p class="text-muted mb-0">Scan barcode and deduct sold quantity. Reason is automatically set to Sold.</p>
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
		<div class="col-lg-4">
			<div class="card border-0 shadow-sm rounded-4">
				<div class="card-body p-4">
					<h3 class="h5 mb-3">Add Sold Item To Queue</h3>
					<form action="<?= base_url('stock-out/cashier') ?>" method="post" id="cashierStockOutForm">
						<?= csrf_field() ?>
						<input type="hidden" name="action" value="add">

						<div class="mb-3">
							<label class="form-label" for="barcode">Barcode</label>
							<div class="input-group">
								<input type="text" class="form-control" id="barcode" name="barcode" value="<?= esc(old('barcode')) ?>" required>
								<button class="btn btn-outline-primary" type="button" id="fetchProductBtn">Fetch</button>
							</div>
						</div>

						<div class="mb-3">
							<label class="form-label" for="quantity">Quantity</label>
							<input type="number" min="1" class="form-control" id="quantity" name="quantity" value="<?= esc(old('quantity')) ?>" required>
						</div>

						<div class="mb-3">
							<label class="form-label">Reason</label>
							<input type="text" class="form-control" value="Sold" readonly>
						</div>

						<div class="mb-3">
							<label class="form-label" for="stock_out_date">Stock Out Date</label>
							<input type="date" class="form-control" id="stock_out_date" name="stock_out_date" value="<?= esc(old('stock_out_date', date('Y-m-d'))) ?>" required>
						</div>

						<div class="border rounded-3 p-3 bg-light mb-3" id="productLookupCard">
							<div class="small text-muted">Scan result</div>
							<div class="fw-semibold" id="productName">-</div>
							<div class="small mt-1">Category: <span id="productCategory">-</span></div>
							<div class="small">Unit: <span id="productUnit">-</span></div>
							<div class="small">Product Batch ID: <span id="productBatchId">-</span></div>
							<div class="small">Sales Price: <span id="salesPrice">0.00</span></div>
							<div class="small">Next exp: <span id="nextExpiration">-</span></div>
							<div class="small fw-semibold text-primary">Available: <span id="availableQty">0</span></div>
						</div>

						<button type="submit" class="btn btn-primary w-100">Add To List</button>
					</form>
				</div>
			</div>
		</div>

		<div class="col-lg-8">
			<div class="card border-0 shadow-sm rounded-4 h-100">
				<div class="card-body p-4">
					<h3 class="h5 mb-3">Pending Cashier Items</h3>
					<div class="table-responsive">
						<table class="table align-middle">
							<thead>
								<tr>
									<th>Product</th>
									<th>Barcode</th>
									<th>Product Batch ID</th>
									<th>Date</th>
									<th>Qty</th>
									<th>Sales Price</th>
									<th>Line Total</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($cashierCart)): ?>
									<tr>
										<td colspan="8" class="text-center text-muted py-4">No cashier items queued yet.</td>
									</tr>
								<?php else: ?>
									<?php $queuedTotal = 0; ?>
									<?php $queuedAmountTotal = 0.0; ?>
									<?php foreach ($cashierCart as $index => $row): ?>
										<?php $queuedTotal += (int) ($row['quantity'] ?? 0); ?>
										<?php $queuedAmountTotal += (float) ($row['line_total'] ?? 0); ?>
										<tr>
											<td class="fw-semibold"><?= esc($row['product_name'] ?? 'N/A') ?></td>
											<td><?= esc($row['barcode'] ?? '-') ?></td>
											<td><?= esc((string) ($row['product_batch_id'] ?? '-')) ?></td>
											<td><?= esc($row['stock_out_date'] ?? '-') ?></td>
											<td><?= esc($row['quantity']) ?></td>
											<td><?= esc(number_format((float) ($row['sales_price'] ?? 0), 2)) ?></td>
											<td class="fw-semibold"><?= esc(number_format((float) ($row['line_total'] ?? 0), 2)) ?></td>
											<td>
												<form action="<?= base_url('stock-out/cashier') ?>" method="post" class="d-inline">
													<?= csrf_field() ?>
													<input type="hidden" name="action" value="remove">
													<input type="hidden" name="remove_index" value="<?= esc((string) $index) ?>">
													<button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
												</form>
											</td>
										</tr>
									<?php endforeach; ?>
									<tr class="table-light">
										<td colspan="4" class="text-end fw-semibold">Total Queued</td>
										<td class="fw-bold"><?= esc((string) $queuedTotal) ?></td>
										<td class="text-end fw-semibold">Total Amount</td>
										<td class="fw-bold"><?= esc(number_format($queuedAmountTotal, 2)) ?></td>
										<td></td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>

					<?php if (!empty($cashierCart)): ?>
						<div class="d-flex gap-2 justify-content-end">
							<form action="<?= base_url('stock-out/cashier') ?>" method="post" class="d-inline">
								<?= csrf_field() ?>
								<input type="hidden" name="action" value="clear">
								<button type="submit" class="btn btn-outline-secondary">Clear List</button>
							</form>
							<form action="<?= base_url('stock-out/cashier') ?>" method="post" class="d-inline">
								<?= csrf_field() ?>
								<input type="hidden" name="action" value="confirm">
								<button type="submit" class="btn btn-danger">Confirm And Create Receipt</button>
							</form>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	(() => {
		const fetchBtn = document.getElementById('fetchProductBtn');
		const barcodeInput = document.getElementById('barcode');
		const quantityInput = document.getElementById('quantity');

		const setLookup = (data) => {
			document.getElementById('productName').textContent = data.product_name ?? '-';
			document.getElementById('productCategory').textContent = data.category ?? '-';
			document.getElementById('productUnit').textContent = data.unit_type ?? '-';
			document.getElementById('productBatchId').textContent = data.product_batch_id ?? '-';
			document.getElementById('salesPrice').textContent = Number(data.sales_price ?? 0).toFixed(2);
			document.getElementById('nextExpiration').textContent = data.next_batch_expiration ?? '-';
			document.getElementById('availableQty').textContent = data.total_available_qty ?? 0;
			quantityInput.setAttribute('max', String(data.total_available_qty ?? 0));
		};

		const clearLookup = () => {
			setLookup({
				product_name: '-',
				category: '-',
				unit_type: '-',
				product_batch_id: '-',
				sales_price: 0,
				next_batch_expiration: '-',
				total_available_qty: 0,
			});
		};

		const fetchByBarcode = async () => {
			const barcode = barcodeInput.value.trim();
			if (!barcode) {
				clearLookup();
				return;
			}

			try {
				const response = await fetch(`<?= base_url('stock-out/barcode') ?>/${encodeURIComponent(barcode)}`);
				const data = await response.json();
				if (!response.ok || !data.ok) {
					clearLookup();
					return;
				}
				setLookup(data);
			} catch (error) {
				clearLookup();
			}
		};

		fetchBtn.addEventListener('click', fetchByBarcode);
		barcodeInput.addEventListener('change', fetchByBarcode);
	})();
</script>
