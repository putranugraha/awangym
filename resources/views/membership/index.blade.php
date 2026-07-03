<x-layouts::app title="Membership"><div class="awan-page">
<div class="page-heading"><div><span class="eyebrow">KEANGGOTAAN</span><h1>Detail Membership</h1></div></div>
<article class="summary-card"><span class="status status-{{ $status['key'] }}">{{ $status['label'] }}</span><h2 class="mt-4 text-xl font-bold">{{ $currentSubscription?->package?->package_name ?? 'Belum ada paket aktif' }}</h2><p>{{ $currentSubscription ? $currentSubscription->start_date->format('d M Y').' — '.$currentSubscription->end_date->format('d M Y') : 'Hubungi admin untuk pendaftaran membership.' }}</p></article>
<div class="section-title"><h2>Riwayat subscription</h2></div>
@foreach($subscriptions as $sub)<article class="list-card"><div class="grow"><h3>{{ $sub->package->package_name }}</h3><p>{{ $sub->start_date->format('d M Y') }} — {{ $sub->end_date->format('d M Y') }}</p></div><span class="chip">{{ $sub->subscription_type==='renewal'?'Perpanjangan':'Pendaftaran' }}</span></article>@endforeach
</div></x-layouts::app>

