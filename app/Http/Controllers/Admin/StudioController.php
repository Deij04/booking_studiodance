<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class StudioController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($filters['search'] ?? '');
        $studios = Studio::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->latest('id')->paginate(10)->withQueryString();

        return view('admin.studios.index', compact('studios', 'search'));
    }

    public function create(): View
    {
        return view('admin.studios.create', ['studio' => new Studio(['status' => 'active'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $path = $request->file('image')?->store('studios', 'public');
        abort_if($path === false, 500, 'Gambar gagal disimpan.');
        $data['image'] = $path;

        try {
            Studio::create($data);
        } catch (Throwable $exception) {
            $this->deleteImage($path);
            throw $exception;
        }

        return to_route('admin.studios.index')->with('success', 'Studio berhasil ditambahkan.');
    }

    public function edit(Studio $studio): View
    {
        return view('admin.studios.edit', compact('studio'));
    }

    public function update(Request $request, Studio $studio): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['image']);
        $oldImage = $studio->image;
        $path = $request->file('image')?->store('studios', 'public');
        abort_if($path === false, 500, 'Gambar gagal disimpan.');
        if ($path) {
            $data['image'] = $path;
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }

        try {
            $studio->update($data);
        } catch (Throwable $exception) {
            $this->deleteImage($path);
            throw $exception;
        }

        if ($oldImage !== $studio->image) {
            $this->deleteImage($oldImage);
        }

        return to_route('admin.studios.index')->with('success', 'Studio berhasil diperbarui.');
    }

    public function destroy(Studio $studio): RedirectResponse
    {
        if ($studio->studioSchedules()->exists() || $studio->classSchedules()->exists()) {
            return to_route('admin.studios.index')->with('error', 'Studio memiliki jadwal studio atau kelas. Nonaktifkan melalui Edit agar jadwal dan riwayat booking tetap tersimpan.');
        }

        $image = $studio->image;
        $studio->delete();
        $this->deleteImage($image);

        return to_route('admin.studios.index')->with('success', 'Studio berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'price_per_hour' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);
        unset($data['remove_image']);

        return $data;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'studios/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
