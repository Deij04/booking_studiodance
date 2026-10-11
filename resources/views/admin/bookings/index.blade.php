@extends('layout.dashboard')
@section('title', 'Kelola Booking')
@section('content')
    <div class="mx-auto max-w-[1500px] space-y-6 p-4 sm:p-7 lg:p-10">
        <div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="text-2xl font-bold">Kelola Booking</h2><p class="mt-2 text-sm text-white/50">Pantau reservasi studio, pembayaran, dan status booking. Semua waktu menggunakan WITA.</p></div><a href="{{ route('admin.bookings.create') }}" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black hover:bg-[#ffda58]">+ Tambah Booking</a></div>
        @if(session('success'))<div data-booking-notice="success" role="status" class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-300">{{ session('success') }}</div>@endif
        @if($errors->any())<div data-booking-notice="error" role="alert" class="rounded-xl bg-red-400/10 p-4 text-sm text-red-300">@foreach($errors->all() as $error)<p>{{ $error }} </p>@endforeach</div>@endif
        @php($inputClass = 'mt-2 w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-2.5 text-sm outline-none focus:border-[#f2c83f]')
        <nav aria-label="Kategori booking" class="flex flex-wrap gap-3">
            @foreach($tabs as $key => $item)
                <a href="{{ route('admin.bookings.index', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif class="flex items-center gap-3 rounded-xl border px-5 py-3 text-sm {{ $tab === $key ? 'border-[#f2c83f] bg-[#f2c83f] font-semibold text-black' : 'border-white/10 bg-[#171717] text-white/60 hover:text-white' }}">
                    {{ $item['label'] }} <span class="rounded-full bg-black/10 px-2 py-0.5 text-xs">{{ $tabCounts[$key] }}</span>
                </a>
            @endforeach
        </nav>
        <p class="text-sm text-white/50">{{ ['requests' => 'Periksa bukti pembayaran awal. Terima sekaligus memverifikasi pembayaran, atau tolak pengajuan.', 'active' => 'Booking yang sudah dikonfirmasi dan menunggu pelaksanaan atau penyelesaian.', 'history' => 'Booking yang sudah selesai, ditolak, atau dibatalkan.'][$tab] }}</p>
        <section class="overflow-hidden rounded-2xl border border-white/10 bg-[#171717]">
            <form method="GET" action="{{ route('admin.bookings.index') }}" class="flex flex-wrap items-end gap-3 border-b border-white/10 p-5">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="min-w-48 flex-1"><label for="search" class="text-xs text-white/60">Kode, pelanggan, atau studio</label><input id="search" name="search" value="{{ $filters['search'] }}" maxlength="100" placeholder="Cari booking..." class="{{ $inputClass }}"></div>
                @if($tab === 'history')
                    <div><label for="status" class="text-xs text-white/60">Status booking</label><select id="status" name="status" class="{{ $inputClass }}"><option value="">Semua status</option>@foreach($tabs[$tab]['statuses'] as $value)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ \App\Models\Booking::STATUSES[$value] }}</option>@endforeach</select></div>
                @endif
                @if($tab === 'requests')
                    <div><label for="payment_type" class="text-xs text-white/60">Jenis pembayaran</label><select id="payment_type" name="payment_type" class="{{ $inputClass }}"><option value="">Semua pembayaran</option><option value="full" @selected(($filters['payment_type'] ?? '') === 'full')>Lunas</option><option value="dp" @selected(($filters['payment_type'] ?? '') === 'dp')>DP (belum lunas)</option></select></div>
                @endif
                <div><label for="date" class="text-xs text-white/60">Tanggal penggunaan</label><input type="date" id="date" name="date" value="{{ $filters['date'] ?? '' }}" class="{{ $inputClass }} [color-scheme:dark]"></div>
                <button class="rounded-xl bg-[#f2c83f] px-5 py-2.5 text-sm font-semibold text-black">Terapkan</button><a href="{{ route('admin.bookings.index', ['tab' => $tab]) }}" class="px-3 py-2.5 text-sm text-white/60">Reset</a>
            </form>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                <thead class="bg-white/5 text-xs uppercase tracking-wide text-white/45"><tr>@foreach(['Booking / Pelanggan', 'Studio / Jadwal', 'Total', 'Pembayaran', 'Verifikasi Pengajuan Booking', 'Verifikasi Pelunasan', 'Status', 'Aksi'] as $heading)<th scope="col" class="px-5 py-4 {{ $heading === 'Aksi' ? 'text-center' : '' }}">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($bookings as $booking)
                        @php($paymentSummary = app(\App\Services\PaymentSummary::class)->for($booking))
                        <tr class="hover:bg-white/[.025]">
                            <td class="px-5 py-4"><p class="font-semibold text-[#f2c83f]">{{ $booking->booking_code }}</p><p class="mt-2">{{ $booking->user->name }}</p><p class="mt-1 text-xs text-white/45">{{ $booking->user->email }}</p></td>
                            <td class="whitespace-nowrap px-5 py-4"><p>{{ $booking->studio->name }}</p><p class="mt-2 text-xs text-white/50">{{ \Carbon\Carbon::parse($booking->date)->format('d/m/Y') }}</p><p class="mt-1 text-xs text-white/50">{{ substr($booking->start_time, 0, 5) }} &ndash; {{ substr($booking->end_time, 0, 5) }}</p></td>
                            <td class="whitespace-nowrap px-5 py-4">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4">{{ \App\Models\Booking::PAYMENT_METHODS[$booking->payment_method] ?? 'Belum ditentukan' }}@if($booking->payment_type)<p class="mt-1 text-xs text-white/50">{{ \App\Models\Booking::PAYMENT_TYPES[$booking->payment_type] ?? '-' }}</p>@endif
                            @if($tab === 'active')<div class="mt-2"><span class="inline-block rounded-lg px-3 py-1 text-xs font-semibold {{ $paymentSummary['remaining'] <= 0 ? 'bg-emerald-400/10 text-emerald-300' : 'bg-amber-400/10 text-amber-300' }}">{{ $paymentSummary['remaining'] <= 0 ? 'Lunas' : ($paymentSummary['dp'] ? 'DP - Belum Lunas' : 'Menunggu Verifikasi') }}</span></div>@if($paymentSummary['dp'] && $paymentSummary['remaining'] > 0)<p class="mt-2 text-xs text-white/50">Sisa: Rp {{ number_format($booking->remaining_amount, 0, ',', '.') }}</p>@if($paymentSummary['settlement']?->status === 'pending')<p class="mt-1 text-xs text-[#f2c83f]">Pelunasan menunggu verifikasi</p>@endif @endif @endif</td>
                            <td class="px-5 py-4"><p>{{ \App\Models\Booking::PAYMENT_STATUSES[$booking->payment_status] }}</p>@if($booking->verified_by_name)<p class="mt-1 text-xs text-white/50">Oleh {{ $booking->verified_by_name }}</p>@endif</td>
                            <td class="px-5 py-4">
                                @if($booking->payment_type === 'dp')
                                    <p class="{{ $booking->settlement?->status === 'rejected' ? 'text-red-300' : '' }}">{{ $booking->settlement ? \App\Models\Payment::STATUSES[$booking->settlement->status] : 'Belum diajukan' }}</p>
                                    @if($booking->settlement?->verified_by_name)
                                        <p class="mt-1 text-xs text-white/50">Oleh {{ $booking->settlement->verified_by_name }}</p>
                                    @endif
                                @else
                                    <p class="text-xs text-white/50">Tidak diperlukan</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4"><span class="rounded-full px-3 py-1 text-xs {{ ['pending' => 'bg-amber-400/10 text-amber-300', 'confirmed' => 'bg-blue-400/10 text-blue-300', 'cancelled' => 'bg-red-400/10 text-red-300', 'completed' => 'bg-emerald-400/10 text-emerald-300'][$booking->status] }}">{{ \App\Models\Booking::STATUSES[$booking->status] }}</span></td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.bookings.show', $booking) }}" aria-label="Detail booking {{ $booking->booking_code }}" class="text-[#f2c83f] hover:underline">Detail</a>
                                    @if($tab === 'active' && $booking->status === 'confirmed')
                                    <a href="{{ route('admin.bookings.edit', $booking) }}" aria-label="Edit booking {{ $booking->booking_code }}" class="text-sm text-white/60 hover:underline">Edit</a>
                                        @if($paymentSummary['dp'] && $paymentSummary['remaining'] > 0)
                                            <a href="{{ route('admin.bookings.settlement-form', $booking) }}" aria-label="Pelunasan booking {{ $booking->booking_code }}" class="rounded-lg bg-[#f2c83f] px-3 py-2 text-xs font-semibold text-black hover:bg-[#ffda58]">{{ $paymentSummary['settlement']?->status === 'pending' ? 'Verifikasi Pelunasan' : 'Pelunasan' }}</a>
                                        @endif
                                    @endif
                                    @if($tab === 'requests')
                                        @foreach(['accept' => 'Terima', 'reject' => 'Tolak'] as $decision => $label)
                                            <form method="POST" action="{{ route('admin.bookings.decide', $booking) }}" data-booking-confirm data-booking-code="{{ $booking->booking_code }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="decision" value="{{ $decision }}">
                                                <button type="submit" aria-label="{{ $label }} booking {{ $booking->booking_code }}" class="rounded-lg px-3 py-2 text-xs font-semibold {{ $decision === 'accept' ? 'bg-emerald-400/10 text-emerald-300 hover:bg-emerald-400/20' : 'bg-red-400/10 text-red-300 hover:bg-red-400/20' }}">{{ $label }}</button>
                                            </form>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-12 text-center text-white/45">Belum ada booking yang sesuai.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <div class="border-t border-white/10 p-5"><p class="mb-3 text-xs text-white/45">{{ $bookings->total() }} booking ditemukan</p>{{ $bookings->links() }}</div>
        </section>
    </div>
@endsection
