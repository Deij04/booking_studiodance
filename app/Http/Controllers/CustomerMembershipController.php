<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Services\MembershipEnrollment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerMembershipController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeCustomer($request);

        return view('memberships.index', [
            'plans' => MembershipPlan::where('status', 'active')->orderBy('price')->get(),
            'memberships' => $request->user()->memberships()->with('membershipPlan')->latest('id')->paginate(10),
        ]);
    }

    public function store(Request $request, MembershipEnrollment $enrollment)
    {
        $this->authorizeCustomer($request);
        $data = $request->validate([
            'membership_plan_id' => ['required', Rule::exists('membership_plans', 'id')->where('status', 'active')],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before:9990-01-01'],
        ]);
        $enrollment->create($data + ['user_id' => $request->user()->id]);

        return to_route('memberships.index')->with('success', 'Pengajuan membership terkirim. Silakan hubungi admin untuk pembayaran dan persetujuan.');
    }

    private function authorizeCustomer(Request $request): void
    {
        abort_unless($request->user()->role === 'customer' && $request->user()->status === 'active', 403);
    }
}
