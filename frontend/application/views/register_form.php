<!DOCTYPE html>
<html>
<head>
    <title>Register</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Glass card */
        .glass-card {
            width: 100%;
            max-width: 620px;
            padding: 35px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(16px);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.35);
            color: #fff;
            animation: fadeUp 0.9s ease;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .glass-card h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        /* Grid */
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .input-group {
            margin-bottom: 18px;
        }

        label {
            font-size: 13px;
            color: #e5e5e5;
            margin-bottom: 6px;
            display: block;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input::placeholder {
            color: #ddd;
        }

        input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.35);
            box-shadow: 0 0 0 2px rgba(79, 172, 254, 0.9);
        }

        .full-width {
            grid-column: 1 / -1;
        }

        /* Button */
        .btn-register {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: #000;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 242, 254, 0.65);
        }

        .footer-text {
            text-align: center;
            font-size: 13px;
            margin-top: 22px;
            color: #ddd;
        }

        .footer-text span {
            color: #4facfe;
            font-weight: bold;
        }

        /* Mobile */
        @media (max-width: 640px) {
            .row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="glass-card">
    <h2>Create Account</h2>

    <form method="post" action="<?php echo site_url('chat/submit'); ?>">

        <div class="row">

            <div class="input-group">
                <label>First Name</label>
                <input type="text" name="first_name" placeholder="First name" required>
            </div>

            <div class="input-group">
                <label>Last Name</label>
                <input type="text" name="last_name" placeholder="Last name" required>
            </div>

            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Email address" required>
            </div>

            <div class="input-group">
                <label>User Name</label>
                <input type="text" name="username" placeholder="Username" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Create password" required>
            </div>

            <div class="input-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm password" required>
            </div>

            <div class="full-width">
                <button type="submit" class="btn-register">Register</button>
            </div>

        </div>

    </form>

    <div class="footer-text">
        Powered by <span>Isha kadam</span>
    </div>
</div>
<script>
// SIMPLE VALIDATION - No ID changes needed
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('.btn-register');
    
    if (!form || !submitBtn) return;
    
    // Get all input fields by their name attributes
    const inputs = form.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
    
    // Create error display areas
    inputs.forEach(input => {
        // Create error span if it doesn't exist
        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('error-msg')) {
            const errorSpan = document.createElement('span');
            errorSpan.className = 'error-msg';
            errorSpan.style.cssText = 'color: #ff6b6b; font-size: 12px; margin-top: 4px; display: block;';
            input.parentNode.appendChild(errorSpan);
        }
        
        // Add error/success styling
        input.addEventListener('input', function() {
            validateField(this);
            updateSubmitButton();
        });
        
        input.addEventListener('blur', function() {
            validateField(this);
        });
    });
    
    // Add CSS for validation styles
    const style = document.createElement('style');
    style.textContent = `
        input.error-border {
            border: 2px solid #ff6b6b !important;
            background: rgba(255, 107, 107, 0.1) !important;
        }
        input.success-border {
            border: 2px solid #51cf66 !important;
            background: rgba(81, 207, 102, 0.1) !important;
        }
        .error-msg {
            color: #ff6b6b;
            font-size: 12px;
            margin-top: 4px;
            display: block;
        }
    `;
    document.head.appendChild(style);
    
    // Validation function
    function validateField(field) {
        const value = field.value.trim();
        const errorSpan = field.parentNode.querySelector('.error-msg');
        
        // Clear previous error
        field.classList.remove('error-border', 'success-border');
        if (errorSpan) errorSpan.textContent = '';
        
        // Skip empty fields (will be caught on submit)
        if (!value) return false;
        
        let isValid = true;
        let errorMessage = '';
        
        switch(field.name) {
            case 'first_name':
            case 'last_name':
                if (value.length < 2) {
                    errorMessage = 'Must be at least 2 characters';
                    isValid = false;
                }
                break;
                
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    errorMessage = 'Please enter a valid email';
                    isValid = false;
                }
                break;
                
            case 'username':
                if (value.length < 3) {
                    errorMessage = 'Username must be at least 3 characters';
                    isValid = false;
                } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                    errorMessage = 'Only letters, numbers, and underscore allowed';
                    isValid = false;
                }
                break;
                
            case 'password':
                if (value.length < 6) {
                    errorMessage = 'Password must be at least 6 characters';
                    isValid = false;
                }
                break;
                
            case 'confirm_password':
                const passwordField = form.querySelector('[name="password"]');
                if (passwordField && value !== passwordField.value) {
                    errorMessage = 'Passwords do not match';
                    isValid = false;
                }
                break;
        }
        
        // Apply styling
        if (!isValid && errorMessage) {
            field.classList.add('error-border');
            if (errorSpan) errorSpan.textContent = errorMessage;
        } else if (value) {
            field.classList.add('success-border');
        }
        
        return isValid;
    }
    
    // Validate all fields
    function validateAll() {
        let allValid = true;
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                allValid = false;
            }
        });
        
        return allValid;
    }
    
    // Update submit button state
    function updateSubmitButton() {
        const allFilled = Array.from(inputs).every(input => input.value.trim());
        const allValid = validateAll();
        submitBtn.disabled = !(allFilled && allValid);
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        if (!validateAll()) {
            e.preventDefault();
            alert('Please fix the validation errors before submitting.');
            return;
        }
        
        // Simple password match check
        const password = form.querySelector('[name="password"]').value;
        const confirmPassword = form.querySelector('[name="confirm_password"]').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return;
        }
        
        // Disable button during submission
        submitBtn.disabled = true;
        submitBtn.textContent = 'Registering...';
    });
    
    // Real-time password match check
    const passwordField = form.querySelector('[name="password"]');
    const confirmField = form.querySelector('[name="confirm_password"]');
    
    if (passwordField && confirmField) {
        confirmField.addEventListener('input', function() {
            if (this.value && passwordField.value) {
                validateField(this);
                updateSubmitButton();
            }
        });
    }
    
    // Initialize
    updateSubmitButton();
});
</script>
</body>
</html>
