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

    // Create 2 initial transactions for June
    Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'WiFi Indihome',
        'amount' => 350000,
        'paid_amount' => 350000,
        'status' => 'sudah',
        'type' => 'expense',
        'transaction_date' => '2026-06-01',
    ]);
    Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'Asuransi Swasta',
        'amount' => 450000,
        'paid_amount' => 0,
        'status' => 'belum',
        'type' => 'expense',
        'transaction_date' => '2026-06-01',
    ]);

    Volt::test('finance.bills')
        ->set('title', 'Tagihan Listrik')
        ->set('amount', 500000)
        ->set('formMonth', 6)
        ->set('formYear', 2026)
        ->set('status', 'belum')
        ->call('save')
        ->assertHasNoErrors()
        ->assertViewHas('monthlyData', function ($data) {
            $june = $data[6];
            return count($june) === 3 && collect($june)->contains(fn($item) => $item->title === 'Tagihan Listrik' && $item->amount == 500000);
        });
});

test('authenticated user can update bill status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $trx = Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'Listrik PLN',
        'amount' => 600000,
        'paid_amount' => 400000,
        'status' => 'belum',
        'type' => 'expense',
        'transaction_date' => '2026-01-01',
    ]);

    Volt::test('finance.bills')
        ->call('updateStatus', $trx->id, 'sudah')
        ->assertHasNoErrors()
        ->assertViewHas('monthlyData', function ($data) use ($trx) {
            $pln = collect($data[1])->firstWhere('id', $trx->id);
            return $pln && $pln->status === 'sudah' && $pln->paid_amount == 600000;
        });
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

    // Test incomes view monthly data
    Volt::test('finance.incomes')
        ->set('selectedYear', 2026)
        ->assertViewHas('monthlyExpenses', function ($expenses) {
            return $expenses[1] == 30000;
        });
});

test('it correctly calculates sisa hutang based on status and paid amount', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'WiFi Indihome',
        'amount' => 350000,
        'paid_amount' => 350000,
        'status' => 'sudah',
        'type' => 'expense',
        'transaction_date' => '2026-01-01',
    ]);
    Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'Listrik PLN',
        'amount' => 600000,
        'paid_amount' => 400000,
        'status' => 'belum',
        'type' => 'expense',
        'transaction_date' => '2026-01-01',
    ]);

    Volt::test('finance.bills')
        ->set('selectedYear', 2026)
        ->assertViewHas('monthlyData', function ($data) {
            $jan = $data[1];
            $pln = collect($jan)->firstWhere('title', 'Listrik PLN');
            $wifi = collect($jan)->firstWhere('title', 'WiFi Indihome');

            $sisaPLN = $pln->status === 'sudah' ? 0 : ($pln->amount - $pln->paid_amount);
            $sisaWifi = $wifi->status === 'sudah' ? 0 : ($wifi->amount - $wifi->paid_amount);

            return $sisaPLN == 200000 && $sisaWifi == 0;
        });
});

test('it correctly gets previous month salary as Uang Gaji for current month bills', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'Gaji Januari',
        'amount' => 8500000,
        'type' => 'income',
        'transaction_date' => '2026-01-25',
    ]);

    // February bills should see January static salary as Uang Gaji (which is 8500000)
    Volt::test('finance.bills')
        ->set('selectedYear', 2026)
        ->assertViewHas('monthlySalaries', function ($salaries) {
            return $salaries[2]['amount'] == 8500000; // February (month 2) reads January salary
        });
});

test('authenticated user can view debts and log debt, then edit it', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Test creating a debt
    $component = Volt::test('finance.debts')
        ->set('name', 'Budi Santoso')
        ->set('type', 'payable')
        ->set('amount', 1500000)
        ->set('due_date', '2026-07-25')
        ->set('description', 'Pinjam untuk bayar UKT')
        ->set('formMonth', 6)
        ->set('formYear', 2026)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('debts', [
        'user_id' => $user->id,
        'name' => 'Budi Santoso',
        'type' => 'payable',
        'amount' => 1500000.00,
        'due_date' => '2026-07-25 00:00:00',
        'status' => 'unpaid',
        'description' => 'Pinjam untuk bayar UKT',
    ]);

    $debt = \App\Models\Debt::first();

    // Test editing the debt
    $component->call('edit', $debt->id)
        ->assertSet('editingId', $debt->id)
        ->assertSet('name', 'Budi Santoso')
        ->set('name', 'Budi Santoso Edit')
        ->set('amount', 2000000)
        ->set('formMonth', 6)
        ->set('formYear', 2026)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('editingId', null);

    $this->assertDatabaseHas('debts', [
        'id' => $debt->id,
        'name' => 'Budi Santoso Edit',
        'amount' => 2000000.00,
    ]);
});

