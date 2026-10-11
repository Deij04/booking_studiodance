import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import './booking-calendar';
import './reservation-calendar';
import './class-reservation';
import './date-inputs';

const bookingAlert = Swal.mixin({
    background: '#171717',
    color: '#ffffff',
    confirmButtonColor: '#a88719',
    cancelButtonColor: '#404040',
    reverseButtons: true,
});

document.querySelectorAll('[data-booking-confirm]').forEach((form) => {
    let busy = false;
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy) return;
        busy = true;
        const accepting = form.elements.decision.value === 'accept';
        const result = await bookingAlert.fire({
            title: accepting ? 'Terima pengajuan booking?' : 'Tolak pengajuan booking?',
            text: `${form.dataset.bookingCode}: ${accepting ? 'Booking akan masuk ke Booking Aktif.' : 'Booking akan dipindahkan ke Riwayat.'}`,
            icon: accepting ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonText: accepting ? 'Ya, Terima' : 'Ya, Tolak',
            cancelButtonText: 'Batal',
            confirmButtonColor: accepting ? '#15803d' : '#dc2626',
            focusCancel: true,
            ...(accepting ? {} : {
                input: 'textarea',
                inputLabel: 'Alasan penolakan',
                inputPlaceholder: 'Tuliskan alasan pengajuan ditolak...',
                inputAttributes: { maxlength: '1000', 'aria-label': 'Alasan penolakan' },
                inputValidator: (value) => !value.trim()
                    ? 'Alasan penolakan wajib diisi.'
                    : value.length > 1000 ? 'Alasan maksimal 1000 karakter.' : undefined,
            }),
        });
        if (result.isConfirmed) {
            if (!accepting) {
                let reason = form.querySelector('[name="rejection_reason"]');
                if (!reason) {
                    reason = document.createElement('input');
                    reason.type = 'hidden';
                    reason.name = 'rejection_reason';
                    form.appendChild(reason);
                }
                reason.value = result.value.trim();
            }
            form.querySelectorAll('button').forEach((button) => { button.disabled = true; });
            HTMLFormElement.prototype.submit.call(form);
        } else {
            busy = false;
        }
    });
});

const notice = document.querySelector('[data-booking-notice]');
if (notice) {
    notice.hidden = true;
    bookingAlert.fire({
        title: notice.dataset.bookingNotice === 'success' ? 'Berhasil' : 'Tidak dapat memproses booking',
        text: notice.textContent.trim(),
        icon: notice.dataset.bookingNotice,
        confirmButtonText: 'Mengerti',
    });
}

 document.querySelectorAll('[data-booking-cancel]').forEach(form => {
    let approved = false;
    form.addEventListener('submit', async event => {
        if (approved) return;
        event.preventDefault();
        const result = await bookingAlert.fire({ title: 'Batalkan booking?', text: 'Booking akan masuk Riwayat dan jadwal dibuka kembali. Pembayaran yang terverifikasi tetap tercatat. Pembatalan tidak dapat diaktifkan kembali.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Batalkan Booking', cancelButtonText: 'Kembali', confirmButtonColor: '#dc2626', focusCancel: true });
        if (result.isConfirmed) { approved = true; form.requestSubmit(); }
    });
});

document.querySelectorAll('[data-class-payment-reject]').forEach(form => {
    let busy = false;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy) return;
        busy = true;
        const result = await bookingAlert.fire({
            title: 'Tolak pengajuan reservasi class?', input: 'textarea', inputLabel: 'Alasan penolakan',
            inputPlaceholder: 'Tuliskan alasan penolakan...', inputValue: form.elements.rejection_reason.value,
            inputAttributes: { maxlength: '1000', 'aria-label': 'Alasan penolakan pembayaran class' },
            showCancelButton: true, confirmButtonText: 'Tolak Pengajuan', cancelButtonText: 'Kembali',
            confirmButtonColor: '#dc2626', focusCancel: true,
            inputValidator: value => !value.trim() ? 'Alasan penolakan wajib diisi.' : value.length > 1000 ? 'Alasan maksimal 1000 karakter.' : undefined,
        });
        if (result.isConfirmed) {
            form.elements.rejection_reason.value = result.value.trim();
            form.querySelector('button').disabled = true;
            HTMLFormElement.prototype.submit.call(form);
        } else busy = false;
    });
});

const approvedSaves = new WeakSet();
const pendingSaves = new WeakSet();
document.addEventListener('submit', async event => {
    const form = event.target;
    const button = event.submitter ?? form.querySelector('[data-save-confirm]');
    if (!button?.hasAttribute('data-save-confirm') || event.defaultPrevented) return;
    if (approvedSaves.has(form)) {
        approvedSaves.delete(form);
        return;
    }
    event.preventDefault();
    if (pendingSaves.has(form)) return;
    pendingSaves.add(form);
    try {
        const result = await bookingAlert.fire({
            title: 'Simpan data?',
            text: 'Pastikan data yang Anda isi sudah benar sebelum disimpan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Periksa Kembali',
            focusCancel: true,
        });
        if (result.isConfirmed && button.isConnected && !button.disabled) {
            approvedSaves.add(form);
            form.requestSubmit(button);
            approvedSaves.delete(form);
        }
    } finally {
        pendingSaves.delete(form);
    }
});
