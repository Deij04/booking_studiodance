<div class="sm:col-span-2">
    <label for="payment_proof" class="text-sm text-white/70">Bukti transfer</label>
    @if(isset($booking) && $booking->payment_proof)
        <div class="my-3">
            <a href="{{ route('admin.bookings.payment-proof', $booking) }}" target="_blank" rel="noopener" class="text-sm text-[#f2c83f] hover:underline">Lihat bukti transfer tersimpan &rarr;</a>
            @if(in_array(strtolower(pathinfo($booking->payment_proof, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp']))
                <img src="{{ route('admin.bookings.payment-proof', $booking) }}" alt="Bukti transfer booking {{ $booking->booking_code }}" class="mt-3 max-h-64 max-w-full rounded-xl border border-white/10 object-contain" loading="lazy">
            @endif
        </div>
    @endif
    <input id="payment_proof" type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp,application/pdf" class="{{ $inputClass }} file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-[#f2c83f] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-black hover:file:bg-[#ffda58]">
    <p class="mt-2 text-xs text-white/45">Opsional. JPG, PNG, WEBP, atau PDF, maksimal 2 MB. Upload bukti tidak otomatis mengubah pembayaran menjadi lunas.{{ isset($booking) && $booking->payment_proof ? ' Pilih file baru untuk mengganti bukti tersimpan.' : '' }}</p>
    @error('payment_proof')<p class="mt-2 text-xs text-red-300">{{ $message }}</p>@enderror
</div>
