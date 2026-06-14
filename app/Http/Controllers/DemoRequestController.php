<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Notifications\DemoRequestConfirmationNotification;
use App\Notifications\DemoRequestReceivedNotification;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DemoRequestController extends Controller
{
    public function create()
    {
        return view('demo-request', [
            'targetPlan' => request('plan'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company_size' => ['nullable', 'string', 'max:255'],
            'target_plan' => ['nullable', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:255'],
            'employees_count' => ['nullable', 'integer', 'min:1'],
            'message' => ['nullable', 'string'],
        ]);

        if (empty($data['company_size']) && ! empty($data['employees_count'])) {
            $data['company_size'] = (string) $data['employees_count'];
        }

        $demoRequest = DemoRequest::query()->create([
            ...$data,
            'source' => $request->routeIs('request-demo.store') ? 'request-demo' : 'demo',
            'status' => 'new',
        ]);

        try {
            $adminEmail = config('smartrh.support_email');
            if ($adminEmail) {
                Notification::route('mail', $adminEmail)
                    ->notify(new DemoRequestReceivedNotification($demoRequest));
            }
            Notification::route('mail', $demoRequest->email)
                ->notify(new DemoRequestConfirmationNotification($demoRequest));
        } catch (\Throwable $e) {
            Log::warning('Demo notification failed: ' . $e->getMessage());
        }

        try {
            app(AuditLogger::class)->log('demo_request_created', $demoRequest, [], $demoRequest->only(['full_name', 'company_name', 'phone', 'email']));
        } catch (\Throwable $e) {
            Log::warning('Demo audit log failed: ' . $e->getMessage());
        }

        return redirect()->route('request-demo.thank-you');
    }

    public function thankYou()
    {
        return view('demo-request-thank-you');
    }
}
