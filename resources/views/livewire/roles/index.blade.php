<?php

use Livewire\Component;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public string $search = '';

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function with(): array
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return [
            'roles' => $roles,
            'totalRoles' => Role::count(),
            'totalAssignments' => Role::withCount('users')->get()->sum('users_count'),
            'totalPermissions' => \Spatie\Permission\Models\Permission::count(),
        ];
    }

    public function deleteRole(): void
    {
        abort_unless(auth()->user()->can('manage roles and permissions'), 403);
        
        $role = Role::findOrFail($this->deletingId);
        $this->showDeleteModal = false;
        
        if (in_array($role->name, ['admin', 'personal_trainer', 'member'], true)) {
            session()->flash('error', 'Role bawaan sistem tidak dapat dihapus.');
            return;
        }

        if ($role->users()->exists()) {
            session()->flash('error', 'Role tidak dapat dihapus karena sedang digunakan oleh user.');
            return;
        }

        $role->delete();
        session()->flash('success', 'Role berhasil dihapus.');
    }
};
?>

<div class="awan-page">
    <header class="resource-header"><div><span class="eyebrow">ACCESS CONTROL</span><h1>Role</h1><p>Kelola kelompok akses dan permission pengguna.</p></div><a class="primary-btn" href="{{ route('roles.create') }}" wire:navigate><span>+</span> Tambah Role</a></header>
    <div class="resource-stats">
        <article><span>Total role</span><strong>{{ $totalRoles }}</strong></article>
        <article><span>Assignment user</span><strong>{{ $totalAssignments }}</strong></article>
        <article><span>Total permission</span><strong>{{ $totalPermissions }}</strong></article>
    </div>
    @if(session('success'))
        <div x-data x-init="Flux.toast({ variant: 'success', text: '{{ session('success') }}' })"></div>
    @endif
    @if(session('error'))
        <div x-data x-init="Flux.toast({ variant: 'danger', text: '{{ session('error') }}' })"></div>
    @endif
    <section class="data-panel">
        <div class="data-toolbar"><label class="data-search"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg><input wire:model.live.debounce.300ms="search" placeholder="Cari nama role…"></label></div>
        <div class="responsive-table">
            <table class="member-table">
                <thead><tr><th>Role</th><th>Guard</th><th>User</th><th>Permission</th><th class="action-column">Aksi</th></tr></thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr wire:key="role-{{ $role->id }}">
                            <td data-label="Role"><span class="user-role user-role-{{ $role->name }}">{{ str($role->name)->replace('_', ' ')->title() }}</span></td>
                            <td data-label="Guard"><span class="table-primary">{{ $role->guard_name }}</span></td>
                            <td data-label="User"><span class="usage-count">{{ $role->users_count }}</span></td>
                            <td data-label="Permission"><span class="usage-count usage-count-active">{{ $role->permissions_count }}</span></td>
                            <td data-label="Aksi" class="action-column">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="right" />
                                    <flux:menu>
                                        <flux:menu.item href="{{ route('roles.edit', $role) }}" icon="pencil-square" wire:navigate>
                                            Edit Role
                                        </flux:menu.item>
                                        @if(!in_array($role->name, ['admin', 'personal_trainer', 'member'], true))
                                            <flux:menu.item
                                                wire:click="confirmDelete({{ $role->id }})"
                                                variant="danger"
                                                icon="trash"
                                            >
                                                Hapus Role
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="table-empty"><strong>Role tidak ditemukan</strong></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <flux:modal name="delete-confirm" wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Hapus Role?</flux:heading>
                <flux:text>Apakah Anda yakin ingin menghapus role ini? Tindakan ini tidak dapat dibatalkan.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="outline" wire:click="$set('showDeleteModal', false)">Batal</flux:button>
                <flux:button variant="danger" wire:click="deleteRole">Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
