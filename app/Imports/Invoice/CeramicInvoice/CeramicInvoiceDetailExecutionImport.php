<?php

namespace App\Imports\Invoice\CeramicInvoice;

use App\Models\Commission\Commission;
use App\Models\Invoice\Invoice;
use App\Models\Invoice\InvoiceDetail;
use App\Traits\CommissionProcess;
use App\Traits\GetSystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class CeramicInvoiceDetailExecutionImport implements ToCollection
{
    use GetSystemSetting, CommissionProcess;
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collections)
    {
        //
        try {
            foreach ($collections as $key => $collection) {
                if ($key == 0) {
                    continue;
                }

                $get_invoice = Invoice::where('invoice_number', $collection[0])->first();

                if (!$get_invoice) {
                    continue;
                }

                DB::transaction(function () use ($get_invoice, $collection) {
                    $get_invoice->invoiceDetails()->create(
                        [
                            'amount'     => $collection[1],
                            'date'       => Carbon::parse($collection[2])->toDateString(),
                            'percentage' => $this->percentageInvoiceDetail($get_invoice, Carbon::parse($collection[2])->toDateString()),
                        ]
                    );

                    $datas = array();
                    $this->ceramicCommissionDetail($get_invoice, $datas);
                });
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            Log::error("Ada kesalahan saat import detail faktur keramik");
        }
    }

    private function percentageInvoiceDetail($get_invoice, $invoice_detail_date)
    {
        $get_diffDay    = Carbon::parse($get_invoice?->date?->format('d M Y'))->diffInDays($invoice_detail_date);
        $desc_due_dates = $get_invoice->dueDateRules()->orderBy('due_date', 'DESC')->get();
        $percentage     = 0;

        if (Carbon::parse($invoice_detail_date)->toDateString() <= Carbon::parse($get_invoice?->date?->format('d M Y'))->toDateString()) {
            $percentage = 100;
        } else {
            foreach ($desc_due_dates as $key => $desc_due_date) {
                if ((int)$get_diffDay > (int)$desc_due_date?->due_date) {
                    $percentage = $desc_due_date?->value;
                    break;
                }
            }
        }

        return $percentage;
    }
}