<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('incomes', 'incomes')
    ->middleware(['auth'])
    ->name('incomes');

Route::view('bills', 'bills')
    ->middleware(['auth'])
    ->name('bills');

Route::view('debts', 'debts')
    ->middleware(['auth'])
    ->name('debts');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
