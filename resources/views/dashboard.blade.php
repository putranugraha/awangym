<x-layouts::app :title="__('Dashboard')">
    <div class="awan-page">
        <header class="awan-header">
            <div>
                <span class="eyebrow">AWAN GYM</span>
                <h1>Halo, {{ auth()->user()->full_name }}</h1>
                <p>{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
        </header>

        @if(auth()->user()->hasRole('member'))
            <section class="membership-card">
                <div class="card-glow"></div>
                <div class="membership-brand"><strong>AWAN GYM</strong><span>DIGITAL MEMBERSHIP</span></div>
                <div class="member-identity">
                    <div class="member-photo">
                        @if($member->profile_photo)<img src="{{ Storage::url($member->profile_photo) }}" alt="Foto {{ $member->user->full_name }}">@else{{ $member->user->initials() }}@endif
                    </div>
                    <div><h2>{{ $member->user->full_name }}</h2><p>{{ $member->member_code }}</p></div>
                </div>
                <div class="status-row">
                    <span class="status status-{{ $membershipStatus['key'] }}">● {{ strtoupper($membershipStatus['label']) }}</span>
                    <span>{{ $subscription?->package?->package_name ?? 'Belum ada paket' }}</span>
                </div>
                <div class="validity"><small>Berlaku hingga</small><strong>{{ $subscription?->end_date?->translatedFormat('d F Y') ?? '—' }}</strong></div>
            </section>

            <section class="summary-card">
                <div><span class="metric-number">{{ $membershipStatus['days_left'] }}</span><span> hari tersisa</span></div>
                <p>Status dihitung langsung dari periode aktif dan pembayaran terverifikasi.</p>
            </section>

            <section>
                <div class="section-title"><h2>Program latihan aktif</h2></div>
                @forelse($member->programs as $assignment)
                    <article class="list-card">
                        <div><span class="chip">{{ ucfirst($assignment->program->difficulty_level) }}</span><h3>{{ $assignment->program->program_name }}</h3><p>{{ $assignment->trainer ? 'Trainer '.$assignment->trainer->user->full_name : 'Latihan mandiri' }}</p></div>
                        <strong>{{ number_format($assignment->progress_percentage) }}%</strong>
                    </article>
                @empty
                    <div class="empty-card"><strong>Belum ada program aktif</strong><p>Program dari personal trainer akan muncul di sini.</p></div>
                @endforelse
            </section>
        @elseif(auth()->user()->hasRole('personal_trainer'))
            <div class="metric-grid">
                <article class="metric-card"><span>Member binaan</span><strong>{{ $memberCount }}</strong></article>
                <article class="metric-card accent"><span>Program aktif</span><strong>{{ $activePrograms }}</strong></article>
            </div>
            <section class="empty-card"><h2>Pendampingan member</h2><p>Lihat member binaan dan validasi gerakan yang dilakukan bersama di gym.</p><a class="primary-btn" href="{{ route('trainer-members.index') }}">Buka Member Binaan</a></section>
        @else
            <section class="dashboard-welcome">
                <div>
                    <span class="dashboard-welcome-label">RINGKASAN OPERASIONAL</span>
                    <h2>Pantau performa gym hari ini.</h2>
                    <p>Data membership dan pembayaran diperbarui dari transaksi terverifikasi.</p>
                </div>
                <div class="dashboard-quick-actions">
                    <a href="{{ route('members.create') }}" wire:navigate>+ Tambah Member</a>
                    <a href="{{ route('transactions.create') }}" wire:navigate>+ Buat Transaksi</a>
                </div>
            </section>

            <div class="dashboard-metrics">
                <article class="dashboard-metric dashboard-metric-primary">
                    <span class="dashboard-metric-icon">M</span>
                    <div><small>Member aktif</small><strong>{{ $activeMembers }}</strong><p>Membership terverifikasi</p></div>
                </article>
                <article class="dashboard-metric">
                    <span class="dashboard-metric-icon warning">!</span>
                    <div><small>Segera berakhir</small><strong>{{ $expiringMembers }}</strong><p>Dalam tujuh hari</p></div>
                </article>
                <article class="dashboard-metric">
                    <span class="dashboard-metric-icon revenue">Rp</span>
                    <div><small>Pendapatan bulan ini</small><strong class="dashboard-currency">Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}</strong><p class="{{ $revenueGrowth >= 0 ? 'trend-up' : 'trend-down' }}">{{ $revenueGrowth >= 0 ? '↑' : '↓' }} {{ abs($revenueGrowth) }}% dari bulan lalu</p></div>
                </article>
                <article class="dashboard-metric">
                    <span class="dashboard-metric-icon pending">⌛</span>
                    <div><small>Menunggu verifikasi</small><strong>{{ $pendingPayments }}</strong><p>Perlu tindakan admin</p></div>
                </article>
            </div>

            @php
                $revenueConfig = [
                    'type' => 'line',
                    'data' => [
                        'labels' => $revenueChart['labels'],
                        'datasets' => [[
                            'data' => $revenueChart['values'],
                            'borderColor' => '#E52335',
                            'backgroundColor' => 'rgba(229,35,53,.1)',
                            'fill' => true,
                            'tension' => .42,
                            'pointRadius' => 3,
                            'pointBackgroundColor' => '#E52335',
                        ]],
                    ],
                    'options' => [
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'plugins' => ['legend' => ['display' => false]],
                        'scales' => [
                            'x' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                            'y' => ['beginAtZero' => true, 'border' => ['display' => false], 'ticks' => ['maxTicksLimit' => 5]],
                        ],
                    ],
                ];
                $membershipConfig = [
                    'type' => 'doughnut',
                    'data' => [
                        'labels' => $membershipChart['labels'],
                        'datasets' => [[
                            'data' => $membershipChart['values'],
                            'backgroundColor' => ['#24C96B', '#F5A524', '#D1D5DB'],
                            'borderWidth' => 0,
                            'hoverOffset' => 4,
                        ]],
                    ],
                    'options' => [
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'cutout' => '72%',
                        'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'padding' => 18]]],
                    ],
                ];
            @endphp

            <div class="dashboard-chart-grid">
                <section class="dashboard-panel dashboard-panel-wide">
                    <div class="dashboard-panel-head"><div><span>KEUANGAN</span><h2>Tren Pendapatan</h2></div><a href="{{ route('reports.index') }}">Lihat laporan</a></div>
                    <div class="chart-box"><canvas data-dashboard-chart='@json($revenueConfig)'></canvas></div>
                </section>
                <section class="dashboard-panel">
                    <div class="dashboard-panel-head"><div><span>MEMBERSHIP</span><h2>Komposisi Status</h2></div></div>
                    <div class="chart-box chart-box-donut"><canvas data-dashboard-chart='@json($membershipConfig)'></canvas></div>
                </section>
            </div>

            <div class="dashboard-bottom-grid">
                <section class="dashboard-panel">
                    <div class="dashboard-panel-head"><div><span>MEMBER</span><h2>Member Terbaru</h2></div><a href="{{ route('members.index') }}">Lihat semua</a></div>
                    <div class="dashboard-list">
                        @forelse($recentMembers as $item)
                            <a href="{{ route('members.edit', $item) }}" class="dashboard-list-item" wire:navigate>
                                <span class="mini-avatar">{{ $item->user->initials() }}</span>
                                <span class="grow"><strong>{{ $item->user->full_name }}</strong><small>{{ $item->member_code }} · Terdaftar {{ $item->registered_at->format('d M Y') }}</small></span>
                                <span class="dashboard-arrow">→</span>
                            </a>
                        @empty
                            <div class="dashboard-empty">Belum ada member terdaftar.</div>
                        @endforelse
                    </div>
                </section>

                <section class="dashboard-panel">
                    <div class="dashboard-panel-head"><div><span>AKTIVITAS</span><h2>Transaksi Terbaru</h2></div><a href="{{ route('transactions.index') }}">Lihat semua</a></div>
                    <div class="dashboard-list">
                        @forelse($recentTransactions as $transaction)
                            <a href="{{ route('transactions.edit', $transaction) }}" class="dashboard-list-item" wire:navigate>
                                <span class="transaction-dot transaction-dot-{{ $transaction->payment_status }}"></span>
                                <span class="grow"><strong>{{ $transaction->member->user->full_name }}</strong><small>{{ $transaction->invoice_number }} · {{ $transaction->subscription->package->package_name }}</small></span>
                                <span class="transaction-value">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                            </a>
                        @empty
                            <div class="dashboard-empty">Belum ada transaksi.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        @endif
    </div>
</x-layouts::app>


