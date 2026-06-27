<?php

use App\Models\Transaction;
use App\Models\ActiveDebt;
use App\Actions\CreateTransactionAction;
use App\Actions\DeleteTransactionAction;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;

new class extends Component {
    use WithPagination;

    // Keep transaction inputs/actions for compatibility with existing tests
    public string $title = '';
    public string $amount = '';
    public string $type = 'income';
    public string $category = 'Gaji';
    public string $description = '';
    public string $transaction_date = '';

    // Search and filters for transactions (compatibility)
    public string $filterCategory = 'all';

    public function mount(): void
    {
        $this->transaction_date = now()->format('Y-m-d');
    }

    public function save(CreateTransactionAction $createAction): void
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'required|date',
        ]);

        $createAction->execute(auth()->user(), [
            'title' => $this->title,
            'amount' => (float) $this->amount,
            'type' => $this->type,
            'category' => $this->category,
            'description' => $this->description ?: null,
            'transaction_date' => $this->transaction_date,
        ]);

        $this->reset(['title', 'amount', 'description']);
        $this->transaction_date = now()->format('Y-m-d');

        session()->flash('message', 'Transaksi berhasil disimpan!');
    }

    public function delete(string $id, DeleteTransactionAction $deleteAction): void
    {
        $transaction = auth()->user()->transactions()->findOrFail($id);
        $deleteAction->execute($transaction);

        session()->flash('message', 'Transaksi berhasil dihapus!');
    }

    // New Debt-related properties and actions for Dashboard
    public string $search = '';
    public string $filterType = 'all'; 
    public string $filterStatus = 'all'; 

    // Debt Form properties
    public ?string $editingDebtId = null;
    public string $debtName = '';
    public string $debtType = 'payable';
    public string $debtAmount = '';
    public string $debtPaidAmount = '';
    public string $debtDueDate = '';
    public string $debtTenorMonths = '';
    public string $debtDescription = '';

    public function editDebt(string $id): void
    {
        $debt = auth()->user()->activeDebts()->findOrFail($id);
        $this->editingDebtId = $debt->id;
        $this->debtName = $debt->name;
        $this->debtType = $debt->type;
        $this->debtAmount = (string) (float) $debt->amount;
        $this->debtPaidAmount = (string) (float) $debt->paid_amount;
        $this->debtDueDate = $debt->due_date ? $debt->due_date->format('Y-m-d') : '';
        $this->debtTenorMonths = $debt->tenor_months !== null ? (string) $debt->tenor_months : '';
        $this->debtDescription = $debt->description ?: '';
    }

    public function saveDebt(): void
    {
        $this->validate([
            'debtName' => 'required|string|max:255',
            'debtType' => 'required|in:payable,receivable',
            'debtAmount' => 'required|numeric|min:0.01',
            'debtPaidAmount' => 'nullable|numeric|min:0',
            'debtDueDate' => 'nullable|date',
            'debtTenorMonths' => 'nullable|integer|min:0',
            'debtDescription' => 'nullable|string|max:500',
        ]);

        $amountVal = (float) $this->debtAmount;
        $paidAmountVal = (float) ($this->debtPaidAmount ?: 0);
        $statusVal = $paidAmountVal >= $amountVal ? 'paid' : 'unpaid';

        if ($this->editingDebtId) {
            $debt = auth()->user()->activeDebts()->findOrFail($this->editingDebtId);
            $debt->update([
                'name' => $this->debtName,
                'type' => $this->debtType,
                'amount' => $amountVal,
                'paid_amount' => $paidAmountVal,
                'status' => $statusVal,
                'due_date' => $this->debtDueDate ?: null,
                'tenor_months' => $this->debtTenorMonths !== '' ? (int) $this->debtTenorMonths : null,
                'description' => $this->debtDescription ?: null,
            ]);
            $this->editingDebtId = null;
            session()->flash('message', 'Catatan hutang berhasil diperbarui!');
        } else {
            auth()->user()->activeDebts()->create([
                'name' => $this->debtName,
                'type' => $this->debtType,
                'amount' => $amountVal,
                'paid_amount' => $paidAmountVal,
                'status' => $statusVal,
                'due_date' => $this->debtDueDate ?: null,
                'tenor_months' => $this->debtTenorMonths !== '' ? (int) $this->debtTenorMonths : null,
                'description' => $this->debtDescription ?: null,
            ]);
            session()->flash('message', 'Catatan hutang berhasil ditambahkan!');
        }

        $this->reset(['debtName', 'debtAmount', 'debtPaidAmount', 'debtDescription', 'debtDueDate', 'debtTenorMonths']);
        $this->dispatch('close-modal');
    }

    public function deleteDebt(string $id): void
    {
        $debt = auth()->user()->activeDebts()->findOrFail($id);
        $debt->delete();

        session()->flash('message', 'Catatan hutang berhasil dihapus!');
    }

    public function updatePaidAmount(string $id, $amount): void
    {
        $paidAmount = max(0, (float) $amount);
        $debt = auth()->user()->activeDebts()->findOrFail($id);
        
        $status = $paidAmount >= $debt->amount ? 'paid' : 'unpaid';

        $debt->update([
            'paid_amount' => $paidAmount,
            'status' => $status,
        ]);

        session()->flash('message', 'Jumlah terbayar berhasil diperbarui!');
    }

    public function updateTenorMonths(string $id, $tenor): void
    {
        $tenorVal = $tenor === '' ? null : max(0, (int) $tenor);
        $debt = auth()->user()->activeDebts()->findOrFail($id);
        $debt->update([
            'tenor_months' => $tenorVal,
        ]);

        session()->flash('message', 'Tenor berhasil diperbarui!');
    }

    public function updateStatus(string $id, string $newStatus): void
    {
        $debt = auth()->user()->activeDebts()->findOrFail($id);
        $debt->update([
            'status' => $newStatus,
            'paid_amount' => $newStatus === 'paid' ? $debt->amount : 0,
        ]);

        session()->flash('message', 'Status hutang berhasil diperbarui!');
    }

    public function getCategories(): array
    {
        return [];
    }

    public function with(): array
    {
        $debts = auth()->user()->activeDebts()
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate summary stats for the cards
        $totalPayable = auth()->user()->activeDebts()->payable()->where('status', 'unpaid')->get()->sum(fn($d) => $d->amount - $d->paid_amount);
        $totalReceivable = auth()->user()->activeDebts()->receivable()->where('status', 'unpaid')->get()->sum(fn($d) => $d->amount - $d->paid_amount);

        return [
            'debts' => $debts,
            'totalPayable' => $totalPayable,
            'totalReceivable' => $totalReceivable,
            'transactions' => collect(),
            'stats' => [],
            'categories' => [],
            'dailyExpenses' => collect(),
        ];
    }
}; ?>

