<?php

use App\Services\TrainerSalaryService;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url]
    public string $month = '';

    public function mount(): void
    {
        $this->authorizeSalary();
        if (! $this->validMonth()) {
            $this->month = now()->format('Y-m');
        }
    }

    public function updatedMonth(): void
    {
        $this->validateOnly('month', ['month' => ['required', 'date_format:Y-m']]);
    }

    private function validMonth(): bool
    {
        return (bool) preg_match('/^[1-9]\d{3}-(0[1-9]|1[0-2])$/', $this->month);
    }

    private function authorizeSalary(): void
    {
        abort_unless(auth()->user()?->hasRole('personal_trainer') && auth()->user()->can('view own salary'), 403);
    }

    public function with(): array
    {
        $this->authorizeSalary();
        $trainer = auth()->user()->personalTrainer;
        $month = $this->validMonth() ? $this->month : now()->format('Y-m');

        return [
            'trainer' => $trainer,
            'summary' => $trainer ? app(TrainerSalaryService::class)->summarize($trainer, $month) : null,
        ];
    }
};
?>

<div class="awan-page salary-page">
    <header class="resource-header">
        <div><span class="eyebrow">PENDAPATAN TRAINER</span><h1>Salary</h1><p>Perhitungan gaji bulanan dari layanan PT member binaanmu.</p></div>
        <label class="salary-month"><span>Periode gaji</span><input class="form-input" type="month" wire:model.live="month" value="{{ $month }}" aria-label="Pilih bulan gaji"></label>
    </header>
    @error('month')<div class="error-box" role="alert">{{ $message }}</div>@enderror

    @if(! $trainer)
        <section class="empty-card"><h2>Profil trainer belum lengkap</h2><p>Hubungi admin untuk menghubungkan akunmu dengan data personal trainer.</p></section>
    @else
        <section class="salary-hero" aria-labelledby="salary-total">
            <div>
                <span class="salary-hero-kicker">{{ $summary['period_label'] }}</span>
                <h2 id="salary-total">Estimasi gaji periode ini</h2>
                <strong class="salary-total">Rp {{ number_format($summary['total'], 0, ',', '.') }}</strong>
                <p>{{ $trainer->user->full_name }} <span aria-hidden="true">·</span> {{ $trainer->trainer_code }}</p>
            </div>
            <div class="salary-hero-note"><span>ESTIMASI</span><p>{{ $summary['is_future'] ? 'Proyeksi dari layanan yang sudah lunas untuk bulan mendatang.' : 'Dihitung untuk seluruh bulan yang dipilih, termasuk hari layanan yang belum lewat.' }}</p><small>Belum merupakan bukti pembayaran gaji.</small></div>
        </section>

        <dl class="salary-metrics">
            <div><dt>Member diperhitungkan</dt><dd>{{ $summary['member_count'] }} <span>member</span></dd><p>Layanan PT berbayar pada periode ini.</p></div>
            <div><dt>Tarif gaji per member</dt><dd>Rp {{ number_format($summary['monthly_rate'], 0, ',', '.') }}</dd><p>Untuk satu siklus bulanan PT penuh.</p></div>
            <div><dt>Hari layanan dihitung</dt><dd>{{ $summary['service_days'] }} <span>hari</span></dd><p>Akumulasi hari dari seluruh member.</p></div>
        </dl>

        <section class="salary-breakdown" aria-labelledby="salary-breakdown-title">
            <div class="salary-section-heading"><div><span class="eyebrow">TRANSPARANSI GAJI</span><h2 id="salary-breakdown-title">Rincian per Member</h2><p>Buka rincian untuk melihat periode, invoice, dan pembagian gaji.</p></div><span class="salary-count">{{ $summary['member_count'] }} member</span></div>
            @forelse($summary['members'] as $row)
                <details class="salary-member" wire:key="salary-{{ $summary['month'] }}-{{ $row['member_id'] }}">
                    <summary>
                        <span class="salary-member-identity"><span class="salary-avatar" aria-hidden="true">{{ mb_substr($row['name'], 0, 1) }}</span><span><strong>{{ $row['name'] }}</strong><small>{{ $row['code'] }} · {{ $row['days'] }} hari dihitung</small></span></span>
                        <span class="salary-member-amount"><strong>Rp {{ number_format($row['amount'], 0, ',', '.') }}</strong><small>Lihat rincian <span class="salary-chevron" aria-hidden="true">⌄</span></small></span>
                    </summary>
                    <div class="salary-segments">
                        @foreach($row['segments'] as $segment)
                            <article class="salary-segment">
                                <div class="salary-segment-title"><span class="salary-source">{{ $segment['source'] }}</span><span>{{ $segment['invoice'] }}</span></div>
                                <h3>{{ $segment['package'] }}</h3>
                                <dl class="salary-periods">
                                    <div><dt>Periode layanan PT</dt><dd>{{ $segment['period_start']->translatedFormat('d M Y') }} – {{ $segment['period_end']->translatedFormat('d M Y') }}</dd></div>
                                    <div><dt>Siklus gaji</dt><dd>{{ $segment['cycle_start']->translatedFormat('d M Y') }} – {{ $segment['cycle_end']->translatedFormat('d M Y') }}</dd></div>
                                    <div><dt>Hari dihitung pada bulan ini</dt><dd>{{ $segment['counted_from']->translatedFormat('d M Y') }} – {{ $segment['counted_until']->translatedFormat('d M Y') }}</dd></div>
                                </dl>
                                <div class="salary-calculation"><span>Rp {{ number_format($summary['monthly_rate'], 0, ',', '.') }} × {{ $segment['days'] }} / {{ $segment['cycle_days'] }} hari</span><strong>Rp {{ number_format($segment['amount'], 0, ',', '.') }}</strong></div>
                                @if($segment['skipped_days'])<p class="salary-footnote">{{ $segment['skipped_days'] }} hari tumpang tindih sudah dihitung melalui periode lain.</p>@endif
                            </article>
                        @endforeach
                    </div>
                </details>
            @empty
                <div class="salary-empty"><span aria-hidden="true">Rp</span><h3>Belum ada gaji yang terhitung</h3><p>Tidak ada layanan PT yang memenuhi syarat pada {{ $summary['period_label'] }}. Pembayaran pending, gagal, dikembalikan, dan membership yang dibatalkan tidak dihitung.</p></div>
            @endforelse
            @if($summary['member_count'])
                <div class="salary-total-row"><span>Total estimasi · {{ $summary['period_label'] }}</span><strong>Rp {{ number_format($summary['total'], 0, ',', '.') }}</strong></div>
            @endif
            @if($summary['duplicate_days'])<p class="salary-overlap-note">Ada periode member yang tumpang tindih. Hari yang sama pada member yang sama hanya dihitung sekali.</p>@endif
        </section>

        <section class="salary-rules" aria-labelledby="salary-rules-title">
            <div><span class="eyebrow">CARA PERHITUNGAN</span><h2 id="salary-rules-title">Supaya nominalnya jelas</h2></div>
            <div class="salary-rule-grid">
                <article><span>01</span><h3>Rp1 juta per siklus bulanan</h3><p>Biaya layanan PT Rp1,8 juta berbeda dari tarif gaji trainer Rp1 juta per member. Pembelian PT 3 bulan tidak langsung menjadi gaji Rp3 juta pada bulan pertama.</p></article>
                <article><span>02</span><h3>Dibagi sesuai hari layanan</h3><p>Siklus yang melewati dua bulan kalender dibagi secara prorata. Total satu siklus penuh tetap Rp1 juta. Nilai antarbulan dapat berbeda sesuai panjang siklus dan hari yang masuk setiap bulan.</p></article>
                <article><span>03</span><h3>Hanya layanan yang sudah lunas</h3><p>Gym dan PT harus lunas. Perhitungan dimulai paling awal pada tanggal pelunasan keduanya. Layanan pending, gagal, refunded, atau membership dibatalkan tidak dihitung.</p></article>
            </div>
            <details class="salary-method"><summary>Penjelasan tanggal, pembulatan, dan status estimasi</summary><div>
                <p>Untuk gaji, siklus 20 Oktober–20 November menghitung 20 Oktober sampai 19 November. Tanggal batas siklus tidak dihitung lagi sehingga tidak terjadi hitung ganda. Ketentuan akses gym tidak berubah.</p>
                <p>Nominal dibulatkan ke rupiah dengan selisih pembulatan dibagikan antarhari, agar seluruh bagian satu siklus tetap berjumlah tepat Rp1.000.000. Hari member yang tumpang tindih hanya dihitung sekali.</p>
                <p>Halaman ini menghitung ulang berdasarkan periode dan status pembayaran saat ini. Perubahan transaksi, refund, atau pembatalan dapat mengubah estimasi, termasuk bulan sebelumnya. Pembayaran gaji dan slip gaji belum dicatat di halaman ini.</p>
            </div></details>
        </section>
    @endif
</div>
