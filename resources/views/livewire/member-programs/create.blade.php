<?php

use App\Models\Member;
use App\Models\MemberProgram;
use App\Models\PersonalTrainer;
use App\Models\WorkoutProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url(as: 'member')]
    public int|string $member_id = '';

    public int|string $program_id = '';
    public int|string $trainer_id = '';
    public string $start_date = '';
    public string $trainer_notes = '';

    public function save(): void
    {
        $data = $this->validate([
            'member_id' => ['required', 'exists:members,member_id'],
            'program_id' => ['required', 'exists:workout_programs,program_id'],
            'trainer_id' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                if ($value !== 'none' && ! PersonalTrainer::where('employment_status', 'active')->whereKey($value)->exists()) {
                    $fail('Personal trainer yang dipilih tidak valid.');
                }
            }],
            'start_date' => ['required', 'date'],
            'trainer_notes' => ['nullable', 'string'],
        ]);
        $data['trainer_id'] = $data['trainer_id'] === 'none' ? null : $data['trainer_id'];
        $program = WorkoutProgram::where('program_status', 'active')->findOrFail($data['program_id']);

        DB::transaction(function () use ($data, $program) {
            Member::query()->lockForUpdate()->findOrFail($data['member_id']);
            if (MemberProgram::where('member_id', $data['member_id'])->where('program_status', 'active')->exists()) {
                throw ValidationException::withMessages(['member_id' => 'Member masih memiliki program aktif. Edit atau selesaikan program tersebut terlebih dahulu.']);
            }
            MemberProgram::create($data + [
                'assigned_date' => today(),
                'end_date' => now()->parse($data['start_date'])->addWeeks($program->duration_weeks)->subDay(),
                'progress_percentage' => 0,
                'program_status' => 'active',
            ]);
        });

        session()->flash('success', 'Program berhasil diberikan kepada member.');
        $this->redirectRoute('members.index', navigate: true);
    }

    public function with(): array
    {
        return [
            'members' => Member::with('user')->orderBy('member_code')->get(),
            'programs' => WorkoutProgram::where('program_status', 'active')->get(),
            'trainers' => PersonalTrainer::with('user')->where('employment_status', 'active')->get(),
        ];
    }
};
?>

@php
    $selectionComplete = filled($member_id) && filled($program_id);
    $trainerComplete = filled($trainer_id);
    $scheduleComplete = filled($start_date);
    $completedSections = collect([$selectionComplete, $trainerComplete, $scheduleComplete])->filter()->count();
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">ASSIGNMENT BARU</span><h1>Berikan Program</h1><p>Tetapkan program gym dan personal trainer opsional kepada member.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('workout-programs.index') }}" wire:navigate>Kembali</a>
    </header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card assignment-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Member dan Program</h2><p>Pilih member penerima dan program yang sesuai.</p></div></div>
            <label><span>Member <em>*</em></span><select class="form-input" wire:model.live="member_id"><option value="">Pilih member</option>@foreach($members as $member)<option value="{{ $member->member_id }}">{{ $member->member_code }} — {{ $member->user->full_name }}</option>@endforeach</select></label>
            <label><span>Program <em>*</em></span><select class="form-input" wire:model.live="program_id"><option value="">Pilih program</option>@foreach($programs as $program)<option value="{{ $program->program_id }}">{{ $program->program_name }} · {{ $program->duration_weeks }} minggu</option>@endforeach</select></label>

            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Pendampingan</h2><p>Tentukan apakah member menggunakan personal trainer.</p></div></div>
            <label><span>Personal trainer <em>*</em></span><select class="form-input" wire:model.live="trainer_id"><option value="">Pilih pendampingan</option><option value="none">Tanpa personal trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->trainer_id }}">{{ $trainer->trainer_code }} — {{ $trainer->user->full_name }}</option>@endforeach</select></label>

            <div class="form-section-title member-section-gap"><span>03</span><div><h2>Jadwal dan Catatan</h2><p>Tentukan awal periode program.</p></div></div>
            <label><span>Tanggal mulai <em>*</em></span><input class="form-input" type="date" wire:model.live="start_date"></label>
            <label><span>Catatan</span><textarea class="form-input" wire:model="trainer_notes" rows="3" placeholder="Catatan assignment (opsional)"></textarea></label>
        </section>
        <aside class="form-side-stack">
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan assignment</span><strong>{{ $completedSections }}/3 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 3) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="{{ $selectionComplete ? 'is-complete' : '' }}"><i>{{ $selectionComplete ? '✓' : '1' }}</i><span><strong>Member dan program</strong><small>Penerima dan program latihan</small></span></li>
                    <li class="{{ $trainerComplete ? 'is-complete' : '' }}"><i>{{ $trainerComplete ? '✓' : '2' }}</i><span><strong>Pendampingan</strong><small>Dengan PT atau latihan mandiri</small></span></li>
                    <li class="{{ $scheduleComplete ? 'is-complete' : '' }}"><i>{{ $scheduleComplete ? '✓' : '3' }}</i><span><strong>Jadwal program</strong><small>Tanggal mulai latihan</small></span></li>
                </ul>
            </section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Assignment</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('workout-programs.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>