<div class="space-y-8" x-data="{ showFormModal: false }" @close-modal.window="showFormModal = false">
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" 
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="opacity-0 transform translate-y-[-20px] md:translate-x-[20px]" 
             x-transition:enter-end="opacity-100 transform translate-y-0 md:translate-x-0" 
             x-transition:leave="transition ease-in duration-200" 
             x-transition:leave-start="opacity-100 transform translate-y-0 md:translate-x-0" 
             x-transition:leave-end="opacity-0 transform translate-y-[-20px] md:translate-x-[20px]" 
             x-init="setTimeout(() => show = false, 4000)" 
             class="fixed top-5 right-5 z-50 flex items-center p-4 text-sm text-emerald-200 bg-gray-900/95 backdrop-blur border border-emerald-500 rounded-xl shadow-2xl max-w-sm" role="alert">
            <svg class="flex-shrink-0 inline w-5 h-5 text-emerald-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <div class="flex-1 pr-2 ml-3">
                <span class="font-bold text-gray-200">{{ session('message') }}</span>
            </div>
            <button @click="show = false" class="text-gray-450 hover:text-gray-200 transition-colors ml-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="w-full">
        <!-- Card Total Hutang -->
        <div class="bg-gray-900 border border-gray-855 rounded-2xl p-6 shadow-sm flex items-center justify-between transition hover:border-rose-900/50">
            <div class="flex items-center">
                <div class="p-3 bg-rose-955/40 rounded-xl mr-4 text-rose-455">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <p class="text-gray-550 text-xs font-semibold uppercase tracking-wider">Total Hutang Aktif</p>
                    <h4 class="text-2xl font-bold text-rose-500 mt-1">
                        Rp {{ number_format($totalPayable, 0, ',', '.') }}
                    </h4>
                </div>
            </div>
            <span class="text-[10px] bg-rose-955/20 border border-rose-900/30 text-rose-400 px-2 py-0.5 rounded-full font-black uppercase">Kewajiban</span>
        </div>
    </div>

    <!-- Main Large Table Section -->
    <div class="bg-gray-900 rounded-2xl border border-gray-850 shadow-sm overflow-hidden p-6 space-y-6">
        <!-- Header & Action Buttons -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-800 pb-5">
            <div>
                <h3 class="text-xl font-black text-white flex items-center">
                    <svg class="w-6 h-6 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Daftar Hutang & Piutang Aktif
                </h3>
                <p class="text-xs text-gray-500 mt-1">Gunakan tabel ini untuk melacak tenor jatuh tempo dan nominal cicilan secara real-time.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <button type="button" @click="showFormModal = true"
                        class="py-2 px-4 bg-indigo-650 hover:bg-indigo-600 text-white text-xs font-black rounded-xl transition active:scale-[0.98] flex items-center gap-1.5 shadow-md shadow-indigo-900/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Catat Baru
                </button>
            </div>
        </div>

        <!-- Big Table -->
        <div class="overflow-x-auto pb-4">
            <table class="min-w-[1250px] w-full text-left border-collapse border border-gray-800">
                <thead>
                    <tr class="bg-gray-900 border-b border-gray-855 text-xs text-white uppercase font-black tracking-wider">
                        <th class="px-6 py-4 border border-gray-800">Nama / Deskripsi</th>
                        <th class="px-6 py-4 border border-gray-800 text-center w-32">Tipe</th>
                        <th class="px-6 py-4 border border-gray-800 text-center w-40">Tenor (Jatuh Tempo)</th>
                        <th class="px-6 py-4 border border-gray-800 text-center w-32">Tenor (Sisa Bulan)</th>
                        <th class="px-6 py-4 border border-gray-800 text-right w-44">Total Nominal</th>
                        <th class="px-6 py-4 border border-gray-800 text-right w-56">Telah Dicicil</th>
                        <th class="px-6 py-4 border border-gray-800 text-right w-44">Sisa Belum Dibayar</th>
                        <th class="px-6 py-4 border border-gray-800 text-center w-36">Status</th>
                        <th class="px-6 py-4 border border-gray-800 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800 bg-black/20">
                    @forelse($debts as $debt)
                        @php
                            $remaining = max(0, $debt->amount - $debt->paid_amount);
                            $percent = $debt->amount > 0 ? min(100, ($debt->paid_amount / $debt->amount) * 100) : 0;
                        @endphp
                        <tr class="hover:bg-gray-850/30 text-xs transition group">
                            <!-- Nama / Deskripsi -->
                            <td class="px-6 py-4 border border-gray-800">
                                <div class="flex flex-col">
                                    <span class="font-bold text-sm text-white">{{ $debt->name }}</span>
                                    @if($debt->description)
                                        <span class="text-xs text-gray-400 font-medium mt-1">{{ $debt->description }}</span>
                                    @endif
                                </div>
                            </td>
                            <!-- Tipe -->
                            <td class="px-6 py-4 text-center border border-gray-800 w-36">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $debt->type === 'payable' ? 'bg-rose-950/40 text-rose-400 border-rose-900/30' : 'bg-emerald-950/40 text-emerald-400 border-emerald-900/30' }}">
                                    {{ $debt->type === 'payable' ? 'Hutang' : 'Piutang' }}
                                </span>
                            </td>
                            <!-- Tenor (Jatuh Tempo) -->
                            <td class="px-6 py-4 text-center border border-gray-800 w-40 whitespace-nowrap">
                                @if($debt->status === 'paid')
                                    <span class="text-emerald-400 font-bold">Lunas</span>
                                @elseif($debt->due_date)
                                    <span class="font-bold text-gray-200 text-xs">{{ $debt->due_date->format('d M Y') }}</span>
                                @else
                                    <span class="text-gray-550">-</span>
                                @endif
                            </td>
                            <!-- Tenor (Sisa Bulan) -->
                            <td class="px-6 py-4 border border-gray-800 w-32 text-center">
                                <div class="relative flex items-center justify-center gap-1" wire:key="tenor-{{ $debt->id }}-{{ $debt->tenor_months }}">
                                    <input type="number" 
                                           value="{{ (int) $debt->tenor_months }}" 
                                           wire:blur="updateTenorMonths('{{ $debt->id }}', $event.target.value)"
                                           wire:keydown.enter="updateTenorMonths('{{ $debt->id }}', $event.target.value)"
                                           class="w-16 bg-black/60 border border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-lg text-xs font-black text-white text-center py-1 px-1.5" />
                                    <span class="text-gray-400 text-xs">Bulan</span>
                                </div>
                            </td>
                            <!-- Total Nominal -->
                            <td class="px-6 py-4 text-right font-bold text-white border border-gray-800 whitespace-nowrap w-44">
                                Rp {{ number_format($debt->amount, 0, ',', '.') }}
                            </td>
                            <!-- Telah Dicicil (Inline Edit) -->
                            <td class="px-6 py-4 border border-gray-800 w-56 whitespace-nowrap">
                                <div class="flex flex-col items-end gap-1.5" wire:key="paid-{{ $debt->id }}-{{ $debt->paid_amount }}">
                                    <div class="relative flex items-center justify-end">
                                        <span class="absolute left-2 text-gray-400 text-[10px]">Rp</span>
                                        <input type="text" 
                                               value="{{ number_format($debt->paid_amount, 0, ',', '.') }}" 
                                               x-on:input="$event.target.value = $event.target.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                               wire:blur="updatePaidAmount('{{ $debt->id }}', $event.target.value.replace(/\./g, ''))"
                                               wire:keydown.enter="updatePaidAmount('{{ $debt->id }}', $event.target.value.replace(/\./g, ''))"
                                               class="pl-6 pr-1 w-36 bg-black/60 border border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-lg text-xs font-black text-white text-right py-1 px-1.5" />
                                    </div>
                                    <div class="w-36 bg-black/60 border border-gray-800/40 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-gradient-to-r from-emerald-600 to-teal-500 h-full rounded-full transition-all duration-300" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <!-- Sisa Belum Dibayar -->
                            <td class="px-6 py-4 text-right font-black border border-gray-800 whitespace-nowrap w-44 {{ $remaining > 0 ? ($debt->type === 'payable' ? 'text-rose-455' : 'text-emerald-455') : 'text-gray-550' }}">
                                Rp {{ number_format($remaining, 0, ',', '.') }}
                            </td>
                            <!-- Status (Dropdown Select) -->
                            <td class="px-6 py-4 text-center border border-gray-800 w-36">
                                <select wire:change="updateStatus('{{ $debt->id }}', $event.target.value)" 
                                        class="bg-transparent border-0 text-xs font-black p-0.5 focus:ring-0 focus:outline-none cursor-pointer w-full text-center {{ $debt->status === 'paid' ? 'text-emerald-400' : ($debt->paid_amount > 0 ? 'text-amber-400' : 'text-rose-500') }}">
                                    <option value="unpaid" class="bg-gray-900 text-rose-500 font-semibold" {{ $debt->status === 'unpaid' ? 'selected' : '' }}>
                                        {{ $debt->paid_amount > 0 ? 'Belum Lunas (Cicil)' : 'Belum Lunas' }}
                                    </option>
                                    <option value="paid" class="bg-gray-900 text-emerald-400 font-semibold" {{ $debt->status === 'paid' ? 'selected' : '' }}>Lunas</option>
                                </select>
                            </td>
                            <!-- Aksi (Edit & Delete) -->
                            <td class="px-6 py-4 text-center border border-gray-800 w-28 whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" 
                                            wire:click="editDebt('{{ $debt->id }}')" 
                                            @click="showFormModal = true"
                                            class="text-gray-450 hover:text-indigo-400 p-1 rounded transition-all cursor-pointer"
                                            title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button type="button" 
                                            @click="$dispatch('open-confirm-modal', { id: '{{ $debt->id }}' })"
                                            class="text-gray-450 hover:text-rose-455 p-1 rounded transition-all cursor-pointer"
                                            title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-550 border border-gray-800">
                                <svg class="w-16 h-16 mx-auto mb-4 opacity-35 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <p class="text-base font-semibold text-gray-400">Tidak ada catatan hutang atau piutang aktif</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form Modal (Edit Hutang Popup) -->
    <div class="relative z-40" 
         x-show="showFormModal" 
         @close-modal.window="showFormModal = false"
         style="display: none;">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity"></div>
        
        <!-- Modal Container -->
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden rounded-2xl bg-gray-900 border border-gray-850 p-6 text-left shadow-2xl transition-all w-full max-w-lg"
                     @click.away="showFormModal = false">
                    
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-250 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            {{ $editingDebtId ? 'Edit Catatan' : 'Catat Hutang / Piutang' }}
                        </h3>
                        <button type="button" @click="showFormModal = false" class="text-gray-555 hover:text-gray-350">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form wire:submit="saveDebt" class="space-y-4">
                        <!-- Tipe Selection -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Tipe</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" wire:click="$set('debtType', 'payable')" 
                                        class="py-2.5 rounded-xl text-sm font-semibold text-center border transition-all duration-200 {{ $debtType === 'payable' ? 'bg-rose-955/20 border-rose-500 text-rose-450 ring-2 ring-rose-500/10' : 'bg-black border-gray-800 text-gray-400 hover:bg-gray-850' }}">
                                    Hutang
                                </button>
                                <button type="button" wire:click="$set('debtType', 'receivable')" 
                                        class="py-2.5 rounded-xl text-sm font-semibold text-center border transition-all duration-200 {{ $debtType === 'receivable' ? 'bg-emerald-950/20 border-emerald-500 text-emerald-400 ring-2 ring-emerald-500/10' : 'bg-black border-gray-800 text-gray-400 hover:bg-gray-850' }}">
                                    Piutang
                                </button>
                            </div>
                        </div>

                        <!-- Name -->
                        <div>
                            <label for="debt_name" class="block text-xs font-semibold text-gray-555 uppercase tracking-wider mb-1.5">Nama Orang / Lembaga</label>
                            <input wire:model="debtName" type="text" id="debt_name" placeholder="Nama..."
                                   class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-505 focus:border-indigo-505 text-gray-100">
                            @error('debtName') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Amount -->
                        <div>
                            <label for="debt_amount" class="block text-xs font-semibold text-gray-550 uppercase tracking-wider mb-1.5">Jumlah</label>
                            <div class="relative" x-data="{
                                raw: @entangle('debtAmount'),
                                format(val) {
                                    if (!val) return '';
                                    return Number(val).toLocaleString('id-ID');
                                }
                            }">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-555 text-sm font-semibold">Rp</span>
                                <input type="text" id="debt_amount" placeholder="0"
                                       x-init="$watch('raw', val => $el.value = format(val))"
                                       x-bind:value="format(raw)"
                                       x-on:input="
                                           let clean = $event.target.value.replace(/\D/g, '');
                                           raw = clean ? parseInt(clean) : '';
                                           $el.value = format(raw);
                                       "
                                       class="pl-10 w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                            </div>
                            @error('debtAmount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Paid Amount -->
                        <div>
                            <label for="debt_paid_amount" class="block text-xs font-semibold text-gray-550 uppercase tracking-wider mb-1.5">Nominal Yang Sudah Dibayar (Opsional)</label>
                            <div class="relative" x-data="{
                                raw: @entangle('debtPaidAmount'),
                                format(val) {
                                    if (!val) return '';
                                    return Number(val).toLocaleString('id-ID');
                                }
                            }">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-555 text-sm font-semibold">Rp</span>
                                <input type="text" id="debt_paid_amount" placeholder="0"
                                       x-init="$watch('raw', val => $el.value = format(val))"
                                       x-bind:value="format(raw)"
                                       x-on:input="
                                           let clean = $event.target.value.replace(/\D/g, '');
                                           raw = clean ? parseInt(clean) : '';
                                           $el.value = format(raw);
                                       "
                                       class="pl-10 w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                            </div>
                            @error('debtPaidAmount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Tenor (Sisa Bulan) -->
                        <div>
                            <label for="debt_tenor" class="block text-xs font-semibold text-gray-550 uppercase tracking-wider mb-1.5">Tenor (Sisa Bulan) (Opsional)</label>
                            <input wire:model="debtTenorMonths" type="number" id="debt_tenor" placeholder="0" min="0"
                                   class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                            @error('debtTenorMonths') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Due Date -->
                        <div>
                            <label for="debt_due" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tenggat Waktu / Jatuh Tempo</label>
                            <input wire:model="debtDueDate" type="date" id="debt_due"
                                   x-on:click="$el.showPicker()"
                                   class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-555 focus:border-indigo-555 text-gray-100 cursor-pointer">
                            @error('debtDueDate') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="debt_desc" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Deskripsi / Keperluan</label>
                            <textarea wire:model="debtDescription" id="debt_desc" placeholder="Catatan tambahan..." rows="2"
                                      class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100"></textarea>
                            @error('debtDescription') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" 
                                class="w-full py-3 bg-indigo-600 hover:bg-indigo-750 text-white text-sm font-bold rounded-xl shadow-md transition-all duration-150 active:scale-[0.98]">
                            {{ $editingDebtId ? 'Simpan Perubahan' : 'Tambah Catatan' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div x-data="{ openConfirm: false, deleteId: null }"
         @open-confirm-modal.window="deleteId = $event.detail.id; openConfirm = true"
         class="relative z-50"
         x-show="openConfirm"
         style="display: none;">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity"></div>
        
        <!-- Modal Container -->
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden rounded-2xl bg-gray-900 border border-gray-850 p-6 text-left shadow-xl transition-all w-full max-w-md animate-fade-in"
                     @click.away="openConfirm = false">
                    <div class="flex items-center gap-4 text-rose-500 mb-4">
                        <div class="p-3 bg-rose-955/50 rounded-xl border border-rose-900/30">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-250">Konfirmasi Hapus</h3>
                    </div>
                    
                    <p class="text-sm text-gray-400 mb-6">Apakah Anda yakin ingin menghapus catatan hutang ini?</p>
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="openConfirm = false"
                                class="px-4 py-2 bg-black border border-gray-800 text-gray-400 hover:bg-gray-850 rounded-xl text-sm font-semibold transition">
                            Batal
                        </button>
                        <button type="button" 
                                @click="$wire.call('deleteDebt', deleteId); openConfirm = false"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-rose-900/10 transition">
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
