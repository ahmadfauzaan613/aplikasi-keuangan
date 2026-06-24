<?php

use App\Models\Debt;
use App\Actions\CreateDebtAction;
use App\Actions\ToggleDebtStatusAction;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Form inputs
    public string $name = '';
    public string $type = 'payable'; // 'payable' or 'receivable'
    public string $amount = '';
    public string $due_date = '';
    public string $description = '';

    // Filters
    public string $filterType = 'all';
    public string $filterStatus = 'all';
    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => 'all'],
        'filterStatus' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        $this->due_date = now()->addMonth()->format('Y-m-d');
    }

    public function save(CreateDebtAction $createAction): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:payable,receivable',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string|max:500',
        ]);

        $createAction->execute(auth()->user(), [
            'name' => $this->name,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'due_date' => $this->due_date ?: null,
            'description' => $this->description ?: null,
        ]);

        $this->reset(['name', 'amount', 'description']);
        $this->due_date = now()->addMonth()->format('Y-m-d');

        session()->flash('message', 'Catatan hutang/piutang berhasil ditambahkan!');
    }

    public function toggleStatus(string $id, ToggleDebtStatusAction $toggleAction): void
    {
        $debt = auth()->user()->debts()->findOrFail($id);
        $toggleAction->execute($debt);

        session()->flash('message', 'Status hutang/piutang berhasil diperbarui!');
    }

    public function delete(string $id): void
    {
        $debt = auth()->user()->debts()->findOrFail($id);
        $debt->delete();

        session()->flash('message', 'Catatan berhasil dihapus!');
    }

    public function getStatsProperty(): array
    {
        $query = auth()->user()->debts();

        $totalPayable = (clone $query)->payable()->unpaid()->sum('amount');
        $totalReceivable = (clone $query)->receivable()->unpaid()->sum('amount');

        return [
            'payable' => $totalPayable,
            'receivable' => $totalReceivable,
        ];
    }

    public function with(): array
    {
        $query = auth()->user()->debts()
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterType !== 'all', function ($q) {
                $q->where('type', $this->filterType);
            })
            ->when($this->filterStatus !== 'all', function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc');

        return [
            'debts' => $query->paginate(5),
            'stats' => $this->stats,
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
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Debts Payable (Hutang Kita) -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-sm flex items-center transition-all duration-300 hover:scale-[1.02]">
            <div class="p-3 bg-rose-950/40 rounded-xl mr-4 text-rose-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">{{ __('Hutang Saya (Belum Lunas)') }}</p>
                <h4 class="text-2xl font-extrabold text-gray-200 mt-1">
                    Rp {{ number_format($stats['payable'], 2, ',', '.') }}
                </h4>
            </div>
        </div>

        <!-- Debts Receivable (Piutang Kita) -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-sm flex items-center transition-all duration-300 hover:scale-[1.02]">
            <div class="p-3 bg-emerald-950/40 rounded-xl mr-4 text-emerald-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">{{ __('Piutang Orang Lain (Belum Lunas)') }}</p>
                <h4 class="text-2xl font-extrabold text-gray-200 mt-1">
                    Rp {{ number_format($stats['receivable'], 2, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Table Column -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Filters -->
            <div class="bg-gray-900 p-5 rounded-2xl border border-gray-850 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h3 class="text-lg font-bold text-gray-200 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Daftar Hutang & Piutang
                    </h3>
                    <div class="relative w-full md:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama..." 
                               class="pl-10 pr-4 py-2 w-full text-sm bg-black border border-gray-800 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button wire:click="$set('filterType', 'all')" 
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterType === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-black text-gray-400 hover:bg-gray-800' }}">
                        Semua Jenis
                    </button>
                    <button wire:click="$set('filterType', 'payable')" 
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterType === 'payable' ? 'bg-rose-600 text-white shadow-sm' : 'bg-black text-gray-400 hover:bg-gray-800' }}">
                        Hutang Saya
                    </button>
                    <button wire:click="$set('filterType', 'receivable')" 
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterType === 'receivable' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-black text-gray-400 hover:bg-gray-800' }}">
                        Piutang Orang
                    </button>

                    <div class="ml-auto w-full sm:w-auto flex items-center gap-2">
                        <span class="text-xs text-gray-500">Status:</span>
                        <select wire:model.live="filterStatus" 
                                class="text-xs py-1.5 pl-3 pr-8 bg-black text-gray-400 border-none rounded-lg focus:ring-indigo-500">
                            <option value="all">Semua Status</option>
                            <option value="unpaid">Belum Lunas</option>
                            <option value="paid">Lunas</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Debts Table -->
            <div class="bg-gray-900 rounded-2xl border border-gray-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-800 bg-black/50">
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Nama') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Tipe') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">{{ __('Jumlah') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Jatuh Tempo') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            @forelse($debts as $debt)
                                <tr class="hover:bg-gray-800/30 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-gray-200">{{ $debt->name }}</span>
                                            @if($debt->description)
                                                <span class="text-xs text-gray-500 mt-0.5 max-w-xs truncate">{{ $debt->description }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $debt->type === 'payable' ? 'bg-rose-950/40 text-rose-400 border border-rose-900/50' : 'bg-emerald-950/40 text-emerald-400 border border-emerald-900/50' }}">
                                            {{ $debt->type === 'payable' ? 'Hutang Saya' : 'Piutang Orang' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <span class="text-sm font-bold text-gray-200">
                                            Rp {{ number_format($debt->amount, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-400 whitespace-nowrap">
                                        {{ $debt->due_date ? $debt->due_date->format('d M Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button wire:click="toggleStatus('{{ $debt->id }}')" 
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold transition-all duration-200 {{ $debt->status === 'paid' ? 'bg-emerald-900/20 text-emerald-400 hover:bg-emerald-900/30' : 'bg-amber-900/20 text-amber-400 hover:bg-amber-900/30' }}">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $debt->status === 'paid' ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                            {{ $debt->status === 'paid' ? 'Lunas' : 'Belum Lunas' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <button type="button"
                                                @click="$dispatch('open-confirm-modal', { id: '{{ $debt->id }}', action: 'delete' })" 
                                                class="text-gray-500 hover:text-rose-400 transition-colors p-1 rounded hover:bg-rose-955/20">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        <p class="text-sm font-semibold">{{ __('Tidak ada catatan hutang/piutang') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($debts->hasPages())
                    <div class="px-6 py-4 bg-black/30 border-t border-gray-800">
                        {{ $debts->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Form Column -->
        <div class="space-y-6">
            <div class="bg-gray-900 p-6 rounded-2xl border border-gray-800 shadow-sm">
                <h3 class="text-lg font-bold text-gray-200 mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Catat Hutang/Piutang
                </h3>

                <form wire:submit="save" class="space-y-4">
                    <!-- Type selection -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Jenis Catatan</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" wire:click="$set('type', 'payable')" 
                                    class="py-2 rounded-xl text-xs font-bold text-center border transition-all duration-200 {{ $type === 'payable' ? 'bg-rose-950/20 border-rose-500 text-rose-400 ring-2 ring-rose-500/10' : 'bg-black border-gray-800 text-gray-400 hover:bg-gray-850' }}">
                                Hutang Saya
                            </button>
                            <button type="button" wire:click="$set('type', 'receivable')" 
                                    class="py-2 rounded-xl text-xs font-bold text-center border transition-all duration-200 {{ $type === 'receivable' ? 'bg-emerald-950/20 border-emerald-500 text-emerald-400 ring-2 ring-emerald-500/10' : 'bg-black border-gray-800 text-gray-400 hover:bg-gray-850' }}">
                                Piutang Orang
                            </button>
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="debt_name" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Nama Orang / Lembaga</label>
                        <input wire:model="name" type="text" id="debt_name" placeholder="Nama..."
                               class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                        @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="debt_amount" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Jumlah</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500 text-sm font-semibold">Rp</span>
                            <input wire:model="amount" type="number" id="debt_amount" placeholder="0" step="0.01" min="0.01"
                                   class="pl-10 w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                        </div>
                        @error('amount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label for="debt_due" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tenggat Waktu / Jatuh Tempo</label>
                        <input wire:model="due_date" type="date" id="debt_due"
                               class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100">
                        @error('due_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="debt_desc" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Deskripsi / Keperluan</label>
                        <textarea wire:model="description" id="debt_desc" placeholder="cth: Pinjam uang beli makan..." rows="2"
                                  class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-100"></textarea>
                        @error('description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" 
                            class="w-full py-3 bg-indigo-600 hover:bg-indigo-750 text-white text-sm font-bold rounded-xl shadow-md transition-all duration-150 active:scale-[0.98]">
                        Simpan Catatan
                    </button>
                </form>
            </div>
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
                    
                    <p class="text-sm text-gray-400 mb-6">Apakah Anda yakin ingin menghapus catatan hutang/piutang ini?</p>
                    
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
