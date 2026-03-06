<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
	<title>Login - Inventory & Financial Tracking System</title>
	<style>
		:root {
			--ci-primary: #0d6efd;
			--ci-secondary: #6c757d;
			--ci-card-radius: 28px;
		}

		body {
			min-height: 100vh;
			margin: 0;
			background: linear-gradient(115deg, rgba(13, 110, 253, 0.95), rgba(108, 117, 125, 0.85));
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 2rem 1rem;
			font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, "Helvetica Neue", sans-serif;
		}

		.auth-wrapper {
			width: min(1100px, 100%);
		}

		.auth-card {
			border-radius: var(--ci-card-radius);
			overflow: hidden;
			box-shadow: 0 32px 80px rgba(13, 45, 80, 0.35);
		}

		.gradient-panel {
			background: linear-gradient(135deg, rgba(13, 110, 253, 0.95), rgba(108, 117, 125, 0.92));
			color: #fff;
			position: relative;
			isolation: isolate;
		}

		.gradient-panel::after {
			content: "";
			position: absolute;
			top: 15%;
			right: -60px;
			width: 240px;
			height: 240px;
			background: rgba(255, 255, 255, 0.12);
			filter: blur(0px);
			border-radius: 50%;
			opacity: 0.6;
			z-index: -1;
		}

		.hero-badge {
			background: rgba(255, 255, 255, 0.15);
			border: 1px solid rgba(255, 255, 255, 0.3);
			border-radius: 999px;
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			padding: 0.3rem 1rem;
			font-size: 0.9rem;
			text-transform: uppercase;
			letter-spacing: 0.08em;
		}

		.feature-list {
			list-style: none;
			padding: 0;
			margin: 1.5rem 0 0;
		}

		.feature-list li + li {
			margin-top: 0.75rem;
		}

		.feature-list i {
			color: #75ffda;
			margin-right: 0.35rem;
		}

		.form-panel {
			background: #fff;
			padding: clamp(2rem, 4vw, 3.25rem);
		}

		.form-control:focus {
			border-color: var(--ci-primary);
			box-shadow: 0 0 0 .2rem rgba(13, 110, 253, 0.25);
		}

		.btn-primary {
			background: var(--ci-primary);
			border-color: var(--ci-primary);
			font-weight: 600;
			padding: 0.75rem 1rem;
		}

		.text-link {
			color: var(--ci-secondary);
			text-decoration: none;
			font-weight: 500;
		}

		.text-link:hover {
			color: var(--ci-primary);
		}

		@media (max-width: 991px) {
			body {
				padding: 1.5rem;
			}
			.gradient-panel {
				text-align: center;
			}
		}
	</style>
</head>
<body>
	<div class="auth-wrapper">
		<div class="card auth-card border-0">
			<div class="row g-0 align-items-stretch">
				<div class="col-lg-6 gradient-panel p-5">
					<h1 class="display-6 fw-semibold">Inventory &amp; Financial Tracking System</h1>
                    
				</div>
				<div class="col-lg-6 form-panel">
					<h4 class="fw-semibold mb-2">Welcome back</h4>
					<p class="text-muted mb-4">Sign in to continue.</p>

					<?php if (session()->getFlashdata('success')) : ?>
						<div class="alert alert-success small"><?= esc(session()->getFlashdata('success')) ?></div>
					<?php endif; ?>

					<?php if (session()->getFlashdata('error')) : ?>
						<div class="alert alert-danger small"><?= esc(session()->getFlashdata('error')) ?></div>
					<?php endif; ?>

					<?php $errors = session()->getFlashdata('errors') ?? null; ?>
					<?php if (!empty($errors) && is_array($errors)) : ?>
						<div class="alert alert-danger small">
							<ul class="mb-0">
								<?php foreach ($errors as $field => $msg) : ?>
									<li><?= esc($msg) ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<form method="post" action="" novalidate>
						<?php if (function_exists('csrf_field')) echo csrf_field(); ?>

						<div class="mb-3">
							<label for="email" class="form-label">Email address</label>
							<input type="email" class="form-control form-control-lg" id="email" name="email" value="<?= esc(old('email')) ?>" required autofocus>
						</div>

						<div class="mb-4">
							<label for="password" class="form-label">Password</label>
							<input type="password" class="form-control form-control-lg" id="password" name="password" required>
						</div>

						<div class="d-grid gap-2">
							<button type="submit" class="btn btn-primary btn-lg">Sign in</button>
						</div>
					</form>

					<div class="text-center small text-muted mt-4">
				    <a class="text-link" href="<?= site_url('/') ?>">Return to homepage</a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
