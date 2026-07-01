<?php

namespace App\Livewire\Exercises;

use App\Models\Exercise;
use Livewire\Component;

class EditPage extends Component
{
    public Exercise $exercise;

    public string $exercise_name = '';

    public string $category = '';

    public string $description = '';

    public string $instruction = '';

    public string $image_url = '';

    public string $video_url = '';

    public string $exercise_status = 'active';

    public function mount(Exercise $exercise): void
    {
        $this->exercise = $exercise;
        $this->fill($exercise->only(['exercise_name', 'category', 'description', 'instruction', 'exercise_status']));
        $this->image_url = $exercise->image_url ?? '';
        $this->video_url = $exercise->video_url ?? '';
    }

    public function save(): void
    {
        $d = $this->validate(['exercise_name' => ['required', 'max:100'], 'category' => ['required', 'max:100'], 'description' => ['required'], 'instruction' => ['required'], 'image_url' => ['nullable', 'url'], 'video_url' => ['nullable', 'url'], 'exercise_status' => ['required', 'in:active,inactive']]);
        $this->exercise->update($d);
        session()->flash('success', 'Latihan berhasil diperbarui.');
        $this->redirectRoute('trainer.exercises', navigate: true);
    }

    public function render()
    {
        return view('livewire.exercises.edit-page')->layout('layouts.app', ['title' => 'Edit Latihan']);
    }
}
