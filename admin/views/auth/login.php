<?php include VIEW_PATH . '/layouts/header.php'; ?>

<div class="login-container d-flex align-items-center justify-content-center">
    <div class="container-fluid h-100">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-11 col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
                <div class="login-card p-4">
                    <!-- Logo -->
                    <div class="login-logo">
                        <i class="fas fa-store"></i>
                    </div>
                    
                    <!-- Title -->
                    <h2 class="text-center mb-3">Deal Machan Admin</h2>
                    <p class="text-center text-muted mb-3">Sign in to your admin account</p>
                    
                    <!-- Error Messages -->
                    <?php if (isset($error) && $error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= escape($error) ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Success Messages -->
                    <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= escape($success) ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Login Form -->
                    <form method="POST" action="<?= BASE_URL ?>auth/processLogin" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="Enter your email" 
                                       required 
                                       autocomplete="username"
                                       value="<?= isset($_POST['email']) ? escape($_POST['email']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Enter your password" 
                                       required 
                                       autocomplete="current-password">
                                <button class="btn btn-outline-secondary password-toggle" type="button" id="togglePassword" title="Toggle password visibility">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </button>
                        </div>
                    </form>
                    
                    <!-- Forgot Password -->
                    <div class="text-center mt-4">
                        <a href="<?= BASE_URL ?>auth/forgotPassword" class="text-muted">
                            <i class="fas fa-question-circle me-1"></i>
                            Forgot your password?
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility - Pure JavaScript implementation
    const toggleButton = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (toggleButton && passwordField && toggleIcon) {
        console.log('Password toggle elements found and initialized');
        
        toggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Password toggle clicked, current type:', passwordField.type);
            
            try {
                if (passwordField.type === 'password') {
                    // Show password
                    passwordField.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                    toggleButton.setAttribute('title', 'Hide password');
                    toggleButton.setAttribute('aria-label', 'Hide password');
                    console.log('Password shown');
                } else {
                    // Hide password
                    passwordField.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                    toggleButton.setAttribute('title', 'Show password');
                    toggleButton.setAttribute('aria-label', 'Show password');
                    console.log('Password hidden');
                }
                
                // Add visual feedback
                toggleButton.classList.add('active');
                setTimeout(() => {
                    toggleButton.classList.remove('active');
                }, 150);
                
                // Focus back on password field
                passwordField.focus();
                
            } catch (error) {
                console.error('Password toggle error:', error);
            }
        });
    } else {
        console.error('Password toggle elements not found:', {
            toggleButton: !!toggleButton,
            passwordField: !!passwordField,
            toggleIcon: !!toggleIcon
        });
        
        // Keyboard support for password toggle
        toggleButton.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    }
    
    // jQuery fallback for form validation if jQuery is available
    if (typeof $ !== 'undefined') {
        // Form validation with jQuery
        $('#loginForm').submit(function(e) {
            const email = $('#email').val().trim();
            const password = $('#password').val().trim();
            
            // Reset validation classes
            $('.form-control').removeClass('is-invalid');
            
            let isValid = true;
            
            // Validate email
            if (!email) {
                $('#email').addClass('is-invalid');
                isValid = false;
            } else if (!isValidEmail(email)) {
                $('#email').addClass('is-invalid');
                isValid = false;
            }
            
            // Validate password
            if (!password) {
                $('#password').addClass('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            $('#loginBtn').html('<i class="fas fa-spinner fa-spin me-2"></i>Signing In...')
                         .prop('disabled', true);
        });
        
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Auto-focus first field
        $('#email').focus();
    } else {
        // Pure JavaScript form validation fallback
        const loginForm = document.getElementById('loginForm');
        const emailField = document.getElementById('email');
        const passwordField = document.getElementById('password');
        const loginBtn = document.getElementById('loginBtn');
        
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                const email = emailField.value.trim();
                const password = passwordField.value.trim();
                
                let isValid = true;
                
                // Reset validation classes
                emailField.classList.remove('is-invalid');
                passwordField.classList.remove('is-invalid');
                
                // Validate email
                if (!email) {
                    emailField.classList.add('is-invalid');
                    isValid = false;
                } else if (!isValidEmailJS(email)) {
                    emailField.classList.add('is-invalid');
                    isValid = false;
                }
                
                // Validate password
                if (!password) {
                    passwordField.classList.add('is-invalid');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading state
                loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
                loginBtn.disabled = true;
            });
        }
        
        function isValidEmailJS(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Auto-focus first field
        if (emailField) {
            emailField.focus();
        }
    }
});
</script>

<?php include VIEW_PATH . '/layouts/footer.php'; ?>