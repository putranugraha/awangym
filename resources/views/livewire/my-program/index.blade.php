<?php

use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $member = auth()->user()->member;

        return [
            'assignments' => $member->programs()
                ->with(['program.exercises.exercise', 'trainer.user'])
                ->where('program_status', 'active')
                ->latest('assigned_date')
                ->get()
                ->unique('program_id')
                ->values(),
            'trainerSubscription' => $member->subscriptions()
                ->with(['package', 'trainer.user', 'trainerSessions'])
                ->whereNotNull('trainer_id')
                ->current()
                ->latest('end_date')
                ->first(),
        ];
    }
};
?>

<div class="awan-page">
    <header class="resource-header">
        <div><span class="eyebrow">PROGRAM SAYA</span><h1>Referensi Latihan</h1><p>Lihat contoh susunan latihan untuk membantu member pemula berlatih dengan benar.</p></div>
    </header>

    @if($trainerSubscription)
        @php
            $trainerSessions = $trainerSubscription->trainerSessions->sortBy('session_date')->values();
            $remainingSessions = max(($trainerSubscription->trainer_session_limit ?? 0) - $trainerSessions->count(), 0);
        @endphp
        <section class="member-trainer-panel">
            <div class="member-trainer-panel-head">
                <div><span class="eyebrow">PENDAMPINGAN PT</span><h2>{{ $trainerSubscription->trainer->user->full_name }}</h2><p>{{ $trainerSubscription->package->package_name }} · {{ $trainerSubscription->start_date->format('d M Y') }}–{{ $trainerSubscription->end_date->format('d M Y') }}</p></div>
                <div class="member-trainer-quota"><strong>{{ $trainerSessions->count() }}/{{ $trainerSubscription->trainer_session_limit }}</strong><span>Pertemuan</span><small>Sisa {{ $remainingSessions }} sesi</small></div>
            </div>

            <div class="member-session-heading"><div><h3>Catatan Personal Trainer</h3><p>Tanggal dan catatan berikut diinput langsung oleh trainer pendampingmu.</p></div></div>
            <div class="member-session-list">
                @forelse($trainerSessions as $session)
                    <article>
                        <span class="trainer-session-number">{{ $loop->iteration }}</span>
                        <div>
                            <div class="trainer-session-meta"><strong>Pertemuan ke-{{ $loop->iteration }}</strong><time>{{ $session->session_date->translatedFormat('l, d F Y') }}</time></div>
                            <p>{{ $session->notes }}</p>
                        </div>
                    </article>
                @empty
                    <div class="member-session-empty"><strong>Belum ada catatan pertemuan</strong><p>Catatan akan tampil setelah trainer menyimpan sesi latihan pertama.</p></div>
                @endforelse
            </div>
        </section>
    @endif

    <div class="member-reference-heading">
        <div><span class="eyebrow">CONTOH PROGRAM</span><h2>Panduan Latihan</h2><p>Program berikut adalah referensi, bukan pencatatan progres latihan.</p></div>
    </div>

    @forelse($assignments as $assignment)
        @php
            $weeks = $assignment->program->exercises->groupBy(
                fn ($item) => intdiv($item->training_day - 1, 7) + 1
            );
        @endphp

        <section class="my-program-summary">
            <div class="my-program-summary-head">
                <div><span class="chip">Referensi</span><h2>{{ $assignment->program->program_name }}</h2><p>{{ $assignment->program->description }}</p></div>
                <span class="my-program-level">{{ ucfirst($assignment->program->difficulty_level) }}</span>
            </div>

            <div class="program-content-stats">
                <article><span>Durasi contoh</span><strong>{{ $assignment->program->duration_weeks }}</strong><small>Minggu</small></article>
                <article><span>Tingkat</span><strong class="my-program-trainer">{{ ucfirst($assignment->program->difficulty_level) }}</strong><small>Level latihan</small></article>
                <article><span>Tujuan</span><strong class="my-program-period">{{ $assignment->program->target_goal }}</strong><small>Target program</small></article>
            </div>
        </section>

        <section class="data-panel">
            <div class="data-toolbar my-program-toolbar">
                <div><span class="eyebrow">JADWAL REFERENSI</span><h2>Susunan Latihan</h2><p>Buka setiap minggu untuk melihat contoh exercise.</p></div>
                <span class="payment-status payment-status-pending"><i></i>Panduan pemula</span>
            </div>

            <div class="program-weeks">
                @foreach($weeks as $weekNumber => $weekItems)
                    <details class="program-week" @if($loop->first) open @endif>
                        <summary class="program-week-header"><span>MINGGU {{ $weekNumber }}</span><small>{{ $weekItems->groupBy('training_day')->count() }} hari latihan</small></summary>
                        @foreach($weekItems->groupBy('training_day') as $day => $items)
                            <div class="program-day">
                                <div class="program-day-header"><div><strong>Hari {{ (($day - 1) % 7) + 1 }}</strong><small>{{ $items->first()->session_name }}</small></div><span>{{ $items->count() }} exercise</span></div>
                                <div class="my-program-exercise-list">
                                    @foreach($items as $item)
                                        <article class="my-program-exercise member-reference-exercise">
                                            <span class="my-program-exercise-order">{{ $loop->iteration }}</span>
                                            <div><strong>{{ $item->exercise->exercise_name }}</strong><small>{{ $item->sets ? $item->sets.' set × '.$item->repetitions : $item->duration_minutes.' menit' }} · Istirahat {{ $item->rest_seconds }} detik</small></div>
                                            <span class="my-program-validation">Contoh</span>
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
        <div class="empty-card"><h2>Belum ada referensi program</h2><p>Admin akan memberikan contoh Gym Beginner atau Gym Strength.</p></div>
    @endforelse
</div>
