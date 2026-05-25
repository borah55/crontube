<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal FaucetPay API client.
 * Docs: https://faucetpay.io/merchant/api
 *
 * All endpoints accept POST with form-urlencoded body.  We deliberately
 * keep this tiny and dependency-free so it works on plain shared hosting.
 */
final class FaucetPay
{
    private const BASE = 'https://faucetpay.io/api/v1/';

    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string)Setting::get('faucetpay_api_key', '');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /** Returns array of supported currency codes from FaucetPay. */
    public function currencies(): array
    {
        return $this->call('currencies', []);
    }

    /** Check the user's wallet balance with FaucetPay (the merchant balance). */
    public function balance(string $currency): array
    {
        return $this->call('balance', ['currency' => $currency]);
    }

    /** Verify whether a wallet address is registered on FaucetPay. */
    public function checkAddress(string $address, string $currency): array
    {
        return $this->call('checkaddress', [
            'address'  => $address,
            'currency' => $currency,
        ]);
    }

    /**
     * Send a payout. Amount is in main coin units (e.g. 0.001 LTC).
     * FaucetPay expects amount in satoshi via "amount" param when ip is set,
     * but the v1 API also accepts decimal "amount" with currency.
     */
    public function send(string $address, float $amount, string $currency, ?string $ip = null): array
    {
        $params = [
            'to'       => $address,
            'amount'   => number_format($amount, 8, '.', ''),
            'currency' => $currency,
        ];
        if ($ip) $params['ip_address'] = $ip;
        return $this->call('send', $params);
    }

    /** @param array<string,scalar> $params */
    private function call(string $endpoint, array $params): array
    {
        if ($this->apiKey === '') {
            return ['status' => 500, 'message' => 'FaucetPay API key not configured'];
        }
        $params['api_key'] = $this->apiKey;
        $body = http_build_query($params);

        if (function_exists('curl_init')) {
            $ch = curl_init(self::BASE . $endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_USERAGENT      => 'CryptoFaucet/1.0',
            ]);
            $resp = curl_exec($ch);
            $err  = curl_error($ch);
            curl_close($ch);
            if ($resp === false) {
                return ['status' => 500, 'message' => 'curl_error: ' . $err];
            }
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $body,
                    'timeout' => 15,
                ],
            ]);
            $resp = @file_get_contents(self::BASE . $endpoint, false, $ctx);
            if ($resp === false) {
                return ['status' => 500, 'message' => 'http_error'];
            }
        }
        $data = json_decode((string)$resp, true);
        return is_array($data) ? $data : ['status' => 500, 'message' => 'invalid_response'];
    }
}
