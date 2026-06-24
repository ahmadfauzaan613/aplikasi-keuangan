<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div x-data="{ mobileMenuOpen: false }">
    <!-- Desktop Sidebar -->
    <aside class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0 bg-gray-950 border-r border-gray-900 z-20">
        <!-- Logo Area -->
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-900">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5" wire:navigate>
                <div class="p-1.5 bg-indigo-600 rounded-lg text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-lg font-black tracking-wider text-white">Keuangan</span>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <!-- Dashboard link -->
            <a href="{{ route('dashboard') }}" wire:navigate 
               class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>

            <!-- Incomes link -->
            <a href="{{ route('incomes') }}" wire:navigate 
               class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('incomes') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Pendapatan Bulanan
            </a>

            <!-- Bills link -->
            <a href="{{ route('bills') }}" wire:navigate 
               class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('bills') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Bayaran Bulanan
            </a>

            <!-- Debts link -->
            <a href="{{ route('debts') }}" wire:navigate 
               class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('debts') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Hutang & Piutang
            </a>

            <!-- Profile Settings -->
            <a href="{{ route('profile') }}" wire:navigate 
               class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('profile') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Profil
            </a>
        </nav>

        <!-- User Profile Area Footer -->
        <div class="p-4 border-t border-gray-900 bg-gray-950 flex flex-col space-y-3">
            <div class="flex items-center px-2">
                <div class="w-9 h-9 rounded-full bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-sm border border-indigo-500/20">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="ml-3 min-w-0">
                    <p class="text-sm font-bold text-gray-200 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-550 truncate">{{ auth()->user()->username }}</p>
                </div>
            </div>
            <button wire:click="logout" 
                    class="w-full flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-gray-400 hover:bg-rose-950/20 hover:text-rose-400 border border-transparent hover:border-rose-900/30 transition-all duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Keluar
            </button>
        </div>
    </aside>

    <!-- Mobile Top Bar -->
    <header class="flex lg:hidden items-center justify-between h-16 px-6 bg-gray-950 border-b border-gray-900 sticky top-0 z-20">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2" wire:navigate>
            <div class="p-1 bg-indigo-600 rounded text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-md font-extrabold text-white">Keuangan</span>
        </a>
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 rounded-lg text-gray-400 hover:bg-gray-900 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!mobileMenuOpen"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="mobileMenuOpen" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </header>

    <!-- Mobile Navigation Drawer Overlay -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-black/60 z-30 lg:hidden" 
         style="display: none;" 
         @click="mobileMenuOpen = false"></div>

    <!-- Mobile Navigation Drawer Menu -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-250 transform" 
         x-transition:enter-start="-translate-x-full" 
         x-transition:enter-end="translate-x-0" 
         x-transition:leave="transition ease-in duration-200 transform" 
         x-transition:leave-start="translate-x-0" 
         x-transition:leave-end="-translate-x-full" 
         class="fixed inset-y-0 left-0 w-72 max-w-xs bg-gray-950 border-r border-gray-900 p-6 flex flex-col z-40 lg:hidden"
         style="display: none;">
        <!-- Logo Area -->
        <div class="flex items-center space-x-2.5 pb-6 border-b border-gray-900">
            <div class="p-1.5 bg-indigo-600 rounded-lg text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-lg font-black tracking-wider text-white">Keuangan</span>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 py-6 space-y-1.5 overflow-y-auto">
            <a href="{{ route('dashboard') }}" wire:navigate @click="mobileMenuOpen = false"
               class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>

            <a href="{{ route('incomes') }}" wire:navigate @click="mobileMenuOpen = false"
               class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('incomes') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Pendapatan Bulanan
            </a>

            <a href="{{ route('bills') }}" wire:navigate @click="mobileMenuOpen = false"
               class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('bills') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Bayaran Bulanan
            </a>

            <a href="{{ route('debts') }}" wire:navigate @click="mobileMenuOpen = false"
               class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('debts') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Hutang & Piutang
            </a>

            <a href="{{ route('profile') }}" wire:navigate @click="mobileMenuOpen = false"
               class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('profile') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-gray-200' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Profil
            </a>
        </nav>

        <!-- Profile Footer Area -->
        <div class="pt-6 border-t border-gray-900 flex flex-col space-y-4">
            <div class="flex items-center">
                <div class="w-9 h-9 rounded-full bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-sm border border-indigo-500/20">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="ml-3 min-w-0">
                    <p class="text-sm font-bold text-gray-200 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ auth()->user()->username }}</p>
                </div>
            </div>
            <button wire:click="logout" 
                    class="w-full flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-gray-400 hover:bg-rose-950/20 hover:text-rose-400 border border-transparent hover:border-rose-900/30 transition-all duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Keluar
            </button>
        </div>
    </div>
</div>
