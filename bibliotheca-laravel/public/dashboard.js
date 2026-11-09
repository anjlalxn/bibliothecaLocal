document.addEventListener('DOMContentLoaded', () => {
    const coverCache = new Map();

    async function findCover(title, author) {
        const key = title + '|' + author;
        if (coverCache.has(key)) return coverCache.get(key);

        let url = null;
        const attempts = author ? [{ title, author }, { title }] : [{ title }];

        for (const q of attempts) {
            try {
                const params = new URLSearchParams({ ...q, limit: '1', fields: 'cover_i' });
                const res = await fetch('https://openlibrary.org/search.json?' + params);
                if (!res.ok) continue;
                const data = await res.json();
                const coverId = data.docs && data.docs[0] && data.docs[0].cover_i;
                if (coverId) {
                    url = 'https://covers.openlibrary.org/b/id/' + coverId + '-M.jpg?default=false';
                    break;
                }
            } catch (e) { /* Ignore network errors */ }
        }

        coverCache.set(key, url);
        return url;
    }

    document.querySelectorAll('.book-day-card').forEach(async (card) => {
        const title = card.dataset.title;
        const author = card.dataset.author;
        const img = card.querySelector('.book-cover-img');
        const fallback = card.querySelector('.book-cover-fallback');

        const url = await findCover(title, author);
        if (url) {
            img.src = url;
            // Switch visibility only after image successfully loads
            img.onload = () => {
                img.style.display = 'block';
                fallback.style.display = 'none';
            };
        }
    });
});

  // Put the cursor in the search box as soon as the modal opens
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('transactionSearchModal');
        if (!modal) return;

        modal.addEventListener('shown.bs.modal', () => {
            document.getElementById('transaction_search').focus();
        });
    });