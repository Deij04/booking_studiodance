<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\DanceClass;
use App\Models\Studio;
use App\Services\BookingHours;
use App\Services\StudioConflicts;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClassScheduleController extends Controller
{
    public function create()
    {
        return view('admin.class-schedules.create', [
            'schedule' => new ClassSchedule(['status' => 'active']),
            'hours' => app(BookingHours::class)->current(),
            'danceClasses' => DanceClass::where('status', 'active')->orderBy('name')->get(),
            'studios' => Studio::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->save($this->validated($request) + ['status' => 'active']);

        return to_route('admin.class-registrations.index', ['tab' => 'classes'])
            ->with('success', 'Jadwal class berhasil ditambahkan. Peserta sekarang dapat melakukan reservasi.');
    }

    public function edit(ClassSchedule $classSchedule)
    {
        return view('admin.class-schedules.create', [
            'schedule' => $classSchedule,
            'hours' => app(BookingHours::class)->current(),
            'danceClasses' => DanceClass::where('status', 'active')->orWhere('id', $classSchedule->dance_class_id)->orderBy('name')->get(),
            'studios' => Studio::where('status', 'active')->orWhere('id', $classSchedule->studio_id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ClassSchedule $classSchedule)
    {
        $data = $this->validated($request);
        $data += $request->validate(['status' => ['required', Rule::in(['active', 'inactive'])]]);
        $this->save($data, $classSchedule);

        return to_route('admin.class-registrations.index', ['tab' => 'classes'])->with('success', 'Jadwal class berhasil diperbarui.');
    }

    public function deactivate(ClassSchedule $classSchedule)
    {
        DB::transaction(function () use ($classSchedule) {
            Studio::whereKey($classSchedule->studio_id)->lockForUpdate()->firstOrFail();
            $schedule = ClassSchedule::lockForUpdate()->findOrFail($classSchedule->id);
            $this->ensureCanDeactivate($schedule);
            $schedule->update(['status' => 'inactive']);
        });

        return to_route('admin.class-registrations.index', ['tab' => 'classes'])->with('success', 'Jadwal class dinonaktifkan. Riwayat peserta tetap tersimpan.');
    }

    private function ensureCanDeactivate(ClassSchedule $schedule): void
    {
        if ($schedule->classRegistrations()->whereIn('registration_status', ['pending', 'confirmed'])->exists()) {
            throw ValidationException::withMessages(['status' => 'Masih ada reservasi menunggu atau terkonfirmasi. Batalkan reservasi tersebut atau tandai selesai jika kelas telah berakhir sebelum menonaktifkan jadwal.']);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'dance_class_id' => ['required', Rule::exists('dance_classes', 'id')->where('status', 'active')],
            'studio_id' => ['required', Rule::exists('studios', 'id')->where('status', 'active')],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', new \App\Rules\HalfHour],
            'end_time' => ['required', 'date_format:H:i', new \App\Rules\HalfHour, 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ]);
    }

    private function save(array $data, ?ClassSchedule $existing = null): void
    {
        DB::transaction(function () use ($data, $existing) {
            // Use the same studio lock as studio booking creation.
            $studios = Studio::whereIn('id', array_filter([$data['studio_id'], $existing?->studio_id]))->orderBy('id')->lockForUpdate()->get();
            $studio = $studios->firstWhere('id', $data['studio_id']);
            abort_unless($studio, 404);
            $schedule = $existing ? ClassSchedule::lockForUpdate()->findOrFail($existing->id) : null;
            if ($schedule) {
                if (! $studios->contains('id', $schedule->studio_id)) {
                    throw ValidationException::withMessages(['studio_id' => 'Jadwal baru saja berubah. Muat ulang halaman sebelum menyimpan.']);
                }
                if (Carbon::parse($schedule->date.' '.$schedule->start_time)->lte(now())) {
                    throw ValidationException::withMessages(['date' => 'Jadwal yang sudah dimulai tidak dapat diedit. Gunakan Nonaktifkan untuk mengarsipkan jadwal.']);
                }
                if ($schedule->dance_class_id != $data['dance_class_id'] && $schedule->classRegistrations()->exists()) {
                    throw ValidationException::withMessages(['dance_class_id' => 'Kelas tidak dapat diganti karena jadwal memiliki riwayat peserta.']);
                }
                if ($data['capacity'] < $schedule->classRegistrations()->where('registration_status', '!=', 'cancelled')->count()) {
                    throw ValidationException::withMessages(['capacity' => 'Kuota tidak boleh lebih kecil dari jumlah peserta yang terdaftar.']);
                }
                if ($data['status'] === 'inactive') {
                    $this->ensureCanDeactivate($schedule);
                }
            }
            $danceClass = DanceClass::lockForUpdate()->findOrFail($data['dance_class_id']);
            if ($studio->status !== 'active' || $danceClass->status !== 'active') {
                throw ValidationException::withMessages(['studio_id' => 'Kelas dan studio harus aktif.']);
            }
            if ($data['capacity'] > min($studio->capacity, $danceClass->capacity)) {
                throw ValidationException::withMessages(['capacity' => 'Kuota tidak boleh melebihi kapasitas kelas atau studio.']);
            }
            if (Carbon::parse($data['date'].' '.$data['start_time'])->lte(now())) {
                throw ValidationException::withMessages(['start_time' => 'Waktu mulai harus lebih dari waktu sekarang.']);
            }
            $hours = app(BookingHours::class)->current();
            if ($data['start_time'] < $hours['opens_at'] || $data['end_time'] > $hours['closes_at']) {
                throw ValidationException::withMessages(['start_time' => 'Jadwal harus berada dalam jam operasional '.$hours['opens_at'].'–'.$hours['closes_at'].'.']);
            }
            if ($data['status'] === 'active' && app(StudioConflicts::class)->exists($studio, $data, $schedule?->id)) {
                throw ValidationException::withMessages(['start_time' => 'Studio sudah digunakan oleh kelas, booking, atau blokir pada waktu tersebut. Pilih waktu atau studio lain.']);
            }
            $schedule ? $schedule->update($data) : ClassSchedule::create($data);
        });
    }
}
