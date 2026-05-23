<div class="dashboard-shell">
    <div class="sidebar-overlay d-lg-none"></div>
    <div class="dashboard-main">
        <main class="content-area px-3 px-lg-4 py-4 py-lg-5">
            <button class="btn btn-outline-primary mb-3 d-lg-none sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-list"></i> Menu
            </button>

            <!-- Summary Cards -->
            <section class="mb-4">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <div class="stat-card position-relative overflow-hidden h-100 border-start border-4 border-primary">
                            <i class="bi bi-boxes stat-bg-icon"></i>
                            <p class="small text-uppercase text-muted mb-2">Total Product Batches</p>
                            <h2 class="h3 mb-0 text-primary"><?= esc((string) ($totalProducts ?? 0)) ?></h2>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="stat-card position-relative overflow-hidden h-100 border-start border-4 border-success">
                            <i class="bi bi-layers stat-bg-icon"></i>
                            <p class="small text-uppercase text-muted mb-2">Total Quantity</p>
                            <h2 class="h3 mb-0 text-success"><?= esc((string) ($totalQuantity ?? 0)) ?></h2>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Product List Section -->
            <section>
                <div class="card border-0 shadow-sm overflow-hidden mb-4">
                    <div class="card-header bg-white border-bottom p-4">
                        <div class="row align-items-center g-3">
                            <div class="col-lg-4 col-md-12">
                                <h3 class="h5 mb-0 fw-semibold">Product Catalog</h3>
                            </div>
                            <div class="col-lg-3 col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" id="inventorySearch" class="form-control border-start-0 ps-0" placeholder="Search...">
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-4">
                                <select id="categoryFilter" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php
                                        $uniqueCategories = [];
                                        if (!empty($productSummary)) {
                                            foreach ($productSummary as $row) {
                                                $c = $row['categories'] ?? 'N/A';
                                                if (!in_array($c, $uniqueCategories)) {
                                                    $uniqueCategories[] = $c;
                                                }
                                            }
                                            sort($uniqueCategories);
                                            foreach ($uniqueCategories as $c) {
                                                echo '<option value="' . esc((string) $c) . '">' . esc((string) $c) . '</option>';
                                            }
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <select id="batchFilter" class="form-select">
                                    <option value="">All Batches</option>
                                    <?php
                                        $uniqueBatches = [];
                                        if (!empty($productSummary)) {
                                            foreach ($productSummary as $row) {
                                                $b = $row['batch_number'] ?? 'No Batch';
                                                if (!in_array($b, $uniqueBatches)) {
                                                    $uniqueBatches[] = $b;
                                                }
                                            }
                                            sort($uniqueBatches);
                                            foreach ($uniqueBatches as $b) {
                                                echo '<option value="' . esc((string) $b) . '">' . esc((string) $b) . '</option>';
                                            }
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-4 bg-light bg-opacity-50">
                        <?php if (empty($productSummary)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox text-muted fs-1 mb-3 d-block"></i>
                                <h5 class="text-muted">No products found</h5>
                                <p class="text-muted small">Your inventory catalog is currently empty.</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-4" id="productCardsGrid">
                                <?php foreach ($productSummary as $row): ?>
                                    <?php
                                        $productName = (string) ($row['product_name'] ?? 'N/A');
                                        $batchNumber = (string) ($row['batch_number'] ?? 'No Batch');
                                        $expirationDate = !empty($row['expiration_date']) ? date('M d, Y', strtotime((string) $row['expiration_date'])) : 'No Expiry';
                                        $categories = (string) ($row['categories'] ?? 'N/A');
                                        $unitTypes = (string) ($row['unit_types'] ?? 'N/A');
                                        $quantity = (int) ($row['total_quantity'] ?? 0);
                                        $lastDate = !empty($row['last_stock_in_date']) ? date('M d, Y', strtotime((string) $row['last_stock_in_date'])) : '-';
                                        $searchText = strtolower($productName . ' ' . $batchNumber . ' ' . $categories . ' ' . $unitTypes);
                                    ?>
                                    <div class="col-md-6 col-xl-4 product-card-item" data-search="<?= esc($searchText) ?>" data-batch="<?= esc($batchNumber) ?>" data-category="<?= esc($categories) ?>">
                                        <div class="card product-card border-0 h-100 shadow-sm">
                                            <div class="card-body p-4">
                                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                                    <div>
                                                        <h4 class="h5 mb-1 fw-bold text-dark text-truncate" style="max-width: 200px;" title="<?= esc($productName) ?>">
                                                            <?= esc($productName) ?>
                                                        </h4>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <span class="badge bg-light text-secondary border d-inline-flex align-items-center gap-1">
                                                                <i class="bi bi-tags-fill"></i> <?= esc($categories) ?>
                                                            </span>
                                                            <span class="badge bg-light text-secondary border d-inline-flex align-items-center gap-1">
                                                                <i class="bi bi-box2-fill"></i> Batch: <?= esc($batchNumber) ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 border border-primary border-opacity-25">
                                                            <span class="small fw-normal">Qty:</span> <span class="fs-6 fw-bold"><?= esc((string) $quantity) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row g-2 mt-4 pt-3 border-top">
                                                    <div class="col-6">
                                                        <div class="d-flex align-items-center gap-2 text-muted small">
                                                            <i class="bi bi-calendar-x"></i>
                                                            <div class="text-truncate">
                                                                <span class="d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Expiration</span>
                                                                <span class="fw-medium text-dark"><?= esc($expirationDate) ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="d-flex align-items-center gap-2 text-muted small">
                                                            <i class="bi bi-calendar-check"></i>
                                                            <div class="text-truncate">
                                                                <span class="d-block" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Last Stock In</span>
                                                                <span class="fw-medium text-dark"><?= esc($lastDate) ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="inventoryNoResults" class="text-center py-5 d-none">
                                <i class="bi bi-search text-muted fs-1 mb-3 d-block"></i>
                                <h5 class="text-muted">No matching products</h5>
                                <p class="text-muted small">Try adjusting your search terms.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Sidebar Toggle
        const body = document.body;
        const toggleBtn = document.getElementById('sidebarToggle');
        const overlay = document.querySelector('.sidebar-overlay');
        const hasSidebar = document.querySelector('.sidebar') !== null;
        const closeSidebar = () => body.classList.remove('sidebar-open');

        if (toggleBtn && hasSidebar) {
            toggleBtn.addEventListener('click', () => body.classList.toggle('sidebar-open'));
        }

        if (overlay && hasSidebar) {
            overlay.addEventListener('click', closeSidebar);
        }

        // Live Search & Filters
        const searchInput = document.getElementById('inventorySearch');
        const batchFilter = document.getElementById('batchFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const cards = Array.from(document.querySelectorAll('.product-card-item'));
        const noResults = document.getElementById('inventoryNoResults');

        if (cards.length > 0 && noResults) {
            const applyFilter = () => {
                const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
                const selectedBatch = batchFilter ? batchFilter.value : '';
                const selectedCategory = categoryFilter ? categoryFilter.value : '';
                let visibleCount = 0;

                cards.forEach((card) => {
                    const haystack = (card.getAttribute('data-search') || '').toLowerCase();
                    const cardBatch = card.getAttribute('data-batch') || '';
                    const cardCategory = card.getAttribute('data-category') || '';
                    
                    const matchesSearch = query === '' || haystack.includes(query);
                    const matchesBatch = selectedBatch === '' || cardBatch === selectedBatch;
                    const matchesCategory = selectedCategory === '' || cardCategory === selectedCategory;
                    
                    const visible = matchesSearch && matchesBatch && matchesCategory;
                    card.classList.toggle('d-none', !visible);
                    if (visible) {
                        visibleCount += 1;
                    }
                });

                noResults.classList.toggle('d-none', visibleCount !== 0);
            };

            if (searchInput) searchInput.addEventListener('input', applyFilter);
            if (batchFilter) batchFilter.addEventListener('change', applyFilter);
            if (categoryFilter) categoryFilter.addEventListener('change', applyFilter);
        }
    });
</script>

<style>
    /* Card Styles */
    .stat-card {
        background: #fff;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        transition: transform 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
    }

    .stat-bg-icon {
        position: absolute;
        right: -10px;
        bottom: -15px;
        font-size: 5rem;
        opacity: 0.04;
        transform: rotate(-10deg);
        z-index: 0;
    }

    .product-card {
        border-radius: 16px;
        transition: all 0.2s ease-in-out;
        border: 1px solid rgba(0,0,0,0.05) !important;
    }

    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(13, 110, 253, 0.08) !important;
        border-color: rgba(13, 110, 253, 0.2) !important;
    }

    .input-group-text {
        color: #9ca3af;
    }
    
    .form-control:focus {
        box-shadow: none;
        border-color: #dee2e6;
    }
    
    .input-group:focus-within {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        border-radius: 0.375rem;
    }
    
    .input-group:focus-within .input-group-text,
    .input-group:focus-within .form-control {
        border-color: #86b7fe;
    }
</style>
