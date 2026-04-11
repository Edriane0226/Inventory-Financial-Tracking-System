<div class="container-fluid px-4 px-lg-5 py-4 py-lg-5">

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
					<h3 class="h5 mb-3">Stock Out</h3>
					<form action="<?= base_url('stock-out/inventory') ?>" method="post" id="inventoryStockOutForm">
						<?= csrf_field() ?>

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
							<label class="form-label" for="reason">Reason</label>
							<select class="form-select" id="reason" name="reason" required>
								<option value="">Select reason</option>
								<?php foreach (($reasons ?? []) as $reason): ?>
									<option value="<?= esc($reason['reason_text']) ?>" <?= old('reason') === $reason['reason_text'] ? 'selected' : '' ?>>
										<?= esc($reason['reason_text']) ?>
									</option>
								<?php endforeach; ?>
							</select>
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
							<div class="small">Next batch: <span id="nextBatch">-</span></div>
							<div class="small">Next exp: <span id="nextExpiration">-</span></div>
							<div class="small fw-semibold text-primary">Available: <span id="availableQty">0</span></div>
						</div>

						<button type="submit" class="btn btn-danger w-100">Save Stock Out</button>
					</form>
				</div>
			</div>
		</div>

		<div class="col-lg-8">
			<div class="card border-0 shadow-sm rounded-4 h-100">
				<div class="card-body p-4">
					<h3 class="h5 mb-3">Records</h3>
					<div class="table-responsive">
						<table class="table align-middle">
							<thead>
								<tr>
									<th>Product</th>
									<th>Barcode</th>
									<th>Batch</th>
									<th>Qty</th>
									<th>Reason</th>
									<th>Date</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($stockOuts)): ?>
									<tr>
										<td colspan="6" class="text-center text-muted py-4">No stock-out records yet.</td>
									</tr>
								<?php else: ?>
									<?php foreach ($stockOuts as $row): ?>
										<tr>
											<td class="fw-semibold"><?= esc($row['product_name'] ?? 'N/A') ?></td>
											<td><?= esc($row['barcode'] ?? '-') ?></td>
											<td><?= esc($row['batch_number'] ?? '-') ?></td>
											<td><?= esc($row['quantity']) ?></td>
											<td><?= esc($row['reason_text'] ?? '-') ?></td>
											<td><?= esc(date('Y-m-d', strtotime($row['stock_out_date']))) ?></td>
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

<script>
	(() => {
		const fetchBtn = document.getElementById('fetchProductBtn');
		const barcodeInput = document.getElementById('barcode');
		const quantityInput = document.getElementById('quantity');

		const setLookup = (data) => {
			document.getElementById('productName').textContent = data.product_name ?? '-';
			document.getElementById('productCategory').textContent = data.category ?? '-';
			document.getElementById('productUnit').textContent = data.unit_type ?? '-';
			document.getElementById('nextBatch').textContent = data.next_batch_number ?? '-';
			document.getElementById('nextExpiration').textContent = data.next_batch_expiration ?? '-';
			document.getElementById('availableQty').textContent = data.total_available_qty ?? 0;
			quantityInput.setAttribute('max', String(data.total_available_qty ?? 0));
		};

		const clearLookup = () => {
			setLookup({
				product_name: '-',
				category: '-',
				unit_type: '-',
				next_batch_number: '-',
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
