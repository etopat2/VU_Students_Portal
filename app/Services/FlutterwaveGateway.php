<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Support\Money;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class FlutterwaveGateway
{
    /**
     * Start a hosted checkout and return Flutterwave's checkout URL.
     *
     * @throws RequestException
     */
    public function createCheckout(Payment $payment, Student $student, string $callbackUrl): string
    {
        $secretKey = config('services.flutterwave.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            throw new RuntimeException('Flutterwave is not configured. Add FLW_SECRET_KEY to the application environment.');
        }

        $user = $student->user;

        if (! $user || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Your student account needs a valid email address before you can pay online.');
        }

        $response = Http::baseUrl(rtrim((string) config('services.flutterwave.base_url'), '/'))
            ->withToken($secretKey)
            ->acceptJson()
            ->asJson()
            ->timeout(25)
            ->post('/payments', [
                'tx_ref' => $payment->reference,
                'amount' => Money::fromMinorUnits(Money::minorUnits((string) $payment->amount)),
                'currency' => $payment->currency,
                'redirect_url' => $callbackUrl,
                'payment_options' => 'card',
                'customer' => [
                    'email' => $user->email,
                    'name' => $student->name,
                ],
                'customizations' => [
                    'title' => config('app.name', 'VU Student Portal'),
                    'description' => 'Payment for invoice '.$payment->invoice->invoice_number,
                ],
                'meta' => [
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
                    'student_id' => $payment->student_id,
                ],
            ])
            ->throw();

        $link = $response->json('data.link');

        $host = is_string($link) ? strtolower((string) parse_url($link, PHP_URL_HOST)) : '';
        $isFlutterwaveHost = $host === 'flutterwave.com' || str_ends_with($host, '.flutterwave.com');

        if (! is_string($link)
            || ! filter_var($link, FILTER_VALIDATE_URL)
            || parse_url($link, PHP_URL_SCHEME) !== 'https'
            || ! $isFlutterwaveHost) {
            throw new RuntimeException('Flutterwave did not return a valid checkout link. Please try again.');
        }

        return $link;
    }

    /**
     * Initiate a Uganda mobile money charge.
     *
     * @return array{transaction_id: string|null, authorization_url: string|null}
     */
    public function initiateUgandaMobileMoney(
        Payment $payment,
        Student $student,
        string $phoneNumber,
        string $network,
        string $callbackUrl,
    ): array {
        $secretKey = config('services.flutterwave.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            throw new RuntimeException('Flutterwave is not configured. Add FLW_SECRET_KEY to the application environment.');
        }

        $user = $student->user;

        if (! $user || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Your student account needs a valid email address before you can pay online.');
        }

        $response = Http::baseUrl(rtrim((string) config('services.flutterwave.base_url'), '/'))
            ->withToken($secretKey)
            ->acceptJson()
            ->asJson()
            ->timeout(25)
            ->post('/charges?type=mobile_money_uganda', [
                'amount' => intdiv(Money::minorUnits((string) $payment->amount), 100),
                'currency' => $payment->currency,
                'email' => $user->email,
                'fullname' => $student->name,
                'phone_number' => $phoneNumber,
                'network' => $network,
                'tx_ref' => $payment->reference,
                'redirect_url' => $callbackUrl,
                'meta' => [
                    'payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
                    'student_id' => $payment->student_id,
                ],
            ])
            ->throw();

        if ($response->json('status') !== 'success') {
            throw new RuntimeException('Flutterwave could not initiate the mobile money payment. Please check the number and try again.');
        }

        $transactionId = $response->json('data.id');
        $authorizationUrl = $response->json('meta.authorization.redirect');

        $authorizationHost = is_string($authorizationUrl)
            ? strtolower((string) parse_url($authorizationUrl, PHP_URL_HOST))
            : '';
        $isFlutterwaveAuthorization = $authorizationHost === 'flutterwave.com'
            || str_ends_with($authorizationHost, '.flutterwave.com')
            || $authorizationHost === 'ravemodal-dev.herokuapp.com';

        return [
            'transaction_id' => is_string($transactionId) || is_int($transactionId) ? (string) $transactionId : null,
            'authorization_url' => is_string($authorizationUrl)
                && filter_var($authorizationUrl, FILTER_VALIDATE_URL)
                && parse_url($authorizationUrl, PHP_URL_SCHEME) === 'https'
                && $isFlutterwaveAuthorization
                ? $authorizationUrl
                : null,
        ];
    }

    /** @throws RequestException */
    public function verifyTransaction(string|int $transactionId): array
    {
        $secretKey = config('services.flutterwave.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            throw new RuntimeException('Flutterwave is not configured.');
        }

        $response = Http::baseUrl(rtrim((string) config('services.flutterwave.base_url'), '/'))
            ->withToken($secretKey)
            ->acceptJson()
            ->timeout(20)
            ->get('/transactions/'.rawurlencode((string) $transactionId).'/verify')
            ->throw();

        $transaction = $response->json('data');

        if (! is_array($transaction)) {
            throw new RuntimeException('Flutterwave returned an invalid transaction verification response.');
        }

        return $transaction;
    }

    public function newReference(): string
    {
        return 'VU-'.Str::upper((string) Str::ulid());
    }

    public function hasValidWebhookSignature(string $rawBody, ?string $signature, ?string $legacyHash): bool
    {
        $secretHash = config('services.flutterwave.secret_hash');

        if (! is_string($secretHash) || $secretHash === '') {
            return false;
        }

        if (is_string($signature) && $signature !== '') {
            $expected = base64_encode(hash_hmac('sha256', $rawBody, $secretHash, true));

            return hash_equals($expected, $signature);
        }

        return is_string($legacyHash) && $legacyHash !== '' && hash_equals($secretHash, $legacyHash);
    }
}
