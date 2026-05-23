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
		<div class="col-lg-4">
			<div class="card border-0 shadow-sm rounded-4">
				<div class="card-body p-4">
					<h3 class="h5 mb-3">Stock Out</h3>
					<form action="<?= base_url('stock-out/inventory') ?>" method="post" id="inventoryStockOutForm">
						<?= csrf_field() ?>

						<div class="mb-3 text-center">
							<label class="form-label d-block" for="barcode">Barcode</label>
							<div class="input-group justify-content-center">
								<input type="text" class="form-control" id="barcode" name="barcode" value="<?= esc(old('barcode')) ?>" style="max-width: 180px; text-align: center;" required>
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
									<option value="<?= esc((string) ($reason['reason_text'] ?? '')) ?>" <?= old('reason') === ($reason['reason_text'] ?? '') ? 'selected' : '' ?>>
										<?= esc((string) ($reason['reason_text'] ?? '')) ?>
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
					<p class="text-muted small mb-3">Product, barcode, batch, quantity, reason, and date at a glance.</p>
					<div class="table-responsive">
						<table class="table align-middle stockout-records-table">
							<colgroup>
								<col style="width: 20%;">
								<col style="width: 18%;">
								<col style="width: 15%;">
								<col style="width: 10%;">
								<col style="width: 15%;">
								<col style="width: 12%;">
							</colgroup>
							<thead class="table-light">
								<tr>
									<th class="fw-bold">Product</th>
								<th class="fw-bold text-center">Barcode</th>
								<th class="fw-bold text-center">Batch</th>
								<th class="fw-bold text-center">Qty</th>
								<th class="fw-bold text-center">Reason</th>
									<th class="fw-bold text-center">Date</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($stockOuts)): ?>
									<tr>
										<td colspan="6" class="text-center text-muted py-5">No stock-out records yet.</td>
									</tr>
								<?php else: ?>
									<?php foreach ($stockOuts as $row): ?>
										<tr class="stockout-row">
											<td class="product-cell">
												<span class="product-name fw-semibold"><?= esc((string) ($row['product_name'] ?? 'N/A')) ?></span>
											</td>
											<td class="barcode-cell">
												<code class="barcode-badge"><?= esc((string) ($row['barcode'] ?? '-')) ?></code>
											</td>
										<td class="batch-cell text-center">
												<?php if (isset($row['batch_number']) && $row['batch_number'] !== '-'): ?>
													<span class="batch-badge"><?= esc((string) $row['batch_number']) ?></span>
												<?php else: ?>
													<span class="text-muted">-</span>
												<?php endif; ?>
											</td>
											<td class="text-center">
												<span class="qty-badge"><?= esc((string) ($row['quantity'] ?? 0)) ?></span>
											</td>
										<td class="reason-cell text-center">
												<?php 
													$reasonText = (string) ($row['reason_text'] ?? '-');
													$reasonClass = 'reason-badge-default';
													if (stripos($reasonText, 'sold') !== false) {
														$reasonClass = 'reason-badge-sold';
													} elseif (stripos($reasonText, 'damaged') !== false || stripos($reasonText, 'defect') !== false) {
														$reasonClass = 'reason-badge-damaged';
													} elseif (stripos($reasonText, 'expired') !== false) {
														$reasonClass = 'reason-badge-expired';
													} elseif (stripos($reasonText, 'return') !== false) {
														$reasonClass = 'reason-badge-return';
													} elseif (stripos($reasonText, 'adjustment') !== false) {
														$reasonClass = 'reason-badge-adjustment';
													}
												?>
												<span class="reason-badge <?= $reasonClass ?>"><?= esc($reasonText) ?></span>
											</td>
											<td class="text-center">
												<span class="date-badge"><?= esc(date('m/d/y', strtotime((string) ($row['stock_out_date'] ?? date('Y-m-d'))))) ?></span>
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

