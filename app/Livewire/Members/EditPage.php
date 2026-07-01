<?php

namespace App\Livewire\Members;

use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EditPage extends Component
{
    public Member $member;

    public string $full_name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $gender = 'L';

    public string $birth_date = '';

    public string $address = '';

    public string $registered_at = '';

    public string $account_status = 'active';

    public function mount(Member $member): void
    {
        $this->member = $member->load('user');
        $this->fill([
            'full_name' => $member->user->full_name, 'email' => $member->user->email, 'phone' => $member->user->phone,
            'gender' => $member->gender, 'birth_date' => $member->birth_date->format('Y-m-d'), 'address' => $member->address,
            'registered_at' => $member->registered_at->format('Y-m-d'), 'account_status' => $member->user->account_status,
        ]);
    }

    public function save(): void
    {
        $data = $this->validate([
            'full_name' => ['required', 'max:100'], 'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->member->user_id, 'user_id')],
            'phone' => ['required', 'max:20'], 'password' => ['nullable', 'min:8'], 'gender' => ['required', 'in:L,P'],
            'birth_date' => ['required', 'date', 'before:today'], 'address' => ['required'], 'registered_at' => ['required', 'date'],
            'account_status' => ['required', 'in:active,inactive'],
        ]);
        DB::transaction(function () use ($data) {
            $userData = collect($data)->only(['full_name', 'email', 'phone', 'account_status'])->all();
            if ($data['password'] !== '') {
                $userData['password'] = $data['password'];
            }
            $this->member->user->update($userData);
            $this->member->update(collect($data)->only(['gender', 'birth_date', 'address', 'registered_at'])->all());
        });
        session()->flash('success', 'Data member berhasil diperbarui.');
        $this->redirectRoute('admin.members', navigate: true);
    }

    public function render()
    {
        return view('livewire.members.edit-page')->layout('layouts.app', ['title' => 'Edit Member']);
    }
}
