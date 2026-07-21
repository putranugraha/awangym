<?php

use App\Models\ProgramExercise;
use App\Models\WorkoutProgram;
use Illuminate\Validation\Validator;
use Livewire\Component;

new class extends Component
{
    public WorkoutProgram $program;
    public ProgramExercise $programExercise;
    public string $link = '';

    public function mount(WorkoutProgram $program, ProgramExercise $programExercise): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        abort_unless($programExercise->program_id === $program->program_id, 404);

        $this->program = $program;
        $this->programExercise = $programExercise->load('exercise');
        $this->link = $programExercise->link ?? '';
    }

    public function save(): void
    {
        $data = $this->validate([
            'link' => ['nullable', 'url', 'max:2048'],
        ]);

        validator($data, [])->after(function (Validator $validator) use ($data) {
            if (filled($data['link']) && ProgramExercise::toEmbedUrl($data['link']) === null) {
                $validator->errors()->add('link', 'Gunakan link video YouTube atau Vimeo yang valid agar dapat ditampilkan di halaman.');
            }
        })->validate();

        $this->programExercise->update(['link' => blank($data['link']) ? null : $data['link']]);

        session()->flash('success', 'Link video latihan berhasil diperbarui.');
        $this->redirectRoute('workout-programs.show', $this->program, navigate: true);
    }
};
?>

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">{{ $program->program_code }}</span><h1>Edit Video Exercise</h1><p>{{ $programExercise->exercise->exercise_name }} · Minggu {{ intdiv($programExercise->training_day - 1, 7) + 1 }}, Hari {{ (($programExercise->training_day - 1) % 7) + 1 }}</p></div>
        <a class="secondary-btn" href="{{ route('workout-programs.show', $program) }}" wire:navigate>Kembali</a>
    </header>

    <form wire:submit="save" class="form-layout">
        <section class="form-card">
            <div class="form-section-title"><span>01</span><div><h2>Link Video</h2><p>Masukkan URL YouTube atau Vimeo. Video akan ditampilkan langsung pada program latihan.</p></div></div>
            <label><span>Link video</span><input class="form-input" type="url" wire:model="link" placeholder="https://www.youtube.com/watch?v=..." autocomplete="url"></label>
            @error('link')<div class="error-box">{{ $message }}</div>@enderror
        </section>
        <aside class="form-side-stack">
            <section class="form-card program-exercise-summary"><span>Exercise</span><strong>{{ $programExercise->exercise->exercise_name }}</strong><small>{{ $programExercise->sets }} set × {{ $programExercise->repetitions ?? $programExercise->duration_minutes.' menit' }}</small></section>
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Link Video</span><span wire:loading>Menyimpan…</span></button>
        </aside>
    </form>
</div>
