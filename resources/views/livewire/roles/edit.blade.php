<?php

use App\Support\AccessControl;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public Role $role;
    public string $name = '';
    public array $selectedPermissions = [];
    public bool $systemRole = false;

    public function mount(Role $role): void
    {
        $this->role = $role->load('permissions');
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->all();
        $this->systemRole = in_array($role->name, AccessControl::SYSTEM_ROLES, true);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9 _-]*$/', Rule::unique('roles', 'name')->ignore($this->role->id)],
            'selectedPermissions' => ['required', 'array', 'min:1'],
            'selectedPermissions.*' => ['exists:permissions,name'],
        ]);
        if (! $this->systemRole) {
            $this->role->update(['name' => str($data['name'])->lower()->trim()->toString()]);
        }
        if ($this->role->name === 'admin') {
            $data['selectedPermissions'] = array_values(array_unique([...$data['selectedPermissions'], 'view dashboard', 'manage roles and permissions']));
        }
        $this->role->syncPermissions($data['selectedPermissions']);
        session()->flash('success', 'Role berhasil diperbarui.');
        $this->redirectRoute('roles.index', navigate: true);
    }

    public function with(): array
    {
        return ['permissions' => Permission::orderBy('name')->get()];
    }
};
?>

<div class="awan-page">
    <header class="form-page-header"><div><span class="eyebrow">EDIT ROLE</span><h1>{{ str($role->name)->replace('_', ' ')->title() }}</h1><p>Perbarui permission untuk role ini.</p></div><a class="secondary-btn member-back-desktop" href="{{ route('roles.index') }}" wire:navigate>Kembali</a></header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card access-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Identitas Role</h2><p>Role sistem tidak dapat diganti namanya.</p></div></div>
            <label><span>Nama role <em>*</em></span><input class="form-input" wire:model="name" @disabled($systemRole)></label>
            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Permission</h2><p>Pilih akses yang dimiliki role ini.</p></div></div>
            <div class="permission-choice-grid">
                @foreach($permissions as $permission)
                    @php
                        $locked = $role->name === 'admin' && in_array($permission->name, ['view dashboard', 'manage roles and permissions'], true);
                    @endphp
                    <label wire:key="permission-{{ $permission->id }}"><input type="checkbox" value="{{ $permission->name }}" wire:model.live="selectedPermissions" @disabled($locked)><span>{{ str($permission->name)->title() }}</span></label>
                @endforeach
            </div>
        </section>
        <aside class="form-side-stack">
            <section class="form-card access-summary-card"><span class="member-identity-avatar">R</span><div><strong>{{ str($role->name)->replace('_', ' ')->title() }}</strong><small>{{ count($selectedPermissions) }} permission · {{ $role->users()->count() }} user</small></div></section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled">Simpan Perubahan</button>
            <a class="secondary-btn member-back-mobile" href="{{ route('roles.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>
