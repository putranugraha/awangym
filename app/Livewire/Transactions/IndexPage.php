<?php

namespace App\Livewire\Transactions;

use App\Models\PaymentTransaction;
use Livewire\Component;
use Livewire\WithPagination;

class IndexPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public string $method = 'all';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'method'], true)) {
            $this->resetPage();
        }
    }

    public function verify(int $id): void
    {
        $transaction = PaymentTransaction::findOrFail($id);

        if ($transaction->payment_status !== 'pending') {
            return;
        }

        $transaction->update([
            'payment_status' => 'paid',
            'payment_date' => now(),
            'verified_by' => auth()->id(),
        ]);

        session()->flash('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function render()
    {
        $transactions = PaymentTransaction::with(['member.user', 'subscription.package'])
            ->when($this->search, fn ($query) => $query->where(function ($searchQuery) {
                $searchQuery->where('invoice_number', 'like', "%{$this->search}%")
                    ->orWhereHas('member.user', fn ($user) => $user->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%"));
            }))
            ->when($this->status !== 'all', fn ($query) => $query->where('payment_status', $this->status))
            ->when($this->method !== 'all', fn ($query) => $query->where('payment_method', $this->method))
            ->latest('created_at')
            ->paginate(12);

        $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];

        return view('livewire.transactions.index-page', [
            'transactions' => $transactions,
            'monthlyRevenue' => PaymentTransaction::where('payment_status', 'paid')
                ->whereBetween('payment_date', $thisMonth)->sum('amount'),
            'monthlyPaid' => PaymentTransaction::where('payment_status', 'paid')
                ->whereBetween('payment_date', $thisMonth)->count(),
            'pendingCount' => PaymentTransaction::where('payment_status', 'pending')->count(),
            'refundedCount' => PaymentTransaction::where('payment_status', 'refunded')->count(),
        ])->layout('layouts.app', ['title' => 'Transaksi']);
    }
}
