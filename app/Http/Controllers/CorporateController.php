<?php

namespace App\Http\Controllers;

use App\Models\Banking\Account;
use App\Models\Banking\BulkPayment;
use App\Models\Banking\BusinessProfile;
use App\Models\Banking\CorporateUser;
use App\Services\CorporateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CorporateController extends Controller
{
    public function __construct(
        private readonly CorporateService $corporateService,
    ) {}

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $business = BusinessProfile::where('user_id', $user->id)->first();

        if (!$business) {
            return Inertia::render('corporate/business-profile', [
                'business' => null,
            ]);
        }

        $team = CorporateUser::where('business_profile_id', $business->id)
            ->with('user')
            ->get();

        $recentBulkPayments = BulkPayment::where('business_profile_id', $business->id)
            ->withCount('items')
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('corporate/dashboard', [
            'business' => [
                'id' => $business->id,
                'company_name' => $business->company_name,
                'registration_number' => $business->registration_number,
                'business_type' => $business->business_type,
                'status' => $business->status,
                'verified_at' => $business->verified_at,
            ],
            'team' => $team->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->user->name,
                'email' => $m->user->email,
                'role' => $m->role,
                'status' => $m->status,
            ]),
            'recent_bulk_payments' => $recentBulkPayments->map(fn($b) => [
                'id' => $b->id,
                'batch_reference' => $b->batch_reference,
                'total_amount' => (float) $b->total_amount,
                'total_transactions' => $b->total_transactions,
                'status' => $b->status,
                'created_at' => $b->created_at,
            ]),
        ]);
    }

    public function businessProfile(Request $request)
    {
        $user = $request->user();
        $business = BusinessProfile::where('user_id', $user->id)->first();

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'registration_number' => 'nullable|string|max:100',
                'tax_id' => 'nullable|string|max:100',
                'vat_number' => 'nullable|string|max:50',
                'registered_address' => 'nullable|string|max:500',
                'business_type' => 'nullable|string|max:100',
                'industry' => 'nullable|string|max:100',
                'website' => 'nullable|url|max:255',
                'contact_email' => 'required|email|max:255',
                'contact_phone' => 'nullable|string|max:30',
            ]);

            try {
                $business = $this->corporateService->createBusinessProfile($user, $validated);
                return redirect()->route('corporate.dashboard')->with('success', 'Business profile created');
            } catch (\Exception $e) {
                return back()->withErrors(['company_name' => $e->getMessage()]);
            }
        }

        return Inertia::render('corporate/business-profile', [
            'business' => $business ? [
                'id' => $business->id,
                'company_name' => $business->company_name,
                'registration_number' => $business->registration_number,
                'tax_id' => $business->tax_id,
                'vat_number' => $business->vat_number,
                'registered_address' => $business->registered_address,
                'business_type' => $business->business_type,
                'industry' => $business->industry,
                'website' => $business->website,
                'contact_email' => $business->contact_email,
                'contact_phone' => $business->contact_phone,
                'founded_year' => $business->founded_year,
                'employee_count' => $business->employee_count,
                'annual_revenue' => $business->annual_revenue,
                'status' => $business->status,
            ] : null,
        ]);
    }

    public function team(Request $request)
    {
        $user = $request->user();
        $business = BusinessProfile::where('user_id', $user->id)->first();

        if (!$business) {
            return redirect()->route('corporate.business-profile');
        }

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email',
                'role' => 'required|in:admin,finance,operator,viewer',
                'spending_limit' => 'nullable|numeric|min:0',
            ]);

            $invitee = \App\Models\User::where('email', $validated['email'])->first();

            try {
                $this->corporateService->inviteTeamMember(
                    $business,
                    $invitee,
                    $validated['role'],
                    $validated['spending_limit'],
                );

                return redirect()->route('corporate.team')->with('success', 'Team member added');
            } catch (\Exception $e) {
                return back()->withErrors(['email' => $e->getMessage()]);
            }
        }

        $team = CorporateUser::where('business_profile_id', $business->id)
            ->with('user')
            ->get();

        return Inertia::render('corporate/team', [
            'team' => $team->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->user->name,
                'email' => $m->user->email,
                'role' => $m->role,
                'spending_limit' => (float) ($m->spending_limit ?? 0),
                'status' => $m->status,
                'accepted_at' => $m->accepted_at,
            ]),
        ]);
    }

    public function bulkPayments(Request $request)
    {
        $user = $request->user();
        $business = BusinessProfile::where('user_id', $user->id)->first();

        if (!$business) {
            return redirect()->route('corporate.business-profile');
        }

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'debit_account_id' => 'required|exists:accounts,id',
                'type' => 'required|string|max:50',
                'payments' => 'required|array|min:1|max:500',
                'payments.*.beneficiary_name' => 'required|string|max:255',
                'payments.*.beneficiary_iban' => 'required|string|max:34',
                'payments.*.amount' => 'required|numeric|min:0.01',
                'payments.*.reference' => 'nullable|string|max:255',
            ]);

            $debitAccount = Account::findOrFail($validated['debit_account_id']);

            if ($debitAccount->user_id !== $user->id) {
                return back()->withErrors(['debit_account_id' => 'Account not found']);
            }

            try {
                $this->corporateService->processBulkPayment(
                    $business,
                    $debitAccount,
                    $validated['payments'],
                    $validated['type'],
                );

                return redirect()->route('corporate.bulk-payments')->with('success', 'Bulk payment processed');
            } catch (\Exception $e) {
                return back()->withErrors(['payments' => $e->getMessage()]);
            }
        }

        $payments = BulkPayment::where('business_profile_id', $business->id)
            ->withCount('items')
            ->latest()
            ->paginate(20);

        $accounts = $user->accounts()->where('status', 'active')->get();

        return Inertia::render('corporate/bulk-payments', [
            'payments' => $payments,
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'number' => $a->account_number,
                'label' => $a->label,
                'balance' => (float) $a->balance,
                'currency' => $a->currency,
            ]),
        ]);
    }
}
