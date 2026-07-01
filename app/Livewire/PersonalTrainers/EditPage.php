<?php

namespace App\Livewire\PersonalTrainers;

use App\Models\PersonalTrainer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EditPage extends Component
{
    public PersonalTrainer $trainer;

    public string $full_name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $bio = '';

    public string $employment_status = 'active';

    public string $account_status = 'active';

    public function mount(PersonalTrainer $trainer): void
    {
        $this->trainer = $trainer->load('user');
        $this->fill(['full_name' => $trainer->user->full_name, 'email' => $trainer->user->email, 'phone' => $trainer->user->phone, 'bio' => $trainer->bio ?? '', 'employment_status' => $trainer->employment_status, 'account_status' => $trainer->user->account_status]);
    }

    public function save(): void
    {
        $d = $this->validate(['full_name' => ['required', 'max:100'], 'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->trainer->user_id, 'user_id')], 'phone' => ['required', 'max:20'], 'password' => ['nullable', 'min:8'], 'bio' => ['nullable'], 'employment_status' => ['required', 'in:active,inactive'], 'account_status' => ['required', 'in:active,inactive']]);
        DB::transaction(function () use ($d) {
            $u = collect($d)->only(['full_name', 'email', 'phone', 'account_status'])->all();
            if ($d['password'] !== '') {
                $u['password'] = $d['password'];
            }$this->trainer->user->update($u);
            $this->trainer->update(['bio' => $d['bio'], 'employment_status' => $d['employment_status']]);
        });
        session()->flash('success', 'Trainer berhasil diperbarui.');
        $this->redirectRoute('admin.trainers', navigate: true);
    }

    public function render()
    {
        return view('livewire.personal-trainers.edit-page')->layout('layouts.app', ['title' => 'Edit Trainer']);
    }
}
