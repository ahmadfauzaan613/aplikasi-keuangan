<?php

use App\Models\Allowance;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;

new class extends Component {
    public string $name = '';
    public string $amount = '';
    public string $status = 'belum';
    public string $description = '';
    public int $formMonth = 1;
    public int $formYear = 2026;
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
        if (now()->month === 12) {
            $years[] = max($startYear, $currentYear) + 1;
        }
        return array_unique($years);
    }

    public function getMonthsProperty(): array
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'status' => 'required|in:sudah,belum',
            'description' => 'nullable|string|max:500',
            'formMonth' => 'required|integer|between:1,12',
            'formYear' => 'required|integer',
        ]);

        $date = Carbon::create($this->formYear, $this->formMonth, 25)->format('Y-m-d');

        auth()->user()->allowances()->create([
            'name' => $this->name,
            'amount' => (float) $this->amount,
            'allowance_date' => $date,
            'status' => $this->status,
            'description' => $this->description ?: null,
        ]);

        $this->reset(['name', 'amount', 'description']);
        $this->status = 'belum';

        session()->flash('allowance_message', 'Tunjangan berhasil dicatat!');
        $this->dispatch('close-allowance-modal');
    }

    public function updateStatus(string $id, string $newStatus): void
    {
        $allowance = auth()->user()->allowances()->findOrFail($id);
        $allowance->update(['status' => $newStatus]);

        session()->flash('allowance_message', 'Status tunjangan berhasil diperbarui!');
    }

    public function delete(string $id): void
    {
        $allowance = auth()->user()->allowances()->findOrFail($id);
        $allowance->delete();

        session()->flash('allowance_message', 'Tunjangan berhasil dihapus!');
    }

    public function getMonthlyDataProperty(): array
    {
        $allowances = auth()->user()->allowances()
            ->whereYear('allowance_date', $this->selectedYear)
            ->get();

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[$m] = $allowances->filter(fn($a) => Carbon::parse($a->allowance_date)->month === $m);
        }
        return $data;
    }

    public function getStatsProperty(): array
    {
        $query = auth()->user()->allowances()->whereYear('allowance_date', $this->selectedYear);
        $sudah = (clone $query)->where('status', 'sudah')->sum('amount');
        $belum = (clone $query)->where('status', 'belum')->sum('amount');
        return ['sudah' => $sudah, 'belum' => $belum, 'total' => $sudah + $belum];
    }

    public function with(): array
    {
        return [
            'monthlyData' => $this->monthlyData,
            'years' => $this->years,
            'months' => $this->months,
            'stats' => $this->stats,
        ];
    }
}; ?>

