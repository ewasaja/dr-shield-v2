<?php

namespace App\Livewire\Invoice\CeramicInvoice;

use App\Models\Commission\Commission;
use App\Models\Invoice\Invoice;
use App\Models\Invoice\InvoiceDetail;
use App\Traits\GetSystemSetting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class CeramicInvoiceDetail extends Component
{
    use LivewireAlert, WithPagination, GetSystemSetting;
    protected $paginationTheme = 'bootstrap';
    public $perPage = 10, $search;

    public $due_date_ceramic_rules;
    public $get_invoice, $sales_code, $date, $invoice_number, $customer, $id_customer, $due_date, $income_tax, $value_tax, $amount;
    public $payment_amount, $remaining_amount;
    public $get_invoice_detail, $id_data, $type, $invoice_detail_amount, $invoice_detail_date, $percentage;

    public function render()
    {
        $this->payment_amount   = "Rp. ". number_format((int)$this->get_invoice->invoiceDetails()->sum('amount'), 0, ',', '.');
        $this->remaining_amount = "Rp. ". number_format((int)$this->get_invoice?->amount - (int)$this->get_invoice->invoiceDetails()->sum('amount'), 0, ',', '.');
        return view('livewire.invoice.ceramic-invoice.ceramic-invoice-detail',[
            'invoice_details' => $this->get_invoice?->invoiceDetails()->get(),
        ])->extends('layouts.layout.app')->section('content');
    }

    public function mount($id)
    {
        $this->get_invoice    = Invoice::find($id);
        $this->date           = $this->get_invoice?->date?->format('d M Y');
        $this->invoice_number = $this->get_invoice?->invoice_number;
        $this->sales_code     = $this->get_invoice?->user?->userDetail?->sales_code;
        $this->due_date       = $this->get_invoice?->due_date. " Hari";
        $this->customer       = $this->get_invoice?->customer;
        $this->id_customer    = $this->get_invoice?->id_customer;
        $this->income_tax     = "Rp. ". number_format($this->get_invoice?->income_tax, 0, ',', '.');
        $this->value_tax      = "Rp. ". number_format($this->get_invoice?->value_tax, 0, ',', '.');
        $this->amount         = "Rp. ". number_format($this->get_invoice?->amount, 0, ',', '.');

        $this->due_date_ceramic_rules = $this->get_invoice->dueDateRules()->whereNot('number', 0)->get();
        $this->type                   = 'ceramic';
    }

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updated()
    {
        if ($this->invoice_detail_date) {

            $get_diffDay    = Carbon::parse($this->date)->diffInDays($this->invoice_detail_date);
            $desc_due_dates = $this->get_invoice->dueDateRules()->orderBy('due_date', 'DESC')->get();
            $percentage = null;

            if (Carbon::parse($this->invoice_detail_date)->toDateString() <= Carbon::parse($this->date)->toDateString()) {
                $percentage = 100;
            } else {
                foreach ($desc_due_dates as $key => $desc_due_date) {
                    if ((int)$get_diffDay > (int)$desc_due_date?->due_date) {
                        $percentage = $desc_due_date?->value;
                        break;
                    }
                }
            }
            $this->percentage = $percentage ;
        }
    }

    public function closeModal()
    {
        $this->reset('get_invoice_detail','id_data', 'invoice_detail_amount', 'invoice_detail_date', 'percentage');
        $this->dispatch('closeModal');
    }

    public function saveData()
    {
        $this->validate([
            'type'                  => 'required',
            'invoice_detail_date'   => 'required|date',
            'invoice_detail_amount' => 'required|numeric',
            'percentage'            => 'required|numeric',
        ]);

        if ((int)$this->get_invoice?->amount - ((int)$this->get_invoice->invoiceDetails()->sum('amount') + (int)$this->invoice_detail_amount) < 0 && $this->id_data == null) {
            return $this->alert('warning', 'Pemberitahuan', [
                'text' => 'Nominal pembayaran melebihi total !'
            ]);
        }

        try {
            DB::transaction(function () {
                $this->get_invoice->invoiceDetails()->updateOrCreate(
                    [
                        'id' => $this->id_data,
                    ],
                    [
                        // 'type'       => $this->type,
                        'amount'     => $this->invoice_detail_amount,
                        'date'       => $this->invoice_detail_date,
                        'percentage' => $this->percentage,
                    ]
                );

                $get_commission = Commission::where('user_id', $this->get_invoice?->user?->id)->where('year', (int)$this->get_invoice?->date?->format('Y'))->where('month', (int)$this->get_invoice?->date?->format('m'))->whereNull('category')->first();
                if ($get_commission) {
                    $invoice_details = InvoiceDetail::whereHas('invoice', function ($query) {
                        $query->whereYear('date', (int)$this->get_invoice?->date?->format('Y'))->whereMonth('date', (int)$this->get_invoice?->date?->format('m'))
                            ->where('user_id', $this->get_invoice?->user?->id);
                    });

                    foreach ($invoice_details->distinct()->pluck('percentage')->toArray() as $key => $percentage_invoice_details) {

                        foreach (($invoice_details->selectRaw('YEAR(date) as year, MONTH(date) as month')->groupBy('year', 'month')
                        ->distinct()
                        ->get()
                        ->map(function ($item) {
                            return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                        })
                        ->toArray()) as $key => $year_month_invoice_detail) {

                            $total_income = InvoiceDetail::whereHas('invoice', function ($query) {
                                $query->whereYear('date', (int)$this->get_invoice?->date?->format('Y'))->whereMonth('date', (int)$this->get_invoice?->date?->format('m'))
                                    ->where('user_id', $this->get_invoice?->user?->id);
                            })->whereYear('date', (int)Carbon::parse($year_month_invoice_detail)->format('Y'))->whereMonth('date', (int)Carbon::parse($year_month_invoice_detail)->format('m'))->where('percentage', (int)$percentage_invoice_details)->sum('amount');

                            $get_commission->commissionDetails()->updateOrCreate(
                                [
                                    'year'                   => (int)Carbon::parse($year_month_invoice_detail)->format('Y'),
                                    'month'                  => (int)Carbon::parse($year_month_invoice_detail)->format('m'),
                                    'percentage_of_due_date' => $percentage_invoice_details
                                ],
                                [
                                    'total_income'      => round((int)$total_income/floatval($this->getSystemSetting()?->value_of_total_income)*((int)$percentage_invoice_details/100), 2),
                                    'value_of_due_date' => $get_commission?->percentage_value_commission != null ? round((int)$total_income/floatval($this->getSystemSetting()?->value_of_total_income)*((int)$percentage_invoice_details/100), 2) * ($get_commission?->percentage_value_commission/100) : null
                                ]
                            );
                        }
                    }

                    if ($get_commission->commissionDetails()->whereNot('percentage_of_due_date', 0)->sum('value_of_due_date') > 0) {
                        $get_commission->update([
                            'value_commission' => $get_commission->commissionDetails()->whereNot('percentage_of_due_date', 0)->sum('value_of_due_date')
                        ]);
                    }

                }
            });
        } catch (Exception | Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
            Log::error("Terjadi Kesalahan Saat Menyimpan Data Detail Faktur Keramik!");

            return $this->alert('error', 'Maaf', [
                'text' => 'Terjadi Kesalahan Saat Menyimpan Data Detail Faktur Keramik !'
            ]);
        }
        $this->closeModal();

        return $this->alert('success', 'Berhasil', [
            'text' => 'Data Detail Faktur Keramik Telah Disimpan !'
        ]);
    }

    public function edit($id)
    {
        $this->get_invoice_detail    = Invoice::find($this->get_invoice?->id)->invoiceDetails()->where('id', $id)->first();
        $this->id_data               = $this->get_invoice_detail?->id;
        $this->type                  = $this->get_invoice_detail?->type;
        $this->invoice_detail_date   = $this->get_invoice_detail?->date?->format('Y-m-d');
        $this->invoice_detail_amount = $this->get_invoice_detail?->amount;
        $this->percentage            = $this->get_invoice_detail?->percentage;

        $this->dispatch('openModal');
    }

    public function deleteConfirm($id)
    {
        $this->confirm('Konfirmasi', [
            'inputAttributes'    => ['id' => $id],
            'onConfirmed'        => 'delete',
            'text'               => 'Data yang dihapus tidak dapat di kembalikan lagi',
            'reverseButtons'     => 'true',
            'confirmButtonColor' => '#24B464',
        ]);
    }

    public function getListeners()
    {
        return ['delete'];
    }

    public function delete($data)
    {
        try {
            DB::transaction(function () use ($data) {
                $result = Invoice::find($this->get_invoice?->id)->invoiceDetails()->where('id', $data['inputAttributes']['id'])->first();
                $result?->delete();
            });

            DB::commit();
        } catch (Throwable | Exception $e) {
            DB::rollBack();

            Log::error($e->getMessage());

            return $this->alert('error', 'Maaf', [
                'text' => 'Terjadi Kesalahan Saat Menghapus Data Detail Faktur Keramik!'
            ]);
        }

        $this->closeModal();

        return $this->alert('success', 'Berhasil', [
            'text' => 'Data Detail Faktur Keramik Telah Dihapus !'
        ]);
    }
}