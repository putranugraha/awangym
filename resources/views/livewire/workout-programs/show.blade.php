<?php

use App\Models\WorkoutProgram;
use Livewire\Component;

new class extends Component
{
    public WorkoutProgram $program;

    public function mount(WorkoutProgram $program): void
    {
        abort_unless($program->program_status === 'active', 404);
        $this->program = $program;
    }

    public function with(): array
    {
        return ['schedule' => $this->program->exercises()->with('exercise')->get()->groupBy('training_day')];
    }
};
?>

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">{{ $program->program_code }}</span><h1>{{ $program->program_name }}</h1><p>{{ $program->description }}</p></div>
        <a class="secondary-btn" href="{{ route('workout-programs.index') }}" wire:navigate>Kembali</a>
    </header>
    @foreach($schedule as $day => $items)
        <section class="form-card">
            <div class="section-title"><h2>Minggu {{ intdiv($day - 1, 7) + 1 }} · Hari {{ (($day - 1) % 7) + 1 }}</h2><span>{{ $items->first()->session_name }}</span></div>
            <div class="exercise-list">
                @foreach($items as $item)
                    <div><span><strong>{{ $item->exercise->exercise_name }}</strong><br>{{ $item->sets }} set × {{ $item->repetitions ?? $item->duration_minutes.' menit' }}</span><small>{{ $item->exercise->equipment }} · Istirahat {{ $item->rest_seconds }} detik</small></div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
