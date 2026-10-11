@extends('layout.dashboard')

@section('title', 'Tambah User')

@section('content')
    <div class="mx-auto max-w-4xl p-4 sm:p-7 lg:p-10">
        <h2 class="text-2xl font-bold">Tambah User</h2>
        <p class="mt-2 mb-6 text-sm text-white/50">Buat akun baru dengan role sesuai hak akses Anda.</p>
        <form method="POST" action="{{ route('admin.users.store') }}" class="rounded-2xl border border-white/10 bg-[#171717] p-5 sm:p-7">
            @csrf
            @php
                $inputClass = 'mt-2 block w-full rounded-xl border border-white/15 bg-[#0d0d0d] px-4 py-3 text-sm text-white outline-none focus:border-[#f2c83f]';
                $isSelf = $managedUser->exists && auth()->user()->is($managedUser);
            @endphp

            @if($errors->any())
                <div role="alert" class="mb-6 rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-300">
                    <p class="font-semibold">Periksa kembali data berikut:</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="text-sm text-white/70">Nama <span class="text-[#f2c83f]">*</span></label>
                    <input id="name" name="name" value="{{ old('name', $managedUser->name) }}" required maxlength="255" autocomplete="name" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="email" class="text-sm text-white/70">Email <span class="text-[#f2c83f]">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email', $managedUser->email) }}" required maxlength="255" autocomplete="email" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="phone" class="text-sm text-white/70">Telepon</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone', $managedUser->phone) }}" maxlength="25" autocomplete="tel" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="state" class="text-sm text-white/70">Provinsi</label>
                    <input id="state" name="state" value="{{ old('state', $managedUser->state) }}" maxlength="100" autocomplete="address-level1" class="{{ $inputClass }}">
                </div>
                <div class="sm:col-span-2">
                    <label for="address" class="text-sm text-white/70">Alamat</label>
                    <textarea id="address" name="address" rows="3" maxlength="500" autocomplete="street-address" class="{{ $inputClass }}">{{ old('address', $managedUser->address) }}</textarea>
                </div>
                <div>
                    <label for="role" class="text-sm text-white/70">Role <span class="text-[#f2c83f]">*</span></label>
                    <select id="role" name="role" required @disabled($isSelf) class="{{ $inputClass }}">
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $managedUser->role) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if($isSelf)<input type="hidden" name="role" value="{{ $managedUser->role }}">@endif
                </div>
                <div>
                    <label for="status" class="text-sm text-white/70">Status <span class="text-[#f2c83f]">*</span></label>
                    <select id="status" name="status" required @disabled($isSelf) class="{{ $inputClass }}">
                        <option value="active" @selected(old('status', $managedUser->status) === 'active')>Aktif</option>
                        <option value="inactive" @selected(old('status', $managedUser->status) === 'inactive')>Nonaktif</option>
                    </select>
                    @if($isSelf)<input type="hidden" name="status" value="{{ $managedUser->status }}">@endif
                </div>
                @if($isSelf)
                    <p class="text-xs text-white/45 sm:col-span-2">Role dan status akun yang sedang digunakan tidak dapat diubah.</p>
                @endif
                <div>
                    <label for="password" class="text-sm text-white/70">Password {{ $managedUser->exists ? '(opsional)' : '*' }}</label>
                    <div class="relative">
                        <input id="password" type="password" name="password" minlength="8" maxlength="255" @required(! $managedUser->exists) autocomplete="new-password" class="{{ $inputClass }} pr-12">
                        <button id="toggle-password" type="button" aria-controls="password" aria-label="Tampilkan password" aria-pressed="false" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-white/50 hover:text-[#f2c83f] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#f2c83f]">
                            <svg id="password-eye" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg id="password-eye-off" class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="m3 3 18 18M10.6 5.1A11 11 0 0 1 12 5c6.5 0 10 7 10 7a19 19 0 0 1-3 3.8M6.2 6.2A20 20 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.8-1.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-white/40">{{ $managedUser->exists ? 'Kosongkan jika tidak ingin mengganti password.' : 'Minimal 8 karakter.' }}</p>
                </div>
            </div>

            <div class="mt-7 flex items-center justify-end gap-4 border-t border-white/10 pt-6">
                <a href="{{ route('admin.users.index') }}" class="text-sm text-white/60 hover:text-white">Batal</a>
                <button data-save-confirm type="submit" class="rounded-xl bg-[#f2c83f] px-5 py-3 text-sm font-semibold text-black hover:bg-[#ffda58]">{{ $managedUser->exists ? 'Simpan Perubahan' : 'Tambah User' }}</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('toggle-password').addEventListener('click', function () {
            const input = document.getElementById('password');
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            this.setAttribute('aria-pressed', String(visible));
            this.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
            document.getElementById('password-eye').classList.toggle('hidden', visible);
            document.getElementById('password-eye-off').classList.toggle('hidden', !visible);
        });
    </script>
@endpush
