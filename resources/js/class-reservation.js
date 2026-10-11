const form = document.querySelector('[data-class-reservation]');
if (form) {
    const find = selector => form.querySelector(selector);
    const session = () => find('[name="class_schedule_id"]:checked');
    const method = find('#class-payment-method');
    const type = find('#class-payment-type');
    const errors = find('[data-class-errors]');
    const submit = find('[data-class-submit]');
    let busy = false;
    function show(step) {
        form.querySelectorAll('[data-class-step]').forEach(panel => { panel.hidden = panel.dataset.classStep !== step; });
        const steps = ['sessions', 'details', 'payment'];
        form.querySelectorAll('[data-progress]').forEach(item => {
            item.classList.toggle('current', item.dataset.progress === step);
            const done = steps.indexOf(item.dataset.progress) < steps.indexOf(step);
            item.classList.toggle('done', done);
            item.querySelector('span').textContent = done ? '\u2713' : String(steps.indexOf(item.dataset.progress) + 1);
            if (item.dataset.progress === step) item.setAttribute('aria-current', 'step'); else item.removeAttribute('aria-current');
        });
        form.querySelector('.class-booking-progress').style.setProperty('--class-progress', `${steps.indexOf(step) * 33}%`);
        document.querySelector('[data-class-heading]').textContent = step === 'sessions' ? 'Choose Your Session' : step === 'details' ? 'Detail Class Booking' : 'Payment';
        document.querySelector('.reservation-heading').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function sync() {
        const selected = session();
        find('[data-class-next="details"]').disabled = !selected;
        for (const field of ['date', 'time', 'studio']) form.querySelectorAll(`[data-class-${field}]`).forEach(element => { element.textContent = selected?.dataset[field] ?? ''; });
        find('[data-class-method-label]').textContent = method.selectedOptions[0].textContent;
        find('[data-class-type-label]').textContent = 'Lunas (100%)';
        const total = Number(form.dataset.price);
        const due = total;
        const currency = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(value);
        find('[data-class-due-label]').textContent = 'Bayar Lunas';
        find('[data-class-due]').textContent = currency(due);
        find('[data-class-qris]').hidden = method.value !== 'qris';
        find('[data-class-bank]').hidden = method.value !== 'bank_transfer';
    }
    function showErrors(messages) {
        errors.replaceChildren(...messages.map(message => { const p = document.createElement('p'); p.textContent = message; return p; }));
        errors.hidden = false; errors.focus(); errors.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    form.querySelectorAll('[name="class_schedule_id"]').forEach(input => input.addEventListener('change', sync));
    form.querySelectorAll('[data-class-next]').forEach(button => button.addEventListener('click', () => {
        if (!session()) return;
        if (button.dataset.classNext === 'payment') {
            for (const input of form.querySelector('[data-class-step="details"]').querySelectorAll('select, textarea')) if (!input.reportValidity()) return;
        }
        sync(); show(button.dataset.classNext);
    }));
    form.querySelectorAll('[data-class-back]').forEach(button => button.addEventListener('click', () => show(button.dataset.classBack)));
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy) return;
        if (!session()) { showErrors(['Pilih sesi kelas yang tersedia.']); show('sessions'); return; }
        const proof = find('#class-payment-proof').files[0];
        if (!proof) { showErrors(['Bukti pembayaran wajib diunggah.']); return; }
        if (proof && proof.size > 2048 * 1024) { showErrors(['Ukuran bukti pembayaran maksimal 2 MB.']); return; }
        busy = true; submit.disabled = true; submit.textContent = 'Submitting...'; errors.hidden = true;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
            if (response.redirected && response.ok) { window.location.assign(response.url); return; }
            if (response.status === 422) { const data = await response.json(); showErrors(Object.values(data.errors ?? {}).flat()); }
            else showErrors([response.status === 419 ? 'Sesi berakhir. Muat ulang halaman.' : 'Reservasi belum berhasil diproses. Silakan coba lagi.']);
        } catch { showErrors(['Koneksi bermasalah. Silakan coba kembali.']); }
        finally { busy = false; submit.disabled = false; submit.textContent = 'Ajukan Reservasi Class'; }
    });
    sync();
    if (form.dataset.resume === 'payment' && session()) show('payment');
}
