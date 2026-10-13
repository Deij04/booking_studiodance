<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Studio 2', 15, 700000],
            ['Studio 2', 30, 1200000],
            ['Studio 3', 15, 550000],
            ['Studio 3', 30, 1000000],
        ] as [$studio, $package, $price]) {
            $plan = MembershipPlan::withTrashed()->firstOrCreate(['name' => $studio.' - Membership '.$package], [
                'price' => $price,
                'duration_days' => 30, 'quota_hours' => $package, 'free_classes' => true,
                'description' => "Paket membership penyewaan studio sesuai pilihan paket.\nHarga paket bulanan Rp ".number_format($price, 0, ',', '.').".\nAktivasi setelah pembayaran diperiksa dan disetujui admin.",
                'status' => 'active',
            ]);
            $oldDescription = "Paket membership penyewaan {$studio}.\nHarga paket bulanan Rp ".number_format($price, 0, ',', '.').".\nAktivasi setelah pembayaran diperiksa dan disetujui admin.";
            if ($plan->description === $oldDescription) $plan->update(['description' => str_replace("Paket membership penyewaan {$studio}.", 'Paket membership penyewaan studio sesuai pilihan paket.', $oldDescription)]);
        }
        foreach ([15, 30] as $quota) {
            MembershipPlan::where('name', 'like', '% - Membership '.$quota)->whereNull('quota_hours')->update(['quota_hours' => $quota, 'free_classes' => true]);
        }
        MembershipPlan::whereIn('name', ['Basic', 'Premium', 'VIP'])
            ->whereIn('description', ['Akses membership dasar selama 30 hari.', 'Akses membership premium selama 90 hari.', 'Akses membership VIP selama 180 hari.'])
            ->update(['status' => 'inactive']);
    }
}
