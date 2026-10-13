@extends('layout.dashboard')
@section('title', $registration->exists ? 'Edit Reservasi Class' : 'Tambah Reservasi Class')
@section('content')
<div class="mx-auto max-w-4xl p-4 sm:p-7 lg:p-10">
    <h2 class="mb-6 text-2xl font-bold">{{ $registration->exists ? 'Edit Reservasi Class' : 'Tambah Reservasi Class' }}</h2>
    <form enctype="multipart/form-data" method="POST" action="{{ $registration->exists ? route('admin.class-registrations.update', $registration) : route('admin.class-registrations.store') }}" class="rounded-2xl border border-white/10 bg-[#171717] p-5 sm:p-7">
        @csrf
        @php($inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm text-white focus:border-[#f2c83f]')
        @if($errors->any())<div role="alert" class="mb-6 rounded-xl bg-red-400/10 p-4 text-sm text-red-300"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div><label for="user_id" class="text-sm">Peserta</label><select id="user_id" name="user_id" required class="{{ $inputClass }}"><option value="">Pilih peserta</option>@foreach($users as $user)<option data-member-start="{{ $user->memberships->first()?->start_date?->toDateString() }}" data-member-end="{{ $user->memberships->first()?->end_date?->toDateString() }}" value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} — {{ $user->email }}</option>@endforeach</select></div>
            @if($users->isEmpty())<p class="mt-2 text-sm text-[#f2c83f]">Belum ada customer aktif. Tambahkan peserta melalui menu Kelola User.</p>@endif
            <div class="mt-5"><label for="class_schedule_id" class="text-sm">Jadwal class</label><select id="class_schedule_id" name="class_schedule_id" required class="{{ $inputClass }}"><option value="">Pilih jadwal class</option>@foreach($schedules as $schedule)<option data-date="{{ $schedule->date }}" value="{{ $schedule->id }}" @selected(old('class_schedule_id') == $schedule->id) @disabled($schedule->occupied >= $schedule->capacity)>{{ $schedule->danceClass->name }} · {{ \Illuminate\Support\Carbon::parse($schedule->date)->format('d/m/Y') }} {{ substr($schedule->start_time, 0, 5) }} · {{ $schedule->studio->name }} · Rp {{ number_format($schedule->danceClass->price, 2, ',', '.') }} · Sisa {{ max(0, $schedule->capacity - $schedule->occupied) }} kursi</option>@endforeach</select></div>
            @if($schedules->isEmpty())<p class="mt-2 text-sm text-[#f2c83f]">Belum ada jadwal class aktif yang akan datang.</p>@endif
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><label for="payment_method" class="text-sm">Metode pembayaran</label><select id="payment_method" name="payment_method" required class="{{ $inputClass }}"><option value="">Pilih metode pembayaran</option>@foreach(\App\Models\Booking::PAYMENT_METHODS as $value => $label)<option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label for="payment_type_display" class="text-sm">Jenis pembayaran</label><input id="payment_type_display" type="text" value="Lunas (100%)" readonly aria-readonly="true" class="{{ $inputClass }} cursor-default"><input type="hidden" name="payment_type" value="full"></div>
                <div class="sm:col-span-2"><label for="payment_proof" class="text-sm">Bukti pembayaran</label><input id="payment_proof" name="payment_proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="{{ $inputClass }} file:mr-4 file:rounded-lg file:border-0 file:bg-[#f2c83f] file:px-4 file:py-2 file:text-black"><p class="mt-2 text-xs text-white/50">Wajib untuk QRIS dan transfer bank; opsional untuk tunai. JPG, PNG, WEBP, atau PDF, maksimal 2 MB.</p></div>
            </div>
            <p id="member-class-benefit" hidden class="mt-4 text-sm text-[#f2c83f]">Benefit membership berlaku: class gratis dan otomatis dikonfirmasi tanpa bukti bayar.</p>
            <p class="mt-4 text-xs leading-5 text-white/50">Harga mengikuti class saat reservasi dibuat. Reservasi baru berstatus Menunggu dan Belum Terverifikasi, serta langsung menggunakan satu kursi.</p>
        <div class="mt-7 flex justify-end gap-4 border-t border-white/10 pt-6"><a href="{{ route('admin.class-registrations.index') }}" class="px-3 py-3 text-sm text-white/60">Batal</a><button data-save-confirm type="submit" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black">Simpan Reservasi</button></div>
    </form>

</div>
<script>
    (() => {
        const method = document.getElementById('payment_method');
        const proof = document.getElementById('payment_proof');
        const sync = () => {
            const user = document.getElementById('user_id').selectedOptions[0];
            const date = document.getElementById('class_schedule_id').selectedOptions[0]?.dataset.date;
            const member = Boolean(user?.dataset.memberStart && date >= user.dataset.memberStart && date <= user.dataset.memberEnd);
            document.getElementById('member-class-benefit').hidden = !member;
            proof.required = !member && method.value !== '' && method.value !== 'cash';
            const option = method.querySelector('[value="membership"]');
            if (option) option.disabled = !member;
            if (member) method.value = 'membership'; else if (method.value === 'membership') method.value = '';
        };
        document.getElementById('user_id').addEventListener('change', sync);
        document.getElementById('class_schedule_id').addEventListener('change', sync);
        method.addEventListener('change', sync);
        proof.addEventListener('change', () => proof.setCustomValidity(proof.files[0]?.size > 2048 * 1024 ? 'Ukuran bukti maksimal 2 MB.' : ''));
        sync();
    })();
</script>
@endsection
