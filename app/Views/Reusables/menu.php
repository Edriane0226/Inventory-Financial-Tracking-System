<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"> 
</head>
<body>
    <?php if (session()->get('role') === 'Owner'): ?>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <a class="navbar-brand" href="/">Inventory System</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="/products">Products</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/suppliers">Suppliers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/orders">Orders</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/register">Users</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php elseif (session()->get('role') === 'Employee'): ?>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <a class="navbar-brand" href="/">Inventory System</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="/products">Products</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/suppliers">Suppliers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/orders">Orders</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>
</body>
</html>