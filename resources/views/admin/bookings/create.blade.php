@extends('layout.dashboard')
@section('title', 'Tambah Booking')
@section('content')
    <div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-7 lg:p-10">
        <div><h2 class="text-2xl font-bold">Tambah Booking</h2><p class="mt-2 text-sm text-white/50">Buat booking untuk pelanggan. Booking langsung dikonfirmasi dan masuk ke Booking Aktif.</p></div>
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.bookings.store') }}" class="rounded-2xl border border-white/10 bg-[#171717] p-6">
            @csrf
            @if($errors->any())<div role="alert" class="mb-6 rounded-xl bg-red-400/10 p-4 text-sm text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            @if($customers->isEmpty() || $studios->isEmpty())<p class="mb-6 text-sm text-amber-300">Tambahkan pelanggan aktif dan studio aktif terlebih dahulu sebelum membuat booking.</p>@endif
            @php($inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm outline-none focus:border-[#f2c83f]')
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="user_id" class="text-sm text-white/70">Pelanggan</label><select id="user_id" name="user_id" required class="{{ $inputClass }}"><option value="">Pilih pelanggan</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(old('user_id') == $customer->id)>{{ $customer->name }} — {{ $customer->email }}</option>@endforeach</select></div>
                <div><label for="studio_id" class="text-sm text-white/70">Studio</label><select id="studio_id" name="studio_id" required class="{{ $inputClass }}"><option value="">Pilih studio</option>@foreach($studios as $studio)<option value="{{ $studio->id }}" data-price="{{ $studio->price_per_hour }}" @selected(old('studio_id') == $studio->id)>{{ $studio->name }} — Rp {{ number_format($studio->price_per_hour, 0, ',', '.') }}/jam</option>@endforeach</select></div>
                <div><label for="date" class="text-sm text-white/70">Tanggal penggunaan</label><input id="date" type="date" name="date" min="{{ today()->toDateString() }}" value="{{ old('date') }}" required class="{{ $inputClass }} [color-scheme:dark]"></div>
                <div><label for="payment_status" class="text-sm text-white/70">Pembayaran</label><select id="payment_status" name="payment_status" required class="{{ $inputClass }}">@foreach(\App\Models\Reservation::PAYMENT_STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('payment_status', 'unpaid') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label for="start_time" class="text-sm text-white/70">Jam mulai</label><input type="time" id="start_time" name="start_time" value="{{ old('start_time') }}" required class="{{ $inputClass }} [color-scheme:dark]"></div>
                <div><label for="end_time" class="text-sm text-white/70">Jam selesai</label><input type="time" id="end_time" name="end_time" value="{{ old('end_time') }}" required class="{{ $inputClass }} [color-scheme:dark]"><p class="mt-2 text-xs text-white/45">Jam selesai harus setelah jam mulai pada hari yang sama.</p></div>
                <div class="sm:col-span-2 rounded-xl bg-[#f2c83f]/10 p-4"><p class="text-xs text-white/60">Total biaya (tarif per jam × durasi)</p><p id="booking-price" aria-live="polite" class="mt-2 text-xl font-semibold text-[#f2c83f]">Pilih studio dan jam penggunaan</p></div>
                <div class="sm:col-span-2"><label for="purpose" class="text-sm text-white/70">Keperluan</label><input id="purpose" name="purpose" value="{{ old('purpose') }}" maxlength="255" class="{{ $inputClass }}"></div>
                @include('admin.bookings.payment-proof')
                <div class="sm:col-span-2"><label for="notes" class="text-sm text-white/70">Catatan</label><textarea id="notes" name="notes" rows="3" maxlength="5000" class="{{ $inputClass }}">{{ old('notes') }}</textarea></div>
            </div>
            <div class="mt-6 flex justify-end gap-4 border-t border-white/10 pt-6"><a href="{{ route('admin.bookings.index', ['tab' => 'active']) }}" class="px-3 py-3 text-sm text-white/60">Batal</a><button type="submit" @disabled($customers->isEmpty() || $studios->isEmpty()) class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black disabled:opacity-40">Buat Booking</button></div>
        </form>
    </div>
@endsection
@push('scripts')
<script>
    const studioInput = document.getElementById('studio_id');
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    const minutes = (value) => { const [hours, minutes] = value.split(':').map(Number); return hours * 60 + minutes; };
    const updatePrice = () => {
        const price = studioInput.selectedOptions[0]?.dataset.price;
        const duration = minutes(endInput.value) - minutes(startInput.value);
        document.getElementById('booking-price').textContent = price !== undefined && duration > 0
            ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(price) * duration / 60)
            : 'Pilih studio dan jam penggunaan';
    };
    [studioInput, startInput, endInput].forEach((input) => input.addEventListener('change', updatePrice));
    updatePrice();
</script>
@endpush
