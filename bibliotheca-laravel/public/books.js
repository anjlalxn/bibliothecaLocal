  document.addEventListener('DOMContentLoaded', function () {

        // ==========================================
        // RESULT POPUP: show if success/error flash is present
        // ==========================================
        const resultModal = document.getElementById('resultModal');
        if (resultModal) {
            bootstrap.Modal.getOrCreateInstance(resultModal).show();
        }

        // ==========================================
        // ADD / EDIT MODALS: reopen if validation fails
        // ==========================================
        ['addBook', 'editBook'].forEach(function (id) {
            const modal = document.getElementById(id);
            if (modal && modal.dataset.showOnLoad === '1') {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });

        // ==========================================
        // FILTER MODAL: don't send empty fields
        // ==========================================
        const filterForm = document.querySelector('#filterBooks form');

        if (filterForm) {
            filterForm.addEventListener('submit', function () {
                filterForm.querySelectorAll('input, select').forEach(function (field) {
                    // Skip the default sort too
                    if (field.value === '' || (field.name === 'sort' && field.value === 'id')) {
                        field.disabled = true;
                    }
                });
            });
        }

        // ==========================================
        // EDIT BOOK MODAL
        // ==========================================
        const editForm = document.getElementById('editBookForm');
        const editBookId = document.getElementById('edit_book_id');
        const editBorrowedNote = document.getElementById('edit_borrowed_note');

        document.querySelectorAll('.edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const d = this.dataset;

                editForm.action = d.url;
                editBookId.value = d.bookId;

                document.getElementById('edit_title').value = d.title || '';
                document.getElementById('edit_author').value = d.author || '';
                document.getElementById('edit_category_id').value = d.categoryId || '';
                document.getElementById('edit_publisher').value = d.publisher || '';
                document.getElementById('edit_publication_year').value = d.year || '';
                document.getElementById('edit_quantity').value = d.qty || 1;
                document.getElementById('edit_status').value =
                    d.status === 'unavailable' ? 'unavailable' : 'available';

                // Show how many copies are currently out, and don't
                // let the quantity go below that in the browser either
                const borrowed = parseInt(d.borrowed || '0', 10);
                const qtyInput = document.getElementById('edit_quantity');

                if (d.status === 'available' && borrowed > 0) {
                    qtyInput.min = borrowed;
                    editBorrowedNote.textContent =
                        borrowed + ' cop' + (borrowed === 1 ? 'y is' : 'ies are')
                        + ' currently borrowed, so quantity cannot go below that.';
                } else {
                    qtyInput.min = 1;
                    editBorrowedNote.textContent = 'Total number of physical copies.';
                }

                // Clear errors left over from a previous failed attempt
                const oldErrors = document.getElementById('editErrors');
                if (oldErrors) oldErrors.remove();

                editForm.querySelectorAll('.is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                });
            });
        });

        // ==========================================
        // DELETE BOOK MODAL
        // ==========================================
        const deleteForm = document.getElementById('deleteBookForm');
        const deleteTitle = document.getElementById('deleteBookTitle');

        document.querySelectorAll('.delete-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                deleteTitle.textContent = this.dataset.title || 'this book';
                deleteForm.action = this.dataset.url;
            });
        });

        // ==========================================
        // BOOK COVER PREVIEW
        // ==========================================
        const coverBox = document.getElementById('v-cover-box');
        const coverImg = document.getElementById('v-cover');
        const coverFallback = document.getElementById('v-cover-fallback');

        const coverCache = new Map();
        let latestRequest = 0;

        function normalize(s) {
            return (s || '')
                .toString()
                .toLowerCase()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')   // strip accents
                .replace(/[^\p{L}\p{N}\s]/gu, '')  // strip punctuation
                .replace(/\s+/g, ' ')
                .trim();
        }

        // Display cover or fallback icon
        function showCover(url) {
            coverBox.classList.remove('is-loading');

            if (url) {
                coverImg.src = url;
                coverImg.hidden = false;
                coverFallback.hidden = true;
            } else {
                coverImg.hidden = true;
                coverImg.removeAttribute('src');
                coverFallback.hidden = false;
            }
        }

        // Handle failed image loading
        coverImg.addEventListener('error', function () {
            showCover(null);
        });

        // Open Library lookup: exact (normalized) title match + author match required.
        // Returns a cover URL only if a candidate passes BOTH checks; otherwise null.
        async function findCover(title, author) {
            const normTitle = normalize(title);
            const normAuthor = normalize(author);
            if (!normTitle) return null;

            const key = normTitle + '|' + normAuthor;

            if (coverCache.has(key)) {
                return coverCache.get(key);
            }

            let url = null;

            try {
                const params = new URLSearchParams({
                    title,
                    limit: '10',
                    fields: 'title,author_name,cover_i'
                });
                if (author) params.set('author', author);

                const response = await fetch('https://openlibrary.org/search.json?' + params);

                if (response.ok) {
                    const data = await response.json();
                    const docs = (data && data.docs) || [];

                    const match = docs.find(function (doc) {
                        if (normalize(doc.title) !== normTitle) return false;
                        if (!doc.cover_i) return false;

                        // No author provided → accept on exact title match
                        if (!normAuthor) return true;

                        const docAuthors = (doc.author_name || []).map(normalize);
                        // Author must match (equality or substring, either direction).
                        // Records with no author_name are rejected when an author is given.
                        return docAuthors.some(function (a) {
                            return a === normAuthor
                                || a.includes(normAuthor)
                                || normAuthor.includes(a);
                        });
                    });

                    if (match) {
                        url = 'https://covers.openlibrary.org/b/id/'
                            + encodeURIComponent(match.cover_i)
                            + '-L.jpg?default=false';
                    }
                }
            } catch (error) {
                console.warn('Open Library lookup failed:', error);
            }

            // Cache result, including missing covers
            coverCache.set(key, url);
            return url;
        }

        // ==========================================
        // VIEW BOOK DETAILS
        // ==========================================
        document.querySelectorAll('.view-btn').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const data = this.dataset;

                ['id', 'title', 'author', 'publisher', 'year', 'qty', 'category', 'status']
                    .forEach(function (key) {
                        document.getElementById('v-' + key).textContent = data[key] || '—';
                    });

                // Change status text color
                const status = document.getElementById('v-status');
                status.className = 'fw-bold ' +
                    (data.status?.startsWith('Available') ? 'text-success' : 'text-danger');

                // Reset cover before loading another one
                showCover(null);
                coverBox.classList.add('is-loading');

                // Track latest request to avoid outdated responses
                const request = ++latestRequest;

                const url = await findCover(data.title || '', data.author || '');

                // Only update the currently selected book
                if (request === latestRequest) {
                    showCover(url);
                }
            });
        });

    });