<?php

namespace App\Livewire\Exercises;

use App\Models\Exercise;
use Livewire\Component;

class CreatePage extends Component
{
    public string $exercise_name = '';

    public string $category = '';

    public string $description = '';

    public string $instruction = '';

    public string $image_url = '';

    public string $video_url = '';

    public function save(): void
    {
        $d = $this->validate(['exercise_name' => ['required', 'max:100'], 'category' => ['required', 'max:100'], 'description' => ['required'], 'instruction' => ['required'], 'image_url' => ['nullable', 'url'], 'video_url' => ['nullable', 'url']]);
        Exercise::create($d + ['exercise_status' => 'active']);
        session()->flash('success', 'Latihan berhasil ditambahkan.');
        $this->redirectRoute('trainer.exercises', navigate: true);
    }

    public function render()
    {
        return view('livewire.exercises.create-page')->layout('layouts.app', ['title' => 'Tambah Latihan']);
    }
}
