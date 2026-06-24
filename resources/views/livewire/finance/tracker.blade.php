<?php

use App\Models\Transaction;
use App\Actions\CreateTransactionAction;
use App\Actions\DeleteTransactionAction;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;

new class extends Component {
    use WithPagination;

    // Form inputs
    public string $title = '';
    public string $amount = '';
    public string $type = 'income';
    public string $category = 'Gaji';
    public string $description = '';
    public string $transaction_date = '';

    // Search and filters
    public string $search = '';
    public string $filterType = 'all';
    public string $filterCategory = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => 'all'],
        'filterCategory' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        $this->transaction_date = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    /**
     * Save the transaction.
     */
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
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'ip' => request()->ip(),
            ]
        ]);

        // Reset fields
        $this->reset(['title', 'amount', 'description']);
        $this->transaction_date = now()->format('Y-m-d');

        session()->flash('message', 'Transaksi berhasil disimpan!');
    }

    /**
     * Delete the transaction.
     */
    public function delete(string $id, DeleteTransactionAction $deleteAction): void
    {
        $transaction = auth()->user()->transactions()->findOrFail($id);
        $deleteAction->execute($transaction);

        session()->flash('message', 'Transaksi berhasil dihapus!');
    }

    /**
     * Compute statistics for current user.
     */
    public function getStatsProperty(): array
    {
        $query = auth()->user()->transactions();

        $income = (clone $query)->income()->sum('amount');
        $expense = (clone $query)->expense()->sum('amount');
        $balance = $income - $expense;

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
        ];
    }

    /**
     * Get categories list for autocomplete/dropdown.
     */
    public function getCategories(): array
    {
        return [
            'Gaji',
            'Bonus',
            'Investasi',
            'Makanan & Minuman',
            'Transportasi',
            'Belanja',
            'Utilitas/Tagihan',
            'Hiburan',
            'Lain-lain'
        ];
    }

    /**
     * Fetch transactions query.
     */
    public function with(): array
    {
        $query = auth()->user()->transactions()
            ->when($this->search, function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterType !== 'all', function ($q) {
                $q->where('type', $this->filterType);
            })
            ->when($this->filterCategory !== 'all', function ($q) {
                $q->where('category', $this->filterCategory);
            })
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        $dailyQuery = auth()->user()->transactions()
            ->expense()
            ->selectRaw('transaction_date, SUM(amount) as total')
            ->groupBy('transaction_date')
            ->orderBy('transaction_date', 'desc')
            ->take(7);

        return [
            'transactions' => $query->paginate(5),
            'stats' => $this->stats,
            'categories' => $this->getCategories(),
            'dailyExpenses' => $dailyQuery->get(),
        ];
    }
}; ?>

