document.addEventListener('DOMContentLoaded', async () => {
    const box = document.getElementById('book-cover-box');
    if (!box) return;

    const title = box.dataset.title;
    const author = box.dataset.author;
    const img = document.getElementById('book-cover-img');
    const fallback = document.getElementById('book-cover-fallback');

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

    const normTitle = normalize(title);
    const normAuthor = normalize(author);
    if (!normTitle) return;

    let coverUrl = null;

    try {
        // Ask for several candidates instead of just one
        const params = new URLSearchParams({
            title,
            limit: '10',
            fields: 'title,author_name,cover_i'
        });
        if (author) params.set('author', author);

        const res = await fetch('https://openlibrary.org/search.json?' + params);
        if (res.ok) {
            const data = await res.json();
            const docs = (data && data.docs) || [];

            // Exact (normalized) title match + author match required.
            // Title-only fallback removed — it is what caused wrong covers.
            const match = docs.find(doc => {
                if (normalize(doc.title) !== normTitle) return false;
                if (!doc.cover_i) return false;

                // No author provided → accept on exact title match
                if (!normAuthor) return true;

                const docAuthors = (doc.author_name || []).map(normalize);
                // Author must match (equality or substring, either direction).
                // Records with no author_name are rejected when an author is given.
                return docAuthors.some(a =>
                    a === normAuthor ||
                    a.includes(normAuthor) ||
                    normAuthor.includes(a)
                );
            });

            if (match) {
                coverUrl = 'https://covers.openlibrary.org/b/id/'
                    + encodeURIComponent(match.cover_i)
                    + '-L.jpg?default=false';
            }
        }
    } catch (e) { /* network error → keep the icon */ }

    if (coverUrl) {
        // Swap only after the image has actually loaded
        img.onload = () => {
            img.style.display = 'inline-block';
            fallback.style.display = 'none';
        };
        img.onerror = () => {
            img.style.display = 'none';
            fallback.style.display = '';
        };
        img.src = coverUrl;
    }
});