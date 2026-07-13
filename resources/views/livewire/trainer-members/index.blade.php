<?php

use App\Models\MemberProgram;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public function with(): array
    {
        $trainer = auth()->user()->personalTrainer;
        
        if (!$trainer) {
            return [
                'assignments' => collect(),
                'totalAssignments' => 0,
                'pendingPrograms' => 0,
                'totalChecks' => 0
            ];
        }
        
        $trainerId = $trainer->trainer_id;

        $assignmentsQuery = MemberProgram::with(['member.user', 'program'])
            ->withCount('checks')
            ->where('trainer_id', $trainerId)
            ->where('program_status', 'active');

        if ($this->search) {
            $assignmentsQuery->where(function($query) {
                $query->whereHas('member.user', function ($uQuery) {
                    $uQuery->where('full_name', 'like', "%{$this->search}%");
                })->orWhereHas('member', function ($mQuery) {
                    $mQuery->where('member_code', 'like', "%{$this->search}%");
                });
            });
        }

        $assignments = $assignmentsQuery->latest('assigned_date')->get();

        // Calculate statistics
        $totalAssignments = $assignments->count();
        $pendingPrograms = $assignments->filter(fn($a) => $a->checks_count === 0)->count();
        $totalChecks = $assignments->sum('checks_count');

        return [
            'assignments' => $assignments,
            'totalAssignments' => $totalAssignments,
            'pendingPrograms' => $pendingPrograms,
            'totalChecks' => $totalChecks
        ];
    }
};
?>

<div class="awan-page">
    <header class="resource-header">
        <div>
            <span class="eyebrow">PENDAMPINGAN PT</span>
            <h1>Member Binaan</h1>
            <p>Kelola program latihan dan validasi checklist gerakan member Anda.</p>
        </div>
    </header>

    <div class="resource-stats">
        <article><span>Total member</span><strong>{{ $totalAssignments }}</strong></article>
        <article><span>Belum mulai latihan</span><strong class="text-warning">{{ $pendingPrograms }}</strong></article>
        <article><span>Gerakan tervalidasi</span><strong class="text-success">{{ $totalChecks }}</strong></article>
    </div>

    <section class="data-panel">
        <div class="data-toolbar">
            <label class="data-search">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau kode member…">
            </label>
        </div>

        <div class="responsive-table">
            <table class="member-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Program Latihan</th>
                        <th>Progress</th>
                        <th>Masa Latihan</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)
                        <tr wire:key="assignment-{{ $assignment->member_program_id }}">
                            <td data-label="Member">
                                <div class="member-cell">
                                    <span class="member-table-avatar">{{ $assignment->member->user->initials() }}</span>
                                    <span>
                                        <strong>{{ $assignment->member->user->full_name }}</strong>
                                        <small>{{ $assignment->member->member_code }}</small>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Program Latihan">
                                <span class="table-primary">{{ $assignment->program->program_name }}</span>
                                <small class="table-secondary">{{ ucfirst($assignment->program->difficulty_level) }} · {{ $assignment->program->duration_weeks }} Minggu</small>
                            </td>
                            <td data-label="Progress">
                                <span class="usage-count usage-count-active">{{ $assignment->checks_count }} gerakan</span>
                                <small class="table-secondary">Tervalidasi</small>
                            </td>
                            <td data-label="Masa Latihan">
                                <span class="table-primary">{{ $assignment->start_date->format('d M Y') }}</span>
                                <small class="table-secondary">s/d {{ $assignment->end_date->format('d M Y') }}</small>
                            </td>
                            <td data-label="Aksi" class="action-column">
                                <a class="table-action table-action-primary" href="{{ route('trainer-members.show', $assignment) }}" wire:navigate>
                                    Buka Checklist
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="table-empty">
                                    <strong>Member tidak ditemukan</strong>
                                    <p>Belum ada program latihan aktif yang didampingi oleh Anda.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
