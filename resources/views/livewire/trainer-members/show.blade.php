<?php

use App\Models\MemberExerciseCheck;
use App\Models\MemberProgram;
use App\Models\ProgramExercise;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public MemberProgram $memberProgram;

    public function mount(MemberProgram $memberProgram): void
    {
        $trainerId = auth()->user()->personalTrainer->trainer_id;
        abort_unless($memberProgram->trainer_id === $trainerId, 403);
        $this->memberProgram = $memberProgram->load(['member.user', 'program']);
    }

    public function toggle(int $programExerciseId): void
    {
        abort_unless(auth()->user()->can('validate member exercises'), 403);
        $trainerId = auth()->user()->personalTrainer->trainer_id;
        abort_unless($this->memberProgram->trainer_id === $trainerId && $this->memberProgram->program_status === 'active', 403);

        $item = ProgramExercise::where('program_id', $this->memberProgram->program_id)->findOrFail($programExerciseId);
        DB::transaction(function () use ($item, $trainerId) {
            $check = MemberExerciseCheck::where('member_program_id', $this->memberProgram->member_program_id)
                ->where('program_exercise_id', $item->program_exercise_id)
                ->first();

            if ($check) {
                $check->delete();
            } else {
                MemberExerciseCheck::create([
                    'member_program_id' => $this->memberProgram->member_program_id,
                    'program_exercise_id' => $item->program_exercise_id,
                    'validated_by' => $trainerId,
                    'validated_at' => now(),
                ]);
            }

            $total = $this->memberProgram->program->exercises()->count();
            $completed = $this->memberProgram->checks()->count();
            $this->memberProgram->update(['progress_percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0]);
        });
    }

    public function with(): array
    {
        return [
            'schedule' => $this->memberProgram->program->exercises()->with('exercise')->get()->groupBy('training_day'),
            'checkedIds' => $this->memberProgram->checks()->pluck('program_exercise_id')->all(),
        ];
    }
};
?>

<div class="awan-page">
    <header class="form-page-header"><div><span class="eyebrow">{{ $memberProgram->member->member_code }}</span><h1>{{ $memberProgram->member->user->full_name }}</h1><p>{{ $memberProgram->program->program_name }} · PT hanya memvalidasi gerakan yang dilakukan bersama member.</p></div><a class="secondary-btn" href="{{ route('trainer-members.index') }}" wire:navigate>Kembali</a></header>
    @foreach($schedule as $day => $items)
        <section class="form-card">
            <div class="section-title"><h2>Minggu {{ intdiv($day - 1, 7) + 1 }} · Hari {{ (($day - 1) % 7) + 1 }}</h2><span>{{ $items->first()->session_name }}</span></div>
            <div class="exercise-list">
                @foreach($items as $item)
                    <div wire:key="check-{{ $item->program_exercise_id }}">
                        <span><strong>{{ $item->exercise->exercise_name }}</strong><br>{{ $item->sets }} set × {{ $item->repetitions ?? $item->duration_minutes.' menit' }}</span>
                        <button type="button" class="table-action {{ in_array($item->program_exercise_id, $checkedIds) ? 'table-action-primary' : 'table-action-secondary' }}" wire:click="toggle({{ $item->program_exercise_id }})">
                            {{ in_array($item->program_exercise_id, $checkedIds) ? '✓ Selesai' : 'Validasi' }}
                        </button>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
