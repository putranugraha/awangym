<x-layouts::app title="Program Saya"><div class="awan-page">
<div class="page-heading"><div><span class="eyebrow">WORKOUT</span><h1>Program Saya</h1></div></div>
@forelse($assignments as $assignment)<article class="program-panel"><div><span class="chip">{{ ucfirst($assignment->program_status) }}</span><h2>{{ $assignment->program->program_name }}</h2><p>Trainer {{ $assignment->trainer->user->full_name }} Â· Progress {{ number_format($assignment->progress_percentage) }}%</p></div>
@foreach($assignment->program->exercises->groupBy('training_day') as $day=>$items)<details class="day-card" @if($loop->first) open @endif><summary>Hari {{ $day }}</summary><div class="exercise-list">@foreach($items as $item)<div><span><strong>{{ $item->exercise->exercise_name }}</strong><br>{{ $item->sets ? $item->sets.' set Ã— '.$item->repetitions : $item->duration_minutes.' menit' }}</span><small>Istirahat {{ $item->rest_seconds ?? 0 }} detik</small></div>@endforeach</div></details>@endforeach</article>@empty<div class="empty-card"><h2>Belum ada program</h2><p>Program yang ditetapkan trainer akan muncul di sini.</p></div>@endforelse
</div></x-layouts::app>

