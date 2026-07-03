<?php

use Livewire\Component;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public string $search = '';

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
};
?>

<div class="awan-page">
    <header class="resource-header"><div><span class="eyebrow">ACCESS CONTROL</span><h1>Role</h1><p>Kelola kelompok akses dan permission pengguna.</p></div><a class="primary-btn" href="{{ route('roles.create') }}" wire:navigate><span>+</span> Tambah Role</a></header>
    <div class="resource-stats">
        <article><span>Total role</span><strong>{{ $totalRoles }}</strong></article>
        <article><span>Assignment user</span><strong>{{ $totalAssignments }}</strong></article>
        <article><span>Total permission</span><strong>{{ $totalPermissions }}</strong></article>
    </div>
    @if(session('success'))<div class="notice">{{ session('success') }}</div>@endif
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
                            <td data-label="Aksi" class="action-column"><a class="table-action table-action-secondary" href="{{ route('roles.edit', $role) }}" wire:navigate>Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="table-empty"><strong>Role tidak ditemukan</strong></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
