<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Studio;
use App\Models\StudioSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudioManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Studio Utama', 'description' => 'Studio latihan tari.',
            'capacity' => 20, 'price_per_hour' => '150000.50', 'status' => 'active',
        ], $overrides);
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('studio.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
    }

    public static function staffRoles(): array
    {
        return [['admin'], ['super_admin']];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_can_manage_studios(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]));
        $this->get(route('admin.studios.index'))->assertOk()->assertSee('Belum ada studio.');
        $this->get(route('admin.studios.create'))->assertOk()->assertSee('multipart/form-data', false);
        $this->post(route('admin.studios.store'), $this->data())->assertRedirect(route('admin.studios.index'));
        $studio = Studio::firstOrFail();
        $this->assertSame('150000.50', $studio->price_per_hour);
        $this->get(route('admin.studios.index'))->assertOk()->assertSee('Rp 150.000,50')->assertSeeInOrder(['Kelola Studio', 'Dance Classes']);
        $this->get(route('admin.studios.edit', $studio))->assertOk()->assertSee('Studio Utama');
        $this->put(route('admin.studios.update', $studio), $this->data(['status' => 'inactive', 'capacity' => 30]))
            ->assertRedirect(route('admin.studios.index'));
        $this->assertDatabaseHas('studios', ['id' => $studio->id, 'status' => 'inactive', 'capacity' => 30]);
        $this->delete(route('admin.studios.destroy', $studio))->assertRedirect(route('admin.studios.index'));
        $this->assertModelMissing($studio);
    }

    public function test_guest_and_customer_cannot_access_any_management_endpoint(): void
    {
        $studio = Studio::create($this->data());
        $requests = [
            ['get', route('admin.studios.index')], ['get', route('admin.studios.create')],
            ['post', route('admin.studios.store')], ['get', route('admin.studios.edit', $studio)],
            ['put', route('admin.studios.update', $studio)], ['delete', route('admin.studios.destroy', $studio)],
        ];
        foreach ($requests as [$method, $url]) {
            $this->{$method}($url)->assertRedirect(route('login'));
        }
        $this->actingAs(User::factory()->create());
        foreach ($requests as [$method, $url]) {
            $this->{$method}($url)->assertForbidden();
        }
        $this->assertModelExists($studio);
    }

    public function test_images_can_be_uploaded_preserved_replaced_removed_and_deleted(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('admin.studios.store'), $this->data(['image' => $this->photo()]))->assertSessionHasNoErrors();
        $studio = Studio::firstOrFail();
        $first = $studio->image;
        Storage::disk('public')->assertExists($first);
        $this->get(route('admin.studios.edit', $studio))->assertOk()->assertSee('storage/'.$first);
        $this->put(route('admin.studios.update', $studio), $this->data())->assertSessionHasNoErrors();
        $this->assertSame($first, $studio->fresh()->image);
        $this->put(route('admin.studios.update', $studio), $this->data(['image' => $this->photo()]))->assertSessionHasNoErrors();
        $second = $studio->fresh()->image;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
        $this->put(route('admin.studios.update', $studio), $this->data(['remove_image' => '1']))->assertSessionHasNoErrors();
        $this->assertNull($studio->fresh()->image);
        Storage::disk('public')->assertMissing($second);
        $this->put(route('admin.studios.update', $studio), $this->data(['image' => $this->photo()]))->assertSessionHasNoErrors();
        $third = $studio->fresh()->image;
        $this->delete(route('admin.studios.destroy', $studio))->assertSessionHas('success');
        Storage::disk('public')->assertMissing($third);
    }

    public function test_validation_rejects_invalid_columns_and_uploads(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('admin.studios.store'), $this->data([
            'name' => '', 'capacity' => -1, 'price_per_hour' => '1.234', 'status' => 'other',
            'image' => UploadedFile::fake()->createWithContent('script.php', '<?php echo 1;'),
        ]))->assertSessionHasErrors(['name', 'capacity', 'price_per_hour', 'status', 'image']);
        $this->post(route('admin.studios.store'), $this->data([
            'capacity' => 4294967296, 'price_per_hour' => '10000000000',
            'image' => $this->photo()->size(2049),
        ]))->assertSessionHasErrors(['capacity', 'price_per_hour', 'image']);
        $this->assertDatabaseCount('studios', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_search_and_pagination(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        for ($i = 1; $i <= 12; $i++) {
            Studio::create($this->data(['name' => 'Latihan '.$i]));
        }
        $this->get(route('admin.studios.index', ['search' => 'Latihan', 'page' => 2]))
            ->assertOk()->assertViewHas('studios', fn ($studios) => $studios->total() === 12 && $studios->count() === 2)
            ->assertSee('search=Latihan');
        $this->get(route('admin.studios.index', ['search' => 'tidak ada']))->assertOk()->assertSee('Tidak ada studio yang sesuai pencarian.');
    }

    public static function scheduleTypes(): array
    {
        return [[StudioSchedule::class], [ClassSchedule::class]];
    }

    #[DataProvider('scheduleTypes')]
    public function test_studio_with_schedules_cannot_be_deleted(string $scheduleClass): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $studio = Studio::create($this->data());
        $schedule = $scheduleClass::factory()->create(['studio_id' => $studio->id]);
        $this->delete(route('admin.studios.destroy', $studio))->assertRedirect(route('admin.studios.index'))->assertSessionHas('error');
        $this->assertModelExists($studio);
        $this->assertModelExists($schedule);
    }
}
