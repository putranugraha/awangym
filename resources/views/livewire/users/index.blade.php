<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $role = 'all';
    public string $status = 'all';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $users = User::with(['roles', 'member', 'personalTrainer'])
            ->when($this->search, fn ($query) => $query->where(function ($searchQuery) {
                $searchQuery->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->role !== 'all', fn ($query) => $query->role($this->role))
            ->when($this->status !== 'all', fn ($query) => $query->where('account_status', $this->status))
            ->latest()
            ->paginate(12);

        return [
            'users' => $users,
            'totalUsers' => User::count(),
            'activeUsers' => User::where('account_status', 'active')->count(),
            'inactiveUsers' => User::where('account_status', 'inactive')->count(),
            'adminUsers' => User::role('admin')->count(),
        ];
    }
};
?>

<div class="awan-page">
    <header class="resource-header">
        <div><span class="eyebrow">AKSES SISTEM</span><h1>Kelola User</h1><p>Pantau akun, role, dan status akses seluruh pengguna.</p></div>
        <a class="primary-btn" href="{{ route('users.create') }}" wire:navigate><span>+</span> Tambah User</a>
    </header>

    <div class="transaction-stats">
        <article class="transaction-stat transaction-stat-featured"><span>Total user</span><strong>{{ $totalUsers }}</strong><small>Seluruh akun sistem</small></article>
        <article><span>User aktif</span><strong class="text-success">{{ $activeUsers }}</strong><small>Dapat mengakses sistem</small></article>
        <article><span>User nonaktif</span><strong>{{ $inactiveUsers }}</strong><small>Akses diblokir</small></article>
        <article><span>Administrator</span><strong>{{ $adminUsers }}</strong><small>Akun operasional</small></article>
    </div>

    @if(session('success'))<div class="notice">{{ session('success') }}</div>@endif

    <section class="data-panel">
        <div class="data-toolbar user-toolbar">
            <label class="data-search">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nama, email, atau telepon…">
            </label>
            <div class="transaction-filters">
                <label class="data-filter"><span>Role</span><select wire:model.live="role"><option value="all">Semua role</option><option value="admin">Admin</option><option value="personal_trainer">Personal Trainer</option><option value="member">Member</option></select></label>
                <label class="data-filter"><span>Status</span><select wire:model.live="status"><option value="all">Semua status</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label>
            </div>
        </div>

        <div class="responsive-table">
            <table class="member-table user-table">
                <thead><tr><th>User</th><th>Kontak</th><th>Role</th><th>Profil Terkait</th><th>Status</th><th class="action-column">Aksi</th></tr></thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $roleName = $user->roles->first()?->name;
                            $roleLabel = match($roleName) {'personal_trainer' => 'Personal Trainer', 'member' => 'Member', default => 'Admin'};
                            $profileCode = $user->member?->member_code ?? $user->personalTrainer?->trainer_code;
                        @endphp
                        <tr wire:key="user-{{ $user->user_id }}">
                            <td data-label="User"><div class="member-cell"><span class="member-table-avatar">{{ $user->initials() }}</span><span><strong>{{ $user->full_name }}</strong><small>ID #{{ $user->user_id }}</small></span></div></td>
                            <td data-label="Kontak"><span class="table-primary">{{ $user->email }}</span><small class="table-secondary">{{ $user->phone }}</small></td>
                            <td data-label="Role"><span class="user-role user-role-{{ $roleName }}">{{ $roleLabel }}</span></td>
                            <td data-label="Profil"><span class="table-primary">{{ $profileCode ?? 'Akun operasional' }}</span></td>
                            <td data-label="Status"><span class="account-status account-status-{{ $user->account_status }}">{{ $user->account_status === 'active' ? 'Dapat login' : 'Diblokir' }}</span></td>
                            <td data-label="Aksi" class="action-column"><a class="table-action table-action-secondary" href="{{ route('users.edit', $user) }}" wire:navigate>Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="table-empty"><strong>User tidak ditemukan</strong><p>Coba ubah pencarian atau filter.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())<div class="data-pagination">{{ $users->links() }}</div>@endif
    </section>
</div>
