<?php

namespace App\Livewire\Members;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreatePage extends Component
{
    public string $full_name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $gender = 'L';

    public string $birth_date = '';

    public string $address = '';

    public string $registered_at = '';

    public function mount(): void
    {
        $this->registered_at = today()->format('Y-m-d');
    }

    public function save(): void
    {
        $data = $this->validate([
            'full_name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'max:20'], 'password' => ['required', 'min:8'], 'gender' => ['required', 'in:L,P'],
            'birth_date' => ['required', 'date', 'before:today'], 'address' => ['required'], 'registered_at' => ['required', 'date'],
        ]);
        DB::transaction(function () use ($data) {
            $user = User::create($data + ['account_status' => 'active']);
            $user->assignRole('member');
            $next = (Member::max('member_id') ?? 0) + 1;
            Member::create($data + ['user_id' => $user->user_id, 'member_code' => 'AGM-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT)]);
        });
        session()->flash('success', 'Member berhasil ditambahkan.');
        $this->redirectRoute('admin.members', navigate: true);
    }

    public function render()
    {
        return view('livewire.members.create-page')->layout('layouts.app', ['title' => 'Tambah Member']);
    }
}
