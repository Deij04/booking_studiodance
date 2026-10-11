<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\MembershipEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MembershipController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(Membership::STATUSES))],
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? '';
        $memberships = Membership::with(['user', 'membershipPlan'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                ->orWhereHas('membershipPlan', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))))
            ->when($status === 'expired', fn ($q) => $q->where(fn ($q) => $q->where('status', 'expired')
                ->orWhere(fn ($q) => $q->where('status', 'active')->whereDate('end_date', '<', today()->toDateString()))))
            ->when($status === 'active', fn ($q) => $q->where('status', 'active')->whereDate('end_date', '>=', today()->toDateString()))
            ->when(in_array($status, ['pending', 'cancelled']), fn ($q) => $q->where('status', $status))
            ->latest('id')->paginate(10)->withQueryString();
        $plans = MembershipPlan::latest('id')->paginate(6, ['*'], 'plans_page')->withQueryString();

        return view('admin.memberships.index', compact('memberships', 'plans', 'search', 'status'));
    }

    public function create()
    {
        return view('admin.memberships.form', [
            'membership' => new Membership,
            'users' => User::where('role', 'customer')->where('status', 'active')->orderBy('name')->get(),
            'plans' => MembershipPlan::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, MembershipEnrollment $enrollment)
    {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('role', 'customer')->where('status', 'active')],
            'membership_plan_id' => ['required', Rule::exists('membership_plans', 'id')->where('status', 'active')],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before:9990-01-01'],
        ]);
        $enrollment->create($data);

        return to_route('admin.memberships.index')->with('success', 'Membership berhasil ditambahkan. Catat pembayaran untuk mengaktifkannya.');
    }

    public function edit(Membership $membership)
    {
        return view('admin.memberships.form', ['membership' => $membership->load(['user', 'membershipPlan'])]);
    }

    public function update(Request $request, Membership $membership)
    {
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(['unpaid', 'paid'])],
            'status' => ['required', Rule::in(array_keys(Membership::STATUSES))],
        ]);
        $this->changeStatus($membership, $data);

        return to_route('admin.memberships.index')->with('success', 'Membership berhasil diperbarui.');
    }

    public function decide(Request $request, Membership $membership)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'payment_confirmed' => ['accepted_if:decision,approve'],
        ]);
        $changes = ['status' => $data['decision'] === 'approve' ? 'active' : 'cancelled'];
        if ($data['decision'] === 'approve') {
            $changes['payment_status'] = 'paid';
        }
        $this->changeStatus($membership, $changes, true);

        return to_route('admin.memberships.index')->with('success', $data['decision'] === 'approve'
            ? 'Membership disetujui dan diaktifkan.' : 'Pengajuan membership ditolak.');
    }

    private function changeStatus(Membership $membership, array $data, bool $pendingOnly = false): void
    {
        DB::transaction(function () use ($membership, $data, $pendingOnly) {
            $user = User::lockForUpdate()->findOrFail($membership->user_id);
            $current = Membership::lockForUpdate()->findOrFail($membership->id);
            if ($pendingOnly && $current->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah diproses. Muat ulang halaman untuk melihat status terbaru.']);
            }
            if ($data['status'] === 'active') {
                if ($data['payment_status'] !== 'paid') {
                    throw ValidationException::withMessages(['payment_status' => 'Membership harus lunas sebelum diaktifkan.']);
                }
                if ($current->status === 'pending' && ! $current->start_date && ! $current->end_date) {
                    $plan = MembershipPlan::lockForUpdate()->findOrFail($current->membership_plan_id);
                    $current->start_date = today();
                    $current->end_date = today()->addDays($plan->duration_days - 1);
                    $data['start_date'] = $current->start_date->toDateString();
                    $data['end_date'] = $current->end_date->toDateString();
                }
                if ($user->status !== 'active' || ! $current->start_date || ! $current->end_date
                    || $current->start_date->gt(today()) || $current->end_date->lt(today())) {
                    throw ValidationException::withMessages(['status' => 'Membership hanya dapat diaktifkan untuk customer aktif dalam periode keanggotaannya.']);
                }
            }
            if ($data['status'] === 'expired' && (! $current->end_date || $current->end_date->gte(today()))) {
                throw ValidationException::withMessages(['status' => 'Periode membership belum berakhir.']);
            }
            if (in_array($data['status'], ['pending', 'active'])) {
                $awaitingApproval = $data['status'] === 'pending' && ! $current->start_date && ! $current->end_date;
                if (! $awaitingApproval && (! $current->start_date || ! $current->end_date || $current->end_date->lt(today()))) {
                    throw ValidationException::withMessages(['status' => 'Periode membership sudah berakhir atau belum lengkap. Tambahkan membership baru untuk perpanjangan.']);
                }
                if (! $awaitingApproval) {
                    $this->ensureNoOverlap($current->user_id, $current->start_date->toDateString(), $current->end_date->toDateString(), $current->id);
                }
            }
            $current->update($data);
        });

    }

    private function ensureNoOverlap(int $userId, string $start, string $end, ?int $except = null): void
    {
        if (Membership::where('user_id', $userId)->whereIn('status', ['pending', 'active'])
            ->when($except, fn ($q) => $q->where('id', '!=', $except))
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->exists()) {
            throw ValidationException::withMessages(['start_date' => 'Customer sudah memiliki membership pada periode tersebut. Pilih tanggal setelah membership sebelumnya berakhir.']);
        }
    }
}
