   document.addEventListener('DOMContentLoaded', function () {

        // Open the result popup (success / error) if there is one
        const resultModal = document.getElementById('resultModal');
        if (resultModal) {
            bootstrap.Modal.getOrCreateInstance(resultModal).show();
        }

        // Reopen the add/edit modals if validation failed
        ['addMember', 'editMember', 'addAdmin', 'resetPassword'].forEach(function (id) {
            const modal = document.getElementById(id);
            if (modal && modal.dataset.showOnLoad === '1') {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });

        // Fill the edit modal
        const editForm = document.getElementById('editMemberForm');

        document.querySelectorAll('.edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const d = this.dataset;

                editForm.action = d.url;
                document.getElementById('edit_member_id').value = d.memberId;
                document.getElementById('edit_member_code').value = d.code || '';
                document.getElementById('edit_full_name').value = d.name || '';
                document.getElementById('edit_email').value = d.email || '';
                document.getElementById('edit_status').value =
                    d.status === 'inactive' ? 'inactive' : 'active';

                // Clear leftovers from a previous failed attempt
                const oldErrors = document.getElementById('editErrors');
                if (oldErrors) oldErrors.remove();
                editForm.querySelectorAll('.is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                });
            });
        });

        // Fill the delete modal
        const deleteForm = document.getElementById('deleteMemberForm');
        const deleteName = document.getElementById('deleteMemberName');

        document.querySelectorAll('.delete-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                deleteName.textContent = this.dataset.name || 'this member';
                deleteForm.action = this.dataset.url;
            });
        });

        // Fill the activate / deactivate admin modal
        const toggleUserId = document.getElementById('toggle_user_id');

        if (toggleUserId) {
            const toggleTitle  = document.getElementById('toggleAdminLabel');
            const toggleAsk    = document.getElementById('toggleAdminQuestion');
            const toggleName   = document.getElementById('toggleAdminName');
            const toggleNote   = document.getElementById('toggleAdminNote');
            const toggleNoteTx = document.getElementById('toggleAdminNoteText');
            const toggleSubmit = document.getElementById('toggleAdminSubmit');
            const toggleIcon   = document.getElementById('toggleAdminIcon');
            const toggleSubTx  = document.getElementById('toggleAdminSubmitText');

            document.querySelectorAll('.toggle-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const isActive = this.dataset.active === '1';

                    toggleUserId.value = this.dataset.userId;
                    toggleName.textContent = this.dataset.username || 'this admin';

                    if (isActive) {
                        toggleTitle.textContent = 'Deactivate Admin';
                        toggleAsk.textContent = 'Are you sure you want to deactivate this admin?';
                        toggleNoteTx.textContent =
                            'This admin will no longer be able to log in. You can activate the account again at any time.';
                        toggleNote.className = 'alert alert-danger mb-0';
                        toggleSubmit.className = 'btn btn-danger';
                        toggleIcon.className = 'bi bi-person-dash me-1';
                        toggleSubTx.textContent = 'Deactivate';
                    } else {
                        toggleTitle.textContent = 'Activate Admin';
                        toggleAsk.textContent = 'Are you sure you want to activate this admin?';
                        toggleNoteTx.textContent =
                            'This admin will be able to log in again.';
                        toggleNote.className = 'alert alert-info mb-0';
                        toggleSubmit.className = 'btn btn-info text-white';
                        toggleIcon.className = 'bi bi-person-check me-1';
                        toggleSubTx.textContent = 'Activate';
                    }
                });
            });
        }

        // Fill the reset admin password modal
        const resetUserId = document.getElementById('reset_user_id');
        const resetUsername = document.getElementById('resetUsername');

        if (resetUserId) {
            document.querySelectorAll('.reset-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    resetUserId.value = this.dataset.userId;
                    resetUsername.textContent = this.dataset.username || '';

                    document.getElementById('reset_password').value = '';
                    document.getElementById('reset_password_confirmation').value = '';

                    const oldErrors = document.getElementById('resetErrors');
                    if (oldErrors) oldErrors.remove();
                });
            });
        }
    });