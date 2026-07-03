<?php

use App\Models\MemberProgram;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $trainerId = auth()->user()->personalTrainer->trainer_id;

        return [
            'assignments' => MemberProgram::with(['member.user', 'program'])
                ->withCount('checks')
                ->where('trainer_id', $trainerId)
                ->where('program_status', 'active')
                ->latest('assigned_date')
                ->get(),
        ];
    }
};
?>

<div class="awan-page">
    <header class="resource-header"><div><span class="eyebrow">PENDAMPINGAN PT</span><h1>Member Binaan</h1><p>Validasi gerakan member yang didampingi secara langsung.</p></div></header>
    <div class="package-grid">
        @forelse($assignments as $assignment)
            <article class="package-card">
                <div><span class="chip">{{ $assignment->member->member_code }}</span><h2>{{ $assignment->member->user->full_name }}</h2><p>{{ $assignment->program->program_name }}</p></div>
                <div class="package-meta"><span><strong>{{ $assignment->checks_count }}</strong> gerakan divalidasi</span><span><strong>{{ $assignment->start_date->format('d M Y') }}</strong> mulai</span></div>
                <a class="table-action table-action-primary" href="{{ route('trainer-members.show', $assignment) }}" wire:navigate>Buka Checklist</a>
            </article>
        @empty
            <div class="empty-card"><h2>Belum ada member binaan</h2><p>Admin belum menugaskan member kepada Anda.</p></div>
        @endforelse
    </div>
</div>
