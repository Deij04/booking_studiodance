<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Studio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MembershipPlanController extends Controller
{
    public function create()
    {
        return view('admin.memberships.plan-form', ['plan' => new MembershipPlan(['status' => 'active', 'free_classes' => true]), 'studios' => Studio::where('status', 'active')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        MembershipPlan::create($this->validated($request));

        return to_route('admin.memberships.index', ['tab' => 'plans'])->with('success', 'Paket membership berhasil ditambahkan.');
    }

    public function edit(MembershipPlan $membershipPlan)
    {
        return view('admin.memberships.plan-form', ['plan' => $membershipPlan, 'studios' => Studio::where('status', 'active')->orWhere('id', $membershipPlan->studio_id)->orderBy('name')->get()]);
    }

    public function update(Request $request, MembershipPlan $membershipPlan)
    {
        $membershipPlan->update($this->validated($request));

        return to_route('admin.memberships.index', ['tab' => 'plans'])->with('success', 'Paket membership berhasil diperbarui.');
    }

    public function destroy(MembershipPlan $membershipPlan)
    {
        DB::transaction(function () use ($membershipPlan) {
            $plan = MembershipPlan::lockForUpdate()->findOrFail($membershipPlan->id);
            if ($plan->memberships()->exists()) {
                throw ValidationException::withMessages(['membership_plan' => 'Paket masih memiliki member. Alihkan semua member ke paket lain terlebih dahulu sebelum menghapus paket.']);
            }
            $plan->update(['status' => 'inactive']);
            $plan->delete();
        });

        return to_route('admin.memberships.index', ['tab' => 'plans'])->with('success', 'Paket membership berhasil dihapus.');
    }

    public function transfer(Request $request, MembershipPlan $membershipPlan)
    {
        $data = $request->validate([
            'target_plan_id' => ['required', 'integer', Rule::notIn([$membershipPlan->id]), Rule::exists('membership_plans', 'id')->whereNull('deleted_at')->where('status', 'active')],
        ]);

        $count = DB::transaction(function () use ($membershipPlan, $data) {
            $plans = MembershipPlan::whereIn('id', [$membershipPlan->id, $data['target_plan_id']])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $plans->get($membershipPlan->id);
            $target = $plans->get($data['target_plan_id']);
            if (! $source || ! $target || $target->status !== 'active') {
                throw ValidationException::withMessages(['target_plan_id' => 'Paket tujuan tidak tersedia. Pilih paket aktif lainnya.']);
            }

            return $source->memberships()->update(['membership_plan_id' => $target->id]);
        });

        return to_route('admin.memberships.index', ['tab' => 'plans'])->with('success', $count.' keanggotaan berhasil dialihkan. Paket asal sekarang dapat dihapus jika tidak ada member baru.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'studio_id' => ['nullable', 'exists:studios,id'],
            'quota_hours' => ['nullable', 'integer', Rule::in([15, 30])],
            'free_classes' => ['sometimes', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]) + ['free_classes' => true];
    }
}
