<?php

use App\Models\User;
use App\Models\Transaction;
use Livewire\Volt\Volt;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view transaction tracker', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSeeLivewire('finance.tracker');
});

test('user can add an income transaction', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('finance.tracker')
        ->set('title', 'Project Freelance')
        ->set('amount', 5000000)
        ->set('type', 'income')
        ->set('category', 'Investasi')
        ->set('description', 'Pembayaran project pertama')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('title', '')
        ->assertSet('amount', '');

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'title' => 'Project Freelance',
        'amount' => 5000000.00,
        'type' => 'income',
        'category' => 'Investasi',
        'description' => 'Pembayaran project pertama',
    ]);
});

test('transaction inputs are validated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('finance.tracker')
        ->set('title', '')
        ->set('amount', -100) // Invalid negative amount
        ->call('save')
        ->assertHasErrors(['title' => 'required', 'amount' => 'min']);
});

test('user can delete their own transaction', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'Uang Makan',
        'amount' => 50000,
        'type' => 'expense',
        'category' => 'Makanan & Minuman',
        'transaction_date' => now(),
    ]);

    $this->actingAs($user);

    Volt::test('finance.tracker')
        ->call('delete', $transaction->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('transactions', [
        'id' => $transaction->id,
    ]);
});
