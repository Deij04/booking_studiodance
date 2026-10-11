<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipEnrollment
{
    public function create(array $data): Membership
    {
        return DB::transaction(function () use ($data) {
            $user = User::lockForUpdate()->findOrFail($data['user_id']);
            $plan = MembershipPlan::lockForUpdate()->findOrFail($data['membership_plan_id']);
            if ($user->role !== 'customer' || $user->status !== 'active' || $plan->status !== 'active') {
                throw ValidationException::withMessages(['membership_plan_id' => 'Pilih customer dan paket yang masih aktif.']);
            }
            if (empty($data['start_date'])) {
                if ($user->memberships()->where(fn ($query) => $query->where('status', 'pending')
                    ->orWhere(fn ($query) => $query->where('status', 'active')->whereDate('end_date', '>=', today())))->exists()) {
                    throw ValidationException::withMessages(['membership_plan_id' => 'You already have a pending application or an active membership.']);
                }

                return Membership::create([
                    'user_id' => $user->id, 'membership_plan_id' => $plan->id,
                    'start_date' => null, 'end_date' => null,
                    'price' => $plan->price, 'payment_status' => 'unpaid', 'status' => 'pending',
                ]);
            }
            $end = Carbon::parse($data['start_date'])->addDays($plan->duration_days - 1)->toDateString();
            if ($user->memberships()->whereIn('status', ['pending', 'active'])
                ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $data['start_date'])->exists()) {
                throw ValidationException::withMessages(['start_date' => 'Sudah ada membership pada periode tersebut. Pilih tanggal setelah membership sebelumnya berakhir.']);
            }

            return Membership::create([
                'user_id' => $user->id, 'membership_plan_id' => $plan->id,
                'start_date' => $data['start_date'], 'end_date' => $end,
                'price' => $plan->price, 'payment_status' => 'unpaid', 'status' => 'pending',
            ]);
        });
    }
}