test('authenticated user can edit static bill details in bills ledger', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $trx = Transaction::factory()->create([
        'user_id' => $user->id,
        'title' => 'WiFi Indihome',
        'amount' => 350000,
        'paid_amount' => 350000,
        'status' => 'sudah',
        'type' => 'expense',
        'transaction_date' => '2026-01-01',
    ]);

    Volt::test('finance.bills')
        ->call('edit', $trx->id)
        ->assertSet('editingId', $trx->id)
        ->assertSet('title', 'WiFi Indihome')
        ->set('title', 'WiFi Indihome Premium')
        ->set('amount', 400000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertViewHas('monthlyData', function ($data) use ($trx) {
            $wifi = collect($data[1])->firstWhere('id', $trx->id);
            return $wifi && $wifi->title === 'WiFi Indihome Premium' && $wifi->amount == 400000;
        });
});

test('authenticated user can update debt amount directly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $debt = \App\Models\Debt::forceCreate([
        'user_id' => $user->id,
        'name' => 'Budi Santoso',
        'type' => 'payable',
        'amount' => 1500000,
        'due_date' => '2026-07-25',
        'status' => 'unpaid',
        'description' => 'Pinjam untuk UKT',
        'created_at' => '2026-06-01 00:00:00',
    ]);

    Volt::test('finance.debts')
        ->call('updateAmount', $debt->id, 1200000)
        ->assertHasNoErrors()
        ->assertViewHas('monthlyData', function ($data) use ($debt) {
            $budi = collect($data[6])->firstWhere('id', $debt->id);
            return $budi && $budi->amount == 1200000;
        });
});

test('authenticated user can update debt paid amount directly and it updates status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $debt = \App\Models\Debt::forceCreate([
        'user_id' => $user->id,
        'name' => 'Budi Santoso',
        'type' => 'payable',
        'amount' => 1500000,
        'paid_amount' => 0,
        'due_date' => '2026-07-25',
        'status' => 'unpaid',
        'description' => 'Pinjam untuk UKT',
        'created_at' => '2026-06-01 00:00:00',
    ]);

    Volt::test('finance.debts')
        ->call('updatePaidAmount', $debt->id, 1500000)
        ->assertHasNoErrors()
        ->assertViewHas('monthlyData', function ($data) use ($debt) {
            $budi = collect($data[6])->firstWhere('id', $debt->id);
            return $budi && $budi->paid_amount == 1500000 && $budi->status === 'paid';
        });
});

test('authenticated user can update debt status directly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $debt = \App\Models\Debt::forceCreate([
        'user_id' => $user->id,
        'name' => 'Budi Santoso',
        'type' => 'payable',
        'amount' => 1500000,
        'paid_amount' => 500000,
        'due_date' => '2026-07-25',
        'status' => 'unpaid',
        'description' => 'Pinjam untuk UKT',
        'created_at' => '2026-06-01 00:00:00',
    ]);

    Volt::test('finance.debts')
        ->call('updateStatus', $debt->id, 'paid')
        ->assertHasNoErrors()
        ->assertViewHas('monthlyData', function ($data) use ($debt) {
            $budi = collect($data[6])->firstWhere('id', $debt->id);
            return $budi && $budi->paid_amount == 1500000 && $budi->status === 'paid';
        });

    Volt::test('finance.debts')
        ->call('updateStatus', $debt->id, 'unpaid')
        ->assertHasNoErrors()
        ->assertViewHas('monthlyData', function ($data) use ($debt) {
            $budi = collect($data[6])->firstWhere('id', $debt->id);
            return $budi && $budi->paid_amount == 0 && $budi->status === 'unpaid';
        });
});
