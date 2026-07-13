<?php

use App\Models\PersonalTrainer;
use App\Models\WorkoutProgram;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function toggleStatus(int $trainerId): void
    {
        $nextStatus = DB::transaction(function () use ($trainerId) {
            $trainer = PersonalTrainer::with('user')->lockForUpdate()->findOrFail($trainerId);
            $nextStatus = $trainer->employment_status === 'active' ? 'inactive' : 'active';

            $trainer->update(['employment_status' => $nextStatus]);
            $trainer->user->update(['account_status' => $nextStatus]);

            return $nextStatus;
        });

        session()->flash(
            'success',
            $nextStatus === 'active'
                ? 'Trainer dan akun berhasil diaktifkan.'
                : 'Trainer dan akun berhasil dinonaktifkan.'
        );
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteTrainer(): void
    {
        abort_unless(auth()->user()->can('manage trainers'), 403);
        
        $trainer = PersonalTrainer::findOrFail($this->deletingId);
        $this->showDeleteModal = false;
        
        if ($trainer->memberPrograms()->exists()) {
            session()->flash('error', 'Trainer tidak dapat dihapus karena ditugaskan pada program latihan member.');
            return;
        }

        if (\App\Models\MemberExerciseCheck::where('validated_by', $trainer->trainer_id)->exists()) {
            session()->flash('error', 'Trainer tidak dapat dihapus karena memiliki riwayat validasi gerakan member.');
            return;
        }

        DB::transaction(function () use ($trainer) {
            $user = $trainer->user;
            $trainer->delete();
            $user->delete();
        });

        session()->flash('success', 'Trainer berhasil dihapus.');
    }

    public function with(): array
    {
        $trainers = PersonalTrainer::with([
            'user',
            'memberPrograms:member_program_id,trainer_id,member_id,program_status',
        ])
            ->when($this->search, fn ($query) => $query->where(function ($searchQuery) {
                $searchQuery->where('trainer_code', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%"));
            }))
            ->when($this->status !== 'all', fn ($query) => $query->where('employment_status', $this->status))
            ->latest()
            ->paginate(12);

        return [
            'trainers' => $trainers,
            'totalTrainers' => PersonalTrainer::count(),
            'activeTrainers' => PersonalTrainer::where('employment_status', 'active')->count(),
            'activePrograms' => WorkoutProgram::where('program_status', 'active')->count(),
            'managedMembers' => PersonalTrainer::whereHas('memberPrograms', fn ($query) => $query->where('program_status', 'active'))
                ->with('memberPrograms:member_program_id,trainer_id,member_id,program_status')
                ->get()
                ->flatMap(fn ($trainer) => $trainer->memberPrograms->where('program_status', 'active')->pluck('member_id'))
                ->unique()
                ->count(),
        ];
    }
};
?>

<div class="awan-page">
    <header class="resource-header">
        <div>
            <span class="eyebrow">TIM AWAN GYM</span>
            <h1>Personal Trainer</h1>
            <p>Kelola akun trainer, program aktif, dan member binaan.</p>
        </div>
        <a class="primary-btn" href="{{ route('personal-trainers.create') }}" wire:navigate>
            <span>+</span> Tambah Trainer
        </a>
    </header>

    <div class="trainer-stats">
        <article><span>Total trainer</span><strong>{{ $totalTrainers }}</strong><small>Seluruh akun trainer</small></article>
        <article><span>Trainer aktif</span><strong class="text-success">{{ $activeTrainers }}</strong><small>Dapat mengakses sistem</small></article>
        <article><span>Program aktif</span><strong>{{ $activePrograms }}</strong><small>Program sedang tersedia</small></article>
        <article class="trainer-stat-featured"><span>Member binaan</span><strong>{{ $managedMembers }}</strong><small>Member dengan program aktif</small></article>
    </div>

    @if(session('success'))
        <div x-data x-init="Flux.toast({ variant: 'success', text: '{{ session('success') }}' })"></div>
    @endif
    @if(session('error'))
        <div x-data x-init="Flux.toast({ variant: 'danger', text: '{{ session('error') }}' })"></div>
    @endif

    <section class="data-panel">
        <div class="data-toolbar">
            <label class="data-search">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                <input wire:model.live.debounce.300ms="search" placeholder="Cari nama, kode, atau email…">
            </label>
            <label class="data-filter">
                <span>Status</span>
                <select wire:model.live="status">
                    <option value="all">Semua trainer</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </label>
        </div>

        <div class="responsive-table">
            <table class="member-table trainer-table">
                <thead>
                    <tr>
                        <th>Personal Trainer</th>
                        <th>Kontak</th>
                        <th>Program</th>
                        <th>Member Binaan</th>
                        <th>Status Pekerjaan</th>
                        <th>Status Akun</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trainers as $trainer)
                        @php
                            $activeManagedMembers = $trainer->memberPrograms
                                ->where('program_status', 'active')
                                ->pluck('member_id')
                                ->unique()
                                ->count();
                        @endphp
                        <tr wire:key="trainer-{{ $trainer->trainer_id }}">
                            <td data-label="Trainer">
                                <div class="member-cell trainer-cell">
                                    @if($trainer->profile_photo)
                                        <img class="trainer-table-photo" src="{{ Storage::url($trainer->profile_photo) }}" alt="Foto {{ $trainer->user->full_name }}">
                                    @else
                                        <span class="member-table-avatar">{{ $trainer->user->initials() }}</span>
                                    @endif
                                    <span>
                                        <strong>{{ $trainer->user->full_name }}</strong>
                                        <small>{{ $trainer->trainer_code }}</small>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Kontak">
                                <span class="table-primary">{{ $trainer->user->email }}</span>
                                <small class="table-secondary">{{ $trainer->user->phone }}</small>
                            </td>
                            <td data-label="Program">
                                <span class="trainer-count">{{ $trainer->memberPrograms->where('program_status', 'active')->count() }}</span>
                                <small class="table-secondary">assignment aktif</small>
                            </td>
                            <td data-label="Member Binaan">
                                <span class="trainer-count trainer-count-member">{{ $activeManagedMembers }}</span>
                                <small class="table-secondary">program aktif</small>
                            </td>
                            <td data-label="Status Pekerjaan">
                                <span class="package-status package-status-{{ $trainer->employment_status }}">
                                    <i></i>{{ $trainer->employment_status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td data-label="Status Akun">
                                <span class="account-status account-status-{{ $trainer->user->account_status }}">
                                    {{ $trainer->user->account_status === 'active' ? 'Dapat login' : 'Diblokir' }}
                                </span>
                            </td>
                            <td data-label="Aksi" class="action-column">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="right" />
                                    <flux:menu>
                                        <flux:menu.item href="{{ route('personal-trainers.edit', $trainer) }}" icon="pencil-square" wire:navigate>
                                            Edit Trainer
                                        </flux:menu.item>
                                        <flux:menu.item
                                            wire:click="toggleStatus({{ $trainer->trainer_id }})"
                                            wire:confirm="{{ $trainer->employment_status === 'active' ? 'Nonaktifkan trainer dan blokir akses login?' : 'Aktifkan kembali trainer dan akses login?' }}"
                                            icon="{{ $trainer->employment_status === 'active' ? 'x-circle' : 'check-circle' }}"
                                            variant="{{ $trainer->employment_status === 'active' ? 'danger' : 'primary' }}"
                                        >
                                            {{ $trainer->employment_status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </flux:menu.item>
                                        <flux:menu.item
                                            wire:click="confirmDelete({{ $trainer->trainer_id }})"
                                            variant="danger"
                                            icon="trash"
                                        >
                                            Hapus Trainer
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="table-empty">
                                    <strong>Trainer tidak ditemukan</strong>
                                    <p>Coba ubah pencarian atau filter status.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($trainers->hasPages())
            <div class="data-pagination">{{ $trainers->links() }}</div>
        @endif
    </section>

    <flux:modal name="delete-confirm" wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Hapus Trainer?</flux:heading>
                <flux:text>Apakah Anda yakin ingin menghapus trainer ini? Akun user terkait juga akan dihapus secara permanen.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="outline" wire:click="$set('showDeleteModal', false)">Batal</flux:button>
                <flux:button variant="danger" wire:click="deleteTrainer">Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