<div class="space-y-8" x-data="{ showAllowanceModal: false }" @close-allowance-modal.window="showAllowanceModal = false">
    @if (session()->has('allowance_message'))
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
                <span class="font-bold text-gray-200">{{ session('allowance_message') }}</span>
            </div>
            <button @click="show = false" class="text-gray-450 hover:text-gray-200 transition-colors ml-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-900 p-6 rounded-2xl border border-gray-850 shadow-sm">
        <div>
            <h3 class="text-lg font-bold text-gray-250 mb-1 flex items-center">
                <svg class="w-5 h-5 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Tunjangan
            </h3>
            <p class="text-xs text-gray-500">Catat tunjangan yang sudah atau belum diterima.</p>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <span class="text-sm font-semibold text-gray-400">Berkas Tahun:</span>
                <div class="relative inline-block text-left">
                    <select wire:model.live="selectedYear"
                            class="py-2 pl-9 pr-10 bg-black border border-gray-850 rounded-xl focus:ring-1 focus:ring-teal-500 focus:border-teal-500 text-gray-200 text-sm font-bold appearance-none cursor-pointer">
                        @foreach($years as $yr)
                            <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-teal-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    </div>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-505">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>
            <button type="button" @click="showAllowanceModal = true"
                    class="py-2.5 px-4 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center gap-1.5 active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Catat Tunjangan
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 shadow-sm">
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Total Tunjangan {{ $selectedYear }}</p>
            <h4 class="text-xl font-bold text-gray-200 mt-1">Rp {{ number_format($stats['total'], 2, ',', '.') }}</h4>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 shadow-sm">
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Sudah Diterima</p>
            <h4 class="text-xl font-bold text-emerald-450 mt-1">Rp {{ number_format($stats['sudah'], 2, ',', '.') }}</h4>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 shadow-sm">
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Belum Diterima</p>
            <h4 class="text-xl font-bold text-amber-450 mt-1">Rp {{ number_format($stats['belum'], 2, ',', '.') }}</h4>
        </div>
    </div>

    <!-- Main Table -->
    <div class="w-full space-y-6">
        <div class="bg-gray-900 rounded-2xl border border-gray-850 shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-black/40 border-b border-gray-800 flex items-center justify-between">
                <span class="text-sm font-bold text-gray-250">Laporan Tunjangan Januari - Desember ({{ $selectedYear }})</span>
            </div>

            <div class="overflow-x-auto pb-4">
                <table class="min-w-[950px] w-full text-left border-collapse border border-gray-800">
                    <thead>
                        <tr class="bg-gray-900 border-b border-gray-850 text-xs text-white uppercase font-black tracking-wider">
                            <th class="px-6 py-3 border border-gray-800 text-center">Bulan</th>
                            <th class="px-6 py-3 border border-gray-800">Nama Tunjangan</th>
                            <th class="px-6 py-3 border border-gray-800 text-right">Jumlah (Rupiah)</th>
                            <th class="px-6 py-3 border border-gray-800 text-center">Tahun</th>
                            <th class="px-6 py-3 border border-gray-800 text-center">Status</th>
                            <th class="px-6 py-3 border border-gray-800">Keterangan</th>
                            <th class="px-6 py-3 border border-gray-800 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800 bg-black/20">
                        @foreach($months as $monthIdx => $monthName)
                            @php $monthItems = $monthlyData[$monthIdx]; @endphp
                            @if($monthItems->count() > 0)
                                @foreach($monthItems as $item)
                                    <tr class="hover:bg-gray-850/30 text-xs transition">
                                        <td class="px-6 py-3 font-extrabold text-teal-400 border border-gray-800 text-center">{{ $monthName }}</td>
                                        <td class="px-6 py-3 font-bold text-white border border-gray-800">{{ $item->name }}</td>
                                        <td class="px-6 py-3 text-right font-black text-emerald-450 border border-gray-800">
                                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-3 text-center text-gray-300 font-bold border border-gray-800">{{ $selectedYear }}</td>
                                        <td class="px-6 py-3 text-center border border-gray-800">
                                            <select wire:change="updateStatus('{{ $item->id }}', $event.target.value)"
                                                    class="bg-transparent border-0 text-xs font-black p-0.5 focus:ring-0 focus:outline-none cursor-pointer w-full text-center {{ $item->status === 'sudah' ? 'text-emerald-400' : 'text-amber-400' }}">
                                                <option value="belum" class="bg-gray-900 text-amber-400 font-semibold" {{ $item->status === 'belum' ? 'selected' : '' }}>Belum Diterima</option>
                                                <option value="sudah" class="bg-gray-900 text-emerald-400 font-semibold" {{ $item->status === 'sudah' ? 'selected' : '' }}>Sudah Diterima</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-3 text-gray-300 border border-gray-800 truncate max-w-xs">{{ $item->description ?: '-' }}</td>
                                        <td class="px-6 py-3 text-center border border-gray-800">
                                            <button type="button"
                                                    @click="$dispatch('open-allowance-confirm', { id: '{{ $item->id }}' })"
                                                    class="text-gray-450 hover:text-rose-455 p-1 rounded transition-all cursor-pointer">
                                                <svg class="w-3.5 h-3.5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="text-xs text-gray-550 hover:bg-gray-850/10 transition">
                                    <td class="px-6 py-4 font-extrabold text-teal-400 border border-gray-800 text-center">{{ $monthName }}</td>
                                    <td colspan="5" class="px-6 py-4 text-center italic text-gray-600 border border-gray-800">- Belum ada catatan tunjangan -</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Form Modal -->
    <div class="relative z-40"
         x-show="showAllowanceModal"
         @close-allowance-modal.window="showAllowanceModal = false"
         style="display: none;">
        <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden rounded-2xl bg-gray-900 border border-gray-850 p-6 text-left shadow-2xl transition-all w-full max-w-lg"
                     @click.away="showAllowanceModal = false">

                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-250 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Catat Tunjangan
                        </h3>
                        <button type="button" @click="showAllowanceModal = false" class="text-gray-555 hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tahun</label>
                            <select wire:model="formYear"
                                    class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-gray-100 font-semibold">
                                @foreach($years as $yr)
                                    <option value="{{ $yr }}">{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-505 uppercase tracking-wider mb-1.5">Bulan</label>
                            <select wire:model="formMonth"
                                    class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-gray-100 font-semibold">
                                @foreach($months as $idx => $name)
                                    <option value="{{ $idx }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Nama Tunjangan</label>
                            <input wire:model="name" type="text" placeholder="cth: Tunjangan Makan, Tunjangan Transport"
                                   class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-gray-100">
                            @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Jumlah (Rupiah)</label>
                            <div class="relative" x-data="{
                                raw: @entangle('amount'),
                                format(val) { if (!val) return ''; return Number(val).toLocaleString('id-ID'); }
                            }">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-550 text-sm font-semibold">Rp</span>
                                <input type="text" placeholder="0"
                                       x-init="$watch('raw', val => $el.value = format(val))"
                                       x-bind:value="format(raw)"
                                       x-on:input="let clean = $event.target.value.replace(/\D/g, ''); raw = clean ? parseInt(clean) : ''; $el.value = format(raw);"
                                       class="pl-10 w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-gray-100">
                            </div>
                            @error('amount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
                            <select wire:model="status"
                                    class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-gray-100 font-semibold">
                                <option value="belum">Belum Diterima</option>
                                <option value="sudah">Sudah Diterima</option>
                            </select>
                            @error('status') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Keterangan (Opsional)</label>
                            <textarea wire:model="description" placeholder="Catatan tambahan..." rows="2"
                                      class="w-full text-sm py-2.5 px-4 bg-black border border-gray-800 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-gray-100"></textarea>
                            @error('description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit"
                                class="w-full py-3 bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold rounded-xl shadow-md transition-all duration-150 active:scale-[0.98]">
                            Simpan Tunjangan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-data="{ openConfirm: false, deleteId: null }"
         @open-allowance-confirm.window="deleteId = $event.detail.id; openConfirm = true"
         class="relative z-50"
         x-show="openConfirm"
         style="display: none;">
        <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden rounded-2xl bg-gray-900 border border-gray-850 p-6 text-left shadow-xl transition-all w-full max-w-md"
                     @click.away="openConfirm = false">
                    <div class="flex items-center gap-4 text-rose-500 mb-4">
                        <div class="p-3 bg-rose-955/50 rounded-xl border border-rose-900/30">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-250">Konfirmasi Hapus</h3>
                    </div>
                    <p class="text-sm text-gray-400 mb-6">Apakah Anda yakin ingin menghapus catatan tunjangan ini?</p>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="openConfirm = false"
                                class="px-4 py-2 bg-black border border-gray-800 text-gray-400 hover:bg-gray-850 rounded-xl text-sm font-semibold transition">
                            Batal
                        </button>
                        <button type="button"
                                @click="$wire.call('delete', deleteId); openConfirm = false"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-rose-900/10 transition">
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
