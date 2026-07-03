<?php

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public string $name = '';
    public array $selectedPermissions = [];

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9 _-]*$/', 'unique:roles,name'],
            'selectedPermissions' => ['required', 'array', 'min:1'],
            'selectedPermissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::create(['name' => str($data['name'])->lower()->trim()->toString(), 'guard_name' => 'web']);
        $role->syncPermissions($data['selectedPermissions']);
        session()->flash('success', 'Role berhasil ditambahkan.');
        $this->redirectRoute('roles.index', navigate: true);
    }

    public function with(): array
    {
        return ['permissions' => Permission::orderBy('name')->get()];
    }
};
?>

@php
    $identityComplete = filled($name);
    $permissionComplete = count($selectedPermissions) > 0;
    $completedSections = collect([$identityComplete, $permissionComplete])->filter()->count();
@endphp
<div class="awan-page">
    <header class="form-page-header"><div><span class="eyebrow">ROLE BARU</span><h1>Tambah Role</h1><p>Buat kelompok akses baru dan pilih permission-nya.</p></div><a class="secondary-btn member-back-desktop" href="{{ route('roles.index') }}" wire:navigate>Kembali</a></header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card access-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Identitas Role</h2><p>Gunakan nama singkat yang menjelaskan fungsi role.</p></div></div>
            <label><span>Nama role <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="name" placeholder="Contoh: staff operasional"></label>
            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Permission</h2><p>Pilih akses yang dimiliki role ini.</p></div></div>
            <div class="permission-choice-grid">
                @foreach($permissions as $permission)
                    <label wire:key="permission-{{ $permission->id }}"><input type="checkbox" value="{{ $permission->name }}" wire:model.live="selectedPermissions"><span>{{ str($permission->name)->title() }}</span></label>
                @endforeach
            </div>
        </section>
        <aside class="form-side-stack">
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan role</span><strong>{{ $completedSections }}/2 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 2) * 100 }}%"></i></div></div>
                <ul class="member-checklist"><li class="{{ $identityComplete ? 'is-complete' : '' }}"><i>{{ $identityComplete ? '✓' : '1' }}</i><span><strong>Identitas role</strong><small>Nama kelompok akses</small></span></li><li class="{{ $permissionComplete ? 'is-complete' : '' }}"><i>{{ $permissionComplete ? '✓' : '2' }}</i><span><strong>Permission</strong><small>{{ count($selectedPermissions) }} akses dipilih</small></span></li></ul>
            </section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled">Simpan Role</button>
            <a class="secondary-btn member-back-mobile" href="{{ route('roles.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>
