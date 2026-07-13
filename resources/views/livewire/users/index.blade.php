<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $role = 'all';
    public string $status = 'all';

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteUser(): void
    {
        abort_unless(auth()->user()->can('manage users'), 403);
        
        $user = User::findOrFail($this->deletingId);
        $this->showDeleteModal = false;
        
        if ($user->user_id === auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            return;
        }

        if (\App\Models\MembershipSubscription::where('created_by', $user->user_id)->exists()) {
            session()->flash('error', 'User tidak dapat dihapus karena telah mencatat transaksi subscription.');
            return;
        }

        if ($user->member && $user->member->subscriptions()->exists()) {
            session()->flash('error', 'User tidak dapat dihapus karena member terkait memiliki riwayat subscription.');
            return;
        }

        if ($user->personalTrainer) {
            $trainer = $user->personalTrainer;
            if (\App\Models\MemberExerciseCheck::where('validated_by', $trainer->trainer_id)->exists()) {
                session()->flash('error', 'User tidak dapat dihapus karena trainer terkait memiliki riwayat validasi gerakan member.');
                return;
            }
        }

        DB::transaction(function () use ($user) {
            $user->delete();
        });

        session()->flash('success', 'User berhasil dihapus.');
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

    @if(session('success'))
        <div x-data x-init="Flux.toast({ variant: 'success', text: '{{ session('success') }}' })"></div>
    @endif
    @if(session('error'))
        <div x-data x-init="Flux.toast({ variant: 'danger', text: '{{ session('error') }}' })"></div>
    @endif

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
                            <td data-label="Aksi" class="action-column">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="right" />
                                    <flux:menu>
                                        <flux:menu.item href="{{ route('users.edit', $user) }}" icon="pencil-square" wire:navigate>
                                            Edit User
                                        </flux:menu.item>
                                        <flux:menu.item
                                            wire:click="confirmDelete({{ $user->user_id }})"
                                            variant="danger"
                                            icon="trash"
                                        >
                                            Hapus User
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="table-empty"><strong>User tidak ditemukan</strong><p>Coba ubah pencarian atau filter.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())<div class="data-pagination">{{ $users->links() }}</div>@endif
    </section>

    <flux:modal name="delete-confirm" wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Hapus User?</flux:heading>
                <flux:text>Apakah Anda yakin ingin menghapus user ini? Akun dan profil terkait akan dihapus secara permanen.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="outline" wire:click="$set('showDeleteModal', false)">Batal</flux:button>
                <flux:button variant="danger" wire:click="deleteUser">Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
