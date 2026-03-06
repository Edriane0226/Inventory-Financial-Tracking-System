<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dashboard - Inventory &amp; Financial Tracking System</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
	<style>
		:root {
			--sidebar-width: 280px;
		}

		body {
			background: #f5f6fa;
			font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, "Helvetica Neue", sans-serif;
		}

		.dashboard-shell {
			display: flex;
			min-height: 100vh;
		}

		.sidebar {
			width: var(--sidebar-width);
			background: #fff;
			border-right: 1px solid #e5e7eb;
			padding: 2.5rem 1.75rem;
			position: sticky;
			top: 0;
			height: 100vh;
			flex-shrink: 0;
		}

		.brand-icon {
			width: 48px;
			height: 48px;
			font-size: 1.35rem;
		}

		.avatar {
			width: 48px;
			height: 48px;
			border-radius: 14px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			font-size: 1.1rem;
		}

		.sidebar-link {
			color: #4b5563;
			font-weight: 500;
			padding: 0.65rem 0.95rem;
			border-radius: 12px;
			transition: all 0.2s ease;
		}

		.sidebar-link:hover {
			background: rgba(13, 110, 253, 0.08);
			color: #0d6efd;
		}

		.sidebar-link.active {
			background: rgba(13, 110, 253, 0.12);
			color: #0d6efd;
		}

		.sidebar-tip .p-3 {
			border: 1px dashed rgba(13, 110, 253, 0.3);
		}

		.sidebar-overlay {
			display: none;
		}

		.dashboard-main {
			flex-grow: 1;
		}

		.content-area {
			max-width: 1200px;
			margin: 0 auto;
		}

		.dashboard-hero {
			background: linear-gradient(120deg, rgba(13, 110, 253, 0.15), rgba(108, 117, 125, 0.15));
			border-radius: 24px;
			padding: 2rem;
			border: 1px solid rgba(13, 110, 253, 0.08);
		}

		.stat-card {
			border-radius: 18px;
			padding: 1.5rem;
			background: #fff;
			box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
		}

		.stat-icon {
			width: 48px;
			height: 48px;
			border-radius: 14px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: rgba(13, 110, 253, 0.1);
			font-size: 1.4rem;
		}

		.card {
			border: none;
			border-radius: 22px;
			box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
		}

		.table thead {
			font-size: 0.9rem;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #6c757d;
		}

		.activity-item + .activity-item {
			border-top: 1px dashed rgba(0, 0, 0, 0.08);
			padding-top: 1rem;
		}

		.activity-icon {
			width: 36px;
			height: 36px;
			border-radius: 50%;
			background: rgba(13, 110, 253, 0.12);
			color: #0d6efd;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			margin-right: 0.75rem;
		}

		.quick-actions .btn {
			border-radius: 999px;
			font-weight: 600;
		}

		.sidebar-toggle {
			position: relative;
			z-index: 1;
		}

		@media (max-width: 991px) {
			.dashboard-shell {
				flex-direction: column;
			}

			.sidebar {
				position: fixed;
				inset: 0 auto 0 0;
				height: 100vh;
				transform: translateX(-100%);
				transition: transform 0.3s ease;
				z-index: 1045;
			}

			.sidebar-overlay {
				display: block;
				position: fixed;
				inset: 0;
				background: rgba(15, 23, 42, 0.45);
				opacity: 0;
				pointer-events: none;
				transition: opacity 0.3s ease;
				z-index: 1040;
			}

			body.sidebar-open .sidebar {
				transform: translateX(0);
			}

			body.sidebar-open .sidebar-overlay {
				opacity: 1;
				pointer-events: auto;
			}

			.dashboard-hero {
				text-align: center;
			}
		}
	</style>
</head>
<body>
<?php
$role = session()->get('role') ?? 'Employee';
$firstName = session()->get('first_name') ?? '';
$lastName = session()->get('last_name') ?? '';
$firstInitial = $firstName !== '' ? (function_exists('mb_substr') ? mb_substr($firstName, 0, 1) : substr($firstName, 0, 1)) : '';
$lastInitial = $lastName !== '' ? (function_exists('mb_substr') ? mb_substr($lastName, 0, 1) : substr($lastName, 0, 1)) : '';
$initials = strtoupper($firstInitial . $lastInitial);
if ($initials === '') {
    $initials = 'IS';
}
$navItems = [
    ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => '/dashboard', 'roles' => ['Owner', 'Employee']],
    ['label' => 'Products', 'icon' => 'bi-boxes', 'url' => '/products', 'roles' => ['Owner', 'Employee']],
    ['label' => 'Stock-Out', 'icon' => 'bi-cart-check', 'url' => '/stock-out', 'roles' => ['Owner', 'Employee']],
    ['label' => 'Stock-In', 'icon' => 'bi-receipt', 'url' => '/orders', 'roles' => ['Owner', 'Employee']],
    ['label' => 'Users Management', 'icon' => 'bi-people', 'url' => '/auth/register', 'roles' => ['Owner']],
    ['label' => 'Reports', 'icon' => 'bi-bar-chart-line', 'url' => '/reports', 'roles' => ['Owner']],
];

$uri = service('uri');
$currentPath = '/' . trim($uri->getPath(), '/');
if ($currentPath === '//') {
    $currentPath = '/';
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="/">
            <span class="bg-primary text-white rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.9rem;">
                <i class="bi bi-box-seam"></i>
            </span>
            <span>Control Center</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach ($navItems as $item): ?>
                    <?php if (in_array($role, $item['roles'], true)): ?>
                        <?php $isActive = $currentPath === $item['url']; ?>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded-3 <?= $isActive ? 'active bg-primary text-white' : 'text-dark' ?>" href="<?= esc($item['url']) ?>">
                                <i class="bi <?= esc($item['icon']) ?> small"></i>
                                <span><?= esc($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 text-primary fw-semibold rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 0.9rem;">
                        <?= esc($initials) ?>
                    </div>
                    <div class="d-none d-md-block">
                        <div class="fw-semibold small"><?= esc(trim($firstName . ' ' . $lastName)) ?></div>
                        <span class="badge bg-light text-dark small"><?= esc($role) ?></span>
                    </div>
                    <a href="logout" class="btn btn-outline-danger btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>
</body>
</html>