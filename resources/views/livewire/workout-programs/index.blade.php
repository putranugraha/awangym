<?php

use App\Models\WorkoutProgram;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public string $difficulty = 'all';

    public function with(): array
    {
        $programs = WorkoutProgram::query()
            ->where('program_status', 'active')
            ->withCount('exercises')
            ->when($this->search, fn ($query) => $query->where(function ($searchQuery) {
                $searchQuery->where('program_name', 'like', "%{$this->search}%")
                    ->orWhere('program_code', 'like', "%{$this->search}%")
                    ->orWhere('target_goal', 'like', "%{$this->search}%");
            }))
            ->when($this->difficulty !== 'all', fn ($query) => $query->where('difficulty_level', $this->difficulty))
            ->orderByRaw("CASE difficulty_level WHEN 'beginner' THEN 1 WHEN 'intermediate' THEN 2 ELSE 3 END")
            ->get();

        return [
            'programs' => $programs,
            'totalPrograms' => WorkoutProgram::where('program_status', 'active')->count(),
            'beginnerPrograms' => WorkoutProgram::where('program_status', 'active')->where('difficulty_level', 'beginner')->count(),
            'intermediatePrograms' => WorkoutProgram::where('program_status', 'active')->where('difficulty_level', 'intermediate')->count(),
            'totalSchedules' => WorkoutProgram::where('program_status', 'active')->withCount('exercises')->get()->sum('exercises_count'),
        ];
    }
};
?>

<div class="awan-page">
    <header class="resource-header">
        <div><span class="eyebrow">KATALOG GYM</span><h1>Program Latihan</h1><p>Program bawaan Awan Gym yang dapat diberikan kepada member.</p></div>
    </header>

    <div class="transaction-stats">
        <article class="transaction-stat transaction-stat-featured"><span>Total program</span><strong>{{ $totalPrograms }}</strong><small>Katalog aktif Awan Gym</small></article>
        <article><span>Program beginner</span><strong>{{ $beginnerPrograms }}</strong><small>Untuk member pemula</small></article>
        <article><span>Program intermediate</span><strong>{{ $intermediatePrograms }}</strong><small>Latihan tingkat lanjutan</small></article>
        <article><span>Total jadwal</span><strong>{{ $totalSchedules }}</strong><small>Exercise dalam seluruh program</small></article>
    </div>

    <section class="data-panel">
        <div class="data-toolbar">
            <label class="data-search">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nama, kode, atau target program…">
            </label>
            <label class="data-filter">
                <span>Level</span>
                <select wire:model.live="difficulty">
                    <option value="all">Semua level</option>
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>
            </label>
        </div>

        <div class="program-catalog-grid">
            @forelse($programs as $program)
                <article class="program-summary-card">
                    <div class="program-summary-top">
                        <span class="chip">{{ ucfirst($program->difficulty_level) }}</span>
                        <span class="payment-status payment-status-paid"><i></i>Aktif</span>
                    </div>

                    <span class="program-catalog-code">{{ $program->program_code }}</span>
                    <h2>{{ $program->program_name }}</h2>
                    <p>{{ $program->description }}</p>

                    <div class="program-summary-meta">
                        <span><strong>{{ $program->duration_weeks }}</strong>Minggu</span>
                        <span><strong>{{ $program->exercises_count }}</strong>Jadwal</span>
                        <span><strong>{{ ucfirst($program->difficulty_level) }}</strong>Level</span>
                    </div>

                    <div class="program-catalog-target">
                        <span>Target program</span>
                        <strong>{{ $program->target_goal }}</strong>
                    </div>

                    <div class="program-catalog-footer">
                        <small>{{ $program->source_name ?: 'Katalog Awan Gym' }}</small>
                        <a class="table-action table-action-secondary" href="{{ route('workout-programs.show', $program) }}" wire:navigate>Lihat Program</a>
                    </div>
                </article>
            @empty
                <div class="table-empty program-catalog-empty"><strong>Program tidak ditemukan</strong><p>Coba ubah kata kunci atau filter level.</p></div>
            @endforelse
        </div>
    </section>
</div>
