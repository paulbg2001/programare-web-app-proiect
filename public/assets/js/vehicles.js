document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.management-form');
    if (!form) return;

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var isValid = true;
        var requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(function (field) {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            alert('Te rugam sa completezi toate campurile obligatorii.');
            return;
        }

        var formData = new FormData(form);
        var data = {};
        formData.forEach(function(value, key){
            data[key] = value;
        });

        // Map action to API format if needed
        var url = '/api/vehicles.php';
        var method = 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(result) {
            if (result.success) {
                window.location.reload();
            } else {
                alert('Eroare: ' + (result.error || 'Operatiunea a esuat.'));
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            alert('A aparut o eroare la comunicarea cu serverul.');
        });
    });

    // Handle deactivation via AJAX
    document.querySelectorAll('.management-table form').forEach(function(deactivateForm) {
        deactivateForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (!confirm('Sigur dorești să marchezi acest vehicul ca inactiv?')) {
                return;
            }

            var formData = new FormData(deactivateForm);
            var data = {};
            formData.forEach(function(value, key){
                data[key] = value;
            });

            fetch('/api/vehicles.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(result) {
                if (result.success) {
                    window.location.reload();
                } else {
                    alert('Eroare: ' + (result.error || 'Operatiunea a esuat.'));
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('A aparut o eroare la comunicarea cu serverul.');
            });
        });
    });
});
