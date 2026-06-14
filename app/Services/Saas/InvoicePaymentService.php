<?php

namespace App\Services\Saas;

use App\Models\Invoice;
use App\Models\Payment;

class InvoicePaymentService
{
    public function markPaid(Invoice $invoice, string $method = 'manual'): Payment
    {
        $invoice->forceFill([
            'status' => 'paid',
            'paid_at' => now(),
        ])->save();

        return Payment::query()->firstOrCreate(
            [
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'provider_reference' => 'PAY-' . $invoice->invoice_number,
            ],
            [
                'amount' => $invoice->amount,
                'currency' => $invoice->currency ?: 'MAD',
                'provider' => $method,
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => ['source' => 'admin_mark_paid'],
            ],
        );
    }
}
