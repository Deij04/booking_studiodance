<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Services\MembershipEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CustomerMembershipController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeCustomer($request);

        return view('user.memberships.index', [
            'plans' => MembershipPlan::with('studio')->where('status', 'active')->orderBy('price')->get(),
            'memberships' => $request->user()->memberships()->with('membershipPlan')->latest('id')->paginate(10),
        ]);
    }

    public function payment(Request $request, MembershipPlan $membershipPlan)
    {
        $this->authorizeCustomer($request);
        abort_unless($membershipPlan->status === 'active', 404);

        return view('user.memberships.payment', ['plan' => $membershipPlan->load('studio')]);
    }

    public function store(Request $request, MembershipEnrollment $enrollment)
    {
        $this->authorizeCustomer($request);
        $data = $request->validate([
            'membership_plan_id' => ['required', Rule::exists('membership_plans', 'id')->where('status', 'active')],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);
        $proof = $request->file('payment_proof')->store('membership-payment-proofs', 'local');
        abort_if($proof === false, 500, 'Bukti pembayaran gagal disimpan.');
        try {
            DB::transaction(function () use ($data, $enrollment, $request, $proof) {
                $membership = $enrollment->create([
                    'membership_plan_id' => $data['membership_plan_id'],
                    'user_id' => $request->user()->id,
                ]);
                $membership->update(['payment_proof' => $proof]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($proof);
            throw $exception;
        }

        return to_route('memberships.index')->with('success', 'Your payment proof and membership application have been submitted. Please wait for admin verification. Your membership starts after approval.');
    }

    private function authorizeCustomer(Request $request): void
    {
        abort_unless($request->user()->role === 'customer' && $request->user()->status === 'active', 403);
    }
}
