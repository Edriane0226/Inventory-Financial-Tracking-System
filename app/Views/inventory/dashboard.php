<div class="container-fluid px-4 px-lg-5 py-4 py-lg-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-4 rounded-4 border bg-white shadow-sm">
                <h2 class="h4 mb-1">Inventory Dashboard</h2>
                <p class="text-muted mb-0">Products are grouped by name, and quantity is summed across all batch IDs.</p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Products (by name)</div>
                    <div class="display-6 fw-semibold"><?= esc((string) ($totalProducts ?? 0)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Total Quantity</div>
                    <div class="display-6 fw-semibold"><?= esc((string) ($totalQuantity ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                <h3 class="h5 mb-0">Product Totals</h3>
                <div style="min-width: 280px; max-width: 420px; width: 100%;">
                    <input type="text" id="inventorySearch" class="form-control" placeholder="Search by product, category, or unit type...">
                </div>
            </div>

            <?php if (empty($productSummary)): ?>
                <div class="text-center text-muted py-4">No products found.</div>
            <?php else: ?>
                <div class="row g-3" id="productCardsGrid">
                    <?php foreach ($productSummary as $row): ?>
                        <?php
                            $productName = (string) ($row['product_name'] ?? 'N/A');
                            $categories = (string) ($row['categories'] ?? 'N/A');
                            $unitTypes = (string) ($row['unit_types'] ?? 'N/A');
                            $quantity = (int) ($row['total_quantity'] ?? 0);
                            $lastDate = !empty($row['last_stock_in_date']) ? date('Y-m-d H:i', strtotime((string) $row['last_stock_in_date'])) : '-';
                            $searchText = strtolower($productName . ' ' . $categories . ' ' . $unitTypes);
                        ?>
                        <div class="col-md-6 col-xl-4 product-card-item" data-search="<?= esc($searchText) ?>">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <h4 class="h6 mb-0 fw-semibold"><?= esc($productName) ?></h4>
                                        <span class="badge bg-primary-subtle text-primary">Qty: <?= esc((string) $quantity) ?></span>
                                    </div>
                                    <div class="small text-muted mb-1">Category</div>
                                    <div class="mb-2"><?= esc($categories) ?></div>
                                    <div class="small text-muted mb-1">Unit Type</div>
                                    <div class="mb-2"><?= esc($unitTypes) ?></div>
                                    <div class="small text-muted">Last Stock In</div>
                                    <div><?= esc($lastDate) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="inventoryNoResults" class="text-center text-muted py-4 d-none">No products match your search.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    (() => {
        const searchInput = document.getElementById('inventorySearch');
        const cards = Array.from(document.querySelectorAll('.product-card-item'));
        const noResults = document.getElementById('inventoryNoResults');

        if (!searchInput || cards.length === 0 || !noResults) {
            return;
        }

        const applyFilter = () => {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;

            cards.forEach((card) => {
                const haystack = (card.getAttribute('data-search') || '').toLowerCase();
                const visible = query === '' || haystack.includes(query);
                card.classList.toggle('d-none', !visible);
                if (visible) {
                    visibleCount += 1;
                }
            });

            noResults.classList.toggle('d-none', visibleCount !== 0);
        };

        searchInput.addEventListener('input', applyFilter);
    })();
</script>
