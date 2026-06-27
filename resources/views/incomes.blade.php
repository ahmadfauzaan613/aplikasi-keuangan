<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Pendapatan & Tunjangan') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[95%] mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Tab Navigation -->
            <div x-data="{ activeTab: 'pendapatan' }" class="space-y-6">
                <div class="flex gap-1 bg-gray-900 border border-gray-850 rounded-2xl p-1.5 w-fit">
                    <button type="button"
                            @click="activeTab = 'pendapatan'"
                            :class="activeTab === 'pendapatan' ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-400 hover:text-gray-200'"
                            class="px-5 py-2 rounded-xl text-sm font-bold transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Pendapatan
                    </button>
                    <button type="button"
                            @click="activeTab = 'tunjangan'"
                            :class="activeTab === 'tunjangan' ? 'bg-teal-600 text-white shadow-md' : 'text-gray-400 hover:text-gray-200'"
                            class="px-5 py-2 rounded-xl text-sm font-bold transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        Tunjangan
                    </button>
                </div>

                <div x-show="activeTab === 'pendapatan'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <livewire:finance.incomes />
                </div>

                <div x-show="activeTab === 'tunjangan'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <livewire:finance.allowances />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
