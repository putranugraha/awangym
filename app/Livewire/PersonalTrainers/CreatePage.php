<?php

namespace App\Livewire\PersonalTrainers;

use App\Models\PersonalTrainer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreatePage extends Component
{
    public string $full_name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $bio = '';

    public function save(): void
    {
        $d = $this->validate(['full_name' => ['required', 'max:100'], 'email' => ['required', 'email', 'unique:users,email'], 'phone' => ['required', 'max:20'], 'password' => ['required', 'min:8'], 'bio' => ['nullable']]);
        DB::transaction(function () use ($d) {
            $u = User::create($d + ['account_status' => 'active']);
            $u->assignRole('personal_trainer');
            $n = (PersonalTrainer::max('trainer_id') ?? 0) + 1;
            PersonalTrainer::create(['user_id' => $u->user_id, 'trainer_code' => 'AGT-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT), 'bio' => $d['bio'], 'employment_status' => 'active']);
        });
        session()->flash('success', 'Trainer berhasil ditambahkan.');
        $this->redirectRoute('admin.trainers', navigate: true);
    }

    public function render()
    {
        return view('livewire.personal-trainers.create-page')->layout('layouts.app', ['title' => 'Tambah Trainer']);
    }
}
