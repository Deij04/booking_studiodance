@if($membership->status === 'pending')
    <div class="flex flex-wrap items-center gap-4">
        <form method="POST" action="{{ route('admin.memberships.decide', $membership) }}" class="flex flex-wrap items-center gap-3">
            @csrf
            @method('PATCH')
            <input type="hidden" name="decision" value="approve">
            <label class="flex items-center gap-2 text-xs text-white/60"><input type="checkbox" name="payment_confirmed" value="1" required> Pembayaran sudah diperiksa dan lunas</label>
            <button type="submit" class="rounded-xl bg-[#f2c83f] px-4 py-2 text-sm font-semibold text-black">Setujui</button>
        </form>
        <form method="POST" action="{{ route('admin.memberships.decide', $membership) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="decision" value="reject">
            <button type="submit" class="rounded-xl border border-red-400/30 px-4 py-2 text-sm text-red-300">Tolak</button>
        </form>
    </div>
@endif
