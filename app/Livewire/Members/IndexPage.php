<?php

namespace App\Livewire\Members;

use App\Models\Member;
use App\Services\MembershipStatusService;
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

    public function render(MembershipStatusService $statusService)
    {
        $validSubscription = fn ($query) => $query
            ->where('subscription_status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->whereHas('payment', fn ($payment) => $payment->where('payment_status', 'paid'));

        $members = Member::with(['user', 'subscriptions' => fn ($query) => $query->with(['package', 'payment'])->latest('end_date')])
            ->when($this->search, fn ($query) => $query->where(function ($searchQuery) {
                $searchQuery->where('member_code', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%"));
            }))
            ->when($this->status === 'active', fn ($query) => $query->whereHas('subscriptions', $validSubscription))
            ->when($this->status === 'expiring', fn ($query) => $query->whereHas('subscriptions', fn ($subscription) => $validSubscription($subscription)
                ->whereBetween('end_date', [today(), today()->addDays(7)])))
            ->when($this->status === 'inactive', fn ($query) => $query->whereDoesntHave('subscriptions', $validSubscription))
            ->latest()->paginate(12);

        return view('livewire.members.index-page', [
            'members' => $members,
            'statusService' => $statusService,
            'totalMembers' => Member::count(),
            'activeMembers' => Member::whereHas('subscriptions', $validSubscription)->count(),
            'expiringMembers' => Member::whereHas('subscriptions', fn ($subscription) => $validSubscription($subscription)
                ->whereBetween('end_date', [today(), today()->addDays(7)]))->count(),
        ])->layout('layouts.app', ['title' => 'Member']);
    }
}