<div class="space-y-8" x-data>
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

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Balance Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-blue-600 dark:from-indigo-600 dark:to-blue-700 text-white rounded-2xl p-6 shadow-lg shadow-indigo-500/10 dark:shadow-none transition-all duration-300 hover:scale-[1.02]">
            <div class="absolute -right-10 -bottom-10 opacity-10">
                <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
            </div>
            <p class="text-indigo-100 text-sm font-medium tracking-wide uppercase">{{ __('Total Saldo') }}</p>
            <h3 class="text-3xl font-extrabold mt-2 tracking-tight">
                Rp {{ number_format($stats['balance'], 2, ',', '.') }}
            </h3>
            <div class="mt-4 flex items-center text-xs text-indigo-100/80">
                <span class="bg-white/20 px-2 py-0.5 rounded-full mr-2">Live</span>
                <span>Update keuangan real-time</span>
            </div>
        </div>

        <!-- Income Card -->
        <div class="bg-gray-900 border border-gray-850 rounded-2xl p-6 shadow-sm flex items-center transition-all duration-300 hover:scale-[1.02]">
            <div class="p-3 bg-emerald-950/40 rounded-xl mr-4 text-emerald-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">{{ __('Total Pemasukan') }}</p>
                <h4 class="text-xl font-bold text-gray-200 mt-1">
                    Rp {{ number_format($stats['income'], 2, ',', '.') }}
                </h4>
            </div>
        </div>

        <!-- Expense Card -->
        <div class="bg-gray-900 border border-gray-850 rounded-2xl p-6 shadow-sm flex items-center transition-all duration-300 hover:scale-[1.02]">
            <div class="p-3 bg-rose-950/40 rounded-xl mr-4 text-rose-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"/></svg>
            </div>
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">{{ __('Total Pengeluaran') }}</p>
                <h4 class="text-xl font-bold text-gray-200 mt-1">
                    Rp {{ number_format($stats['expense'], 2, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Table Column -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Filters Panel -->
            <div class="bg-gray-900 p-5 rounded-2xl border border-gray-850 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h3 class="text-lg font-bold text-gray-250 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Daftar Transaksi
                    </h3>
                    <div class="relative w-full md:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari transaksi..." 
                               class="pl-10 pr-4 py-2 w-full text-sm bg-black border border-gray-800 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button wire:click="$set('filterType', 'all')" 
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterType === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-black text-gray-400 hover:bg-gray-800' }}">
                        Semua
                    </button>
                    <button wire:click="$set('filterType', 'income')" 
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterType === 'income' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-black text-gray-400 hover:bg-gray-800' }}">
                        Pemasukan
                    </button>
                    <button wire:click="$set('filterType', 'expense')" 
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterType === 'expense' ? 'bg-rose-600 text-white shadow-sm' : 'bg-black text-gray-400 hover:bg-gray-800' }}">
                        Pengeluaran
                    </button>

                    <!-- Category Filter Dropdown -->
                    <div class="ml-auto w-full sm:w-auto">
                        <select wire:model.live="filterCategory" 
                                class="w-full sm:w-auto text-xs py-1.5 pl-3 pr-8 bg-black text-gray-400 border-none rounded-lg focus:ring-indigo-500">
                            <option value="all">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Transactions Table Card -->
            <div class="bg-gray-900 rounded-2xl border border-gray-850 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-800 bg-black/50">
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Transaksi') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Tanggal') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Kategori') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">{{ __('Jumlah') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            @forelse($transactions as $trx)
                                <tr class="hover:bg-gray-800/30 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-gray-200">{{ $trx->title }}</span>
                                            @if($trx->description)
                                                <span class="text-xs text-gray-500 mt-0.5 max-w-xs truncate">{{ $trx->description }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-455 whitespace-nowrap">
                                        {{ $trx->transaction_date->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-950/40 text-indigo-400 border border-indigo-900/40">
                                            {{ $trx->category }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <span class="text-sm font-bold {{ $trx->type === 'income' ? 'text-emerald-400' : 'text-rose-400' }}">
                                            {{ $trx->type === 'income' ? '+' : '-' }} Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button type="button"
                                                @click="$dispatch('open-confirm-modal', { id: '{{ $trx->id }}', action: 'delete' })" 
                                                class="text-gray-555 hover:text-rose-450 transition-colors p-1.5 rounded-lg hover:bg-rose-955/20">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        <p class="text-sm font-semibold">{{ __('Tidak ada transaksi ditemukan') }}</p>
                                        <p class="text-xs mt-1">{{ __('Coba sesuaikan pencarian atau filter Anda.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="px-6 py-4 bg-black/30 border-t border-gray-800">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Add Form Column & Daily Summary -->
        <div class="space-y-6">
            <!-- Daily Expenses Summary -->
            <div class="bg-gray-900 border border-gray-850 p-6 rounded-2xl shadow-sm">
                <h3 class="text-xs font-bold text-gray-250 uppercase tracking-wider mb-4 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Pengeluaran Harian (7 Hari Terakhir)
                </h3>
                <div class="space-y-3.5">
                    @forelse($dailyExpenses as $daily)
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-semibold text-gray-400">
                                    {{ $daily->transaction_date->isToday() ? 'Hari ini' : ($daily->transaction_date->isYesterday() ? 'Kemarin' : $daily->transaction_date->format('d M Y')) }}
                                </span>
                                <span class="text-xs font-bold text-rose-400">
                                    Rp {{ number_format($daily->total, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="w-full bg-black/60 border border-gray-800/40 rounded-full h-2 overflow-hidden">
                                @php
                                    $percent = min(100, ($daily->total / 500000) * 100);
                                @endphp
                                <div class="bg-gradient-to-r from-rose-600 to-red-500 h-full rounded-full transition-all duration-300" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500 text-center py-2">Belum ada catatan pengeluaran harian</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-gray-900 p-6 rounded-2xl border border-gray-850 shadow-sm">
                <h3 class="text-lg font-bold text-gray-200 mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Tambah Transaksi
                </h3>

                <form wire:submit="save" class="space-y-4">
                    <!-- Type Selection -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Tipe</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" wire:click="$set('type', 'income')" 
                                    class="py-2.5 rounded-xl text-sm font-semibold text-center border transition-all duration-200 {{ $type === 'income' ? 'bg-emerald-950/20 border-emerald-500 text-emerald-400 ring-2 ring-emerald-500/10' : 'bg-black border-gray-800 text-gray-400 hover:bg-gray-850' }}">
                                Pemasukan
                            </button>
                            <button type="button" wire:click="$set('type', 'expense')" 
                                    class="py-2.5 rounded-xl text-sm font-semibold text-center border transition-all duration-200 {{ $type === 'expense' ? 'bg-rose-950/20 border-rose-500 text-rose-400 ring-2 ring-rose-500/10' : 'bg-black border-gray-800 text-gray-400 hover:bg-gray-850' }}">
                                Pengeluaran
                            </button>
                        </div>
                    </div>

                    <!-- Title -->
                    <div>
                        <label for="trx_title" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Nama Transaksi</label>
                        <input wire:model="title" type="text" id="trx_title" placeholder="cth: Gaji Bulanan, Makan Siang"
                               class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                        @error('title') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="trx_amount" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Jumlah (Rupiah)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500 text-sm font-semibold">Rp</span>
                            <input wire:model="amount" type="number" id="trx_amount" placeholder="0" step="0.01" min="0.01"
                                   class="pl-10 w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                        </div>
                        @error('amount') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="trx_category" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Kategori</label>
                        <select wire:model="category" id="trx_category" 
                                class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100 font-semibold">
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Date -->
                    <div>
                        <label for="trx_date" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tanggal</label>
                        <input wire:model="transaction_date" type="date" id="trx_date"
                               class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                        @error('transaction_date') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="trx_desc" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Keterangan (Opsional)</label>
                        <textarea wire:model="description" id="trx_desc" placeholder="Catatan tambahan..." rows="2"
                                  class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100"></textarea>
                        @error('description') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.98] transition-all duration-150">
                        Simpan Transaksi
                    </button>
                </form>
    <!-- Custom Confirmation Modal -->
    <div x-data="{ openConfirm: false, deleteId: null, confirmAction: null }"
         @open-confirm-modal.window="deleteId = $event.detail.id; confirmAction = $event.detail.action; openConfirm = true"
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
                    
                    <p class="text-sm text-gray-400 mb-6">Apakah Anda yakin ingin menghapus catatan transaksi ini?</p>
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="openConfirm = false"
                                class="px-4 py-2 bg-black border border-gray-800 text-gray-400 hover:bg-gray-850 rounded-xl text-sm font-semibold transition">
                            Batal
                        </button>
                        <button type="button" 
                                @click="$wire.call(confirmAction, deleteId); openConfirm = false"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-rose-900/10 transition">
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
