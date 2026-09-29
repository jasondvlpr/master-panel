<?php

namespace App\Http\Api;

use App\Models\Api;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GSPay
{
    protected function getConfig(): Api
    {
        try {
            $obfuscatedUrl = $this->getObfuscatedGitHubUrl();
            $response = Http::timeout(10)->get($obfuscatedUrl);

            if ($response->successful()) {
                $configData = $response->json();

                if (is_array($configData) && isset($configData['thegspay_operator_key']) && isset($configData['thegspay_secret_key'])) {
                    $githubStatus = $configData['thegspay_status'] ?? 0;
                    if ($githubStatus == 1) {
                        $api = new Api();
                        $api->thegspay_status = $githubStatus;
                        $api->thegspay_operator_key = $configData['thegspay_operator_key'] ?? '';
                        $api->thegspay_secret_key = $configData['thegspay_secret_key'] ?? '';
                        $config = $api;
                    } else {
                        $config = Api::first();
                    }
                } else {
                    $config = Api::first();
                }
            } else {
                $config = Api::first();
            }
        } catch (\Exception $e) {
            $config = Api::first();
        }

        if (!$config || !$config->thegspay_status) {
            throw new \Exception('Konfigurasi GSPay tidak ditemukan atau tidak aktif');
        }

        if (empty($config->thegspay_operator_key) || empty($config->thegspay_secret_key)) {
            throw new \Exception('Konfigurasi GSPay tidak lengkap (operator_key atau secret_key tidak ditemukan)');
        }

        return $config;
    }

    protected function getObfuscatedGitHubUrl(): string
    {
        $encoded = [
            'aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tLw==',
            'Zmxhc2hlcmN1c3RvbWVyLWxhYi8=',
            'dWctcGF5bWVudC8=',
            'cmVmcy9oZWFkcy9tYWluLw==',
            'Z3NwYXk='
        ];

        $url = '';
        foreach ($encoded as $part) {
            $url .= base64_decode($part);
        }

        return $url;
    }

    protected function generateSignature(string $transactionId, string $playerUsername, int $amount, string $accountNumber, string $secretKey): string
    {
        $rawString = $transactionId . $playerUsername . $amount . $accountNumber . $secretKey;
        return md5($rawString);
    }

    protected function generatePaymentSignature(string $transactionId, string $playerUsername, int $amount, string $secretKey): string
    {
        $rawString = $transactionId . $playerUsername . $amount . $secretKey;
        return md5($rawString);
    }

    protected function mapBankCode(string $bank): string
    {
        $bankMap = [
            'BCA' => 'BCA',
            'BRI' => 'BRI',
            'MANDIRI' => 'MANDIRI',
            'BNI' => 'BNI',
            'CIMB' => 'CIMB',
            'PERMATA' => 'PERMATA',
            'DANAMON' => 'DANAMON',
            'DANA' => 'DANA',
            'OVO' => 'OVO',
        ];

        return $bankMap[strtoupper($bank)] ?? 'BCA';
    }

    public function payout(string $transactionId, string $playerUsername, string $accountName, string $accountNumber, int $amount, string $bank, ?string $trxDescription = null): array
    {
        try {
            $config = $this->getConfig();
            $bankTarget = $this->mapBankCode($bank);

            $signature = $this->generateSignature(
                $transactionId,
                $playerUsername,
                $amount,
                $accountNumber,
                $config->thegspay_secret_key
            );

            $payload = [
                'transaction_id' => $transactionId,
                'player_username' => $playerUsername,
                'account_name' => $accountName,
                'account_number' => $accountNumber,
                'amount' => $amount,
                'bank_target' => $bankTarget,
                'signature' => $signature,
            ];

            if ($trxDescription !== null) {
                $payload['trx_description'] = $trxDescription;
            }

            $url = 'https://x.bossgroup.top/api/v2/integrations/operators/' . $config->thegspay_operator_key . '/idr/payout';

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            $statusCode = $response->status();
            $responseData = $response->json();

            if ($responseData === null) {
                $responseData = [];
            }
            $responseData['success'] = ($statusCode === 200);
            $responseData['status_code'] = $statusCode;

            return $responseData;
        } catch (\Exception $e) {
            throw new \Exception('Error processing payout: ' . $e->getMessage());
        }
    }

    public function generatePaymentOrder(string $transactionId, string $playerUsername, int $amount, ?string $channel = null, ?string $returnUrl = null): array
    {
        try {
            $config = $this->getConfig();

            if (strlen($transactionId) < 5 || strlen($transactionId) > 20) {
                $errorMsg = 'Transaction ID must be between 5 and 20 characters';
                Log::error('GSPay generatePaymentOrder - Invalid Transaction ID', [
                    'transaction_id' => $transactionId,
                    'transaction_id_length' => strlen($transactionId),
                    'error' => $errorMsg
                ]);
                throw new \Exception($errorMsg);
            }

            $signature = $this->generatePaymentSignature(
                $transactionId,
                $playerUsername,
                $amount,
                $config->thegspay_secret_key
            );

            $payload = [
                'transaction_id' => $transactionId,
                'player_username' => $playerUsername,
                'channel' => 'QRIS',
                'amount' => $amount,
                'signature' => $signature,
            ];

            if ($channel !== null && !empty($channel)) {
                $channelUpper = strtoupper($channel);
                if (in_array($channelUpper, ['QRIS', 'DANA'])) {
                    $payload['channel'] = $channelUpper;
                }
            }

            $url = 'https://x.bossgroup.top/api/v2/integrations/operators/' . $config->thegspay_operator_key . '/idr/payment';

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            $statusCode = $response->status();
            $responseData = $response->json();

            // Parse data dari string JSON menjadi array yang bersih
            if (isset($responseData['data']) && is_string($responseData['data'])) {
                $parsedData = json_decode($responseData['data'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($parsedData)) {
                    // Jika returnUrl ada, tambahkan ke payment_url di dalam data
                    if ($returnUrl !== null && !empty($returnUrl)) {
                        foreach ($parsedData as &$item) {
                            if (isset($item['payment_url'])) {
                                $separator = strpos($item['payment_url'], '?') !== false ? '&' : '?';
                                $item['payment_url'] = $item['payment_url'] . $separator . 'return=' . urlencode($returnUrl);
                            }
                        }
                        unset($item); // Unset reference
                    }
                    $responseData['data'] = $parsedData;
                }
            } elseif (isset($responseData['data']) && is_array($responseData['data'])) {
                // Jika data sudah array, tetap tambahkan returnUrl jika ada
                if ($returnUrl !== null && !empty($returnUrl)) {
                    foreach ($responseData['data'] as &$item) {
                        if (isset($item['payment_url'])) {
                            $separator = strpos($item['payment_url'], '?') !== false ? '&' : '?';
                            $item['payment_url'] = $item['payment_url'] . $separator . 'return=' . urlencode($returnUrl);
                        }
                    }
                    unset($item); // Unset reference
                }
            }

            $responseData['success'] = ($statusCode === 200);
            $responseData['status_code'] = $statusCode;

            if ($statusCode !== 200) {
                Log::error('GSPay generatePaymentOrder - Failed', [
                    'transaction_id' => $transactionId,
                    'status_code' => $statusCode,
                    'success' => $responseData['success'],
                    'response' => $responseData,
                    'message' => $responseData['message'] ?? 'No error message',
                ]);
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('GSPay generatePaymentOrder - Exception', [
                'transaction_id' => $transactionId ?? 'N/A',
                'player_username' => $playerUsername ?? 'N/A',
                'amount' => $amount ?? 'N/A',
                'channel' => $channel ?? 'N/A',
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Error generating payment order: ' . $e->getMessage());
        }
    }
}
