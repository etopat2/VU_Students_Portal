<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Services\FlutterwaveGateway;
use App\Services\OnlinePaymentSettlement;
use App\Support\Money;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StudentPaymentController extends Controller
{
    public function checkout(Request $request, FlutterwaveGateway $gateway): JsonResponse
    {
        $student = $request->user()->student()->firstOrFail();
        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,0'],
            'method' => ['required', Rule::in(['mobile_money', 'card'])],
            'network' => ['nullable', Rule::requiredIf($request->input('method') === 'mobile_money'), Rule::in(['MTN', 'AIRTEL'])],
            'phone_number' => [
                'nullable',
                Rule::requiredIf($request->input('method') === 'mobile_money'),
                'regex:/^(?:\\+?256|0)?7[0-9]{8}$/',
            ],
        ]);

        $invoice = Invoice::query()
            ->whereKey($validated['invoice_id'])
            ->where('student_id', $student->id)
            ->firstOrFail();

        try {
            $payment = DB::transaction(function () use ($invoice, $student, $gateway, $validated): Payment {
                $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
                $amount = Money::minorUnits((string) $validated['amount']);
                $outstanding = $lockedInvoice->outstandingAmountInMinorUnits();

                if (in_array($lockedInvoice->status, ['paid', 'void'], true) || $amount > $outstanding) {
                    throw ValidationException::withMessages([
                        'amount' => 'Enter an amount within the invoice balance.',
                    ]);
                }

                if (strtoupper($lockedInvoice->currency) !== 'UGX') {
                    throw ValidationException::withMessages([
                        'invoice_id' => 'Online checkout currently supports UGX invoices only.',
                    ]);
                }

                $activePayment = $lockedInvoice->payments()
                    ->where('gateway', 'flutterwave')
                    ->where('status', 'pending')
                    ->where('created_at', '>', now()->subMinutes(45))
                    ->exists();

                if ($activePayment) {
                    throw ValidationException::withMessages([
                        'invoice_id' => 'A payment for this invoice is already processing. Wait for its status before starting another payment.',
                    ]);
                }

                return Payment::create([
                    'invoice_id' => $lockedInvoice->id,
                    'student_id' => $student->id,
                    'amount' => Money::fromMinorUnits($amount),
                    'currency' => $lockedInvoice->currency,
                    'method' => $validated['method'] === 'card' ? 'card' : 'mobile_money',
                    'reference' => $gateway->newReference(),
                    'gateway' => 'flutterwave',
                    'status' => 'pending',
                ]);
            });

            $payment->load('invoice');

            if ($validated['method'] === 'card') {
                return response()->json([
                    'status' => 'redirect',
                    'checkout_url' => $gateway->createCheckout(
                        $payment,
                        $student,
                        route('payments.flutterwave.callback'),
                    ),
                    'payment_ref' => $payment->reference,
                ]);
            }

            $charge = $gateway->initiateUgandaMobileMoney(
                $payment,
                $student,
                $validated['phone_number'],
                $validated['network'],
                route('payments.flutterwave.callback'),
            );

            if ($charge['transaction_id']) {
                $payment->update(['gateway_transaction_id' => $charge['transaction_id']]);
            }

            return response()->json([
                'status' => 'pending',
                'payment_ref' => $payment->reference,
                'authorization_url' => $charge['authorization_url'],
                'message' => 'Approve the payment prompt on your phone. We are waiting for Flutterwave to confirm it.',
            ]);
        } catch (RequestException|RuntimeException $exception) {
            $definitiveFailure = ! ($exception instanceof RequestException)
                || $exception->response->clientError();

            if (isset($payment) && $definitiveFailure) {
                $payment->update(['status' => 'failed']);
                Log::warning('Flutterwave checkout could not be started.', [
                    'payment_id' => $payment->id,
                    'exception' => $exception::class,
                ]);
            }

            return response()->json([
                'status' => $definitiveFailure ? 'failed' : 'pending',
                'payment_ref' => $payment->reference ?? null,
                'message' => $definitiveFailure
                    ? ($exception instanceof RequestException
                        ? 'Flutterwave could not start the payment. Please check your details and try again.'
                        : $exception->getMessage())
                    : 'Flutterwave is taking longer to respond. We are checking your payment status.',
            ], $definitiveFailure ? 422 : 202);
        }
    }

    public function status(
        Request $request,
        string $reference,
        FlutterwaveGateway $gateway,
        OnlinePaymentSettlement $settlement,
    ): JsonResponse {
        $student = $request->user()->student()->firstOrFail();
        $payment = Payment::query()
            ->where('reference', $reference)
            ->where('gateway', 'flutterwave')
            ->where('student_id', $student->id)
            ->firstOrFail();

        if ($payment->status === 'verified') {
            return response()->json(['status' => 'success', 'message' => 'Payment confirmed and posted to your student account.']);
        }

        if ($payment->status === 'failed') {
            return response()->json(['status' => 'failed', 'message' => 'The payment was not completed. You can close this window and try again.']);
        }

        if ($payment->gateway_transaction_id) {
            try {
                $transaction = $gateway->verifyTransaction($payment->gateway_transaction_id);
                $settled = $settlement->settle($payment, $transaction);

                if ($settled) {
                    return response()->json(['status' => 'success', 'message' => 'Payment confirmed and posted to your student account.']);
                }

                if (in_array($transaction['status'] ?? null, ['failed', 'cancelled'], true)) {
                    $payment->update(['status' => 'failed']);

                    return response()->json(['status' => 'failed', 'message' => 'The payment was not completed. You can close this window and try again.']);
                }
            } catch (RequestException|RuntimeException|ValidationException $exception) {
                Log::info('Flutterwave payment is still awaiting confirmation.', [
                    'payment_id' => $payment->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return response()->json([
            'status' => 'pending',
            'message' => 'Waiting for payment confirmation. Keep this window open.',
        ]);
    }

    public function callback(
        Request $request,
        FlutterwaveGateway $gateway,
        OnlinePaymentSettlement $settlement,
    ): RedirectResponse {
        $reference = $request->query('tx_ref');
        abort_unless(is_string($reference) && $reference !== '', 404);

        $payment = Payment::query()
            ->where('reference', $reference)
            ->where('gateway', 'flutterwave')
            ->firstOrFail();
        $transactionId = $request->query('transaction_id');
        $message = 'Your payment is still being confirmed.';

        if (is_string($transactionId) || is_int($transactionId)) {
            try {
                $payment->update(['gateway_transaction_id' => (string) $transactionId]);
                $transaction = $gateway->verifyTransaction($transactionId);
                $settled = $settlement->settle($payment, $transaction);
                $message = $settled
                    ? 'Payment confirmed and posted to your student account.'
                    : 'Your payment is still being confirmed.';

                if (! $settled && in_array($transaction['status'] ?? null, ['failed', 'cancelled'], true)) {
                    $payment->update(['status' => 'failed']);
                    $message = 'The payment was not completed.';
                }
            } catch (RequestException|RuntimeException|ValidationException $exception) {
                Log::warning('Flutterwave callback verification did not complete.', [
                    'payment_id' => $payment->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return to_route('financial-statements.mine')
            ->with('payment_ref', $payment->reference)
            ->with('payment_status', $message);
    }

    public function webhook(
        Request $request,
        FlutterwaveGateway $gateway,
        OnlinePaymentSettlement $settlement,
    ): Response {
        abort_unless($gateway->hasValidWebhookSignature(
            $request->getContent(),
            $request->header('flutterwave-signature'),
            $request->header('verif-hash'),
        ), 401);

        $data = $request->input('data', []);
        $reference = is_array($data) ? ($data['tx_ref'] ?? null) : null;
        $transactionId = is_array($data) ? ($data['id'] ?? null) : null;

        if (! is_string($reference) || (! is_string($transactionId) && ! is_int($transactionId))) {
            return response('Acknowledged', 200);
        }

        $payment = Payment::query()
            ->where('reference', $reference)
            ->where('gateway', 'flutterwave')
            ->first();

        if (! $payment) {
            return response('Acknowledged', 200);
        }

        $payment->update(['gateway_transaction_id' => (string) $transactionId]);
        $transaction = $gateway->verifyTransaction($transactionId);
        $settled = $settlement->settle($payment, $transaction);

        if (! $settled && in_array($transaction['status'] ?? null, ['failed', 'cancelled'], true)) {
            Payment::query()->whereKey($payment->id)->where('status', 'pending')->update(['status' => 'failed']);
        }

        return response('Acknowledged', 200);
    }
}
