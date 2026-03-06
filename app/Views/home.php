<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <title>Inventory & Financial Tracking System</title>
    <style>
        /* Small custom styles for the demo homepage */
        .hero {
            padding: 6rem 0;
            background: linear-gradient(90deg, #0d6efd22, #6c757d11);
        }
        .feature-icon {
            font-size: 2rem;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="#">Inventory & Financial Tracking System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">About</a>
                    </li>
                    <li class="nav-item"><a class="btn btn-primary ms-2" href="<?= base_url('login') ?>">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1 class="display-5">Inventory & Financial Tracking System</h1>
                    <p class="lead text-muted">Manage stock, track transactions, and keep your finances in order — all in one simple web app.</p>
                    <p>
                        <a href="#features" class="btn btn-primary btn-lg me-2">Explore features</a>
                    </p>
                </div>
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-box-seam display-1 text-primary"></i>
                            <h5 class="card-title mt-3">Quick overview</h5>
                            <p class="card-text text-muted">Snapshot of stock, low inventory alerts, and recent transactions.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="py-5">
        <div class="container">
            <section id="features" class="mb-5">
                <h2 class="h3 mb-4">Features</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="mb-3"><i class="bi bi-graph-up feature-icon"></i></div>
                                <h5 class="card-title">Inventory Management</h5>
                                <p class="card-text text-muted">Track products, categories, stock levels, and reorder points with ease.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="mb-3"><i class="bi bi-currency-dollar feature-icon"></i></div>
                                <h5 class="card-title">Financial Tracking</h5>
                                <p class="card-text text-muted">Record income and expenses, generate basic reports, and monitor cash flow.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="mb-3"><i class="bi bi-people feature-icon"></i></div>
                                <h5 class="card-title">Users & Roles</h5>
                                <p class="card-text text-muted">Manage user access with roles and permissions to keep operations secure.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="about">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3>About this project</h3>
                        <p class="text-muted">Inventory & Financial Tracking System a software that designed for small business owners. it is ideal for sari-sari store owners or retail shop owners that handle inventory, sales and expenses</p>
                        <ul class="list-unstyled text-muted">
                            <li><i class="bi bi-check2-circle text-success"></i> Product Management</li>
                            <li><i class="bi bi-check2-circle text-success"></i> Inventory Control</li>
                            <li><i class="bi bi-check2-circle text-success"></i> Sales Management</li>
                            <li><i class="bi bi-check2-circle text-success"></i> Financial Tracking</li>
                            <li><i class="bi bi-check2-circle text-success"></i> Security</li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer class="bg-light border-top py-4">
        <div class="container d-flex justify-content-between small text-muted">
            <div><span id="year"></span> PD323 Software Project</div>
            <div>Built with <a href="https://codeigniter.com" target="_blank">CodeIgniter</a> &amp; Bootstrap</div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>
</html>