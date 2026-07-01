<?php

namespace App\Livewire\Packages;

use App\Models\MembershipPackage;
use Livewire\Component;

class CreatePage extends Component
{
    public string $package_name = '';

    public string $description = '';

    public string $package_status = 'active';

    public int|string $duration_days = '';

    public int|string $price = '';

    public function save(): void
    {
        $data = $this->validate(['package_name' => ['required', 'max:100'], 'duration_days' => ['required', 'integer', 'min:1'], 'price' => ['required', 'numeric', 'min:0'], 'description' => ['nullable'], 'package_status' => ['required', 'in:active,inactive']]);
        MembershipPackage::create($data);
        session()->flash('success', 'Paket berhasil disimpan.');
        $this->redirectRoute('admin.packages', navigate: true);
    }

    public function render()
    {
        return view('livewire.packages.create-page')->layout('layouts.app', ['title' => 'Tambah Paket']);
    }
}
