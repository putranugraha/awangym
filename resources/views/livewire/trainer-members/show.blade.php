<?php

use App\Models\MembershipSubscription;
use App\Models\TrainerSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public MembershipSubscription $subscription;
    public string $session_date = '';
    public string $notes = '';

    public function mount(MembershipSubscription $subscription): void
    {
        $trainerId = auth()->user()->personalTrainer?->trainer_id;
        abort_unless($trainerId && $subscription->trainer_id === $trainerId, 403);
        abort_unless($subscription->payment?->payment_status === 'paid', 403);

        $this->subscription = $subscription->load(['member.user', 'package', 'trainerSessions']);
        $this->session_date = today()->between($subscription->start_date, $subscription->end_date)
            ? today()->toDateString()
            : $subscription->start_date->toDateString();
    }

    public function saveSession(): void
    {
        abort_unless(auth()->user()->can('validate member exercises'), 403);
        $trainerId = auth()->user()->personalTrainer?->trainer_id;
        abort_unless($trainerId && $this->subscription->trainer_id === $trainerId, 403);

        $data = $this->validate([
            'session_date' => [
                'required',
                'date',
                'after_or_equal:'.$this->subscription->start_date->toDateString(),
                'before_or_equal:'.$this->subscription->end_date->toDateString(),
                'before_or_equal:today',
                Rule::unique('trainer_sessions', 'session_date')->where('subscription_id', $this->subscription->subscription_id),
            ],
            'notes' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'session_date.after_or_equal' => 'Tanggal pertemuan tidak boleh sebelum periode paket.',
            'session_date.before_or_equal' => 'Tanggal pertemuan harus berada dalam periode paket dan tidak boleh di masa depan.',
            'session_date.unique' => 'Pertemuan pada tanggal tersebut sudah tercatat.',
            'notes.required' => 'Catatan kegiatan pertemuan wajib diisi.',
        ]);

        DB::transaction(function () use ($data, $trainerId) {
            $subscription = MembershipSubscription::query()->lockForUpdate()->findOrFail($this->subscription->subscription_id);
            $used = TrainerSession::where('subscription_id', $subscription->subscription_id)->count();

            if ($used >= ($subscription->trainer_session_limit ?? 0)) {
                throw ValidationException::withMessages(['session_date' => 'Kuota pertemuan paket sudah habis.']);
            }

            TrainerSession::create($data + [
                'subscription_id' => $subscription->subscription_id,
                'trainer_id' => $trainerId,
            ]);
        });

        $this->notes = '';
        $this->subscription->refresh()->load(['member.user', 'package', 'trainerSessions']);
        session()->flash('success', 'Pertemuan berhasil dicatat.');
    }

    public function deleteSession(int $sessionId): void
    {
        abort_unless(auth()->user()->can('validate member exercises'), 403);
        $session = $this->subscription->trainerSessions()->whereKey($sessionId)->firstOrFail();
        abort_unless($session->trainer_id === auth()->user()->personalTrainer?->trainer_id, 403);
        $session->delete();
        $this->subscription->refresh()->load(['member.user', 'package', 'trainerSessions']);
        session()->flash('success', 'Catatan pertemuan dihapus.');
    }

    public function with(): array
    {
        $sessions = $this->subscription->trainerSessions()->oldest('session_date')->oldest('trainer_session_id')->get();
        $limit = $this->subscription->trainer_session_limit ?? 0;

        return ['sessions' => $sessions, 'sessionLimit' => $limit, 'remainingSessions' => max($limit - $sessions->count(), 0)];
    }
};
?>

@php
    $latestSessionDate = today()->lt($subscription->end_date) ? today() : $subscription->end_date;
    $periodHasStarted = today()->gte($subscription->start_date);
@endphp

<div class="awan-page">
    <header class="form-page-header"><div><span class="eyebrow">{{ $subscription->member->member_code }}</span><h1>{{ $subscription->member->user->full_name }}</h1><p>{{ $subscription->package->package_name }} · Pendampingan personal trainer</p></div><a class="secondary-btn" href="{{ route('trainer-members.index') }}" wire:navigate>Kembali</a></header>

    @if(session('success'))<div class="trainer-session-success">{{ session('success') }}</div>@endif

    <div class="program-content-stats">
        <article><span>Periode mulai</span><strong class="trainer-session-stat-date">{{ $subscription->start_date->format('d M Y') }}</strong><small>Tanggal sesi pertama paling awal</small></article>
        <article><span>Batas akhir</span><strong class="trainer-session-stat-date">{{ $subscription->end_date->format('d M Y') }}</strong><small>Tanggal sesi terakhir maksimal</small></article>
        <article><span>Kuota pertemuan</span><strong>{{ $sessions->count() }}/{{ $sessionLimit }}</strong><small>Tersisa {{ $remainingSessions }} pertemuan</small></article>
    </div>

    <div class="trainer-session-layout">
        <section class="form-card trainer-session-form">
            <div class="form-section-title"><span>+</span><div><h2>Catat Pertemuan</h2><p>Nomor pertemuan dibuat otomatis berdasarkan urutan tanggal.</p></div></div>
            @if($remainingSessions > 0 && $periodHasStarted)
                <form wire:submit="saveSession">
                    <label><span>Tanggal pertemuan <em>*</em></span><input class="form-input" type="date" min="{{ $subscription->start_date->toDateString() }}" max="{{ $latestSessionDate->toDateString() }}" wire:model="session_date"></label>
                    <label><span>Catatan latihan <em>*</em></span><textarea class="form-input" rows="5" wire:model="notes" placeholder="Contoh: Pertemuan pertama, latihan upper body. Gerakan bench press sudah baik, posisi bahu masih perlu diperbaiki."></textarea><small class="trainer-session-help">Catat kegiatan, perkembangan, dan hal yang perlu diperbaiki pada sesi berikutnya.</small></label>
                    @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
                    <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Pertemuan ke-{{ $sessions->count() + 1 }}</span><span wire:loading>Menyimpan…</span></button>
                </form>
            @elseif($remainingSessions === 0)
                <div class="trainer-session-limit"><strong>Kuota pertemuan sudah habis</strong><p>Seluruh {{ $sessionLimit }} sesi pada paket ini sudah tercatat.</p></div>
            @else
                <div class="trainer-session-limit"><strong>Periode belum dimulai</strong><p>Pertemuan baru dapat dicatat mulai {{ $subscription->start_date->format('d M Y') }}.</p></div>
            @endif
        </section>

        <section class="data-panel trainer-session-history">
            <div class="data-toolbar"><div><span class="eyebrow">RIWAYAT SESI</span><h2>Catatan Pertemuan</h2></div><span class="usage-count usage-count-active">{{ $sessions->count() }} sesi</span></div>
            <div class="trainer-session-list">
                @forelse($sessions as $session)
                    <article wire:key="session-{{ $session->trainer_session_id }}">
                        <span class="trainer-session-number">{{ $loop->iteration }}</span>
                        <div><div class="trainer-session-meta"><strong>Pertemuan ke-{{ $loop->iteration }}</strong><time>{{ $session->session_date->translatedFormat('l, d F Y') }}</time></div><p>{{ $session->notes }}</p></div>
                        <button type="button" class="table-action table-action-danger" wire:click="deleteSession({{ $session->trainer_session_id }})" wire:confirm="Hapus catatan pertemuan ini?">Hapus</button>
                    </article>
                @empty
                    <div class="table-empty"><strong>Belum ada pertemuan</strong><p>Catat sesi pertama menggunakan formulir di samping.</p></div>
                @endforelse
            </div>
        </section>
    </div>
</div>
