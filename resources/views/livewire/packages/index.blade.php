<?php

use App\Models\MembershipPackage;
use App\Models\MembershipSubscription;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
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

    public function with(): array
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

        return [
            'packages' => $packages,
            'totalPackages' => MembershipPackage::count(),
            'activePackages' => MembershipPackage::where('package_status', 'active')->count(),
            'averagePrice' => MembershipPackage::where('package_status', 'active')->avg('price') ?? 0,
            'activeSubscriptions' => MembershipSubscription::where('subscription_status', 'active')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->whereHas('payment', fn ($payment) => $payment->where('payment_status', 'paid'))
                ->count(),
        ];
    }
};
?>

<div class="awan-page">
    <header class="resource-header">
        <div>
            <span class="eyebrow">MEMBERSHIP</span>
            <h1>Paket Membership</h1>
            <p>Atur pilihan paket, durasi, dan harga membership gym.</p>
        </div>
        <a class="primary-btn" href="{{ route('packages.create') }}" wire:navigate>
            <span>+</span> Tambah Paket
        </a>
    </header>

    <div class="package-stats">
        <article>
            <span>Total paket</span>
            <strong>{{ $totalPackages }}</strong>
            <small>Seluruh paket tersimpan</small>
        </article>
        <article>
            <span>Paket aktif</span>
            <strong class="text-success">{{ $activePackages }}</strong>
            <small>Dapat dipilih untuk transaksi</small>
        </article>
        <article>
            <span>Rata-rata harga</span>
            <strong class="package-stat-currency">Rp {{ number_format($averagePrice, 0, ',', '.') }}</strong>
            <small>Dari paket aktif</small>
        </article>
        <article class="package-stat-featured">
            <span>Subscription aktif</span>
            <strong>{{ $activeSubscriptions }}</strong>
            <small>Dengan pembayaran paid</small>
        </article>
    </div>

    @if(session('success'))
        <div class="notice">{{ session('success') }}</div>
    @endif

    <section class="data-panel">
        <div class="data-toolbar">
            <label class="data-search">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nama paket…">
            </label>

            <label class="data-filter">
                <span>Status</span>
                <select wire:model.live="status">
                    <option value="all">Semua paket</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </label>
        </div>

        <div class="responsive-table">
            <table class="member-table package-table">
                <thead>
                    <tr>
                        <th>Nama Paket</th>
                        <th>Durasi</th>
                        <th>Harga</th>
                        <th>Subscription Aktif</th>
                        <th>Total Digunakan</th>
                        <th>Status</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                        <tr wire:key="package-{{ $package->package_id }}">
                            <td data-label="Paket">
                                <div class="package-name-cell">
                                    <span class="package-icon">{{ strtoupper(substr($package->package_name, 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $package->package_name }}</strong>
                                        <small>{{ $package->description ?: 'Tanpa deskripsi' }}</small>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Durasi">
                                <span class="package-duration">{{ $package->duration_months }}</span>
                                <small class="table-secondary">bulan</small>
                            </td>
                            <td data-label="Harga">
                                <strong class="package-price">Rp {{ number_format($package->price, 0, ',', '.') }}</strong>
                            </td>
                            <td data-label="Subscription Aktif">
                                <span class="usage-count usage-count-active">{{ $package->active_subscriptions_count }}</span>
                            </td>
                            <td data-label="Total Digunakan">
                                <span class="usage-count">{{ $package->subscriptions_count }}</span>
                            </td>
                            <td data-label="Status">
                                <span class="package-status package-status-{{ $package->package_status }}">
                                    <i></i>{{ $package->package_status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td data-label="Aksi" class="action-column">
                                <div class="table-actions">
                                    <a href="{{ route('packages.edit', $package) }}" class="table-action table-action-secondary" wire:navigate>
                                        Edit Paket
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="table-empty">
                                    <strong>Paket tidak ditemukan</strong>
                                    <p>Coba ubah pencarian atau filter status.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($packages->hasPages())
            <div class="data-pagination">{{ $packages->links() }}</div>
        @endif
    </section>
</div>
