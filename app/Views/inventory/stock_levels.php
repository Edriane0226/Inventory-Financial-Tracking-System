<?php
$search = $search ?? '';
$status = $status ?? 'all';
$summary = $summary ?? [
    'total_products' => 0,
    'in_stock' => 0,
    'low_stock' => 0,
    'out_of_stock' => 0,
];
$products = $products ?? [];
?>

<div class="dashboard-shell">
    <div class="sidebar-overlay d-lg-none"></div>
    <div class="dashboard-main">
        <main class="content-area px-3 px-lg-4 py-4 py-lg-5">
            <button class="btn btn-outline-primary mb-3 d-lg-none sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-list"></i> Menu
            </button>

            <section class="dashboard-hero mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="text-uppercase small text-muted mb-1">Inventory Monitoring</p>
                        <h1 class="display-6 fw-semibold mb-2">Stock Levels</h1>
                        <p class="text-muted mb-0">Track product availability, identify low stock quickly, and prioritize restocking.</p>
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <div class="stock-summary-card h-100">
                            <p class="small text-uppercase text-muted mb-2">Total Products</p>
                            <h2 class="h3 mb-0"><?= esc((string) ($summary['total_products'] ?? 0)) ?></h2>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="stock-summary-card h-100 border-start border-4 border-success">
                            <p class="small text-uppercase text-muted mb-2">In Stock</p>
                            <h2 class="h3 mb-0 text-success"><?= esc((string) ($summary['in_stock'] ?? 0)) ?></h2>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="stock-summary-card h-100 border-start border-4 border-warning">
                            <p class="small text-uppercase text-muted mb-2">Low Stock</p>
                            <h2 class="h3 mb-0 text-warning"><?= esc((string) ($summary['low_stock'] ?? 0)) ?></h2>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="stock-summary-card h-100 border-start border-4 border-danger">
                            <p class="small text-uppercase text-muted mb-2">Out of Stock</p>
                            <h2 class="h3 mb-0 text-danger"><?= esc((string) ($summary['out_of_stock'] ?? 0)) ?></h2>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form method="get" action="<?= base_url('stock-levels') ?>" class="row g-3 align-items-end">
                            <div class="col-md-7 col-lg-8">
                                <label for="search" class="form-label">Search Product</label>
                                <input
                                    id="search"
                                    name="search"
                                    type="text"
                                    class="form-control"
                                    placeholder="Search by product name or SKU"
                                    value="<?= esc($search) ?>"
                                >
                            </div>
                            <div class="col-md-5 col-lg-3">
                                <label for="status" class="form-label">Filter Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="all" <?= $status === 'all' || $status === '' ? 'selected' : '' ?>>All</option>
                                    <option value="in_stock" <?= $status === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                                    <option value="low_stock" <?= $status === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                                    <option value="out_of_stock" <?= $status === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                                </select>
                            </div>
                            <div class="col-lg-1 d-grid">
                                <button type="submit" class="btn btn-primary">Apply</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <section>
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 stock-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Product Name</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Stock Quantity</th>
                                    <th>Minimum Stock</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                    <th>Total Value</th>
                                    <th>Last Updated</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-muted">No products found for this filter.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <?php
                                        $stockStatus = $product['stock_status'] ?? 'Out of Stock';
                                        $rowClass = '';
                                        $badgeClass = 'bg-secondary';

                                        if ($stockStatus === 'In Stock') {
                                            $badgeClass = 'bg-success';
                                        } elseif ($stockStatus === 'Low Stock') {
                                            $rowClass = 'row-low-stock';
                                            $badgeClass = 'bg-warning text-dark';
                                        } else {
                                            $rowClass = 'row-out-stock';
                                            $badgeClass = 'bg-danger';
                                        }

                                        $updatedAt = $product['updated_at'] ?? null;
                                        ?>
                                        <tr class="<?= esc($rowClass) ?>">
                                            <td class="fw-semibold"><?= esc((string) ($product['name'] ?? 'N/A')) ?></td>
                                            <td><?= esc((string) ($product['sku'] ?? 'N/A')) ?></td>
                                            <td><?= esc((string) ($product['category'] ?? 'Uncategorized')) ?></td>
                                            <td><?= esc((string) ((int) ($product['stock_quantity'] ?? 0))) ?></td>
                                            <td><?= esc((string) ((int) ($product['minimum_stock'] ?? 0))) ?></td>
                                            <td><span class="badge <?= esc($badgeClass) ?>"><?= esc($stockStatus) ?></span></td>
                                            <td><?= esc(number_format((float) ($product['price'] ?? 0), 2)) ?></td>
                                            <td><?= esc(number_format((float) ($product['total_value'] ?? 0), 2)) ?></td>
                                            <td>
                                                <?= $updatedAt ? esc(date('M d, Y h:i A', strtotime((string) $updatedAt))) : 'N/A' ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url('products') ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const body = document.body;
        const toggleBtn = document.getElementById('sidebarToggle');
        const overlay = document.querySelector('.sidebar-overlay');

        const closeSidebar = () => body.classList.remove('sidebar-open');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => body.classList.toggle('sidebar-open'));
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }
    });
</script>

<style>
    .stock-summary-card {
        background: #fff;
        border-radius: 16px;
        padding: 1rem 1.1rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }

    .stock-table tbody tr.row-low-stock {
        background-color: #fff8d7;
    }

    .stock-table tbody tr.row-out-stock {
        background-color: #ffe5e5;
    }

    .stock-table {
        table-layout: fixed;
        width: 100%;
    }

    .stock-table td,
    .stock-table th {
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: middle;
        padding: 0.6rem 0.5rem;
    }

    .stock-table td:last-child,
    .stock-table th:last-child {
        width: 108px;
    }

    .stock-table .btn {
        white-space: nowrap;
    }

    @media (max-width: 991px) {
        .stock-table td,
        .stock-table th {
            font-size: 0.88rem;
        }

        .stock-table td:last-child,
        .stock-table th:last-child {
            width: 96px;
        }
    }
</style>
