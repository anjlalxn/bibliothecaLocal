    document.addEventListener('DOMContentLoaded', function () {
        const coverCache = new Map();

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

        // Exact (normalized) title match + author match required.
        // Returns a cover URL only if a candidate passes BOTH checks.
        async function findCover(title, author) {
            const normTitle  = normalize(title);
            const normAuthor = normalize(author);
            if (!normTitle) return null;

            const key = normTitle + '|' + normAuthor;
            if (coverCache.has(key)) return coverCache.get(key);

            let url = null;

            try {
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
                        url = 'https://covers.openlibrary.org/b/id/' + match.cover_i + '-M.jpg?default=false';
                    }
                }
            } catch (e) { /* network error → fall back to icon */ }

            coverCache.set(key, url);
            return url;
        }

        document.querySelectorAll('.category-book-card').forEach(async function (card) {
            const title = card.dataset.title;
            if (!title) return;

            const img = card.querySelector('.book-cover-img');
            const fallback = card.querySelector('.book-cover-fallback');

            const url = await findCover(title, card.dataset.author || '');

            if (url) {
                // Swap only after the image has actually loaded
                img.onload = function () {
                    img.style.display = 'block';
                    fallback.style.display = 'none';
                };
                img.src = url;
            }
        });
    });