<?php

namespace App\Livewire\Commission\RoofCommission;

use App\Models\Auth\User;
use App\Models\Commission\Commission;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class RoofCommissionIndex extends Component
{
    use LivewireAlert, WithPagination;
    protected $paginationTheme = 'bootstrap';
    public $perPage = 10, $search;

    public $filter_month;
    public $selectYear, $selectMonth;

    public function render()
    {
        $users =  User::search($this->search);

        return view('livewire.commission.roof-commission.roof-commission-index', [
            'sales' => $users->role('sales')->whereHas('userDetail', function ($query) {
                $query->where('sales_type', 'roof');
            })->paginate($this->perPage)
        ])->extends('layouts.layout.app')->section('content');
    }

    public function mount()
    {
        $this->filter_month = Carbon::now()->format('Y-m');
        // $this->filter_month = Carbon::parse('2024-04-01')->format('Y-m');
    }

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->reset('');
        $this->dispatch('closeModal');
    }

    public function updated()
    {

    }

    public function commissionSales($user_id, $category)
    {
        $get_commission = Commission::whereHas('user', function ($query) use ($user_id) {
            $query->where('id', $user_id);
        })->where('year', (int)Carbon::parse($this->filter_month)->format('Y'))->where('month', (int)Carbon::parse($this->filter_month)->format('m'))->where('category', $category)->first();

        return $get_commission;
    }

    public function lowerLimiCommissions($user_id, $category)
    {
        $get_commission = Commission::whereHas('user', function ($query) use ($user_id) {
            $query->where('id', $user_id);
        })->where('year', (int)Carbon::parse($this->filter_month)->format('Y'))->where('month', (int)Carbon::parse($this->filter_month)->format('m'))->where('category', $category)->first();

        if (!$get_commission ) {
            return [];
        }
        return $get_commission?->lowerLimitCommissions()->orderBy('value', 'DESC')->get();
    }

    public function totalCommission($user_id, $category)
    {
        $get_commission = Commission::whereHas('user', function ($query) use ($user_id) {
            $query->where('id', $user_id);
        })->where('year', (int)Carbon::parse($this->filter_month)->format('Y'))->where('month', (int)Carbon::parse($this->filter_month)->format('m'))->where('category', $category)->first();

        if (!$get_commission || $get_commission?->status == 'not-reach') {
            return null;
        }

        return $get_commission?->commissionDetails()->whereNotIn('percentage_of_due_date', [0])->sum('value_of_due_date');
    }
}