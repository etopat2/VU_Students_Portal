<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\FinancialLedger;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_collects_cash_and_closes_the_register_with_a_receipt(): void
    {
        $cashier = User::factory()->create(['role' => 'accountant']);
        $invoice = $this->createInvoice($cashier, '100.00');
        $this->actingAs($cashier);

        $this->post(route('cash-management.open'), ['opening_balance' => '10.00'])
            ->assertRedirect();
        $register = CashRegister::query()->firstOrFail();

        $paymentResponse = $this->post(route('cash-management.payments.store', $register), [
            'invoice_id' => $invoice->id,
            'amount' => '100.00',
        ]);
        $transaction = CashTransaction::query()->firstOrFail();

        $paymentResponse->assertRedirect(route('cash-management.receipt', $transaction));
        $this->get(route('cash-management.receipt', $transaction))
            ->assertOk()
            ->assertSee($transaction->receipt_number)
            ->assertSee('100.00');
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'method' => 'cash',
            'status' => 'verified',
            'amount' => '100.00',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('financial_ledgers', [
            'student_id' => $invoice->student_id,
            'credit' => '100.00',
            'balance_after' => '0.00',
        ]);
        $this->assertDatabaseHas('students', [
            'id' => $invoice->student_id,
            'clearance_status' => 'cleared',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $cashier->id,
            'action' => 'cash_transaction.created',
            'subject_type' => CashTransaction::class,
        ]);

        $this->put(route('cash-management.close', $register), ['closing_balance' => '110.00'])
            ->assertRedirect(route('cash-management.index'));

        $this->assertDatabaseHas('cash_registers', [
            'id' => $register->id,
            'expected_balance' => '110.00',
            'closing_balance' => '110.00',
            'variance' => '0.00',
            'status' => 'closed',
        ]);
    }

    public function test_cashier_cannot_overpay_an_invoice_or_change_financial_records(): void
    {
        $cashier = User::factory()->create(['role' => 'accountant']);
        $invoice = $this->createInvoice($cashier, '100.00');
        $this->actingAs($cashier);
        $this->post(route('cash-management.open'), ['opening_balance' => '0.00']);
        $register = CashRegister::query()->firstOrFail();

        $this->from(route('cash-management.index'))
            ->post(route('cash-management.payments.store', $register), [
                'invoice_id' => $invoice->id,
                'amount' => '100.01',
            ])
            ->assertRedirect(route('cash-management.index'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('cash_transactions', 0);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'issued',
        ]);
        $this->assertDatabaseCount('financial_ledgers', 1);
    }

    public function test_cashier_records_a_petty_cash_payout_and_reconciliation_variance(): void
    {
        $cashier = User::factory()->create(['role' => 'accountant']);
        $this->actingAs($cashier);
        $this->post(route('cash-management.open'), ['opening_balance' => '50.00'])
            ->assertRedirect();
        $register = CashRegister::query()->firstOrFail();

        $this->post(route('cash-management.expenses.store', $register), [
            'category' => 'Stationery',
            'description' => 'Printer paper',
            'amount' => '20.00',
        ])->assertRedirect(route('cash-management.index'));

        $transaction = CashTransaction::query()->firstOrFail();
        $this->assertSame('payout', $transaction->transaction_type);
        $this->assertNotEmpty($transaction->receipt_number);
        $this->assertDatabaseHas('expenses', [
            'category' => 'Stationery',
            'description' => 'Printer paper',
            'amount' => '20.00',
        ]);

        $this->put(route('cash-management.close', $register), ['closing_balance' => '25.00'])
            ->assertRedirect(route('cash-management.index'));

        $this->assertDatabaseHas('cash_registers', [
            'id' => $register->id,
            'expected_balance' => '30.00',
            'closing_balance' => '25.00',
            'variance' => '-5.00',
            'status' => 'closed',
        ]);
    }

    public function test_cashier_calculates_remaining_balance_in_minor_units(): void
    {
        $cashier = User::factory()->create(['role' => 'accountant']);
        $invoice = $this->createInvoice($cashier, '0.60');
        $firstPayment = $this->createVerifiedPayment($invoice, $cashier, '0.10', 'BANK-001');
        FinancialLedger::create([
            'student_id' => $invoice->student_id,
            'payment_id' => $firstPayment->id,
            'recorded_by' => $cashier->id,
            'debit' => '0.00',
            'credit' => '0.10',
            'balance_after' => '0.50',
            'description' => 'First bank payment',
            'posted_at' => now(),
        ]);
        $secondPayment = $this->createVerifiedPayment($invoice, $cashier, '0.20', 'BANK-002');
        FinancialLedger::create([
            'student_id' => $invoice->student_id,
            'payment_id' => $secondPayment->id,
            'recorded_by' => $cashier->id,
            'debit' => '0.00',
            'credit' => '0.20',
            'balance_after' => '0.30',
            'description' => 'Second bank payment',
            'posted_at' => now(),
        ]);
        $this->actingAs($cashier);
        $this->post(route('cash-management.open'), ['opening_balance' => '0.00']);
        $register = CashRegister::query()->firstOrFail();

        $this->post(route('cash-management.payments.store', $register), [
            'invoice_id' => $invoice->id,
            'amount' => '0.30',
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('financial_ledgers', [
            'student_id' => $invoice->student_id,
            'credit' => '0.30',
            'balance_after' => '0.00',
        ]);
    }

    public function test_admin_cannot_access_cashier_operations(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('cash-management.index'))
            ->assertForbidden();

        $this->get(route('payments.index'))->assertForbidden();
    }

    private function createInvoice(User $user, string $amount): Invoice
    {
        $student = Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => 'Information Technology',
            'year' => 'Year 1',
            'status' => 'Active',
        ]);
        $invoice = Invoice::create([
            'student_id' => $student->id,
            'issued_by' => $user->id,
            'invoice_number' => 'INV-'.str_replace('.', '', $amount),
            'invoice_type' => 'semester',
            'description' => 'Semester fees',
            'amount' => $amount,
            'currency' => 'UGX',
            'status' => 'issued',
        ]);
        FinancialLedger::create([
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'recorded_by' => $user->id,
            'debit' => $amount,
            'credit' => '0.00',
            'balance_after' => $amount,
            'description' => 'Invoice '.$invoice->invoice_number,
            'posted_at' => now(),
        ]);
        $student->update(['clearance_status' => 'uncleared']);

        return $invoice;
    }

    private function createVerifiedPayment(
        Invoice $invoice,
        User $cashier,
        string $amount,
        string $reference,
    ): Payment {
        return Payment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'recorded_by' => $cashier->id,
            'verified_by' => $cashier->id,
            'amount' => $amount,
            'currency' => 'UGX',
            'method' => 'bank_transfer',
            'reference' => $reference,
            'status' => 'verified',
            'paid_at' => now(),
            'verified_at' => now(),
        ]);
    }
}
