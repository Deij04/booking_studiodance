import '../css/reservation-calendar.css';
const form = document.querySelector('[data-reservation-calendar]');
if (form) {
    const find = (selector) => form.querySelector(selector);
    const studio = find('#studio_id');
    const date = find('#reservation-date');
    const start = find('#reservation-start');
    const end = find('#reservation-end');
    const hours = find('#reservation-hours');
    const grid = find('#reservation-days');
    const times = find('#reservation-times');
    const status = find('#reservation-status');
    const next = find('[data-next]');
    const submit = find('[data-submit]');
    const details = find('#reservation-details');
    const payment = find('#reservation-payment');
    const heading = document.querySelector('.reservation-heading');
    const showStep = (step) => {
        schedule.hidden = step !== 'schedule'; details.hidden = step !== 'details'; payment.hidden = step !== 'payment';
        find('[data-schedule-back]').hidden = step !== 'schedule'; find('[data-schedule-studio]').hidden = step !== 'schedule';
        heading.querySelector('h1').textContent = step === 'schedule' ? 'Availability Calendar' : step === 'details' ? 'Detail Booking' : 'Payment';
        heading.querySelector('p').textContent = step === 'schedule' ? 'Real-time scheduling across all studio spaces.' : step === 'details' ? 'Complete your booking information to help us prepare the perfect studio experience for your session.' : 'Review your booking and payment information.';
    };
    const schedule = find('.reservation-schedule');
    const retry = find('[data-retry]');
    const today = form.dataset.today;
    const initial = { date: date.value, start: start.value, end: end.value };
    const validDate = (value) => /^\d{4}-\d{2}-\d{2}$/.test(value) && !Number.isNaN(new Date(`${value}T12:00:00`).getTime());
    const base = validDate(date.value) ? date.value : today;
    let month = new Date(`${base.slice(0, 7)}-01T12:00:00`);
    let days = {};
    let occupied = [];
    let loading = false;
    let sequence = 0;
    let restore = true;
    let resumePayment = form.dataset.resumePayment === '1';
    let submitting = false;
    const minimum = Number(form.dataset.minimum);
    const step = Number(form.dataset.step);
    const minutes = (value) => { const [h, m] = value.split(':').map(Number); return h * 60 + m; };
    const key = (value) => `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
    const duration = () => Number(hours.value) * 60;
    const validDuration = () => Number.isFinite(duration()) && duration() >= minimum && Number.isInteger((duration() - minimum) / step);
    const endFor = (value) => {
        const total = minutes(value) + duration();
        return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
    };
    const availableStarts = () => validDuration() ? Object.keys(days[date.value] ?? {}).filter(value => days[date.value][value].includes(endFor(value))) : [];
    const text = (tag, value, className) => { const element = document.createElement(tag); element.textContent = value; if (className) element.className = className; return element; };
    const resetDetails = () => showStep('schedule');
    function sync() {
        const valid = !loading && availableStarts().includes(start.value);
        end.value = valid ? endFor(start.value) : '';
        next.disabled = !valid;
        submit.disabled = !valid;
        find('#duration-help').textContent = !validDuration() ? `Minimum ${minimum / 60} hour(s), in ${step / 60} hour increments.` : 'Choose an available start time and duration.';
        if (valid) {
            const formattedDate = new Date(`${date.value}T12:00:00`).toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            const clock = value => new Date(`2000-01-01T${value}:00`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
            find('#info-studio').textContent = studio.dataset.name;
            find('#info-date').textContent = `Date: ${formattedDate}`;
            find('#info-time').textContent = `Time: ${clock(start.value)} - ${clock(end.value)}`;
            find('#info-duration').textContent = `Duration: ${hours.value} ${Number(hours.value) === 1 ? 'hour' : 'hours'}`;
            find('#reservation-summary').textContent = `${studio.dataset.name} | ${formattedDate} | ${start.value} - ${end.value}`;
            find('#reservation-price').textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(studio.dataset.price) * duration() / 60);
        }
    }
    function renderTimes() {
        times.replaceChildren();
        find('#selected-date-title').textContent = date.value ? new Date(`${date.value}T12:00:00`).toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'short' }) : 'Choose a date';
        const starts = availableStarts();
        if (!starts.includes(start.value)) start.value = '';
        starts.forEach(value => {
            const button = text('button', value, start.value === value ? 'selected' : '');
            button.type = 'button'; button.setAttribute('aria-pressed', String(start.value === value));
            button.addEventListener('click', () => { start.value = value; renderTimes(); });
            times.append(button);
        });
        if (!starts.length) times.append(text('p', loading ? 'Loading...' : !studio.value ? 'Choose a studio first.' : !date.value ? 'Select an available date on the calendar.' : 'No available times for this duration. Try a shorter session or another date.'));
        sync();
    }
    function renderCalendar() {
        find('#calendar-month-title').textContent = month.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
        find('[data-month-prev]').disabled = month.getFullYear() * 12 + month.getMonth() <= Number(today.slice(0, 4)) * 12 + Number(today.slice(5, 7)) - 1;
        grid.replaceChildren();
        const count = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
        const cells = Math.ceil((month.getDay() + count) / 7) * 7;
        for (let index = 0; index < cells; index++) {
            const day = new Date(month.getFullYear(), month.getMonth(), index - month.getDay() + 1, 12);
            const value = key(day);
            const outside = day.getMonth() !== month.getMonth();
            const slots = Object.keys(days[value] ?? {});
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `calendar-day${outside ? ' outside' : ''}${value < today ? ' past' : ''}${!outside && slots.length ? ' available' : ''}${value === date.value ? ' selected' : ''}`;
            button.disabled = outside || loading || !slots.length;
            button.setAttribute('aria-pressed', String(value === date.value));
            button.setAttribute('aria-label', `${day.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}, ${slots.length ? 'available' : 'unavailable'}`);
            button.append(text('span', day.getDate(), 'day-number'));
            const bookings = outside ? [] : occupied.filter(slot => slot.date === value);
            bookings.slice(0, 2).forEach(slot => { const badge = text('span', slot.label, 'booking-tag'); badge.title = `${slot.label}: ${slot.start_time}-${slot.end_time}`; button.append(badge); });
            if (bookings.length > 2) button.append(text('span', `+${bookings.length - 2} more`, 'booking-more'));
            button.addEventListener('click', () => { date.value = value; start.value = ''; renderCalendar(); renderTimes(); });
            grid.append(button);
        }
    }
    async function load() {
        const id = ++sequence;
        loading = true; days = {}; occupied = []; retry.hidden = true; resetDetails();
        status.textContent = studio.value ? 'Loading availability...' : 'Choose a studio to see available dates.';
        renderCalendar(); renderTimes();
        if (!studio.value) { loading = false; sync(); return; }
        try {
            const response = await fetch(`${form.dataset.url}/${encodeURIComponent(studio.value)}?month=${key(month).slice(0, 7)}`, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) throw new Error('Availability failed');
            const data = await response.json();
            if (id !== sequence) return;
            days = data.days; occupied = data.occupied ?? [];
            if (restore && initial.date.slice(0, 7) === key(month).slice(0, 7)) {
                date.value = initial.date; start.value = initial.start;
                const oldDuration = minutes(initial.end) - minutes(initial.start);
                if (Number.isFinite(oldDuration) && oldDuration >= minimum) hours.value = oldDuration / 60;
            }
            restore = false;
            if (!Object.keys(days[date.value] ?? {}).length) { date.value = ''; start.value = ''; }
            status.textContent = 'Select a date to see available times. Booked times cannot be selected.';
        } catch {
            if (id !== sequence) return;
            date.value = ''; start.value = ''; status.textContent = 'Unable to load availability. Please try again.'; retry.hidden = false;
        } finally {
            if (id === sequence) {
                loading = false; renderCalendar(); renderTimes();
                if (resumePayment && !next.disabled) { resumePayment = false; renderPayment(); showStep('payment'); }
            }
        }
    }
    studio.addEventListener('change', () => { restore = false; date.value = ''; start.value = ''; load(); });
    for (const [selector, amount] of [['[data-month-prev]', -1], ['[data-month-next]', 1]]) find(selector).addEventListener('click', () => { month = new Date(month.getFullYear(), month.getMonth() + amount, 1, 12); date.value = ''; start.value = ''; restore = false; load(); });
    hours.addEventListener('input', renderTimes);
    for (const [selector, amount] of [['[data-duration-up]', step], ['[data-duration-down]', -step]]) find(selector).addEventListener('click', () => { hours.value = Math.max(minimum, (Number.isFinite(duration()) ? duration() : minimum) + amount) / 60; renderTimes(); });
    next.addEventListener('click', () => { sync(); if (next.disabled) return; showStep('details'); heading.scrollIntoView({ behavior: 'smooth', block: 'start' }); find('#booking_name').focus({ preventScroll: true }); });
    find('[data-back]').addEventListener('click', () => { showStep('schedule'); next.focus(); });
    function renderPayment() {
        find('#payment-type-summary').textContent = find('#payment_type').selectedOptions[0].textContent;
        const method = find('#payment_method');
        find('#payment-method-summary').textContent = method.selectedOptions[0].textContent;
        const money = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(studio.dataset.price) * duration() / 60);
        find('#payment-studio-total').textContent = money;
        find('#payment-rental-total').textContent = money;
        const total = Number(studio.dataset.price) * duration() / 60;
        const due = Math.round(total * (find('#payment_type').value === 'dp' ? 0.5 : 1) * 100) / 100;
        const currency = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(value);
        find('#payment-due-label').textContent = find('#payment_type').value === 'dp' ? 'Bayar DP (50%)' : 'Bayar Lunas';
        find('#payment-due').textContent = currency(due);
        find('#payment-remaining').textContent = currency(Math.round((total - due) * 100) / 100);
        find('#payment-rental-label').textContent = `Studio Rental (${hours.value}h)`;
        find('#payment-date').textContent = new Date(`${date.value}T12:00:00`).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
        find('#payment-time').textContent = `${start.value} - ${end.value} (${hours.value}h)`;
        const image = find('#payment-method-image');
        find('[data-qris-panel]').hidden = method.value !== 'qris';
        find('[data-bank-panel]').hidden = method.value !== 'bank_transfer';
        image.src = image.dataset.qrisSrc;
        image.alt = 'Gambar QRIS pembayaran';
        const placeholder = image.src.endsWith('/images/logoorange.png');
        find('#payment-image-caption').textContent = placeholder ? 'Logo sementara - QRIS belum tersedia' : 'QRIS';
        const download = find('#payment-image-download');
        download.href = image.src;
        download.hidden = method.value !== 'qris' || placeholder;
    }
    find('[data-details-next]').addEventListener('click', () => {
        sync(); if (next.disabled) { showStep('schedule'); return; }
        for (const input of details.querySelectorAll('input, select, textarea')) if (!input.reportValidity()) return;
        renderPayment();
        showStep('payment'); find('#payment-title').focus(); heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    find('[data-payment-back]').addEventListener('click', () => { showStep('details'); find('[data-details-next]').focus(); });
    retry.addEventListener('click', load);
    const errors = find('[data-reservation-errors]');
    function showErrors(messages) {
        errors.replaceChildren(...messages.map(message => text('p', message)));
        errors.hidden = false;
        errors.focus();
        errors.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submitting) return;
        sync();
        if (submit.disabled) { showErrors(['Jadwal tidak tersedia. Gunakan Back untuk memilih ulang jadwal.']); return; }
        const proof = find('#payment_proof').files[0];
        if (!proof) { showErrors(['Bukti pembayaran wajib diunggah.']); return; }
        if (proof && proof.size > 2048 * 1024) {
            showErrors(['Ukuran bukti pembayaran maksimal 2 MB. Pilih file yang lebih kecil.']);
            return;
        }
        submitting = true;
        submit.disabled = true;
        submit.textContent = 'Submitting...';
        errors.hidden = true;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
            if (response.redirected && response.ok) { window.location.assign(response.url); return; }
            if (response.status === 422) {
                const data = await response.json();
                showErrors(Object.values(data.errors ?? {}).flat());
            } else {
                showErrors([response.status === 419 ? 'Sesi berakhir. Muat ulang halaman sebelum mengajukan reservasi.' : 'Reservasi belum berhasil diproses. Silakan coba lagi.']);
            }
        } catch {
            showErrors(['Koneksi bermasalah. Periksa koneksi lalu coba kembali.']);
        } finally {
            submitting = false;
            submit.textContent = 'Ajukan Reservasi';
            sync();
        }
    });
    load();
}
