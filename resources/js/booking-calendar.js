import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import 'flatpickr/dist/flatpickr.min.css';
import 'flatpickr/dist/themes/dark.css';

const form = document.querySelector('[data-booking-calendar]');
if (form) {
    const studio = form.querySelector('#studio_id');
    const date = form.querySelector('#date');
    const start = form.querySelector('#start_time');
    const end = form.querySelector('#end_time');
    const status = form.querySelector('#availability-status');
    const retry = form.querySelector('#availability-retry');
    const submit = form.querySelector('[type="submit"]');
    const initiallyDisabled = submit.disabled;
    const oldDate = date.value;
    let days = {};
    let sequence = 0;
    let loading = false;
    let restore = true;
    const key = (year, month) => `${year}-${String(month + 1).padStart(2, '0')}`;
    const options = (select, values, placeholder, selected = '') => {
        select.replaceChildren(new Option(placeholder, ''));
        values.forEach((value) => select.add(new Option(value, value)));
        select.value = values.includes(selected) ? selected : '';
        select.disabled = values.length === 0;
    };
    const updateSubmit = () => { submit.disabled = initiallyDisabled || loading || !date.value || !start.value || !end.value; };
    const updateEnd = (selected = '') => {
        options(end, days[date.value]?.[start.value] ?? [], 'Pilih jam selesai', selected);
        end.dispatchEvent(new Event('change'));
        updateSubmit();
    };
    const updateTimes = () => {
        options(start, Object.keys(days[date.value] ?? {}), 'Pilih jam mulai', restore ? start.dataset.old : '');
        updateEnd(restore ? end.dataset.old : '');
        if (date.value) status.textContent = 'Pilih jam yang tersedia. Durasi tidak dapat melewati jam yang sudah terisi.';
    };
    const picker = flatpickr(date, {
        locale: Indonesian, dateFormat: 'Y-m-d', altInput: true, altFormat: 'd F Y',
        minDate: form.dataset.today, disableMobile: true, allowInput: false,
        disable: [() => true],
        onChange: () => { restore = false; updateTimes(); },
        onMonthChange: () => load(), onYearChange: () => load(),
        onOpen: () => load(),
    });
    picker.altInput.disabled = !studio.value;
    async function load() {
        const requestId = ++sequence;
        const selectedDate = restore ? oldDate : date.value;
        loading = true;
        retry.hidden = true;
        days = {};
        picker.set('disable', [() => true]);
        options(start, [], 'Pilih tanggal terlebih dahulu');
        updateEnd();
        status.textContent = 'Memuat ketersediaan...';
        if (!studio.value) {
            loading = false;
            status.textContent = 'Pilih studio untuk melihat jadwal tersedia.';
            updateSubmit();
            return;
        }
        try {
            const response = await fetch(`${form.dataset.availabilityUrl}/${studio.value}?month=${key(picker.currentYear, picker.currentMonth)}`, {
                headers: { Accept: 'application/json' }, cache: 'no-store',
            });
            if (!response.ok) throw new Error('availability');
            const data = await response.json();
            if (requestId !== sequence) return;
            days = data.days;
            picker.set('disable', [(day) => !Object.keys(days[flatpickr.formatDate(day, 'Y-m-d')] ?? {}).length]);
            const selected = selectedDate;
            if (selected && Object.keys(days[selected] ?? {}).length) picker.setDate(selected, false);
            else picker.clear(false);
            status.textContent = 'Tanggal penuh dinonaktifkan. Pilih tanggal untuk melihat jam tersedia.';
            updateTimes();
            restore = false;
        } catch {
            if (requestId !== sequence) return;
            picker.clear(false);
            status.textContent = 'Ketersediaan gagal dimuat. Coba lagi sebelum membuat booking.';
            retry.hidden = false;
        } finally {
            if (requestId === sequence) { loading = false; updateSubmit(); }
        }
    }
    studio.addEventListener('change', () => {
        restore = false;
        picker.clear(false);
        picker.altInput.disabled = !studio.value;
        load();
    });
    start.addEventListener('change', () => updateEnd());
    end.addEventListener('change', updateSubmit);
    retry.addEventListener('click', () => load());
    form.addEventListener('submit', (event) => { if (submit.disabled) event.preventDefault(); });
    if (oldDate) picker.jumpToDate(oldDate);
    load();
}
