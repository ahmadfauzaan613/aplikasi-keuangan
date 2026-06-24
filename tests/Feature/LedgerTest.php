<?php

use App\Models\User;
use App\Models\Transaction;
use Livewire\Volt\Volt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('authenticated user can view incomes ledger and log income', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('finance.incomes')
        ->set('title', 'Gaji Pokok')
        ->set('amount', 8000000)
        ->set('formMonth', 6)
        ->set('formYear', 2026)
        ->set('status', 'belum')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'title' => 'Gaji Pokok',
        'amount' => 8000000.00,
        'type' => 'income',
        'transaction_date' => '2026-06-25 00:00:00', // Fixed on 25th
        'status' => 'belum',
    ]);
});

test('authenticated user can update income status', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'Bonus THR',
        'amount' => 5000000,
        'type' => 'income',
        'transaction_date' => '2026-06-25',
        'status' => 'belum',
    ]);

    $this->actingAs($user);

    Volt::test('finance.incomes')
        ->call('updateStatus', $transaction->id, 'sudah')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'status' => 'sudah',
    ]);
});

test('december automatically adds next year to select list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Mock time to December
    Carbon::setTestNow(Carbon::parse('2026-12-05'));

    Volt::test('finance.incomes')
        ->assertSee('2027');

    Carbon::setTestNow(); // Reset time
});

test('authenticated user can view bills ledger and log bill', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('finance.bills')
        ->set('title', 'Tagihan Listrik')
        ->set('amount', 500000)
        ->set('formMonth', 6)
        ->set('formYear', 2026)
        ->set('status', 'belum')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'title' => 'Tagihan Listrik',
        'amount' => 500000.00,
        'type' => 'expense',
        'transaction_date' => '2026-06-01 00:00:00', // Fixed on 1st for bills
        'status' => 'belum',
    ]);
});

test('authenticated user can update bill status', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'Internet',
        'amount' => 350000,
        'type' => 'expense',
        'transaction_date' => '2026-06-01',
        'status' => 'belum',
    ]);

    $this->actingAs($user);

    Volt::test('finance.bills')
        ->call('updateStatus', $transaction->id, 'sudah')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'status' => 'sudah',
    ]);
});

test('it correctly calculates monthly net remaining balance', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create income in January 2026 (date: 2026-01-25)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'Gaji Januari',
        'amount' => 100000,
        'type' => 'income',
        'transaction_date' => '2026-01-25 00:00:00',
        'status' => 'sudah',
    ]);

    // Create bill in January 2026 (date: 2026-01-01)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'Tagihan Internet',
        'amount' => 30000,
        'type' => 'expense',
        'transaction_date' => '2026-01-01 00:00:00',
        'status' => 'sudah',
    ]);

    // Test bills view monthly data
    Volt::test('finance.bills')
        ->set('selectedYear', 2026)
        ->assertViewHas('monthlyIncomes', function ($incomes) {
            return $incomes[1] == 100000;
        });

    // Test incomes view monthly data
    Volt::test('finance.incomes')
        ->set('selectedYear', 2026)
        ->assertViewHas('monthlyExpenses', function ($expenses) {
            return $expenses[1] == 30000;
        });
});
