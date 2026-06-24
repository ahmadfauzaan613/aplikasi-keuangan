<?php

use App\Models\Transaction;
use App\Actions\CreateTransactionAction;
use App\Actions\ToggleTransactionStatusAction;
use App\Actions\DeleteTransactionAction;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;

new class extends Component {
    // Form inputs
    public string $title = 'Tagihan Tetap';
    public string $amount = '';
    public string $category = 'Utilitas/Tagihan';
    public string $status = 'belum'; // Default belum
    public string $description = '';
    
    // Add Form Date selections
    public int $formMonth = 1;
    public int $formYear = 2026;

    // Filter Year
    public int $selectedYear = 2026;

    public function mount(): void
    {
        $this->selectedYear = now()->year;
        $this->formYear = now()->year;
        $this->formMonth = now()->month;
    }

    public function getYearsProperty(): array
    {
        $currentYear = now()->year;
        $startYear = 2026;
        
        $years = [];
        for ($y = $startYear; $y <= max($startYear, $currentYear); $y++) {
            $years[] = $y;
        }
        
        // Add next year if we are in December
        if (now()->month === 12) {
            $years[] = max($startYear, $currentYear) + 1;
        }

        return array_unique($years);
    }

    public function getMonthsProperty(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
    }

    public function selectMonthForForm(int $month): void
    {
        $this->formMonth = $month;
        $this->formYear = $this->selectedYear;
        $this->title = 'Tagihan - ' . $this->months[$month];
    }

    public function save(CreateTransactionAction $createAction): void
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'formMonth' => 'required|integer|between:1,12',
            'formYear' => 'required|integer',
            'status' => 'required|in:sudah,belum',
        ]);

        // Fix date on the 1st of the selected month/year
        $date = Carbon::create($this->formYear, $this->formMonth, 1)->format('Y-m-d');

        $createAction->execute(auth()->user(), [
            'title' => $this->title,
            'amount' => (float) $this->amount,
            'type' => 'expense',
            'category' => $this->category,
            'description' => $this->description ?: null,
            'transaction_date' => $date,
            'status' => $this->status,
        ]);

        $this->reset(['amount', 'description']);
        $this->title = 'Tagihan Tetap';

        session()->flash('message', 'Tagihan bulanan berhasil dicatat!');
    }

    public function updateStatus(string $id, string $newStatus): void
    {
        $transaction = auth()->user()->transactions()->findOrFail($id);
        $transaction->update(['status' => $newStatus]);

        session()->flash('message', 'Status pembayaran tagihan berhasil diperbarui!');
    }

    public function delete(string $id, DeleteTransactionAction $deleteAction): void
    {
        $transaction = auth()->user()->transactions()->findOrFail($id);
        $deleteAction->execute($transaction);

        session()->flash('message', 'Catatan tagihan berhasil dihapus!');
    }

    /**
     * Get monthly expenses grouped by month index for the selected year.
     */
    public function getMonthlyDataProperty(): array
    {
        $expenses = auth()->user()->transactions()
            ->expense()
            ->whereYear('transaction_date', $this->selectedYear)
            ->get();

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[$m] = $expenses->filter(fn($trx) => Carbon::parse($trx->transaction_date)->month === $m);
        }

        return $data;
    }

    /**
     * Get monthly incomes for the selected year.
     */
    public function getMonthlyIncomesProperty(): array
    {
        $incomes = auth()->user()->transactions()
            ->income()
            ->whereYear('transaction_date', $this->selectedYear)
            ->get();

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[$m] = $incomes->filter(fn($trx) => Carbon::parse($trx->transaction_date)->month === $m)->sum('amount');
        }

        return $data;
    }

    public function getStatsProperty(): array
    {
        $query = auth()->user()->transactions()->expense()->whereYear('transaction_date', $this->selectedYear);

        $totalPaid = (clone $query)->paid()->sum('amount');
        $totalUnpaid = (clone $query)->unpaid()->sum('amount');

        return [
            'paid' => $totalPaid,
            'unpaid' => $totalUnpaid,
            'total' => $totalPaid + $totalUnpaid,
        ];
    }

    public function getCategories(): array
    {
        return ['Utilitas/Tagihan', 'Makanan & Minuman', 'Transportasi', 'Belanja', 'Hiburan', 'Lain-lain'];
    }

    public function with(): array
    {
        return [
            'monthlyData' => $this->monthlyData,
            'monthlyIncomes' => $this->monthlyIncomes,
            'years' => $this->years,
            'months' => $this->months,
            'stats' => $this->stats,
            'categories' => $this->getCategories(),
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

    <!-- Year Filter Header & Stats -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-900 p-6 rounded-2xl border border-gray-850 shadow-sm">
        <div>
            <h3 class="text-lg font-bold text-gray-250 mb-1 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Berkas Tagihan & Bayaran Tahunan
            </h3>
            <p class="text-xs text-gray-550">Menampilkan rekapan tagihan bulanan wajib.</p>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-gray-400">Berkas Tahun:</span>
            <div class="relative inline-block text-left">
                <select wire:model.live="selectedYear" 
                        class="py-2 pl-9 pr-10 bg-black border border-gray-850 rounded-xl focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-gray-200 text-sm font-bold appearance-none cursor-pointer">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}">{{ $yr }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-indigo-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                </div>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 shadow-sm">
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Total Tagihan {{ $selectedYear }}</p>
            <h4 class="text-xl font-bold text-gray-200 mt-1">
                Rp {{ number_format($stats['total'], 2, ',', '.') }}
            </h4>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 shadow-sm">
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Sudah Dibayar</p>
            <h4 class="text-xl font-bold text-emerald-450 mt-1">
                Rp {{ number_format($stats['paid'], 2, ',', '.') }}
            </h4>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 shadow-sm">
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Belum Dibayar</p>
            <h4 class="text-xl font-bold text-rose-455 mt-1">
                Rp {{ number_format($stats['unpaid'], 2, ',', '.') }}
            </h4>
        </div>
    </div>

    <!-- Main Grid Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- 12 Months Ledger Column -->
        <div class="lg:col-span-2 space-y-6">
            <h3 class="text-base font-bold text-gray-300 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Arsip Tagihan Bulanan ({{ $selectedYear }})
            </h3>
            
            <div class="bg-gray-900 rounded-2xl border border-gray-850 shadow-sm overflow-hidden" x-data="{ expandedMonth: {{ now()->month }} }">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse border border-gray-800">
                        <thead>
                            <tr class="bg-gray-900 border-b border-gray-800 text-xs text-gray-350 uppercase font-bold">
                                <th class="px-4 py-3 border border-gray-800">Bulan / Nama Tagihan</th>
                                <th class="px-4 py-3 border border-gray-800 text-right">Jumlah (Rupiah)</th>
                                <th class="px-4 py-3 border border-gray-800 text-center">Tahun</th>
                                <th class="px-4 py-3 border border-gray-800 text-center">Status</th>
                                <th class="px-4 py-3 border border-gray-800">Keterangan / Rincian</th>
                                <th class="px-4 py-3 border border-gray-800 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($months as $monthIdx => $monthName)
                                @php
                                    $monthItems = $monthlyData[$monthIdx];
                                    $monthTotal = $monthItems->sum('amount');
                                    $monthIncome = $monthlyIncomes[$monthIdx] ?? 0;
                                    $netRemaining = $monthIncome - $monthTotal;
                                @endphp
                                
                                <!-- Month Summary Row -->
                                <tr class="bg-black/50 border-y border-gray-800 hover:bg-gray-800/10 cursor-pointer text-xs transition-colors"
                                    @click="expandedMonth = (expandedMonth === {{ $monthIdx }} ? null : {{ $monthIdx }})">
                                    <td class="px-4 py-3 font-extrabold text-gray-200 flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-gray-500 transition-transform duration-200" 
                                             :class="expandedMonth === {{ $monthIdx }} ? 'rotate-90' : ''" 
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7-7"/>
                                        </svg>
                                        {{ $monthName }}
                                        <span class="text-[10px] text-gray-500 font-normal uppercase tracking-wider">({{ $monthItems->count() }} Tagihan)</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-black text-rose-455">
                                        Rp {{ number_format($monthTotal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-500 font-bold">{{ $selectedYear }}</td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        @if($monthTotal > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $monthItems->where('status', 'belum')->count() > 0 ? 'bg-amber-900/20 text-amber-400 border border-amber-800/40' : 'bg-emerald-900/20 text-emerald-450 border border-emerald-800/40' }}">
                                                {{ $monthItems->where('status', 'belum')->count() > 0 ? 'Ada Belum Dibayar' : 'Lunas Semua' }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-600 italic">Belum Ada</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-[10px] text-gray-500 truncate max-w-xs">
                                        <span>Gaji: Rp {{ number_format($monthIncome, 0, ',', '.') }} | Sisa: <span class="{{ $netRemaining >= 0 ? 'text-indigo-400' : 'text-rose-500' }} font-bold">Rp {{ number_format($netRemaining, 0, ',', '.') }}</span></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <!-- Open indicator -->
                                    </td>
                                </tr>

                                <!-- Item Rows under this Month -->
                                @forelse($monthItems as $item)
                                    <tr x-show="expandedMonth === {{ $monthIdx }}" 
                                        x-transition.opacity 
                                        class="hover:bg-gray-800/20 text-xs transition border-b border-gray-800 bg-black/10">
                                        <td class="px-8 py-2.5 font-semibold text-gray-300 pl-8">
                                            {{ $item->title }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-mono font-bold text-rose-455 border-r border-gray-800/40">
                                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center text-gray-400 font-bold border-r border-gray-800/40">
                                            {{ $selectedYear }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center border-r border-gray-800/40">
                                            <select wire:change="updateStatus('{{ $item->id }}', $event.target.value)" 
                                                    class="bg-transparent border-0 text-xs font-bold p-0.5 focus:ring-0 focus:outline-none cursor-pointer w-full text-center {{ $item->status === 'sudah' ? 'text-emerald-400' : 'text-rose-400' }}">
                                                <option value="belum" class="bg-gray-900 text-rose-400 font-semibold" {{ $item->status === 'belum' ? 'selected' : '' }}>Belum Dibayar</option>
                                                <option value="sudah" class="bg-gray-900 text-emerald-400 font-semibold" {{ $item->status === 'sudah' ? 'selected' : '' }}>Sudah Dibayar</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-450 border-r border-gray-800/40 truncate max-w-xs">
                                            {{ $item->description ?: '-' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <button type="button" 
                                                    @click="$dispatch('open-confirm-modal', { id: '{{ $item->id }}', action: 'delete' })" 
                                                    class="text-gray-555 hover:text-rose-400 p-1 rounded transition-all">
                                                <svg class="w-3.5 h-3.5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr x-show="expandedMonth === {{ $monthIdx }}" 
                                        class="text-xs text-gray-500 bg-gray-950/20 hover:bg-gray-800/10 transition border-b border-gray-800">
                                        <td colspan="6" class="px-8 py-3 text-center italic text-gray-600 bg-black/10">
                                            Belum ada tagihan dicatat untuk bulan ini.
                                        </td>
                                    </tr>
                                @endforelse
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Form Column -->
        <div class="space-y-6">
            <div class="bg-gray-900 p-6 rounded-2xl border border-gray-850 shadow-sm">
                <h3 class="text-lg font-bold text-gray-250 mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Catat Tagihan
                </h3>

                <form wire:submit="save" class="space-y-4">
                    <!-- Year Selection -->
                    <div>
                        <label for="form_year" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tahun</label>
                        <select wire:model="formYear" id="form_year" 
                                class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100 font-semibold">
                            @foreach($years as $yr)
                                <option value="{{ $yr }}">{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Month Selection -->
                    <div>
                        <label for="form_month" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Bulan</label>
                        <select wire:model="formMonth" id="form_month" 
                                class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100 font-semibold">
                            @foreach($months as $idx => $name)
                                <option value="{{ $idx }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Title -->
                    <div>
                        <label for="trx_title" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Nama Tagihan</label>
                        <input wire:model="title" type="text" id="trx_title" placeholder="cth: Listrik PLN, Kos Bulanan, WiFi"
                               class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                        @error('title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="trx_amount" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Jumlah (Rupiah)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500 text-sm font-semibold">Rp</span>
                            <input wire:model="amount" type="number" id="trx_amount" placeholder="0" step="0.01" min="0.01"
                                   class="pl-10 w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                        </div>
                        @error('amount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
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
                        @error('category') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Status Dropdown -->
                    <div>
                        <label for="form_status" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status Pembayaran</label>
                        <select wire:model="status" id="form_status" 
                                class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100 font-semibold">
                            <option value="belum">Belum Dibayar</option>
                            <option value="sudah">Lunas / Sudah Dibayar</option>
                        </select>
                        @error('status') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="trx_desc" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Keterangan (Opsional)</label>
                        <textarea wire:model="description" id="trx_desc" placeholder="Catatan tambahan..." rows="2"
                                  class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100"></textarea>
                        @error('description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full py-3 bg-indigo-600 hover:bg-indigo-750 text-white text-sm font-bold rounded-xl shadow-md transition-all duration-150 active:scale-[0.98]">
                        Simpan Tagihan
                    </button>
                </form>
            </div>
        </div>
    </div>

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
                    
                    <p class="text-sm text-gray-400 mb-6">Apakah Anda yakin ingin menghapus catatan tagihan ini?</p>
                    
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
