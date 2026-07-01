<?php

namespace App\Livewire\Transactions;

use App\Models\Member;
use App\Models\MembershipPackage;
use App\Models\MembershipSubscription;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

class CreatePage extends Component
{
    #[Url(as: 'member')]
    public int|string $member_id = '';

    public int|string $package_id = '';

    public string $payment_method = '';

    public string $payment_status = 'pending';

    public string $start_date = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->start_date = today()->format('Y-m-d');
    }

    public function save(): void
    {
        $d = $this->validate(['member_id' => ['required', 'exists:members,member_id'], 'package_id' => ['required', 'exists:membership_packages,package_id'], 'payment_method' => ['required', 'in:cash,transfer,e_wallet'], 'payment_status' => ['required', 'in:pending,paid'], 'start_date' => ['nullable', 'date'], 'notes' => ['nullable']]);
        DB::transaction(function () use ($d) {
            $p = MembershipPackage::findOrFail($d['package_id']);
            $last = MembershipSubscription::where('member_id', $d['member_id'])->latest('end_date')->first();
            $start = $last && $last->end_date->gte(today()) ? $last->end_date->copy()->addDay() : now()->parse($d['start_date']);
            $s = MembershipSubscription::create([...$d, 'created_by' => auth()->id(), 'subscription_type' => $last ? 'renewal' : 'new_registration', 'start_date' => $start, 'end_date' => $start->copy()->addDays($p->duration_days - 1), 'subscription_status' => 'active']);
            PaymentTransaction::create([...$d, 'subscription_id' => $s->subscription_id, 'amount' => $p->price, 'invoice_number' => 'INV-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)), 'payment_date' => $d['payment_status'] === 'paid' ? now() : null, 'verified_by' => $d['payment_status'] === 'paid' ? auth()->id() : null, 'created_at' => now()]);
        });
        session()->flash('success', 'Transaksi berhasil dibuat.');
        $this->redirectRoute('admin.transactions', navigate: true);
    }

    public function render()
    {
        return view('livewire.transactions.create-page', ['members' => Member::with('user')->get(), 'packages' => MembershipPackage::where('package_status', 'active')->get()])->layout('layouts.app', ['title' => 'Buat Transaksi']);
    }
}
