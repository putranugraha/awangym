<?php

namespace App\Livewire\Transactions;

use App\Models\PaymentTransaction;
use Livewire\Component;

class EditPage extends Component
{
    public PaymentTransaction $transaction;

    public string $payment_method = 'cash';

    public string $payment_status = 'pending';

    public string $notes = '';

    public function mount(PaymentTransaction $transaction): void
    {
        $this->transaction = $transaction->load(['member.user', 'subscription.package']);
        $this->fill($transaction->only(['payment_method', 'payment_status']));
        $this->notes = $transaction->notes ?? '';
    }

    public function save(): void
    {
        $d = $this->validate(['payment_method' => ['required', 'in:cash,transfer,e_wallet'], 'payment_status' => ['required', 'in:pending,paid,failed,refunded'], 'notes' => ['nullable']]);
        if ($d['payment_status'] === 'paid' && $this->transaction->payment_status !== 'paid') {
            $d += ['payment_date' => now(), 'verified_by' => auth()->id()];
        }$this->transaction->update($d);
        session()->flash('success', 'Transaksi berhasil diperbarui.');
        $this->redirectRoute('admin.transactions', navigate: true);
    }

    public function render()
    {
        return view('livewire.transactions.edit-page')->layout('layouts.app', ['title' => 'Edit Transaksi']);
    }
}
