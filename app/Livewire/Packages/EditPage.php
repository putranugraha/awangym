<?php

namespace App\Livewire\Packages;

use App\Models\MembershipPackage;
use Livewire\Component;

class EditPage extends Component
{
    public MembershipPackage $package;

    public string $package_name = '';

    public string $description = '';

    public string $package_status = 'active';

    public int|string $duration_days = '';

    public int|string $price = '';

    public function mount(MembershipPackage $package): void
    {
        $this->package = $package;
        $this->fill($package->only(['package_name', 'duration_days', 'price', 'package_status']));
        $this->description = $package->description ?? '';
    }

    public function save(): void
    {
        $data = $this->validate(['package_name' => ['required', 'max:100'], 'duration_days' => ['required', 'integer', 'min:1'], 'price' => ['required', 'numeric', 'min:0'], 'description' => ['nullable'], 'package_status' => ['required', 'in:active,inactive']]);
        $this->package->update($data);
        session()->flash('success', 'Paket berhasil diperbarui.');
        $this->redirectRoute('admin.packages', navigate: true);
    }

    public function render()
    {
        return view('livewire.packages.edit-page')->layout('layouts.app', ['title' => 'Edit Paket']);
    }
}
