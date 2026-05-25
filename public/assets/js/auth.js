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

document.querySelectorAll('[data-auth-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        clearErrors(form);

        var email = form.querySelector('input[name="email"]');
        var password = form.querySelector('input[name="password"]');
        var confirmPassword = form.querySelector('input[name="confirm_password"]');
        var isValid = true;

        if (email && !email.value.includes('@')) {
            showFieldError(email, 'Enter a valid email address.');
            isValid = false;
        }

        if (password && password.value.length < 6) {
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

