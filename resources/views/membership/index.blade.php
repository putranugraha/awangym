<x-layouts::app title="Membership">
    <div class="awan-page">
        <div class="page-heading"><div><span class="eyebrow">KEANGGOTAAN</span><h1>Detail Membership</h1></div></div>

        <article class="summary-card">
            <span class="status status-{{ $status['key'] }}">{{ $status['label'] }}</span>
            <h2 class="mt-4 text-xl font-bold">{{ $currentSubscription?->package?->package_name ?? 'Belum ada paket aktif' }}</h2>
            <p>{{ $currentSubscription ? $currentSubscription->start_date->format('d M Y').' — '.$currentSubscription->end_date->format('d M Y') : 'Hubungi admin untuk pendaftaran membership.' }}</p>
        </article>

        @if($currentSubscription?->trainer)
            <article class="member-trainer-card">
                <div class="member-trainer-card-head">
                    <div><span class="eyebrow">PERSONAL TRAINER</span><h2>{{ $currentSubscription->trainer->user->full_name }}</h2><p>{{ $currentSubscription->trainer->trainer_code }} · {{ $currentSubscription->trainerSessions->count() }}/{{ $currentSubscription->trainer_session_limit }} pertemuan</p></div>
                    <a class="table-action table-action-primary" href="{{ route('my-program.index') }}" wire:navigate>Lihat catatan PT</a>
                </div>
            </article>
        @endif

        <div class="section-title"><h2>Riwayat subscription</h2></div>
        @foreach($subscriptions as $sub)
            <article class="list-card">
                <div class="grow"><h3>{{ $sub->package->package_name }}</h3><p>{{ $sub->start_date->format('d M Y') }} — {{ $sub->end_date->format('d M Y') }}</p></div>
                <span class="chip">{{ $sub->subscription_type === 'renewal' ? 'Perpanjangan' : 'Pendaftaran' }}</span>
            </article>
        @endforeach
    </div>
</x-layouts::app>
