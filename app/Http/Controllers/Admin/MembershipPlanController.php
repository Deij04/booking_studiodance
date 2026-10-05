<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MembershipPlanController extends Controller
{
    public function create()
    {
        return view('admin.memberships.plan-form', ['plan' => new MembershipPlan(['status' => 'active'])]);
    }

    public function store(Request $request)
    {
        MembershipPlan::create($this->validated($request));

        return to_route('admin.memberships.index', ['tab' => 'plans'])->with('success', 'Paket membership berhasil ditambahkan.');
    }

    public function edit(MembershipPlan $membershipPlan)
    {
        return view('admin.memberships.plan-form', ['plan' => $membershipPlan]);
    }

    public function update(Request $request, MembershipPlan $membershipPlan)
    {
        $membershipPlan->update($this->validated($request));

        return to_route('admin.memberships.index', ['tab' => 'plans'])->with('success', 'Paket membership berhasil diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
