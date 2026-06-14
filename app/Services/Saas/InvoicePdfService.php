<?php

namespace App\Services\Saas;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['company', 'subscription.plan', 'payments']);

        $path = sprintf(
            'companies/%s/billing/invoices/%s.pdf',
            $invoice->company_id,
            $this->filename($invoice),
        );

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice])->setPaper('a4');
        Storage::disk(config('filesystems.private_disk'))->put($path, $pdf->output());

        $invoice->forceFill(['pdf_path' => $path])->save();

        return $path;
    }

    public function filename(Invoice $invoice): string
    {
        $invoiceNumber = Str::of($invoice->invoice_number ?: (string) $invoice->id)
            ->replaceMatches('/[^A-Za-z0-9_-]+/', '-')
            ->trim('-');

        return 'facture-' . ($invoiceNumber->isNotEmpty() ? $invoiceNumber : $invoice->id);
    }
}
