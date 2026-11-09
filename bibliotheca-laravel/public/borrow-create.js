(function () {
    const $ = id => document.getElementById(id);

    const form = $('borrowForm');
    if (!form) return;

    // values passed in from Blade through data- attributes on the form
    const loanDays  = parseInt(form.dataset.loanDays, 10);
    const maxCopies = parseInt(form.dataset.maxCopies, 10);

    const findOpt = (listId, val) =>
        [...document.querySelectorAll('#' + listId + ' option')].find(o => o.value === val);

    const pad = n => String(n).padStart(2, '0');
    const toIso = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const pretty = s => s
        ? new Date(s + 'T00:00:00').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
        : '—';

    function refresh() {
        const m = findOpt('memberList', $('memberInput').value);
        $('memberId').value = m ? m.dataset.id : '';
        $('sumMember').textContent = m ? m.dataset.name : '—';
        $('sumCode').textContent = m ? m.dataset.code : '—';

        const b = findOpt('bookList', $('bookInput').value);
        $('bookId').value = b ? b.dataset.id : '';
        $('sumTitle').textContent = b ? b.dataset.title : '—';
        $('sumAuthor').textContent = b ? b.dataset.author : '';
        $('sumCategory').textContent = b ? b.dataset.category : '';
        $('sumYear').textContent = b ? b.dataset.year : '';

        const avail = b ? Number(b.dataset.avail) : 0;
        const qty = parseInt($('quantity').value, 10) || 0;
        const limit = b ? Math.min(avail, maxCopies) : maxCopies;

        // the number of copies can't go above what's available
        $('quantity').max = Math.max(limit, 1);
        $('sumQty').textContent = qty || '—';

        const box = $('availBox');
        if (!b) {
            box.className = 'alert alert-secondary py-2 mt-2 mb-3';
            box.textContent = 'Select a book to see availability.';
        } else if (avail <= 0) {
            box.className = 'alert alert-danger py-2 mt-2 mb-3';
            box.textContent = 'No copies available';
        } else if (qty > avail) {
            box.className = 'alert alert-warning py-2 mt-2 mb-3';
            box.textContent = 'Only ' + avail + ' copies available';
        } else {
            box.className = 'alert alert-success py-2 mt-2 mb-3';
            box.textContent = avail + ' copies available';
        }

        $('sumBorrow').textContent = pretty($('borrowDate').value);
        $('sumDue').textContent = pretty($('dueDate').value);

        $('confirmBtn').disabled = !(m && b && avail > 0 && qty >= 1 && qty <= limit);
    }

    $('memberInput').addEventListener('input', refresh);
    $('bookInput').addEventListener('input', refresh);
    $('quantity').addEventListener('input', refresh);
    $('dueDate').addEventListener('change', refresh);

    // changing the borrow date moves the due date to borrow date + loan days
    $('borrowDate').addEventListener('change', () => {
        if ($('borrowDate').value) {
            const d = new Date($('borrowDate').value + 'T00:00:00');
            d.setDate(d.getDate() + loanDays);
            $('dueDate').value = toIso(d);
        }
        refresh();
    });

    $('borrowForm').addEventListener('reset', () => setTimeout(() => {
        $('memberId').value = '';
        $('bookId').value = '';
        refresh();
    }, 0));

    refresh();
})();