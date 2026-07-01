<div class="awan-page">
    <header class="resource-header">
        <div>
            <span class="eyebrow">OPERASIONAL MEMBER</span>
            <h1>Kelola Member</h1>
            <p>Pantau data akun dan status membership dalam satu tempat.</p>
        </div>
        <a class="primary-btn" href="{{ route('admin.members.create') }}" wire:navigate>
            <span>+</span> Tambah Member
        </a>
    </header>

    <div class="resource-stats">
        <article><span>Total member</span><strong>{{ $totalMembers }}</strong></article>
        <article><span>Membership aktif</span><strong class="text-success">{{ $activeMembers }}</strong></article>
        <article><span>Segera berakhir</span><strong class="text-warning">{{ $expiringMembers }}</strong></article>
    </div>

    @if(session('success'))
        <div class="notice">{{ session('success') }}</div>
    @endif

    <section class="data-panel">
        <div class="data-toolbar">
            <label class="data-search">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nama, kode, atau email…">
            </label>

            <label class="data-filter">
                <span>Status</span>
                <select wire:model.live="status">
                    <option value="all">Semua membership</option>
                    <option value="active">Aktif</option>
                    <option value="expiring">Segera berakhir</option>
                    <option value="inactive">Tidak aktif</option>
                </select>
            </label>
        </div>

        <div class="responsive-table">
            <table class="member-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Kontak</th>
                        <th>Paket Membership</th>
                        <th>Berlaku Hingga</th>
                        <th>Status</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        @php
                            $subscription = $member->subscriptions->first(function ($item) use ($statusService) {
                                return in_array($statusService->resolve($item)['key'], ['active', 'expiring'], true);
                            }) ?? $member->subscriptions->first();
                            $membershipStatus = $statusService->resolve($subscription);
                        @endphp
                        <tr wire:key="member-{{ $member->member_id }}">
                            <td data-label="Member">
                                <div class="member-cell">
                                    <span class="member-table-avatar">{{ $member->user->initials() }}</span>
                                    <span>
                                        <strong>{{ $member->user->full_name }}</strong>
                                        <small>{{ $member->member_code }}</small>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Kontak">
                                <span class="table-primary">{{ $member->user->email }}</span>
                                <small class="table-secondary">{{ $member->user->phone }}</small>
                            </td>
                            <td data-label="Paket">
                                <span class="table-primary">{{ $subscription?->package?->package_name ?? 'Belum ada paket' }}</span>
                                @if($subscription)
                                    <small class="table-secondary">{{ $subscription->subscription_type === 'renewal' ? 'Perpanjangan' : 'Pendaftaran baru' }}</small>
                                @endif
                            </td>
                            <td data-label="Berlaku Hingga">
                                <span class="table-primary">{{ $subscription?->end_date?->translatedFormat('d M Y') ?? '—' }}</span>
                                @if($membershipStatus['days_left'] > 0)
                                    <small class="table-secondary">{{ $membershipStatus['days_left'] }} hari tersisa</small>
                                @endif
                            </td>
                            <td data-label="Status">
                                <span class="table-status table-status-{{ $membershipStatus['key'] }}">
                                    <i></i>{{ $membershipStatus['label'] }}
                                </span>
                            </td>
                            <td data-label="Aksi" class="action-column">
                                <div class="table-actions">
                                    <a href="{{ route('admin.members.edit', $member) }}" class="table-action table-action-secondary" wire:navigate>
                                        Edit
                                    </a>
                                    <a href="{{ route('admin.transactions.create', ['member' => $member->member_id]) }}" class="table-action table-action-primary" wire:navigate>
                                        Transaksi
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="table-empty">
                                    <strong>Member tidak ditemukan</strong>
                                    <p>Coba ubah kata pencarian atau filter status.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($members->hasPages())
            <div class="data-pagination">{{ $members->links() }}</div>
        @endif
    </section>
</div>
