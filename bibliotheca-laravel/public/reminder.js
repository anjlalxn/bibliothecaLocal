  // Mark a reminder bell as "sent" in this browser after it is clicked
    document.querySelectorAll('.remind-bell').forEach(function (bell) {
        var key = bell.dataset.key;

        function markSent() {
            bell.classList.remove('btn-outline-warning');
            bell.classList.add('btn-warning');
            bell.querySelector('i').className = 'bi bi-bell-fill';
        }

        try { if (localStorage.getItem(key)) markSent(); } catch (e) {}

        bell.addEventListener('click', function (event) {
            if (bell.classList.contains('disabled')) {
                event.preventDefault();
                return;
            }
            try { localStorage.setItem(key, '1'); } catch (e) {}
            markSent();
        });
    });