<?php

namespace App\Livewire\PersonalTrainers;

use App\Models\PersonalTrainer;
use Livewire\Component;

class IndexPage extends Component
{
    public function render()
    {
        return view('livewire.personal-trainers.index-page', ['trainers' => PersonalTrainer::with('user')->latest()->get()])->layout('layouts.app', ['title' => 'Personal Trainer']);
    }
}
