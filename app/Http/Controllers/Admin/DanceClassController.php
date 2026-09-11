<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanceClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class DanceClassController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($filters['search'] ?? '');
        $danceClasses = DanceClass::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->latest('id')->paginate(10)->withQueryString();

        return view('admin.dance-classes.index', compact('danceClasses', 'search'));
    }

    public function create(): View
    {
        return view('admin.dance-classes.create', ['danceClass' => new DanceClass(['status' => 'active'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $path = $request->file('image')?->store('dance-classes', 'public');
        abort_if($path === false, 500, 'Gambar gagal disimpan.');
        $data['image'] = $path;

        try {
            DanceClass::create($data);
        } catch (Throwable $exception) {
            $this->deleteImage($path);
            throw $exception;
        }

        return to_route('admin.dance-classes.index')->with('success', 'Dance class berhasil ditambahkan.');
    }

    public function edit(DanceClass $danceClass): View
    {
        return view('admin.dance-classes.edit', compact('danceClass'));
    }

    public function update(Request $request, DanceClass $danceClass): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['image']);
        $oldImage = $danceClass->image;
        $path = $request->file('image')?->store('dance-classes', 'public');
        abort_if($path === false, 500, 'Gambar gagal disimpan.');
        if ($path) {
            $data['image'] = $path;
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }

        try {
            $danceClass->update($data);
        } catch (Throwable $exception) {
            $this->deleteImage($path);
            throw $exception;
        }

        if ($oldImage !== $danceClass->image) {
            $this->deleteImage($oldImage);
        }

        return to_route('admin.dance-classes.index')->with('success', 'Dance class berhasil diperbarui.');
    }

    public function destroy(DanceClass $danceClass): RedirectResponse
    {
        if ($danceClass->classSchedules()->exists()) {
            return to_route('admin.dance-classes.index')->with('error', 'Dance class memiliki jadwal. Nonaktifkan melalui Edit agar jadwal dan riwayat pendaftaran tetap tersimpan.');
        }

        $image = $danceClass->image;
        $danceClass->delete();
        $this->deleteImage($image);

        return to_route('admin.dance-classes.index')->with('success', 'Dance class berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'level' => ['required', Rule::in(array_keys(DanceClass::LEVELS))],
            'capacity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);
        unset($data['remove_image']);

        return $data;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'dance-classes/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
