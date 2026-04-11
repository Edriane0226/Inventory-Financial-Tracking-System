<style>
	@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Spline+Sans+Mono:wght@500;600&display=swap');

	:root {
		--pos-ink: #0f1f2d;
		--pos-muted: #5c6f7f;
		--pos-surface: #ffffff;
		--pos-ice: #f4f7fb;
		--pos-accent: #ef5b2a;
		--pos-accent-2: #1f7a8c;
		--pos-border: #dbe4ee;
		--pos-highlight: rgba(239, 91, 42, 0.12);
		--pos-shadow: 0 24px 50px -32px rgba(15, 31, 45, 0.45);
	}

	.pos-shell {
		position: relative;
		font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
		color: var(--pos-ink);
		background: radial-gradient(circle at top left, #fdf1e5 0%, #f5f8ff 55%, #eef7f8 100%);
		min-height: 100vh;
		overflow: hidden;
	}

	.pos-shell::before {
		content: '';
		position: absolute;
		inset: 0;
		background: linear-gradient(130deg, rgba(31, 122, 140, 0.18), transparent 40%),
			linear-gradient(320deg, rgba(239, 91, 42, 0.15), transparent 45%);
		pointer-events: none;
	}

	.pos-shell .container-fluid {
		position: relative;
		z-index: 1;
		min-height: 100svh;
		display: flex;
		flex-direction: column;
	}

	.pos-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 24px;
		padding: 14px 22px;
		border-radius: 18px;
		background: var(--pos-surface);
		border: 1px solid var(--pos-border);
		box-shadow: var(--pos-shadow);
		margin-bottom: 24px;
	}

	.pos-title {
		font-size: 1.6rem;
		font-weight: 700;
		letter-spacing: 0.02em;
	}

	.pos-subtitle {
		color: var(--pos-muted);
		font-size: 0.95rem;
	}

	.pos-status {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 6px 12px;
		border-radius: 999px;
		background: var(--pos-highlight);
		color: var(--pos-accent);
		font-weight: 600;
		font-size: 0.85rem;
	}

	.pos-card {
		background: var(--pos-surface);
		border-radius: 22px;
		border: 1px solid var(--pos-border);
		box-shadow: var(--pos-shadow);
		animation: posFadeUp 0.6s ease forwards;
		opacity: 0;
		display: flex;
		flex-direction: column;
		min-height: 0;
	}

	.pos-grid > div:nth-child(1) .pos-card {
		animation-delay: 0.05s;
	}

	.pos-grid > div:nth-child(2) .pos-card {
		animation-delay: 0.12s;
	}

	.pos-card-header {
		padding: 20px 24px 0;
		font-weight: 600;
		font-size: 1.1rem;
	}

	.pos-card-body {
		padding: 20px 24px 24px;
		min-height: 0;
	}

	.pos-card-body--stack {
		flex: 1;
		display: flex;
		flex-direction: column;
		gap: 16px;
	}

	.pos-input {
		border-radius: 14px;
		border: 1px solid var(--pos-border);
		padding: 12px 14px;
		font-weight: 500;
	}

	.pos-input:focus {
		border-color: var(--pos-accent);
		box-shadow: 0 0 0 0.2rem rgba(239, 91, 42, 0.15);
	}

	.pos-btn-primary {
		background: var(--pos-accent);
		border-color: var(--pos-accent);
		border-radius: 14px;
		font-weight: 600;
		padding: 12px 16px;
	}

	.pos-btn-secondary {
		border-radius: 14px;
		font-weight: 600;
		padding: 12px 16px;
		border: 1px solid var(--pos-border);
	}

	.pos-lookup {
		background: var(--pos-ice);
		border-radius: 16px;
		border: 1px dashed rgba(31, 122, 140, 0.4);
		padding: 16px;
	}

	.pos-lookup strong {
		font-size: 1.1rem;
	}

	.pos-mono {
		font-family: 'Spline Sans Mono', 'Consolas', monospace;
	}

	.pos-table {
		margin-bottom: 0;
	}

	.pos-table thead th {
		text-transform: uppercase;
		letter-spacing: 0.08em;
		font-size: 0.7rem;
		color: var(--pos-muted);
		border-bottom: 1px solid var(--pos-border);
	}

	.pos-table tbody td {
		vertical-align: middle;
		border-color: var(--pos-border);
	}

	.pos-table-wrap {
		flex: 1;
		min-height: 0;
		overflow: auto;
		border-radius: 18px;
		border: 1px solid var(--pos-border);
	}

	.pos-summary {
		display: grid;
		gap: 10px;
		padding: 18px 20px;
		background: var(--pos-ice);
		border-radius: 18px;
		border: 1px solid var(--pos-border);
	}

	.pos-summary-row {
		display: flex;
		justify-content: space-between;
		font-weight: 600;
		color: var(--pos-ink);
	}

	.pos-summary-total {
		font-size: 1.25rem;
		color: var(--pos-accent-2);
	}

	.pos-actions {
		display: flex;
		flex-wrap: wrap;
		gap: 12px;
		justify-content: flex-end;
	}

	.pos-empty {
		padding: 26px;
		text-align: center;
		color: var(--pos-muted);
	}

	.pos-grid {
		flex: 1;
		min-height: 0;
	}

	@keyframes posFadeUp {
		from {
			opacity: 0;
			transform: translateY(16px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	@media (max-width: 992px) {
		.pos-header {
			flex-direction: column;
			align-items: flex-start;
		}
		.pos-shell .container-fluid {
			min-height: auto;
		}
		.pos-actions {
			justify-content: stretch;
		}
		.pos-actions form {
			flex: 1 1 100%;
		}
	}
</style>

<div class="pos-shell">
	<div class="container-fluid px-4 px-lg-5 py-4 py-lg-5">

		<?php if (session()->getFlashdata('success')): ?>
			<div class="alert alert-success">
				<?= esc(session()->getFlashdata('success')) ?>
			</div>
		<?php endif; ?>

		<?php if (session()->getFlashdata('error')): ?>
			<div class="alert alert-danger">
				<?= esc(session()->getFlashdata('error')) ?>
			</div>
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

		<div class="row g-4 pos-grid">
			<div class="col-12 col-xl-4">
				<div class="pos-card h-100">
					<div class="pos-card-header">Item Entry</div>
					<div class="pos-card-body">
						<form action="<?= base_url('stock-out/cashier') ?>" method="post" id="cashierStockOutForm">
							<?= csrf_field() ?>
							<input type="hidden" name="action" value="add">

							<div class="mb-3">
								<label class="form-label" for="barcode">Barcode</label>
								<div class="input-group">
									<input type="text" class="form-control pos-input" id="barcode" name="barcode" placeholder="Scan or enter barcode" value="<?= esc(old('barcode')) ?>" required>
									<button class="btn btn-outline-primary pos-btn-secondary" type="button" id="fetchProductBtn">Fetch</button>
								</div>
							</div>

							<div class="mb-3">
								<label class="form-label" for="quantity">Quantity</label>
								<input type="number" min="1" class="form-control pos-input" id="quantity" name="quantity" value="<?= esc(old('quantity')) ?>" required>
							</div>

							<div class="mb-3">
								<label class="form-label">Reason</label>
								<input type="text" class="form-control pos-input" value="Sold" readonly>
							</div>

							<div class="mb-3">
								<label class="form-label" for="stock_out_date">Stock Out Date</label>
								<input type="date" class="form-control pos-input" id="stock_out_date" name="stock_out_date" value="<?= esc(old('stock_out_date', date('Y-m-d'))) ?>" required>
							</div>

							<div class="pos-lookup mb-3" id="productLookupCard">
								<div class="small text-muted">Scan result</div>
								<strong id="productName">-</strong>
								<div class="small mt-2">Category: <span id="productCategory">-</span></div>
								<div class="small">Unit: <span id="productUnit">-</span></div>
								<div class="small">Product Batch ID: <span id="productBatchId">-</span></div>
								<div class="small">Sales Price: <span id="salesPrice" class="pos-mono">0.00</span></div>
								<div class="small">Next exp: <span id="nextExpiration">-</span></div>
								<div class="small fw-semibold text-primary">Available: <span id="availableQty">0</span></div>
							</div>

							<button type="submit" class="btn btn-primary pos-btn-primary w-100">Add To Queue</button>
						</form>
					</div>
				</div>
			</div>

			<div class="col-12 col-xl-8">
				<div class="pos-card h-100">
					<div class="pos-card-header">Transaction Queue</div>
					<div class="pos-card-body pos-card-body--stack">
						<?php $queuedTotal = 0; ?>
						<?php $queuedAmountTotal = 0.0; ?>
						<div class="table-responsive pos-table-wrap">
							<table class="table pos-table align-middle">
								<thead>
									<tr>
										<th>Product</th>
										<th>Barcode</th>
										<th>Batch</th>
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
											<td colspan="8" class="pos-empty">No cashier items queued yet.</td>
										</tr>
									<?php else: ?>
										<?php foreach ($cashierCart as $index => $row): ?>
											<?php $queuedTotal += (int) ($row['quantity'] ?? 0); ?>
											<?php $queuedAmountTotal += (float) ($row['line_total'] ?? 0); ?>
											<tr>
												<td class="fw-semibold"><?= esc($row['product_name'] ?? 'N/A') ?></td>
												<td class="pos-mono"><?= esc($row['barcode'] ?? '-') ?></td>
												<td><?= esc((string) ($row['product_batch_id'] ?? '-')) ?></td>
												<td><?= esc($row['stock_out_date'] ?? '-') ?></td>
												<td><?= esc($row['quantity']) ?></td>
												<td class="pos-mono"><?= esc(number_format((float) ($row['sales_price'] ?? 0), 2)) ?></td>
												<td class="fw-semibold pos-mono"><?= esc(number_format((float) ($row['line_total'] ?? 0), 2)) ?></td>
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
									<?php endif; ?>
								</tbody>
							</table>
						</div>

						<div class="row g-3 align-items-center">
							<div class="col-12 col-lg-6">
								<div class="pos-summary">
									<div class="pos-summary-row">
										<span>Items</span>
										<span class="pos-mono"><?= esc((string) $queuedTotal) ?></span>
									</div>
									<div class="pos-summary-row">
										<span>Subtotal</span>
										<span class="pos-mono"><?= esc(number_format($queuedAmountTotal, 2)) ?></span>
									</div>
									<div class="pos-summary-row pos-summary-total">
										<span>Total Due</span>
										<span class="pos-mono"><?= esc(number_format($queuedAmountTotal, 2)) ?></span>
									</div>
								</div>
							</div>

							<div class="col-12 col-lg-6">
								<?php if (!empty($cashierCart)): ?>
									<div class="pos-actions">
										<form action="<?= base_url('stock-out/cashier') ?>" method="post" class="flex-fill">
											<?= csrf_field() ?>
											<input type="hidden" name="action" value="clear">
											<button type="submit" class="btn btn-outline-secondary pos-btn-secondary w-100">Clear List</button>
										</form>
										<form action="<?= base_url('stock-out/cashier') ?>" method="post" class="flex-fill">
											<?= csrf_field() ?>
											<input type="hidden" name="action" value="confirm">
											<button type="submit" class="btn btn-danger pos-btn-primary w-100">Confirm And Create Receipt</button>
										</form>
									</div>
								<?php else: ?>
									<div class="pos-empty">Scan items to enable checkout actions.</div>
								<?php endif; ?>
							</div>
						</div>
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