<style>
	/* Table Base Styling */
	.stockout-records-table {
		table-layout: fixed;
		width: 100%;
		border-collapse: separate;
		border-spacing: 0;
	}

	.stockout-records-table thead {
		background: linear-gradient(135deg, rgba(15, 23, 42, 0.03) 0%, rgba(239, 91, 42, 0.02) 100%);
	}

	.stockout-records-table thead th {
		font-size: 0.75rem;
		font-weight: 700;
		letter-spacing: 0.06em;
		text-transform: uppercase;
		color: #0f172a;
		padding: 1rem 0.6rem;
		border-bottom: 2px solid #e2e8f0;
		white-space: nowrap;
	}

	/* Row Styling */
	.stockout-row {
		transition: all 0.2s ease;
		border-bottom: 1px solid rgba(226, 232, 240, 0.5);
	}

	.stockout-row:hover {
		background: linear-gradient(90deg, rgba(239, 91, 42, 0.05) 0%, rgba(239, 91, 42, 0.02) 100%);
		box-shadow: inset 0 0 8px rgba(239, 91, 42, 0.08);
	}

	/* Cell Styling */
	.stockout-records-table tbody td {
		padding: 1rem 0.6rem;
		font-size: 0.95rem;
		vertical-align: middle;
	}

	/* Product Cell */
	.product-cell {
		padding: 1rem 0.8rem;
	}

	.product-name {
		color: #0f172a;
		font-weight: 600;
		display: inline-block;
	}

	/* Barcode Cell */
	.barcode-cell {
		font-family: 'Courier New', monospace;
		font-size: 0.85rem;
		padding: 1rem 0.6rem !important;
		text-align: center;
		vertical-align: middle;
	}

	.barcode-badge {
		display: inline-block;
		background: linear-gradient(135deg, rgba(226, 232, 240, 0.3) 0%, rgba(226, 232, 240, 0.1) 100%);
		color: #334155;
		padding: 0.6rem 0.8rem;
		border-radius: 6px;
		border: 1px solid rgba(226, 232, 240, 0.5);
		font-weight: 500;
		line-height: 1.4;
		word-break: keep-all;
		white-space: nowrap;
		font-size: 0.8rem;
		letter-spacing: 0.5px;
	}

	/* Batch Cell */
	.batch-cell {
		text-align: center;
		vertical-align: middle;
		padding: 1rem 0.6rem !important;
	}

	.batch-badge {
		display: inline-block;
		background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%);
		color: #2563eb;
		padding: 0.35rem 0.7rem;
		border-radius: 5px;
		font-weight: 600;
		font-size: 0.85rem;
		font-family: 'Courier New', monospace;
		border: 1px solid rgba(59, 130, 246, 0.2);
	}

	/* Quantity Badge */
	.qty-badge {
		display: inline-block;
		background: linear-gradient(135deg, rgba(14, 165, 233, 0.15) 0%, rgba(14, 165, 233, 0.08) 100%);
		color: #0284c7;
		padding: 0.4rem 0.8rem;
		border-radius: 6px;
		font-weight: 700;
		font-size: 0.9rem;
		border: 1px solid rgba(14, 165, 233, 0.2);
	}

	/* Reason Cell & Badges */
	.reason-cell {
		text-align: center;
		vertical-align: middle;
		padding: 1rem 0.6rem !important;
	}

	.reason-badge {
		display: inline-block;
		padding: 0.4rem 0.75rem;
		border-radius: 6px;
		font-weight: 500;
		font-size: 0.9rem;
		border: 1px solid;
		white-space: nowrap;
	}

	.reason-badge-sold {
		background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.08) 100%);
		color: #16a34a;
		border-color: rgba(34, 197, 94, 0.2);
	}

	.reason-badge-damaged {
		background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.08) 100%);
		color: #dc2626;
		border-color: rgba(239, 68, 68, 0.2);
	}

	.reason-badge-expired {
		background: linear-gradient(135deg, rgba(239, 91, 42, 0.15) 0%, rgba(239, 91, 42, 0.08) 100%);
		color: #ea580c;
		border-color: rgba(239, 91, 42, 0.2);
	}

	.reason-badge-return {
		background: linear-gradient(135deg, rgba(168, 85, 247, 0.15) 0%, rgba(168, 85, 247, 0.08) 100%);
		color: #7c3aed;
		border-color: rgba(168, 85, 247, 0.2);
	}

	.reason-badge-adjustment {
		background: linear-gradient(135deg, rgba(100, 116, 139, 0.1) 0%, rgba(100, 116, 139, 0.05) 100%);
		color: #475569;
		border-color: rgba(100, 116, 139, 0.15);
	}

	.reason-badge-default {
		background: linear-gradient(135deg, rgba(148, 163, 184, 0.1) 0%, rgba(148, 163, 184, 0.05) 100%);
		color: #64748b;
		border-color: rgba(148, 163, 184, 0.15);
	}

	/* Date Badge */
	.date-badge {
		display: inline-block;
		background: linear-gradient(135deg, rgba(203, 213, 225, 0.2) 0%, rgba(203, 213, 225, 0.1) 100%);
		color: #475569;
		padding: 0.35rem 0.65rem;
		border-radius: 5px;
		font-weight: 500;
		font-size: 0.85rem;
	}

	/* Responsive */
	@media (max-width: 991px) {
		.stockout-records-table thead th {
			padding: 0.8rem 0.4rem;
			font-size: 0.7rem;
		}

		.stockout-records-table tbody td {
			padding: 0.85rem 0.4rem;
		}

		.reason-badge {
			font-size: 0.8rem;
			padding: 0.3rem 0.6rem;
		}
	}
</style>
