<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Services\Saas\SubscriptionManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class OnboardingController extends Controller
{
    public function company()
    {
        return view('onboarding.company');
    }

    public function storeCompany(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'ice' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $company = Company::query()->create([
            ...$data,
            'status' => 'trialing',
        ]);

        $request->session()->put('onboarding.company_id', $company->id);

        return redirect()->route('onboarding.plan');
    }

    public function plan()
    {
        $this->ensureCompanyStep();

        return view('onboarding.plan', [
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function storePlan(Request $request)
    {
        $this->ensureCompanyStep();

        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $request->session()->put('onboarding.plan_id', (int) $data['plan_id']);

        return redirect()->route('onboarding.admin-user');
    }

    public function adminUser()
    {
        $this->ensureCompanyStep();
        $this->ensurePlanStep();

        return view('onboarding.admin-user');
    }

    public function storeAdminUser(Request $request, SubscriptionManagementService $subscriptions)
    {
        $company = $this->ensureCompanyStep();
        $plan = $this->ensurePlanStep();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Role::findOrCreate('Company Owner');
        $user->assignRole('Company Owner');

        $subscriptions->startTrial($company, $plan);
        Auth::login($user);
        $request->session()->forget('onboarding');

        return redirect()->route('onboarding.complete');
    }

    public function complete()
    {
        if (! Auth::check()) {
            return redirect()->route('onboarding.company');
        }

        return view('onboarding.complete', [
            'user' => Auth::user(),
        ]);
    }

    private function ensureCompanyStep(): Company
    {
        $company = Company::query()->find(session('onboarding.company_id'));
        if (! $company) {
            throw new HttpResponseException(redirect()->route('onboarding.company'));
        }

        return $company;
    }

    private function ensurePlanStep(): Plan
    {
        $plan = Plan::query()->find(session('onboarding.plan_id'));
        if (! $plan) {
            throw new HttpResponseException(redirect()->route('onboarding.plan'));
        }

        return $plan;
    }
}
