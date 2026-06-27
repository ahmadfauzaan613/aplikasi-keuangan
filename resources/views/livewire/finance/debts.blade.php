<?php

use App\Models\Debt;
use App\Actions\CreateDebtAction;
use App\Actions\ToggleDebtStatusAction;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;

new class extends Component {
    // Form inputs
    public string $name = '';
    public string $type = 'payable'; // 'payable' or 'receivable'
    public string $amount = '';
    public string $due_date = '';
    public string $description = '';
    public string $paid_amount = '';
    public string $tenor_months = '';

    // Add Form Date selections
    public int $formMonth = 1;
    public int $formYear = 2026;

    // Filter Year & Month
    public int $selectedYear = 2026;
    public int $activeMonth = 1;

    // Editing state
    public ?string $editingId = null;

    public function mount(): void
    {
        $nowIndo = now('Asia/Jakarta');
        $nextMonth = $nowIndo->copy()->addMonth();

        $this->selectedYear = $nextMonth->year;
        $this->formYear = $nextMonth->year;
        $this->formMonth = $nextMonth->month;
        $this->activeMonth = $nextMonth->month;
        $this->due_date = $nextMonth->format('Y-m-d');
        $this->amount = '0';
    }

    public function getYearsProperty(): array
    {
        $currentYear = now()->year;
        $startYear = 2026;
        
        $years = [];
        for ($y = $startYear; $y <= max($startYear, $currentYear); $y++) {
            $years[] = $y;
        }
        
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
        $this->editingId = null;
        $this->reset(['name', 'description', 'paid_amount', 'tenor_months']);
        $this->formMonth = $month;
        $this->formYear = $this->selectedYear;
        $this->type = 'payable';
        $this->due_date = Carbon::create($this->selectedYear, $month, 1)->format('Y-m-d');
        $this->amount = '0';
    }

    public function setActiveMonth(int $month): void
    {
        $this->activeMonth = $month;
        $this->selectMonthForForm($month);
    }

    public function edit(string $id): void
    {
        $debt = auth()->user()->debts()->findOrFail($id);
        $this->editingId = $debt->id;
        $this->name = $debt->name;
        $this->type = $debt->type;
        $this->amount = (string) $debt->amount;
        $this->paid_amount = (string) $debt->paid_amount;
        $this->due_date = $debt->due_date ? $debt->due_date->format('Y-m-d') : '';
        $this->tenor_months = $debt->tenor_months !== null ? (string) $debt->tenor_months : '';
        $this->description = $debt->description ?: '';
        $this->formMonth = Carbon::parse($debt->created_at)->month;
        $this->formYear = Carbon::parse($debt->created_at)->year;
    }

    public function save(CreateDebtAction $createAction): void
    {
        $amountVal = (float) $this->amount;
        $paidAmountVal = (float) ($this->paid_amount ?: 0);
        $statusVal = $paidAmountVal >= $amountVal ? 'paid' : 'unpaid';

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:payable,receivable',
            'amount' => 'required|numeric|min:0.01',
            'paid_amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'tenor_months' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
            'formMonth' => 'required|integer|between:1,12',
            'formYear' => 'required|integer',
        ]);

        $date = Carbon::create($this->formYear, $this->formMonth, 1)->startOfDay();

        if ($this->editingId) {
            $debt = auth()->user()->debts()->findOrFail($this->editingId);
            $debt->name = $this->name;
            $debt->type = $this->type;
            $debt->amount = $amountVal;
            $debt->paid_amount = $paidAmountVal;
            $debt->status = $statusVal;
            $debt->due_date = $this->due_date ?: null;
            $debt->tenor_months = $this->tenor_months !== '' ? (int) $this->tenor_months : null;
            $debt->description = $this->description ?: null;
            $debt->created_at = $date;
            $debt->updated_at = $date;
            $debt->save(['timestamps' => false]);
            $this->editingId = null;
            session()->flash('message', 'Catatan hutang berhasil diperbarui!');
        } else {
            $debt = $createAction->execute(auth()->user(), [
                'name' => $this->name,
                'type' => $this->type,
                'amount' => $amountVal,
                'due_date' => $this->due_date ?: null,
                'description' => $this->description ?: null,
            ]);
            $debt->paid_amount = $paidAmountVal;
            $debt->status = $statusVal;
            $debt->tenor_months = $this->tenor_months !== '' ? (int) $this->tenor_months : null;
            $debt->created_at = $date;
            $debt->updated_at = $date;
            $debt->save(['timestamps' => false]);
            session()->flash('message', 'Catatan hutang berhasil ditambahkan!');
        }

        $this->reset(['name', 'amount', 'description', 'paid_amount', 'tenor_months']);
        $this->due_date = now('Asia/Jakarta')->addMonth()->format('Y-m-d');
        $this->dispatch('close-modal');
    }

    public function updateStatus(string $id, string $newStatus): void
    {
        $debt = auth()->user()->debts()->findOrFail($id);
        $debt->update([
            'status' => $newStatus,
            'paid_amount' => $newStatus === 'paid' ? $debt->amount : 0,
        ]);

        session()->flash('message', 'Status hutang berhasil diperbarui!');
    }

    public function updateAmount(string $id, $amount): void
    {
        $amountVal = max(0.01, (float) $amount);
        $debt = auth()->user()->debts()->findOrFail($id);
        
        $status = $debt->paid_amount >= $amountVal ? 'paid' : 'unpaid';

        $debt->update([
            'amount' => $amountVal,
            'status' => $status,
        ]);

        session()->flash('message', 'Nominal hutang berhasil diperbarui!');
    }

    public function updatePaidAmount(string $id, $amount): void
    {
        $paidAmount = max(0, (float) $amount);
        $debt = auth()->user()->debts()->findOrFail($id);
        
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
        $debt = auth()->user()->debts()->findOrFail($id);
        $debt->update([
            'tenor_months' => $tenorVal,
        ]);

        session()->flash('message', 'Tenor berhasil diperbarui!');
    }

    public function delete(string $id): void
    {
        $debt = auth()->user()->debts()->findOrFail($id);
        $debt->delete();

        session()->flash('message', 'Catatan berhasil dihapus!');
    }

    public function getMonthlyDataProperty(): array
    {
        $debts = auth()->user()->debts()
            ->whereYear('created_at', $this->selectedYear)
            ->get();

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[$m] = $debts->filter(fn($d) => Carbon::parse($d->created_at)->month === $m);
        }

        return $data;
    }

    public function getStatsProperty(): array
    {
        $items = $this->monthlyData[$this->activeMonth] ?? collect();

        $totalPayable = collect($items)->where('type', 'payable')->where('status', 'unpaid')->sum('amount');

        return [
            'payable' => $totalPayable,
        ];
    }

    public function getActiveMonthStatsProperty(): array
    {
        $total = 0;
        $items = $this->monthlyData[$this->activeMonth] ?? [];
        foreach ($items as $item) {
            $total += $item->amount;
        }
        return [
            'total' => $total,
        ];
    }

    public function getBillsRemainderProperty(): float
    {
        $user = auth()->user();
        $year = $this->selectedYear;
        $month = $this->activeMonth;

        // Income diambil dari bulan sebelumnya (gaji bulan lalu untuk tagihan bulan ini)
        if ($month === 1) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        } else {
            $prevMonth = $month - 1;
            $prevYear = $year;
        }

        $income = $user->transactions()
            ->income()
            ->whereYear('transaction_date', $prevYear)
            ->whereMonth('transaction_date', $prevMonth)
            ->sum('amount');

        // Total yang sudah dibayar dari tagihan (expense) bulan aktif
        $totalPaid = $user->transactions()
            ->expense()
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->sum('paid_amount');

        return (float) $income - (float) $totalPaid;
    }

    public function with(): array
    {
        return [
            'monthlyData' => $this->monthlyData,
            'years' => $this->years,
            'months' => $this->months,
            'stats' => $this->stats,
            'activeMonthStats' => $this->activeMonthStats,
            'billsRemainder' => $this->billsRemainder,
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

    <!-- Year Filter Header & Stats -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-900 p-6 rounded-2xl border border-gray-850 shadow-sm">
        <div>
            <h3 class="text-lg font-bold text-gray-250 mb-1 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                Berkas Hutang Tahunan
            </h3>
            <p class="text-xs text-gray-550">Menampilkan rekapan hutang.</p>
        </div>
    </div>

    <!-- Stats Cards Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 shadow-md hover:border-gray-700 transition">
            <p class="text-indigo-300 text-xs font-extrabold uppercase tracking-wider">Hutang {{ $months[$activeMonth] }}</p>
            <h4 class="text-2xl font-extrabold text-indigo-300 mt-1.5">
                Rp {{ number_format($activeMonthStats['total'], 0, ',', '.') }}
            </h4>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 shadow-md hover:border-gray-700 transition flex items-center justify-between">
            <div>
                <p class="text-rose-300 text-xs font-extrabold uppercase tracking-wider">Hutang Belum Lunas {{ $months[$activeMonth] }}</p>
                <h4 class="text-2xl font-extrabold text-rose-500 mt-1.5">
                    Rp {{ number_format($stats['payable'], 0, ',', '.') }}
                </h4>
            </div>
            <div class="p-3 bg-rose-950/40 rounded-xl text-rose-455">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.1" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Filter Navigation Bar (Sticky) -->
    <div class="sticky top-4 z-30 bg-gray-900/95 backdrop-blur-md border border-gray-850 p-4 rounded-2xl shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-indigo-400 font-bold text-sm pl-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            <span>Arsip & Filter Catatan</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <!-- Month Selector Dropdown -->
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-semibold text-gray-400">Pilih Bulan:</span>
                <div class="relative inline-block text-left">
                    <select wire:change="setActiveMonth($event.target.value)" 
                            class="py-2 pl-3 pr-10 bg-black border border-gray-855 rounded-xl focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-gray-200 text-xs font-bold appearance-none cursor-pointer">
                        @foreach($months as $monthIdx => $monthName)
                            <option value="{{ $monthIdx }}" {{ $activeMonth === $monthIdx ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Year Selector Dropdown (Berkas Tahun) -->
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-semibold text-gray-400">Berkas Tahun:</span>
                <div class="relative inline-block text-left">
                    <select wire:model.live="selectedYear" 
                            class="py-2 pl-3 pr-10 bg-black border border-gray-855 rounded-xl focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-gray-200 text-xs font-bold appearance-none cursor-pointer">
                        @foreach($years as $yr)
                            <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Ledger Content (Full Width) -->
    <div class="w-full space-y-6">
        @foreach($months as $monthIdx => $monthName)
            @if($monthIdx === $activeMonth)
                @php
                    $monthItems = $monthlyData[$monthIdx];
                @endphp
                <div id="month-card-{{ $monthIdx }}" class="scroll-mt-24 bg-gray-900 rounded-2xl border border-gray-850 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-black/40 border-b border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-base font-black text-white">{{ $monthName }}</span>
                        <span class="text-xs font-extrabold text-indigo-400">({{ count($monthItems) }} Catatan)</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="text-gray-250 font-extrabold">
                            Sisa Gaji:
                            <span class="ml-1 text-sm font-black px-2 py-0.5 border rounded-lg {{ $billsRemainder >= 0 ? 'text-amber-400 bg-amber-950/40 border-amber-900/30' : 'text-rose-500 bg-rose-950/40 border-rose-900/30' }}">{{ $billsRemainder < 0 ? '−' : '' }}Rp {{ number_format(abs($billsRemainder), 0, ',', '.') }}</span>
                        </span>
                        <button type="button" wire:click="selectMonthForForm({{ $monthIdx }})" 
                                @click="showFormModal = true"
                                class="py-1.5 px-3 bg-indigo-650 hover:bg-indigo-600 text-white text-xs font-black rounded-xl transition active:scale-[0.98] flex items-center gap-1 shadow-md shadow-indigo-900/20 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            Catat Hutang
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto pb-4">
                    <table class="min-w-[1100px] w-full text-left border-collapse border border-gray-800">
                        <thead>
                            <tr class="bg-gray-900 border-b border-gray-855 text-xs text-white uppercase font-black tracking-wider">
                                <th class="px-6 py-3 border border-gray-800">Nama</th>
                                <th class="px-6 py-3 border border-gray-800 text-center w-36">Status</th>
                                <th class="px-6 py-3 border border-gray-800 text-center w-40">Jatuh Tempo</th>
                                <th class="px-6 py-3 border border-gray-800 text-right w-44">Nominal</th>
                                <th class="px-6 py-3 border border-gray-800 text-right w-44">Sisa Gaji</th>
                                <th class="px-6 py-3 border border-gray-800 text-right w-52">Nominal yang sudah dibayar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 bg-black/20">
                            @if(count($monthItems) > 0)
                                @php $runningGaji = $billsRemainder; @endphp
                                @foreach($monthItems as $item)
                                @php
                                    $sisaGajiRow = $runningGaji - $item->amount;
                                    $runningGaji = $sisaGajiRow;
                                @endphp
                                    <tr class="hover:bg-gray-850/30 text-xs transition group">
                                        <!-- Nama -->
                                        <td class="px-6 py-3 font-bold text-white border border-gray-800">
                                            <div class="flex items-center justify-between">
                                                <div class="flex flex-col">
                                                    <span>{{ $item->name }}</span>
                                                    @if($item->description)
                                                        <span class="text-xs text-gray-400 font-medium mt-0.5">{{ $item->description }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-all ml-2">
                                                    <button type="button" 
                                                            wire:click="edit('{{ $item->id }}')" 
                                                            @click="showFormModal = true"
                                                            class="text-gray-450 hover:text-indigo-400 p-0.5 rounded transition-all cursor-pointer"
                                                            title="Edit catatan">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    </button>
                                                    <button type="button" 
                                                            wire:click="delete('{{ $item->id }}')" 
                                                            class="text-gray-450 hover:text-rose-455 p-0.5 rounded transition-all cursor-pointer"
                                                            title="Hapus catatan">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Status -->
                                        <td class="px-6 py-3 text-center border border-gray-800 w-36">
                                            <select wire:change="updateStatus('{{ $item->id }}', $event.target.value)" 
                                                    class="bg-transparent border-0 text-xs font-black p-0.5 focus:ring-0 focus:outline-none cursor-pointer w-full text-center {{ $item->status === 'paid' ? 'text-emerald-400' : ($item->paid_amount > 0 ? 'text-amber-400' : 'text-rose-500') }}">
                                                <option value="unpaid" class="bg-gray-900 text-rose-500 font-semibold" {{ $item->status === 'unpaid' ? 'selected' : '' }}>
                                                    {{ $item->paid_amount > 0 ? 'Belum Lunas (Cicil)' : 'Belum Lunas' }}
                                                </option>
                                                <option value="paid" class="bg-gray-900 text-emerald-400 font-semibold" {{ $item->status === 'paid' ? 'selected' : '' }}>Lunas</option>
                                            </select>
                                        </td>
                                        <!-- Jatuh Tempo -->
                                        <td class="px-6 py-3 text-center text-gray-300 font-bold border border-gray-800 w-40 whitespace-nowrap">
                                            {{ $item->due_date ? $item->due_date->format('d M Y') : '-' }}
                                        </td>
                                        <!-- Nominal -->
                                        <td class="px-6 py-3 text-right font-black text-white border border-gray-800 whitespace-nowrap w-44">
                                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                                        </td>
                                        <!-- Sisa Gaji -->
                                        <td class="px-6 py-3 text-right font-black border border-gray-800 whitespace-nowrap w-44 {{ $sisaGajiRow >= 0 ? 'text-amber-400' : 'text-rose-500' }}">
                                            {{ $sisaGajiRow < 0 ? '−' : '' }}Rp {{ number_format(abs($sisaGajiRow), 0, ',', '.') }}
                                        </td>
                                        <!-- Nominal yang sudah dibayar -->
                                        <td class="px-6 py-3 border border-gray-800 whitespace-nowrap w-52">
                                            <div class="relative flex items-center justify-end" wire:key="paid-{{ $item->id }}-{{ $item->paid_amount }}">
                                                <span class="absolute left-2 text-gray-300 text-[10px]">Rp</span>
                                                <input type="text" 
                                                       value="{{ number_format($item->paid_amount, 0, ',', '.') }}" 
                                                       x-on:input="$event.target.value = $event.target.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                                       wire:blur="updatePaidAmount('{{ $item->id }}', $event.target.value.replace(/\./g, ''))"
                                                       wire:keydown.enter="updatePaidAmount('{{ $item->id }}', $event.target.value.replace(/\./g, ''))"
                                                       class="pl-6 pr-1 w-32 bg-black/60 border border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-lg text-xs font-black text-white text-right py-1 px-1.5" />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                <!-- Bottom Row: Total -->
                                @php
                                    $monthTotalAmount = collect($monthItems)->sum('amount');
                                    $monthTotalPaid = collect($monthItems)->sum('paid_amount');
                                @endphp
                                <tr class="bg-indigo-950/30 text-xs font-black border-t-2 border-gray-800">
                                    <td colspan="3" class="px-6 py-3.5 text-indigo-300 font-extrabold border border-gray-800 text-left">
                                        Total Hutang Bulan Ini
                                    </td>
                                    <td class="px-6 py-3.5 text-right text-indigo-300 border border-gray-800 whitespace-nowrap">
                                        Rp {{ number_format($monthTotalAmount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3.5 border border-gray-800"></td>
                                    <td class="px-6 py-3.5 text-right text-indigo-300 border border-gray-800 whitespace-nowrap">
                                        Rp {{ number_format($monthTotalPaid, 0, ',', '.') }}
                                    </td>
                                </tr>

                                <!-- Bottom Row: Sisa Gaji Keseluruhan -->
                                <tr class="bg-amber-950/10 text-xs border-t border-gray-800">
                                    <td colspan="4" class="px-6 py-3.5 text-gray-400 font-extrabold border border-gray-800 text-left">
                                        Sisa Gaji Keseluruhan
                                    </td>
                                    <td class="px-6 py-3.5 text-right font-extrabold border border-gray-800 {{ $runningGaji >= 0 ? 'text-emerald-400' : 'text-rose-500' }} whitespace-nowrap">
                                        {{ $runningGaji < 0 ? '−' : '' }}Rp {{ number_format(abs($runningGaji), 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3.5 border border-gray-800"></td>
                                </tr>
                            @else
                                <tr class="text-xs text-gray-550 hover:bg-gray-850/10 transition">
                                    <td colspan="6" class="px-6 py-6 text-center italic text-gray-400 border border-gray-800">
                                        - Belum ada catatan hutang untuk bulan {{ $monthName }} -
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        @endforeach
    </div>

    <!-- Form Modal (Catat Hutang Popup) -->
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
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $editingId ? 'Edit Catatan Hutang' : 'Catat Hutang' }}
                        </h3>
                        <button type="button" @click="showFormModal = false" class="text-gray-555 hover:text-gray-350">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-4">
                        <!-- Year Selection -->
                        <div>
                            <label for="form_year" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tahun</label>
                            <select wire:model="formYear" id="form_year" 
                                    class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-505 focus:border-indigo-505 text-gray-100 font-semibold">
                                @foreach($years as $yr)
                                    <option value="{{ $yr }}">{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Month Selection -->
                        <div>
                            <label for="form_month" class="block text-xs font-semibold text-gray-555 uppercase tracking-wider mb-1.5">Bulan</label>
                            <select wire:model="formMonth" id="form_month" 
                                    class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-505 focus:border-indigo-505 text-gray-100 font-semibold">
                                @foreach($months as $idx => $name)
                                    <option value="{{ $idx }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Name -->
                        <div>
                            <label for="debt_name" class="block text-xs font-semibold text-gray-555 uppercase tracking-wider mb-1.5">Nama Orang / Lembaga</label>
                            <input wire:model="name" type="text" id="debt_name" placeholder="Nama..."
                                   class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-505 focus:border-indigo-505 text-gray-100">
                            @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Amount -->
                        <div>
                            <label for="debt_amount" class="block text-xs font-semibold text-gray-550 uppercase tracking-wider mb-1.5">Jumlah</label>
                            <div class="relative" x-data="{
                                raw: @entangle('amount'),
                                format(val) {
                                    if (!val) return '';
                                    return Number(val).toLocaleString('id-ID');
                                }
                            }">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-550 text-sm font-semibold">Rp</span>
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
                            @error('amount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Paid Amount -->
                        <div>
                            <label for="debt_paid_amount" class="block text-xs font-semibold text-gray-550 uppercase tracking-wider mb-1.5">Nominal Yang Sudah Dibayar (Opsional)</label>
                            <div class="relative" x-data="{
                                raw: @entangle('paid_amount'),
                                format(val) {
                                    if (!val) return '';
                                    return Number(val).toLocaleString('id-ID');
                                }
                            }">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-550 text-sm font-semibold">Rp</span>
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
                            @error('paid_amount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Due Date -->
                        <div>
                            <label for="debt_due" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tenggat Waktu / Jatuh Tempo</label>
                            <input wire:model="due_date" type="date" id="debt_due"
                                   x-on:click="$el.showPicker()"
                                   class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-505 focus:border-indigo-505 text-gray-100 cursor-pointer">
                            @error('due_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="debt_desc" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Deskripsi / Keperluan</label>
                            <textarea wire:model="description" id="debt_desc" placeholder="cth: Pinjam uang beli makan..." rows="2"
                                      class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-505 focus:border-indigo-505 text-gray-100"></textarea>
                            @error('description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" 
                                class="w-full py-3 bg-indigo-600 hover:bg-indigo-750 text-white text-sm font-bold rounded-xl shadow-md transition-all duration-150 active:scale-[0.98]">
                            {{ $editingId ? 'Perbarui Catatan' : 'Simpan Catatan' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
