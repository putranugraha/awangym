<?php

namespace App\Livewire\WorkoutPrograms;

use App\Models\Exercise;
use App\Models\Member;
use App\Models\MemberProgram;
use App\Models\ProgramExercise;
use App\Models\WorkoutProgram;
use Livewire\Component;

class EditPage extends Component
{
    public WorkoutProgram $program;

    public string $program_name = '';

    public string $target_goal = '';

    public string $difficulty_level = 'beginner';

    public string $description = '';

    public string $program_status = 'active';

    public string $repetitions = '';

    public string $trainer_notes = '';

    public string $start_date = '';

    public int|string $duration_weeks = '';

    public int|string $exercise_id = '';

    public int|string $training_day = 1;

    public int|string $sets = '';

    public int|string $member_id = '';

    public function mount(WorkoutProgram $program): void
    {
        $this->authorizeOwner($program);
        $this->program = $program;
        $this->fill($program->only(['program_name', 'target_goal', 'difficulty_level', 'duration_weeks', 'description', 'program_status']));
        $this->start_date = today()->format('Y-m-d');
    }

    public function save(): void
    {
        $d = $this->validate(['program_name' => ['required', 'max:150'], 'target_goal' => ['required', 'max:100'], 'difficulty_level' => ['required', 'in:beginner,intermediate,advanced'], 'duration_weeks' => ['required', 'integer', 'min:1'], 'description' => ['required'], 'program_status' => ['required', 'in:active,inactive']]);
        $this->program->update($d);
        session()->flash('success', 'Program berhasil diperbarui.');
    }

    public function addExercise(): void
    {
        $d = $this->validate(['exercise_id' => ['required', 'exists:exercises,exercise_id'], 'training_day' => ['required', 'integer', 'min:1'], 'sets' => ['nullable', 'integer', 'min:1'], 'repetitions' => ['nullable', 'max:50']]);
        $d['sequence_order'] = ProgramExercise::where('program_id', $this->program->program_id)->where('training_day', $d['training_day'])->max('sequence_order') + 1;
        $this->program->exercises()->create($d);
        $this->reset(['exercise_id', 'sets', 'repetitions']);
        session()->flash('success', 'Latihan ditambahkan.');
    }

    public function assign(): void
    {
        $d = $this->validate(['member_id' => ['required', 'exists:members,member_id'], 'start_date' => ['required', 'date'], 'trainer_notes' => ['nullable']]);
        MemberProgram::create($d + ['program_id' => $this->program->program_id, 'trainer_id' => auth()->user()->personalTrainer->trainer_id, 'assigned_date' => today(), 'end_date' => now()->parse($d['start_date'])->addWeeks($this->program->duration_weeks)->subDay(), 'progress_percentage' => 0, 'program_status' => 'active']);
        session()->flash('success', 'Program ditetapkan kepada member.');
    }

    private function authorizeOwner(WorkoutProgram $program): void
    {
        abort_unless($program->trainer_id === auth()->user()->personalTrainer->trainer_id, 403);
    }

    public function render()
    {
        return view('livewire.workout-programs.edit-page', ['exercises' => Exercise::where('exercise_status', 'active')->get(), 'members' => Member::with('user')->get(), 'items' => $this->program->exercises()->with('exercise')->get()])->layout('layouts.app', ['title' => 'Edit Program']);
    }
}
