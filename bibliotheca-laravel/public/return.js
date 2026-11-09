 document.addEventListener('DOMContentLoaded', function () {

        // ==========================================
        // RESULT POPUP: show if success/error flash is present
        // ==========================================
        const resultModal = document.getElementById('resultModal');
        if (resultModal) {
            bootstrap.Modal.getOrCreateInstance(resultModal).show();
        }

        // ==========================================
        // FILTER MODAL: don't send empty fields
        // ==========================================
        const filterForm = document.querySelector('#filterReturns form');

        if (filterForm) {
            filterForm.addEventListener('submit', function () {
                filterForm.querySelectorAll('input, select').forEach(function (field) {
                    if (field.value === '') {
                        field.disabled = true;
                    }
                });
            });
        }

        // ==========================================
        // CONFIRM RETURN MODAL
        // ==========================================
        const returnForm = document.getElementById('returnForm');
        const returnBookTitle = document.getElementById('returnBookTitle');
        const returnMemberName = document.getElementById('returnMemberName');

        document.querySelectorAll('.return-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                returnForm.action = this.dataset.url;
                returnBookTitle.textContent = this.dataset.book || 'this book';
                returnMemberName.textContent = this.dataset.member || 'this member';
            });
        });

    });