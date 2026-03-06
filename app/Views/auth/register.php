	<div class="dashboard-shell">
		<div class="dashboard-main">
			<main class="content-area px-2 px-lg-3 py-3 py-lg-4" style="max-width: none;">
				<div class="row gx-4">
					<div class="col-lg-8 col-xl-9 mb-4">
						<!-- Form Section -->
						<div class="card shadow-lg border-0 h-100">
							<div class="card-body p-0">
								<div class="row g-0 h-100">
									<!-- Left Side - Info Panel -->
									<div class="col-md-4 bg-primary text-white d-flex align-items-center">
										<div class="p-4 w-70">
											<div class="d-flex align-items-center mb-4">					
												<div>
													<h3 class="mb-1 fw-bold">User Management</h3>
													<p class="mb-0 opacity-75">Add New Employee</p>
												</div>
											</div>
										</div>
									</div>

									<!-- Right Side - Form -->
									<div class="col-md-8">
										<div class="p-4 p-lg-5">
											<div class="text-center mb-4">
												<h3 class="fw-bold mb-2">Add Employee</h3>
											</div>

											<?php if (session()->getFlashdata('errors')): ?>
												<div class="alert alert-danger border-0 shadow-sm">
													<div class="d-flex align-items-center mb-2">
														<i class="bi bi-exclamation-triangle-fill me-2"></i>
														<strong>Validation Errors:</strong>
													</div>
													<ul class="mb-0 ps-4">
														<?php foreach (session()->getFlashdata('errors') as $error): ?>
															<li><?= esc($error) ?></li>
														<?php endforeach; ?>
													</ul>
												</div>
											<?php endif; ?>

											<?php if (session()->getFlashdata('success')): ?>
												<div class="alert alert-success border-0 shadow-sm">
													<div class="d-flex align-items-center">
														<i class="bi bi-check-circle-fill me-2"></i>
														<?= esc(session()->getFlashdata('success')) ?>
													</div>
												</div>
											<?php endif; ?>

											<?php if (session()->getFlashdata('error')): ?>
												<div class="alert alert-danger border-0 shadow-sm">
													<div class="d-flex align-items-center">
														<i class="bi bi-x-circle-fill me-2"></i>
														<?= esc(session()->getFlashdata('error')) ?>
													</div>
												</div>
											<?php endif; ?>

											<?= form_open('register', ['class' => 'register-form']) ?>
												<div class="row">
													<div class="col-sm-6 mb-3">
														<div class="form-floating">
															<input type="text" class="form-control" id="first_name" name="first_name" 
																   placeholder="First Name" value="<?= old('first_name') ?>" required>
															<label for="first_name"><i class="bi bi-person me-1"></i>First Name</label>
														</div>
													</div>
													<div class="col-sm-6 mb-3">
														<div class="form-floating">
															<input type="text" class="form-control" id="last_name" name="last_name" 
																   placeholder="Last Name" value="<?= old('last_name') ?>" required>
															<label for="last_name"><i class="bi bi-person me-1"></i>Last Name</label>
														</div>
													</div>
												</div>

												<div class="mb-3">
													<div class="form-floating">
														<input type="email" class="form-control" id="email" name="email" 
															   placeholder="Email Address" value="<?= old('email') ?>" required>
														<label for="email"><i class="bi bi-envelope me-1"></i>Email Address</label>
													</div>
												</div>

												<div class="row">
													<div class="col-sm-6 mb-3">
														<div class="form-floating">
															<input type="password" class="form-control" id="password" name="password" 
																   placeholder="Password" required>
															<label for="password"><i class="bi bi-lock me-1"></i>Password</label>
														</div>
														<div id="passwordStrength" class="mt-2"></div>
													</div>
													<div class="col-sm-6 mb-3">
														<div class="form-floating">
															<select class="form-select" id="role_id" name="role_id" required>
																<option value="">Choose Role</option>
																<?php if (isset($roles)): ?>
																	<?php foreach ($roles as $role): ?>
																		<option value="<?= esc($role['id']) ?>" <?= old('role_id') == $role['id'] ? 'selected' : '' ?>>
																			<?= esc($role['role_name']) ?>
																		</option>
																	<?php endforeach; ?>
																<?php endif; ?>
															</select>
															<label for="role_id"><i class="bi bi-shield-check me-1"></i>Select Role</label>
														</div>
													</div>
												</div>

												<div class="row">
													<div class="col-sm-6 mb-3">
														<button type="submit" class="btn btn-primary btn-lg fw-semibold w-100">
															<i class="bi bi-person-plus me-2"></i>Create Account
														</button>
													</div>
													<div class="col-sm-6 mb-3">
														<a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary btn-lg w-100">
															<i class="bi bi-arrow-left me-1"></i>Back to Dashboard
														</a>
													</div>
												</div>
											<?= form_close() ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-4 col-xl-3">
						<!-- Existing Users Section -->
						<?php if (isset($users) && !empty($users)): ?>
							<div class="card shadow-sm border-0 h-100">
								<div class="card-header bg-light border-0 py-3">
									<div class="d-flex align-items-center justify-content-between">
										<h5 class="mb-0 d-flex align-items-center">
											<i class="bi bi-people me-2 text-primary"></i>
											<span>Existing Users</span>
										</h5>
										<span class="badge bg-primary"><?= count($users) ?> users</span>
									</div>
								</div>
								<div class="card-body p-0" style="max-height: 700px; overflow-y: auto;">
									<?php foreach ($users as $index => $user): ?>
										<div class="d-flex align-items-center p-3 <?= $index !== array_key_last($users) ? 'border-bottom' : '' ?>">
											<div class="bg-primary bg-opacity-10 text-primary fw-semibold rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; font-size: 1.1rem;">
												<?= esc(strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1))) ?>
											</div>
											<div class="flex-grow-1">
												<h6 class="mb-1 fw-semibold"><?= esc($user['first_name'] . ' ' . $user['last_name']) ?></h6>
												<small class="text-muted d-block text-truncate" style="max-width: 150px;"><?= esc($user['email']) ?></small>
												<span class="badge <?= strtolower($user['role_name']) === 'owner' ? 'bg-danger' : 'bg-success' ?> text-white mt-1">
													<?= esc($user['role_name']) ?>
												</span>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php else: ?>
							<div class="card shadow-sm border-0 h-100">
								<div class="card-body d-flex align-items-center justify-content-center text-center">
									<div>
										<i class="bi bi-people fs-1 text-muted mb-3"></i>
										<h5 class="text-muted">No Users Yet</h5>
										<p class="text-muted">Create your first user account to get started.</p>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</main>
		</div>
	</div>

	<style>
		.form-floating > .form-control:focus,
		.form-floating > .form-control:not(:placeholder-shown),
		.form-floating > .form-select:focus,
		.form-floating > .form-select:not([value=""]) {
			padding-top: 1.625rem;
			padding-bottom: 0.625rem;
		}

		.form-floating > .form-control:focus ~ label,
		.form-floating > .form-control:not(:placeholder-shown) ~ label,
		.form-floating > .form-select:focus ~ label,
		.form-floating > .form-select:not([value=""]) ~ label {
			opacity: 0.65;
			transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
		}

		.form-control:focus,
		.form-select:focus {
			border-color: #0d6efd;
			box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
		}

		.btn {
			border-radius: 0.5rem;
		}

		.card {
			border-radius: 1rem;
		}

		.password-strength {
			height: 4px;
			background: #e9ecef;
			border-radius: 2px;
			overflow: hidden;
			margin-top: 0.5rem;
		}

		.password-strength-bar {
			height: 100%;
			transition: width 0.3s ease;
		}

		.strength-weak { background-color: #dc3545; width: 25%; }
		.strength-fair { background-color: #fd7e14; width: 50%; }
		.strength-good { background-color: #ffc107; width: 75%; }
		.strength-strong { background-color: #198754; width: 100%; }
	</style>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.querySelector('.register-form');
			const submitBtn = form.querySelector('button[type="submit"]');
			const originalText = submitBtn.innerHTML;
			
			// Form submission handling
			form.addEventListener('submit', function() {
				submitBtn.disabled = true;
				submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating Account...';
			});

			// Password strength indicator
			const passwordField = document.getElementById('password');
			const strengthContainer = document.getElementById('passwordStrength');

			passwordField.addEventListener('input', function() {
				const password = this.value;
				let strength = 0;
				let strengthText = '';
				let strengthClass = '';

				// Calculate strength
				if (password.length >= 6) strength++;
				if (/[A-Z]/.test(password)) strength++;
				if (/[0-9]/.test(password)) strength++;
				if (/[^A-Za-z0-9]/.test(password)) strength++;

				// Determine strength level
				if (password.length === 0) {
					strengthContainer.innerHTML = '';
					return;
				}

				switch (strength) {
					case 1:
						strengthText = 'Weak';
						strengthClass = 'strength-weak';
						break;
					case 2:
						strengthText = 'Fair';
						strengthClass = 'strength-fair';
						break;
					case 3:
						strengthText = 'Good';
						strengthClass = 'strength-good';
						break;
					case 4:
						strengthText = 'Strong';
						strengthClass = 'strength-strong';
						break;
					default:
						strengthText = 'Very Weak';
						strengthClass = 'strength-weak';
				}

				strengthContainer.innerHTML = `
					<div class="d-flex justify-content-between align-items-center">
						<small class="text-muted">Password strength: <span class="fw-semibold">${strengthText}</span></small>
					</div>
					<div class="password-strength">
						<div class="password-strength-bar ${strengthClass}"></div>
					</div>
				`;
			});

			// Enhanced form validation
			const inputs = form.querySelectorAll('input[required], select[required]');
			inputs.forEach(input => {
				input.addEventListener('blur', function() {
					if (!this.value.trim()) {
						this.classList.add('is-invalid');
					} else {
						this.classList.remove('is-invalid');
						this.classList.add('is-valid');
					}
				});

				input.addEventListener('input', function() {
					if (this.classList.contains('is-invalid') && this.value.trim()) {
						this.classList.remove('is-invalid');
						this.classList.add('is-valid');
					}
				});
			});

			// Email validation
			const emailField = document.getElementById('email');
			emailField.addEventListener('blur', function() {
				const email = this.value.trim();
				const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				
				if (email && !emailRegex.test(email)) {
					this.classList.add('is-invalid');
					this.classList.remove('is-valid');
				} else if (email) {
					this.classList.remove('is-invalid');
					this.classList.add('is-valid');
				}
			});
		});
	</script>
