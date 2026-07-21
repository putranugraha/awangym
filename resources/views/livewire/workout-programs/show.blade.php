<?php

use App\Models\WorkoutProgram;
use Livewire\Component;

new class extends Component
{
    public WorkoutProgram $program;
    public ?int $openVideoId = null;

    public function mount(WorkoutProgram $program): void
    {
        abort_unless($program->program_status === 'active', 404);
        $this->program = $program;
    }

    public function with(): array
    {
        return ['schedule' => $this->program->exercises()->with('exercise')->get()->groupBy('training_day')];
    }

    public function toggleVideo(int $programExerciseId): void
    {
        $this->openVideoId = $this->openVideoId === $programExerciseId ? null : $programExerciseId;
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
                    <article class="program-exercise-item">
                        @if($embedUrl = $item->embedUrl())
                            <button type="button" class="program-exercise-trigger" wire:click="toggleVideo({{ $item->program_exercise_id }})" aria-expanded="{{ $openVideoId === $item->program_exercise_id ? 'true' : 'false' }}">
                                <span><strong>{{ $item->exercise->exercise_name }}</strong><br>{{ $item->sets }} set × {{ $item->repetitions ?? $item->duration_minutes.' menit' }}</span>
                                <small>{{ $item->exercise->equipment }} · Istirahat {{ $item->rest_seconds }} detik</small>
                                <svg class="program-video-chevron {{ $openVideoId === $item->program_exercise_id ? 'is-open' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                            @if($openVideoId === $item->program_exercise_id)
                                <div class="program-video"><iframe src="{{ $embedUrl }}" title="Video {{ $item->exercise->exercise_name }}" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>
                            @endif
                        @else
                            <div class="program-exercise-info"><span><strong>{{ $item->exercise->exercise_name }}</strong><br>{{ $item->sets }} set × {{ $item->repetitions ?? $item->duration_minutes.' menit' }}</span><small>{{ $item->exercise->equipment }} · Istirahat {{ $item->rest_seconds }} detik</small></div>
                        @endif
                        @if(auth()->user()->hasRole('admin'))
                            <a class="table-action table-action-secondary program-exercise-edit" href="{{ route('workout-program-exercises.edit', [$program, $item]) }}" wire:navigate>Edit</a>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
