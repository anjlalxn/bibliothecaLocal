document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // RESULT POPUP: show if success/error flash is present
    // ==========================================
    const resultModal = document.getElementById('resultModal');
    if (resultModal) {
        bootstrap.Modal.getOrCreateInstance(resultModal).show();
    }

    // ==========================================
    // Reopen add/edit modal if validation failed
    // ==========================================
    ['addCategory', 'editCategory'].forEach(function (id) {
        const modal = document.getElementById(id);
        if (modal && modal.dataset.showOnLoad === '1') {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    });

    // ==========================================
    // EDIT: fill the form with this category's data
    // ==========================================
    const editForm = document.getElementById('editCategoryForm');

    document.querySelectorAll('.edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            editForm.action = this.dataset.url;
            document.getElementById('edit_category_id').value = this.dataset.categoryId;
            document.getElementById('edit_category_name').value = this.dataset.name || '';

            // Clear leftover errors from a previous failed attempt
            const oldErrors = document.getElementById('editErrors');
            if (oldErrors) oldErrors.remove();

            editForm.querySelectorAll('.is-invalid').forEach(function (el) {
                el.classList.remove('is-invalid');
            });
        });
    });

    // ==========================================
    // DELETE: set the name, the URL, and the "has books" warning
    // ==========================================
    const deleteForm = document.getElementById('deleteCategoryForm');
    const deleteName = document.getElementById('deleteCategoryName');
    const deleteWarning = document.getElementById('deleteCategoryWarning');
    const deleteBooks = document.getElementById('deleteCategoryBooks');

    document.querySelectorAll('.delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const books = parseInt(this.dataset.books || '0', 10);

            deleteName.textContent = this.dataset.name || 'this category';
            deleteForm.action = this.dataset.url;
            deleteBooks.textContent = books;
            deleteWarning.classList.toggle('d-none', books === 0);
        });
    });

});