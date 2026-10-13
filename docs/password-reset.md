# Pengiriman email reset password

Fitur lupa password tersedia di `/forgot-password` dan tautan pada halaman login.
Tautan reset berlaku 60 menit dan hanya dapat dipakai sekali.

Pada konfigurasi lokal saat ini, `MAIL_MAILER=log`: email beserta tautan reset
ditulis ke `storage/logs/laravel.log`. Untuk mencoba, minta reset melalui halaman
lupa password lalu buka tautan dari log terbaru.

Untuk mengirim email ke kotak masuk, isi `.env` dengan kredensial penyedia SMTP:

```dotenv
APP_URL=https://alamat-website-anda
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=host-smtp-anda
MAIL_PORT=587
MAIL_USERNAME=username-smtp-anda
MAIL_PASSWORD=password-smtp-anda
MAIL_FROM_ADDRESS=alamat-pengirim@domain-anda
MAIL_FROM_NAME="Orange Dance Academy"
```

Sesuaikan port dan skema dengan penyedia (contoh: `smtps` untuk port 465).
Jalankan `php artisan config:clear` setelah memperbarui konfigurasi.
Pastikan `APP_URL` menunjuk alamat yang bisa dibuka pengguna.
Jangan masukkan kredensial SMTP ke Git.
