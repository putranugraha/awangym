<x-layouts::app :title="$isOwnProfile ? 'Profil Saya' : 'Profil '.$user->full_name">
    @php
        $roleLabels = ['admin' => 'Admin', 'member' => 'Member', 'personal_trainer' => 'Personal Trainer'];
        $photo = $member?->profile_photo ?? $trainer?->profile_photo;
        $permissionLabels = [
            'view dashboard' => 'Melihat dashboard',
            'manage users' => 'Mengelola akun pengguna',
            'manage roles and permissions' => 'Mengelola role dan hak akses',
            'manage members' => 'Mengelola member',
            'manage trainers' => 'Mengelola trainer',
            'manage packages' => 'Mengelola paket',
            'manage memberships' => 'Mengelola membership',
            'manage payments' => 'Mengelola pembayaran',
            'view reports' => 'Melihat laporan pemasukan',
            'assign workout programs' => 'Menetapkan program latihan',
            'view own membership' => 'Melihat membership sendiri',
            'view own payments' => 'Melihat pembayaran sendiri',
            'view own workout program' => 'Melihat program sendiri',
            'view workout catalog' => 'Melihat katalog latihan',
            'view assigned members' => 'Melihat member binaan',
            'validate member exercises' => 'Mencatat pendampingan member',
        ];
    @endphp

    <div class="awan-page ag-profile">
        <header class="resource-header">
            <div><span class="eyebrow">{{ $isOwnProfile ? 'AKUN SAYA' : 'KELOLA USER' }}</span><h1>{{ $isOwnProfile ? 'Profil Saya' : 'Profil Pengguna' }}</h1><p>{{ $isOwnProfile ? 'Informasi pribadi dan aktivitasmu di Awan Gym.' : 'Informasi akun dan aktivitas '.$user->full_name.' di Awan Gym.' }}</p></div>
            @if($isOwnProfile)
                <a class="secondary-btn" href="{{ route('profile.edit') }}" wire:navigate>Edit Profil</a>
            @else
                <div class="flex flex-wrap gap-3">
                    <a class="secondary-btn" href="{{ route('users.index') }}" wire:navigate>Kembali ke User</a>
                    <a class="primary-btn" href="{{ route('users.edit', $user) }}" wire:navigate>Edit User</a>
                </div>
            @endif
        </header>

        <section class="ag-profile-hero" aria-labelledby="profile-name">
            <div class="ag-profile-avatar">
                @if($photo)
                    <img src="{{ Storage::url($photo) }}" alt="Foto {{ $user->full_name }}">
                @else
                    <span aria-hidden="true">{{ $user->initials() }}</span>
                @endif
            </div>
            <div class="ag-profile-identity">
                <span class="ag-profile-kicker">AWAN GYM · {{ $isOwnProfile ? 'MY PROFILE' : 'PROFIL PENGGUNA' }}</span>
                <h2 id="profile-name">{{ $user->full_name }}</h2>
                <div class="ag-profile-roles">
                    @forelse($user->roles as $role)
                        <span>{{ $roleLabels[$role->name] ?? str($role->name)->replace('_', ' ')->title() }}</span>
                    @empty
                        <span>Pengguna</span>
                    @endforelse
                    @if($member)<span>{{ $member->member_code }}</span>@endif
                    @if($trainer)<span>{{ $trainer->trainer_code }}</span>@endif
                </div>
            </div>
            <div class="ag-profile-joined"><span>Bergabung sejak</span><strong>{{ $user->created_at?->translatedFormat('d F Y') ?? '—' }}</strong></div>
        </section>

        <div class="ag-profile-layout">
            <div class="ag-profile-stack">
                <section class="ag-profile-card" aria-labelledby="profile-account">
                    <div class="ag-profile-section-head"><span class="eyebrow">IDENTITAS</span><h2 id="profile-account">Informasi Akun</h2></div>
                    <dl class="ag-profile-details">
                        <div><dt>Nama lengkap</dt><dd>{{ $user->full_name }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                        <div><dt>Nomor telepon</dt><dd>{{ filled($user->phone) && $user->phone !== '-' ? $user->phone : 'Belum diisi' }}</dd></div>
                        <div><dt>Status akun</dt><dd><span class="ag-profile-badge ag-profile-badge-{{ $user->account_status === 'active' ? 'active' : 'inactive' }}">{{ $user->account_status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></dd></div>
                    </dl>
                </section>

                @if($member)
                    <section class="ag-profile-card" aria-labelledby="profile-personal">
                        <div class="ag-profile-section-head"><span class="eyebrow">{{ $isOwnProfile ? 'TENTANG SAYA' : 'DATA PRIBADI' }}</span><h2 id="profile-personal">Data Member</h2></div>
                        <dl class="ag-profile-details">
                            <div><dt>Kode member</dt><dd>{{ $member->member_code }}</dd></div>
                            <div><dt>Jenis kelamin</dt><dd>{{ match ($member->gender) { 'L' => 'Laki-laki', 'P' => 'Perempuan', default => 'Belum diisi' } }}</dd></div>
                            <div><dt>Tanggal lahir</dt><dd>{{ $member->birth_date?->translatedFormat('d F Y') ?? 'Belum diisi' }}</dd></div>
                            <div><dt>Alamat</dt><dd class="ag-profile-multiline">{{ $member->address ?: 'Belum diisi' }}</dd></div>
                            <div><dt>Terdaftar sebagai member</dt><dd>{{ $member->registered_at?->translatedFormat('d F Y') ?? '—' }}</dd></div>
                        </dl>
                    </section>
                @endif

                @if($trainer)
                    <section class="ag-profile-card" aria-labelledby="profile-trainer-bio">
                        <div class="ag-profile-section-head"><span class="eyebrow">{{ $isOwnProfile ? 'TENTANG SAYA' : 'DATA PRIBADI' }}</span><h2 id="profile-trainer-bio">Profil Trainer</h2></div>
                        <dl class="ag-profile-details">
                            <div><dt>Kode trainer</dt><dd>{{ $trainer->trainer_code }}</dd></div>
                            <div><dt>Status kepegawaian</dt><dd><span class="ag-profile-badge ag-profile-badge-{{ $trainer->employment_status === 'active' ? 'active' : 'inactive' }}">{{ $trainer->employment_status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></dd></div>
                            <div><dt>Bio</dt><dd class="ag-profile-multiline">{{ $trainer->bio ?: 'Bio belum diisi.' }}</dd></div>
                        </dl>
                    </section>
                @endif

                @if($isOwnProfile)
                <section class="ag-profile-card ag-profile-settings">
                    <h2>Pengaturan Akun</h2>
                    <p>Kelola nama, email, dan keamanan akunmu.</p>
                    <div class="ag-profile-actions">
                        <a class="secondary-btn" href="{{ route('profile.edit') }}" wire:navigate>Edit Profil</a>
                        <a class="ag-profile-link" href="{{ route('security.edit') }}" wire:navigate>Keamanan akun <span aria-hidden="true">↗</span></a>
                    </div>
                </section>
                @endif
            </div>

            <div class="ag-profile-stack">
                @if($member)
                    <section class="ag-profile-card ag-profile-membership" aria-labelledby="profile-membership">
                        <div class="ag-profile-section-head">
                            <span class="eyebrow">{{ $isOwnProfile ? 'MEMBERSHIP SAYA' : 'MEMBERSHIP MEMBER' }}</span>
                            <h2 id="profile-membership">{{ $subscription?->package?->package_name ?? 'Belum ada paket' }}</h2>
                            <span class="ag-profile-badge ag-profile-badge-{{ $membershipStatus['key'] }}">{{ $subscription ? $membershipStatus['label'] : 'Belum berlangganan' }}</span>
                        </div>
                        @if($subscription)
                            <div class="ag-profile-period">
                                <div><span>Tanggal mulai</span><strong>{{ $subscription->start_date->translatedFormat('d F Y') }}</strong></div>
                                <div><span>Berlaku sampai</span><strong>{{ $subscription->end_date->translatedFormat('d F Y') }}</strong></div>
                            </div>
                            @if(in_array($membershipStatus['key'], ['active', 'expiring']))
                                <div class="ag-profile-time"><strong>{{ (int) $membershipStatus['days_left'] }}</strong><div><span>hari tersisa</span><p>{{ $membershipStatus['days_left'] == 0 ? 'Masa aktif berakhir hari ini.' : ($isOwnProfile ? 'Nikmati setiap sesi latihanmu.' : 'Sisa masa berlaku paket member.') }}</p></div></div>
                            @else
                                <p class="ag-profile-notice">{{ match ($membershipStatus['key']) {
                                    'pending' => 'Membership belum aktif. Pembayaran masih menunggu konfirmasi admin.',
                                    'upcoming' => 'Pembayaran sudah lunas. Membership akan aktif pada tanggal mulai di atas.',
                                    'expired' => 'Masa berlaku paket ini sudah berakhir. Hubungi admin untuk perpanjangan.',
                                    'cancelled' => 'Membership ini telah dibatalkan.',
                                    'failed' => 'Pembayaran belum berhasil. Hubungi admin untuk informasi lebih lanjut.',
                                    'refunded' => 'Pembayaran paket ini telah dikembalikan dan tidak memberikan akses membership aktif.',
                                    default => 'Paket ini belum memberikan akses membership aktif. Hubungi admin untuk informasi lebih lanjut.',
                                } }}</p>
                            @endif

                            @if($upcomingSubscription && $upcomingSubscription->subscription_id !== $subscription->subscription_id)
                                <div class="ag-profile-notice"><strong>Perpanjangan sudah siap</strong><p>{{ $upcomingSubscription->package->package_name }} · {{ $upcomingSubscription->start_date->translatedFormat('d M Y') }} – {{ $upcomingSubscription->end_date->translatedFormat('d M Y') }}</p></div>
                            @endif
                            @if($pendingPayments > 0 && $membershipStatus['key'] !== 'pending')
                                <p class="ag-profile-notice">Ada {{ $pendingPayments }} transaksi yang masih menunggu pembayaran atau verifikasi admin.</p>
                            @endif
                        @else
                            <p>Paket membership akan tampil setelah transaksi member dibuat.</p>
                        @endif
                        @if($isOwnProfile)
                        <div class="ag-profile-actions">
                            @can('view own membership')<a class="primary-btn" href="{{ route('membership.show') }}" wire:navigate>Detail Membership</a>@endcan
                            @can('view own payments')<a class="ag-profile-link" href="{{ route('payments.index') }}" wire:navigate>Riwayat pembayaran <span aria-hidden="true">↗</span></a>@endcan
                        </div>
                        @endif
                    </section>

                    <section class="ag-profile-card" aria-labelledby="profile-coach">
                        <div class="ag-profile-section-head"><span class="eyebrow">PENDAMPINGAN</span><h2 id="profile-coach">Personal Trainer</h2></div>
                        @if($subscription?->trainer)
                            @if($subscription->trainerAddon)
                                <p class="ag-profile-caption">Tambahan PT: {{ $subscription->trainer_start_date->translatedFormat('d M Y') }} – {{ $subscription->trainer_end_date->translatedFormat('d M Y') }} · {{ today()->lt($subscription->trainer_start_date) ? 'Belum dimulai' : (today()->gt($subscription->trainer_end_date) ? 'Sudah berakhir' : 'Sedang berlaku') }}</p>
                            @endif
                            <div class="ag-profile-coach">
                                <div class="ag-profile-coach-avatar">
                                    @if($subscription->trainer->profile_photo)
                                        <img src="{{ Storage::url($subscription->trainer->profile_photo) }}" alt="Foto {{ $subscription->trainer->user->full_name }}" loading="lazy">
                                    @else
                                        {{ $subscription->trainer->user->initials() }}
                                    @endif
                                </div>
                                <div><h3>{{ $subscription->trainer->user->full_name }}</h3><p>{{ $subscription->trainer->trainer_code }} · Trainer paket ini</p></div>
                            </div>
                            <dl class="ag-profile-stats">
                                <div><dt>Kuota sesi</dt><dd>{{ $subscription->trainer_session_limit ?? 0 }}</dd></div>
                                <div><dt>Terpakai</dt><dd>{{ $subscription->trainer_sessions_count }}</dd></div>
                                <div><dt>Sisa kuota</dt><dd>{{ max(($subscription->trainer_session_limit ?? 0) - $subscription->trainer_sessions_count, 0) }}</dd></div>
                            </dl>
                            <p class="ag-profile-caption">Sesi hanya berlaku selama periode layanan PT dengan pembayaran lunas. Sisa kuota tidak memperpanjang masa berlaku layanan.</p>
                            @if($isOwnProfile && in_array($membershipStatus['key'], ['active', 'expiring']))
                                @can('view own workout program')<a class="ag-profile-link" href="{{ route('my-program.index') }}" wire:navigate>Lihat catatan pertemuan <span aria-hidden="true">↗</span></a>@endcan
                            @endif
                        @else
                            <div class="ag-profile-empty"><span aria-hidden="true">{{ $subscription ? 'GYM MANDIRI' : 'PERSONAL TRAINER' }}</span><h3>{{ $subscription ? 'Tanpa personal trainer' : 'Belum ada pendamping' }}</h3><p>{{ $subscription ? 'Belum ada trainer yang ditugaskan pada paket ini. Referensi latihan dapat dilihat melalui menu Program.' : 'Informasi trainer akan muncul untuk paket dengan pendampingan PT.' }}</p></div>
                        @endif
                    </section>
                @elseif($user->hasRole('member'))
                    <section class="ag-profile-card"><h2>Data member belum lengkap</h2><p>Hubungi admin untuk melengkapi profil member dan informasi membershipmu.</p></section>
                @endif

                @if($trainer)
                    <section class="ag-profile-card" aria-labelledby="profile-activity">
                        <div class="ag-profile-section-head"><span class="eyebrow">{{ $isOwnProfile ? 'PENDAMPINGAN SAYA' : 'PENDAMPINGAN TRAINER' }}</span><h2 id="profile-activity">Aktivitas Trainer</h2><p>Ringkasan member dan pertemuan yang ditangani.</p></div>
                        <dl class="ag-profile-stats">
                            <div><dt>Member aktif</dt><dd>{{ $trainerStats['members'] }}</dd></div>
                            <div><dt>Sesi tercatat</dt><dd>{{ $trainerStats['sessions'] }}</dd></div>
                            <div><dt>Sisa sesi aktif</dt><dd>{{ $trainerStats['remaining'] }}</dd></div>
                        </dl>
                        <p class="ag-profile-caption">Member dan sisa sesi dihitung dari paket aktif yang sudah lunas. Sesi tercatat mencakup seluruh riwayat pendampingan trainer.</p>
                        @if($isOwnProfile)
                        @can('view assigned members')<a class="primary-btn" href="{{ route('trainer-members.index') }}" wire:navigate>Lihat Member Binaan</a>@endcan
                        @endif
                    </section>
                @elseif($user->hasRole('personal_trainer'))
                    <section class="ag-profile-card"><h2>Data trainer belum lengkap</h2><p>Hubungi admin untuk melengkapi profil personal trainer.</p></section>
                @endif

                @if($user->hasRole('admin'))
                    <section class="ag-profile-card" aria-labelledby="profile-access">
                        <div class="ag-profile-section-head"><span class="eyebrow">AKSES ADMIN</span><h2 id="profile-access">{{ $isOwnProfile ? 'Hak Akses Saya' : 'Hak Akses Pengguna' }}</h2><p>Akses yang diberikan pada akun ini untuk mengelola Awan Gym.</p></div>
                        <ul class="ag-profile-permissions">
                            @forelse($permissions as $permission)
                                <li><span aria-hidden="true">✓</span>{{ $permissionLabels[$permission->name] ?? str($permission->name)->replace('_', ' ')->title() }}</li>
                            @empty
                                <li>Belum ada hak akses yang diberikan.</li>
                            @endforelse
                        </ul>
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>
