<?php

namespace App\Livewire\Exercises;

use App\Models\Exercise;
use Livewire\Component;

class IndexPage extends Component
{
    public function render()
    {
        return view('livewire.exercises.index-page', ['exercises' => Exercise::orderBy('category')->orderBy('exercise_name')->get()])->layout('layouts.app', ['title' => 'Latihan']);
    }
}
