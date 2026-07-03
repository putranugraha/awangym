<?php

use App\Models\MemberProgram;
use App\Models\PersonalTrainer;
use App\Models\WorkoutProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public MemberProgram $memberProgram;
    public int|string $program_id = '';
    public int|string $trainer_id = '';
    public string $start_date = '';
    public string $program_status = '';
    public string $trainer_notes = '';

    public function mount(MemberProgram $memberProgram): void
    {
        $this->memberProgram = $memberProgram->load(['member.user', 'program', 'trainer.user']);
        $this->program_id = $memberProgram->program_id;
        $this->trainer_id = $memberProgram->trainer_id ?: 'none';
        $this->start_date = $memberProgram->start_date->format('Y-m-d');
        $this->program_status = $memberProgram->program_status;
        $this->trainer_notes = $memberProgram->trainer_notes ?? '';
    }

    public function save(): void
    {
        $data = $this->validate([
            'program_id' => ['required', 'exists:workout_programs,program_id'],
            'trainer_id' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                if ($value !== 'none' && ! PersonalTrainer::where('employment_status', 'active')->whereKey($value)->exists()) {
                    $fail('Personal trainer yang dipilih tidak valid.');
                }
            }],
            'start_date' => ['required', 'date'],
            'program_status' => ['required', 'in:active,completed,stopped'],
            'trainer_notes' => ['nullable', 'string'],
        ]);
        $data['trainer_id'] = $data['trainer_id'] === 'none' ? null : $data['trainer_id'];
        $program = WorkoutProgram::where('program_status', 'active')->findOrFail($data['program_id']);

        DB::transaction(function () use ($data, $program) {
            $assignment = MemberProgram::query()->lockForUpdate()->findOrFail($this->memberProgram->member_program_id);
            if ($data['program_status'] === 'active' && MemberProgram::where('member_id', $assignment->member_id)
                ->where('member_program_id', '!=', $assignment->member_program_id)->where('program_status', 'active')->exists()) {
                throw ValidationException::withMessages(['program_status' => 'Member sudah memiliki assignment aktif lainnya.']);
            }

            $programChanged = $assignment->program_id !== (int) $data['program_id'];
            $assignment->update($data + [
                'end_date' => now()->parse($data['start_date'])->addWeeks($program->duration_weeks)->subDay(),
                'progress_percentage' => $programChanged ? 0 : $assignment->progress_percentage,
            ]);
            if ($programChanged) {
                $assignment->checks()->delete();
            }
        });

        session()->flash('success', 'Assignment program berhasil diperbarui.');
        $this->redirectRoute('members.index', navigate: true);
    }

    public function with(): array
    {
        return [
            'programs' => WorkoutProgram::where('program_status', 'active')->get(),
            'trainers' => PersonalTrainer::with('user')->where('employment_status', 'active')->get(),
        ];
    }
};
?>

@php
    $programComplete = filled($program_id);
    $trainerComplete = filled($trainer_id);
    $scheduleComplete = filled($start_date) && filled($program_status);
    $completedSections = collect([$programComplete, $trainerComplete, $scheduleComplete])->filter()->count();
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">{{ $memberProgram->member->member_code }}</span><h1>Edit Assignment</h1><p>Perbarui program dan pendampingan {{ $memberProgram->member->user->full_name }}.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('members.index') }}" wire:navigate>Kembali</a>
    </header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card assignment-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Program Member</h2><p>Pilih katalog program yang dijalankan.</p></div></div>
            <label><span>Program <em>*</em></span><select class="form-input" wire:model.live="program_id">@foreach($programs as $program)<option value="{{ $program->program_id }}">{{ $program->program_name }} · {{ $program->duration_weeks }} minggu</option>@endforeach</select></label>

            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Pendampingan</h2><p>Ganti PT atau ubah menjadi latihan mandiri.</p></div></div>
            <label><span>Personal trainer <em>*</em></span><select class="form-input" wire:model.live="trainer_id"><option value="none">Tanpa personal trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->trainer_id }}">{{ $trainer->trainer_code }} — {{ $trainer->user->full_name }}</option>@endforeach</select></label>

            <div class="form-section-title member-section-gap"><span>03</span><div><h2>Jadwal dan Status</h2><p>Atur periode dan status assignment.</p></div></div>
            <div class="form-grid">
                <label><span>Tanggal mulai <em>*</em></span><input class="form-input" type="date" wire:model.live="start_date"></label>
                <label><span>Status program <em>*</em></span><select class="form-input" wire:model.live="program_status"><option value="active">Aktif</option><option value="completed">Selesai</option><option value="stopped">Dihentikan</option></select></label>
            </div>
            <label><span>Catatan</span><textarea class="form-input" wire:model="trainer_notes" rows="3" placeholder="Catatan assignment (opsional)"></textarea></label>
        </section>
        <aside class="form-side-stack">
            <section class="form-card assignment-member-card">
                <span class="member-identity-avatar">{{ $memberProgram->member->user->initials() }}</span>
                <div><strong>{{ $memberProgram->member->user->full_name }}</strong><small>{{ $memberProgram->member->member_code }} · {{ number_format($memberProgram->progress_percentage) }}% selesai</small></div>
                <span class="chip">{{ ucfirst($program_status) }}</span>
            </section>
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan assignment</span><strong>{{ $completedSections }}/3 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 3) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="{{ $programComplete ? 'is-complete' : '' }}"><i>{{ $programComplete ? '✓' : '1' }}</i><span><strong>Program member</strong><small>Katalog latihan terpilih</small></span></li>
                    <li class="{{ $trainerComplete ? 'is-complete' : '' }}"><i>{{ $trainerComplete ? '✓' : '2' }}</i><span><strong>Pendampingan</strong><small>Dengan PT atau latihan mandiri</small></span></li>
                    <li class="{{ $scheduleComplete ? 'is-complete' : '' }}"><i>{{ $scheduleComplete ? '✓' : '3' }}</i><span><strong>Jadwal dan status</strong><small>Periode assignment</small></span></li>
                </ul>
            </section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Perubahan</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('members.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>
