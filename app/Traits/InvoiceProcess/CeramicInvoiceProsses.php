<?php

namespace App\Traits\InvoiceProcess;

use App\Models\Invoice\DueDateRuleCeramic;
use App\Traits\GetSystemSetting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

trait CeramicInvoiceProsses
{
    //
    use GetSystemSetting;

    public function _ceramicInvoice($invoice, $datas)
    {
        try {
            if ($datas['version'] == 1) {
                $this->_ceramicInvoiceV1($invoice, $datas);
            } elseif ($datas['version'] == 2) {
                $this->_ceramicInvoiceV2($invoice, $datas);
            }

            foreach ($invoice?->invoiceDetails as $key => $invoice_detail) {

                $percentage     = null;
                $get_diffDay    = Carbon::parse($invoice?->date)->diffInDays($invoice_detail?->date);
                $desc_due_dates = $invoice->dueDateRules()->where('version', $datas['version'])->orderBy('due_date', 'DESC')->get();

                if (Carbon::parse($invoice_detail?->date)->toDateString() <= Carbon::parse($invoice?->date)->toDateString()) {
                    $percentage = 100;
                } else {
                    foreach ($desc_due_dates as $key => $desc_due_date) {
                        if ((int)$get_diffDay >= (int)$desc_due_date?->due_date) {
                            $percentage = $desc_due_date?->value;
                            break;
                        }
                    }
                }

                $invoice->invoiceDetails()->where('id', $invoice_detail?->id)->where('version', 1)->first()?->update([
                    'percentage' => $percentage
                ]);
            }
        } catch (Exception | Throwable $th) {
            Log::error("Ada kesalahan saat proses invoice keramik");
            throw new Exception($th->getMessage());
        }
    }

    private function _ceramicInvoiceV1($invoice, $datas)
    {
        try {
            $due_date_ceramic_rules = DueDateRuleCeramic::where('type', 'ceramic')->where('version', 1)->orderBy('due_date', 'ASC')->get();

            foreach ($due_date_ceramic_rules as $key => $due_date_ceramic_rule) {
                $invoice->dueDateRules()->where('version', 1)->updateOrCreate(
                    [
                        'version'  => 1,
                        'due_date' => $due_date_ceramic_rule?->due_date,
                    ],
                    [
                        'version'  => 1,
                        'due_date' => $due_date_ceramic_rule?->due_date,
                        'value'    => $due_date_ceramic_rule?->value,
                    ]
                );
            }
        } catch (Exception | Throwable $th) {
            Log::error("Ada kesalahan saat proses invoice keramik due_date_rules v1");
            throw new Exception($th->getMessage());
        }
    }

    private function _ceramicInvoiceV2($invoice, $datas)
    {
        try {
            $due_date_ceramic_rules = DueDateRuleCeramic::where('type', 'ceramic')->where('version', 2)->orderBy('due_date', 'ASC')->get();

            $invoice->dueDateRules()->where('version', 2)->delete();
            foreach ($due_date_ceramic_rules as $key => $due_date_ceramic_rule) {
                if ($key == 0) {
                    $due_date = (int)$due_date_ceramic_rule?->due_date;
                } else {
                    $due_date = (int)$due_date_ceramic_rule?->due_date + (int)$datas['due_date'];
                }

                $invoice->dueDateRules()->where('version', 2)->updateOrCreate(
                    [
                        'version'  => 2,
                        'due_date' => $due_date,
                    ],
                    [
                        'version'  => 2,
                        'due_date' => $due_date,
                        'value'    => $due_date_ceramic_rule?->value,
                    ]
                );
            }
        } catch (Exception | Throwable $th) {
            Log::error("Ada kesalahan saat proses invoice atap due_date_rules v2");
            throw new Exception($th->getMessage());
        }
    }
}