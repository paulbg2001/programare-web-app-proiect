function showFieldError(input, message) {
    var row = input.closest('.form-row');
    var error = row ? row.querySelector('.field-error') : null;

    if (error) {
        error.textContent = message;
    }
}

function clearErrors(form) {
    form.querySelectorAll('.field-error').forEach(function (error) {
        error.textContent = '';
    });
}

function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function isValidUsername(value) {
    return /^[a-z0-9_]{3,40}$/.test(value);
}

document.querySelectorAll('[data-auth-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        clearErrors(form);

        var isValid = true;
        var username = form.querySelector('input[name="username"]');
        var email = form.querySelector('input[name="email"]');
        var password = form.querySelector('input[name="password"]');
        var confirmPassword = form.querySelector('input[name="confirm_password"]');

        form.querySelectorAll('[required]').forEach(function (input) {
            if (!input.value.trim()) {
                showFieldError(input, 'This field is required.');
                isValid = false;
            }
        });

        if (username && username.value.trim() && !isValidUsername(username.value.trim())) {
            showFieldError(username, 'Use 3-40 lowercase letters, numbers or underscores.');
            isValid = false;
        }

        if (email && email.value.trim() && !isValidEmail(email.value.trim())) {
            showFieldError(email, 'Enter a valid email address.');
            isValid = false;
        }

        if (password && password.value && password.value.length < 6) {
            showFieldError(password, 'Password must have at least 6 characters.');
            isValid = false;
        }

        if (confirmPassword && password && confirmPassword.value !== password.value) {
            showFieldError(confirmPassword, 'Passwords do not match.');
            isValid = false;
        }

        if (!isValid) {
            event.preventDefault();
        }
    });
});

