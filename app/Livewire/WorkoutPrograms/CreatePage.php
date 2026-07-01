<?php

namespace App\Livewire\WorkoutPrograms;

use App\Models\WorkoutProgram;
use Livewire\Component;

class CreatePage extends Component
{
    public string $program_name = '';

    public string $target_goal = '';

    public string $difficulty_level = 'beginner';

    public string $description = '';

    public int|string $duration_weeks = '';

    public function save(): void
    {
        $d = $this->validate(['program_name' => ['required', 'max:150'], 'target_goal' => ['required', 'max:100'], 'difficulty_level' => ['required', 'in:beginner,intermediate,advanced'], 'duration_weeks' => ['required', 'integer', 'min:1'], 'description' => ['required']]);
        WorkoutProgram::create($d + ['trainer_id' => auth()->user()->personalTrainer->trainer_id, 'program_status' => 'active']);
        session()->flash('success', 'Program berhasil dibuat.');
        $this->redirectRoute('trainer.programs', navigate: true);
    }

    public function render()
    {
        return view('livewire.workout-programs.create-page')->layout('layouts.app', ['title' => 'Buat Program']);
    }
}
