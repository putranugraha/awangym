<?php

namespace App\Livewire\WorkoutPrograms;

use App\Models\WorkoutProgram;
use Livewire\Component;

class IndexPage extends Component
{
    public function render()
    {
        $trainer = auth()->user()->personalTrainer;

        return view('livewire.workout-programs.index-page', ['programs' => WorkoutProgram::with('exercises')->where('trainer_id', $trainer->trainer_id)->latest()->get()])->layout('layouts.app', ['title' => 'Program']);
    }
}
