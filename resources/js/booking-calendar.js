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
    let hours = { opens_at: '08:00', closes_at: '22:00' };
    let step = 30;
    let minimum = 60;
    const minutes = value => { const [h, m] = value.split(':').map(Number); return h * 60 + m; };
    const clock = value => `${String(Math.floor(value / 60)).padStart(2, '0')}:${String(value % 60).padStart(2, '0')}`;
    const slotPanel = document.createElement('div');
    slotPanel.className = 'mt-4 flex flex-wrap gap-2';
    slotPanel.setAttribute('aria-label', 'Jam mulai: merah tidak tersedia');
    status.after(slotPanel);
    const showSlots = () => {
        slotPanel.replaceChildren();
        if (!date.value) return;
        for (let time = minutes(hours.opens_at); time + minimum <= minutes(hours.closes_at); time += step) {
            const value = clock(time);
            const available = Boolean(days[date.value]?.[value]?.length);
            const button = document.createElement('button');
            button.type = 'button'; button.textContent = value; button.disabled = !available;
            button.title = available ? 'Pilih jam mulai' : 'Terisi atau tidak tersedia untuk minimal 1 jam';
            button.className = available ? 'rounded-lg border border-[#f2c83f]/30 px-3 py-2 text-sm text-[#f2c83f]' : 'rounded-lg border border-red-400/20 bg-red-400/10 px-3 py-2 text-sm text-red-300';
            button.setAttribute('aria-pressed', String(start.value === value));
            button.addEventListener('click', () => { start.value = value; start.dispatchEvent(new Event('change')); showSlots(); });
            slotPanel.append(button);
        }
    };
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
        const selectedStart = restore ? start.dataset.old : start.value;
        const selectedEnd = restore ? end.dataset.old : end.value;
        options(start, Object.keys(days[date.value] ?? {}), 'Pilih jam mulai', selectedStart);
        updateEnd(selectedEnd);
        showSlots();
        if (date.value) status.textContent = 'Pilih jam tersedia. Merah: terisi atau tidak tersedia. Minimal 1 jam, waktu WITA.';
    };
    const picker = flatpickr(date, {
        locale: Indonesian, dateFormat: 'Y-m-d', altInput: true, altFormat: 'd F Y',
        minDate: form.dataset.today, disableMobile: true, allowInput: false,
        disable: [() => true],
        onChange: () => { restore = false; load(); },
        onMonthChange: () => load(), onYearChange: () => load(),
        onOpen: () => load(),
    });
    picker.altInput.disabled = !studio.value;
    async function load() {
        const requestId = ++sequence;
        const selectedDate = restore ? oldDate : date.value;
        const selectedStart = restore ? start.dataset.old : start.value;
        const selectedEnd = restore ? end.dataset.old : end.value;
        loading = true;
        retry.hidden = true;
        days = {};
        slotPanel.replaceChildren();
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
            const response = await fetch(`${form.dataset.availabilityUrl}/${studio.value}?month=${key(picker.currentYear, picker.currentMonth)}${form.dataset.bookingId ? `&booking_id=${form.dataset.bookingId}` : ''}`, {
                headers: { Accept: 'application/json' }, cache: 'no-store',
            });
            if (!response.ok) throw new Error('availability');
            const data = await response.json();
            if (requestId !== sequence) return;
            days = data.days;
            hours = data.hours ?? hours;
            step = data.step ?? step;
            minimum = data.minimum ?? minimum;
            picker.set('disable', [(day) => !Object.keys(days[flatpickr.formatDate(day, 'Y-m-d')] ?? {}).length]);
            const selected = selectedDate;
            if (selected && Object.keys(days[selected] ?? {}).length) picker.setDate(selected, false);
            else picker.clear(false);
            status.textContent = 'Tanggal penuh dinonaktifkan. Pilih tanggal untuk melihat jam tersedia.';
            options(start, Object.keys(days[date.value] ?? {}), 'Pilih jam mulai', selectedStart);
            updateEnd(selectedEnd);
            showSlots();
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
    window.addEventListener('focus', () => { if (studio.value && !loading) load(); });
    setInterval(() => { if (!document.hidden && studio.value && date.value && !loading) load(); }, 30000);
    if (oldDate) picker.jumpToDate(oldDate);
    load();
}
