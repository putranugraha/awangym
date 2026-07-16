<?php

use App\Models\MembershipSubscription;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public function with(): array
    {
        $trainer = auth()->user()->personalTrainer;

        if (! $trainer) {
            return ['subscriptions' => collect(), 'totalMembers' => 0, 'totalSessions' => 0, 'remainingSessions' => 0];
        }

        $query = MembershipSubscription::query()
            ->with(['member.user', 'package', 'payment'])
            ->withCount('trainerSessions')
            ->where('trainer_id', $trainer->trainer_id)
            ->current()
            ->when($this->search, function ($query) {
                $query->where(function ($search) {
                    $search->whereHas('member.user', fn ($user) => $user->where('full_name', 'like', "%{$this->search}%"))
                        ->orWhereHas('member', fn ($member) => $member->where('member_code', 'like', "%{$this->search}%"));
                });
            })
            ->latest('start_date');

        $subscriptions = $query->get();

        return [
            'subscriptions' => $subscriptions,
            'totalMembers' => $subscriptions->pluck('member_id')->unique()->count(),
            'totalSessions' => $subscriptions->sum('trainer_sessions_count'),
            'remainingSessions' => $subscriptions->sum(fn ($item) => max(($item->trainer_session_limit ?? 0) - $item->trainer_sessions_count, 0)),
        ];
    }
};
?>

<div class="awan-page">
    <header class="resource-header">
        <div><span class="eyebrow">PENDAMPINGAN PT</span><h1>Member Binaan</h1><p>Catat pertemuan member sesuai periode dan kuota paket personal trainer.</p></div>
    </header>

    <div class="resource-stats">
        <article><span>Total member</span><strong>{{ $totalMembers }}</strong></article>
        <article><span>Sesi tercatat</span><strong class="text-success">{{ $totalSessions }}</strong></article>
        <article><span>Sisa sesi</span><strong class="text-warning">{{ $remainingSessions }}</strong></article>
    </div>

    <section class="data-panel">
        <div class="data-toolbar">
            <label class="data-search">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau kode member…">
            </label>
        </div>

        <div class="responsive-table">
            <table class="member-table">
                <thead><tr><th>Member</th><th>Paket</th><th>Pertemuan</th><th>Periode</th><th class="action-column">Aksi</th></tr></thead>
                <tbody>
                    @forelse($subscriptions as $subscription)
                        @php
                            $remaining = max(($subscription->trainer_session_limit ?? 0) - $subscription->trainer_sessions_count, 0);
                        @endphp
                        <tr wire:key="subscription-{{ $subscription->subscription_id }}">
                            <td data-label="Member"><div class="member-cell"><span class="member-table-avatar">{{ $subscription->member->user->initials() }}</span><span><strong>{{ $subscription->member->user->full_name }}</strong><small>{{ $subscription->member->member_code }}</small></span></div></td>
                            <td data-label="Paket"><span class="table-primary">{{ $subscription->package->package_name }}</span><small class="table-secondary">Dengan personal trainer</small></td>
                            <td data-label="Pertemuan"><span class="usage-count usage-count-active">{{ $subscription->trainer_sessions_count }}/{{ $subscription->trainer_session_limit }}</span><small class="table-secondary">Sisa {{ $remaining }} sesi</small></td>
                            <td data-label="Periode"><span class="table-primary">{{ $subscription->start_date->format('d M Y') }}</span><small class="table-secondary">s/d {{ $subscription->end_date->format('d M Y') }}</small></td>
                            <td data-label="Aksi" class="action-column"><a class="table-action table-action-primary" href="{{ route('trainer-members.show', $subscription) }}" wire:navigate>Detail Pertemuan</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="table-empty"><strong>Member tidak ditemukan</strong><p>Belum ada member dari paket PT berbayar yang ditugaskan kepada Anda.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
