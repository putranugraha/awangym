<?php

use App\Models\PaymentTransaction;
use App\Services\MembershipPaymentService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public string $method = 'all';

    public bool $showVerificationModal = false;
    public bool $showDeleteModal = false;

    public ?int $verifyingTransactionId = null;
    public ?int $deletingId = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'method'], true)) {
            $this->resetPage();
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteTransaction(): void
    {
        abort_unless(auth()->user()->can('manage payments'), 403);
        
        $transaction = PaymentTransaction::findOrFail($this->deletingId);
        $this->showDeleteModal = false;
        
        if (in_array($transaction->payment_status, ['paid', 'refunded'], true)) {
            session()->flash('error', 'Transaksi yang sudah lunas (Paid) atau dikembalikan (Refunded) tidak dapat dihapus demi audit keuangan.');
            return;
        }

        DB::transaction(function () use ($transaction) {
            if ($transaction->subscription) {
                $transaction->subscription->delete();
            }
            $transaction->delete();
        });

        session()->flash('success', 'Transaksi berhasil dihapus.');
    }

    public function openVerificationModal(int $id): void
    {
        $transaction = PaymentTransaction::where('payment_status', 'pending')->findOrFail($id);
        $this->verifyingTransactionId = $transaction->transaction_id;
        $this->showVerificationModal = true;
    }

    public function closeVerificationModal(): void
    {
        $this->showVerificationModal = false;
        $this->verifyingTransactionId = null;
    }

    public function confirmVerification(): void
    {
        abort_unless(auth()->user()->can('manage payments'), 403);
        $transaction = PaymentTransaction::where('payment_status', 'pending')
            ->findOrFail($this->verifyingTransactionId);

        app(MembershipPaymentService::class)->markAsPaid($transaction, auth()->id());

        $this->closeVerificationModal();
        session()->flash('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function with(): array
    {
        $transactions = PaymentTransaction::with(['member.user', 'subscription.package'])
            ->when($this->search, fn ($query) => $query->where(function ($searchQuery) {
                $searchQuery->where('invoice_number', 'like', "%{$this->search}%")
                    ->orWhereHas('member.user', fn ($user) => $user->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%"));
            }))
            ->when($this->status !== 'all', fn ($query) => $query->where('payment_status', $this->status))
            ->when($this->method !== 'all', fn ($query) => $query->where('payment_method', $this->method))
            ->latest('created_at')
            ->paginate(12);

        $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];

        return [
            'transactions' => $transactions,
            'selectedTransaction' => $this->verifyingTransactionId
                ? PaymentTransaction::with(['member.user', 'subscription.package'])->find($this->verifyingTransactionId)
                : null,
            'monthlyRevenue' => PaymentTransaction::where('payment_status', 'paid')
                ->whereBetween('payment_date', $thisMonth)->sum('amount'),
            'monthlyPaid' => PaymentTransaction::where('payment_status', 'paid')
                ->whereBetween('payment_date', $thisMonth)->count(),
            'pendingCount' => PaymentTransaction::where('payment_status', 'pending')->count(),
            'refundedCount' => PaymentTransaction::where('payment_status', 'refunded')->count(),
        ];
    }
};
?>

<div class="awan-page">
    <header class="resource-header">
        <div>
            <span class="eyebrow">KEUANGAN</span>
            <h1>Transaksi Membership</h1>
            <p>Catat dan verifikasi pembayaran membership member.</p>
        </div>
        <a class="primary-btn" href="{{ route('transactions.create') }}" wire:navigate>
            <span>+</span> Buat Transaksi
        </a>
    </header>

    <div class="transaction-stats">
        <article class="transaction-stat transaction-stat-featured">
            <span>Pendapatan bulan ini</span>
            <strong>Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}</strong>
            <small>Dari transaksi berstatus paid</small>
        </article>
        <article>
            <span>Transaksi paid</span>
            <strong>{{ $monthlyPaid }}</strong>
            <small>Bulan berjalan</small>
        </article>
        <article>
            <span>Menunggu verifikasi</span>
            <strong class="text-warning">{{ $pendingCount }}</strong>
            <small>Perlu tindakan admin</small>
        </article>
        <article>
            <span>Refunded</span>
            <strong>{{ $refundedCount }}</strong>
            <small>Tidak dihitung pemasukan</small>
        </article>
    </div>

    @if(session('success'))
        <div x-data x-init="Flux.toast({ variant: 'success', text: '{{ session('success') }}' })"></div>
    @endif
    @if(session('error'))
        <div x-data x-init="Flux.toast({ variant: 'danger', text: '{{ session('error') }}' })"></div>
    @endif

    <section class="data-panel">
        <div class="data-toolbar transaction-toolbar">
            <label class="data-search">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                <input wire:model.live.debounce.300ms="search" placeholder="Cari invoice, member, atau email…">
            </label>

            <div class="transaction-filters">
                <label class="data-filter">
                    <span>Status</span>
                    <select wire:model.live="status">
                        <option value="all">Semua</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </label>
                <label class="data-filter">
                    <span>Metode</span>
                    <select wire:model.live="method">
                        <option value="all">Semua</option>
                        <option value="cash">Tunai</option>
                        <option value="transfer">Transfer</option>
                        <option value="e_wallet">E-wallet</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="responsive-table">
            <table class="member-table transaction-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Member</th>
                        <th>Paket</th>
                        <th>Metode</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr wire:key="transaction-{{ $transaction->transaction_id }}">
                            <td data-label="Invoice">
                                <span class="invoice-number">{{ $transaction->invoice_number }}</span>
                                <small class="table-secondary">{{ $transaction->created_at?->translatedFormat('d M Y, H:i') }}</small>
                            </td>
                            <td data-label="Member">
                                <div class="member-cell">
                                    <span class="member-table-avatar">{{ $transaction->member->user->initials() }}</span>
                                    <span>
                                        <strong>{{ $transaction->member->user->full_name }}</strong>
                                        <small>{{ $transaction->member->member_code }}</small>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Paket">
                                <span class="table-primary">{{ $transaction->subscription->package->package_name }}</span>
                                <small class="table-secondary">{{ $transaction->subscription->start_date->format('d M') }} – {{ $transaction->subscription->end_date->format('d M Y') }}</small>
                            </td>
                            <td data-label="Metode">
                                <span class="payment-method payment-method-{{ $transaction->payment_method }}">
                                    {{ match($transaction->payment_method) {
                                        'cash' => 'Tunai',
                                        'e_wallet' => 'E-wallet',
                                        default => 'Transfer',
                                    } }}
                                </span>
                            </td>
                            <td data-label="Nominal">
                                <strong class="transaction-amount">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</strong>
                            </td>
                            <td data-label="Status">
                                <span class="payment-status payment-status-{{ $transaction->payment_status }}">
                                    <i></i>{{ ucfirst($transaction->payment_status) }}
                                </span>
                            </td>
                            <td data-label="Aksi" class="action-column">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="right" />
                                    <flux:menu>
                                        <flux:menu.item href="{{ route('transactions.edit', $transaction) }}" icon="pencil-square" wire:navigate>
                                            Edit Transaksi
                                        </flux:menu.item>
                                        @if($transaction->payment_status === 'pending')
                                            <flux:menu.item
                                                wire:click="openVerificationModal({{ $transaction->transaction_id }})"
                                                wire:loading.attr="disabled"
                                                icon="check-circle"
                                            >
                                                Verifikasi Pembayaran
                                            </flux:menu.item>
                                        @endif
                                        @if(!in_array($transaction->payment_status, ['paid', 'refunded'], true))
                                            <flux:menu.item
                                                wire:click="confirmDelete({{ $transaction->transaction_id }})"
                                                variant="danger"
                                                icon="trash"
                                            >
                                                Hapus Transaksi
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="table-empty">
                                    <strong>Transaksi tidak ditemukan</strong>
                                    <p>Coba ubah pencarian atau filter transaksi.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="data-pagination">{{ $transactions->links() }}</div>
        @endif
    </section>

    <flux:modal
        name="verify-transaction"
        wire:model="showVerificationModal"
        class="verification-modal max-w-lg"
        @close="closeVerificationModal"
    >
        @if($selectedTransaction)
            <div class="verification-modal-content">
                <div class="verification-modal-heading">
                    <flux:heading size="lg">Verifikasi pembayaran</flux:heading>
                    <flux:text>Pastikan detail transaksi berikut sudah sesuai.</flux:text>
                </div>

                <div class="verification-member">
                    <span class="member-table-avatar">{{ $selectedTransaction->member->user->initials() }}</span>
                    <div><strong>{{ $selectedTransaction->member->user->full_name }}</strong><small>{{ $selectedTransaction->member->member_code }} · {{ $selectedTransaction->member->user->email }}</small></div>
                </div>

                <dl class="verification-details">
                    <div><dt>Invoice</dt><dd>{{ $selectedTransaction->invoice_number }}</dd></div>
                    <div><dt>Paket membership</dt><dd>{{ $selectedTransaction->subscription->package->package_name }}</dd></div>
                    <div><dt>Metode</dt><dd>{{ match($selectedTransaction->payment_method) {'cash' => 'Tunai', 'e_wallet' => 'E-wallet', default => 'Transfer'} }}</dd></div>
                    <div class="verification-total"><dt>Nominal diterima</dt><dd>Rp {{ number_format($selectedTransaction->amount, 0, ',', '.') }}</dd></div>
                </dl>

                <div class="verification-actions">
                    <flux:button variant="outline" wire:click="closeVerificationModal">Batal</flux:button>
                    <flux:button variant="primary" wire:click="confirmVerification" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="confirmVerification">Ya, Verifikasi</span>
                        <span wire:loading wire:target="confirmVerification">Memproses…</span>
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal name="delete-confirm" wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Hapus Transaksi?</flux:heading>
                <flux:text>Apakah Anda yakin ingin menghapus transaksi ini? Data pendaftaran/perpanjangan paket terkait juga akan terhapus secara permanen.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="outline" wire:click="$set('showDeleteModal', false)">Batal</flux:button>
                <flux:button variant="danger" wire:click="deleteTransaction">Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

