<?php

use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        return [
            'assignments' => auth()->user()->member->programs()
                ->with(['program.exercises.exercise', 'trainer.user', 'checks.validator.user'])
                ->latest('assigned_date')
                ->get(),
        ];
    }
};
?>

<div class="awan-page">
    <header class="resource-header">
        <div><span class="eyebrow">PROGRAM SAYA</span><h1>Jadwal Latihan</h1><p>Lihat jadwal exercise dan validasi dari personal trainer.</p></div>
    </header>

    @forelse($assignments as $assignment)
        @php
            $checked = $assignment->checks->keyBy('program_exercise_id');
            $totalExercises = $assignment->program->exercises->count();
            $completedExercises = $checked->count();
            $weeks = $assignment->program->exercises->groupBy(
                fn ($item) => intdiv($item->training_day - 1, 7) + 1
            );
        @endphp

        <section class="my-program-summary">
            <div class="my-program-summary-head">
                <div>
                    <span class="chip">{{ ucfirst($assignment->program_status) }}</span>
                    <h2>{{ $assignment->program->program_name }}</h2>
                    <p>{{ $assignment->program->description }}</p>
                </div>
                <span class="my-program-level">{{ ucfirst($assignment->program->difficulty_level) }}</span>
            </div>

            <div class="my-program-progress">
                <div><span>Progress latihan</span><strong>{{ $completedExercises }}/{{ $totalExercises }} exercise</strong></div>
                <div class="member-progress-track"><i style="width: {{ $assignment->progress_percentage }}%"></i></div>
            </div>

            <div class="program-content-stats">
                <article><span>Durasi program</span><strong>{{ $assignment->program->duration_weeks }}</strong><small>Minggu</small></article>
                <article><span>Personal trainer</span><strong class="my-program-trainer">{{ $assignment->trainer?->user?->full_name ?? 'Mandiri' }}</strong><small>{{ $assignment->trainer ? 'Pendamping latihan' : 'Tanpa pendamping' }}</small></article>
                <article><span>Periode</span><strong class="my-program-period">{{ $assignment->start_date->format('d M') }}</strong><small>sampai {{ $assignment->end_date?->format('d M Y') }}</small></article>
            </div>
        </section>

        <section class="data-panel">
            <div class="data-toolbar my-program-toolbar">
                <div><span class="eyebrow">JADWAL</span><h2>Susunan Latihan</h2><p>Buka setiap minggu untuk melihat exercise yang harus dilakukan.</p></div>
                <span class="payment-status {{ $assignment->trainer ? 'payment-status-paid' : 'payment-status-pending' }}"><i></i>{{ $assignment->trainer ? 'Dengan PT' : 'Latihan mandiri' }}</span>
            </div>

            <div class="program-weeks">
                @foreach($weeks as $weekNumber => $weekItems)
                    <details class="program-week" @if($loop->first) open @endif>
                        <summary class="program-week-header">
                            <span>MINGGU {{ $weekNumber }}</span>
                            <small>{{ $weekItems->groupBy('training_day')->count() }} hari latihan</small>
                        </summary>

                        @foreach($weekItems->groupBy('training_day') as $day => $items)
                            <div class="program-day">
                                <div class="program-day-header">
                                    <div><strong>Hari {{ (($day - 1) % 7) + 1 }}</strong><small>{{ $items->first()->session_name }}</small></div>
                                    <span>{{ $items->count() }} exercise</span>
                                </div>
                                <div class="my-program-exercise-list">
                                    @foreach($items as $item)
                                        @php($isChecked = isset($checked[$item->program_exercise_id]))
                                        <article class="my-program-exercise {{ $isChecked ? 'is-complete' : '' }}">
                                            <span class="my-program-exercise-order">{{ $loop->iteration }}</span>
                                            <div>
                                                <strong>{{ $item->exercise->exercise_name }}</strong>
                                                <small>{{ $item->sets ? $item->sets.' set × '.$item->repetitions : $item->duration_minutes.' menit' }} · Istirahat {{ $item->rest_seconds }} detik</small>
                                            </div>
                                            <span class="my-program-validation">{{ $isChecked ? '✓ Selesai' : ($assignment->trainer ? 'Belum divalidasi' : 'Mandiri') }}</span>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </details>
                @endforeach
            </div>
        </section>
    @empty
        <div class="empty-card"><h2>Belum ada program</h2><p>Admin akan memberikan program Gym Beginner atau Gym Strength.</p></div>
    @endforelse
</div>
