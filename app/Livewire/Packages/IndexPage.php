<?php

namespace App\Livewire\Packages;

use App\Models\MembershipPackage;
use App\Models\MembershipSubscription;
use Livewire\Component;
use Livewire\WithPagination;

class IndexPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $packages = MembershipPackage::withCount([
            'subscriptions',
            'subscriptions as active_subscriptions_count' => fn ($query) => $query
                ->where('subscription_status', 'active')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->whereHas('payment', fn ($payment) => $payment->where('payment_status', 'paid')),
        ])
            ->when($this->search, fn ($query) => $query->where('package_name', 'like', "%{$this->search}%"))
            ->when($this->status !== 'all', fn ($query) => $query->where('package_status', $this->status))
            ->latest()
            ->paginate(12);

        return view('livewire.packages.index-page', [
            'packages' => $packages,
            'totalPackages' => MembershipPackage::count(),
            'activePackages' => MembershipPackage::where('package_status', 'active')->count(),
            'averagePrice' => MembershipPackage::where('package_status', 'active')->avg('price') ?? 0,
            'activeSubscriptions' => MembershipSubscription::where('subscription_status', 'active')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->whereHas('payment', fn ($payment) => $payment->where('payment_status', 'paid'))
                ->count(),
        ])->layout('layouts.app', ['title' => 'Paket Membership']);
    }
}
