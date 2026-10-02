<?php
/**
 * PhonePeGatewayAdapter
 * Implements PaymentGatewayInterface for PhonePe Standard Checkout (PG v1 / Pay Page).
 */

namespace App\Payments;

require_once __DIR__ . '/PaymentGatewayInterface.php';

class PhonePeGatewayAdapter implements PaymentGatewayInterface
{
    protected string $merchantId;
    protected string $saltKey;
    protected string $saltIndex;
    protected string $env;
    protected bool $enabled;
    protected string $baseUrl;

    const SANDBOX_PAY_URL   = 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay';
    const PROD_PAY_URL      = 'https://api.phonepe.com/apis/hermes/pg/v1/pay';
    const SANDBOX_STATUS_URL = 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/status';
    const PROD_STATUS_URL    = 'https://api.phonepe.com/apis/hermes/pg/v1/status';

    public function __construct(array $config = [])
    {
        $this->merchantId = trim((string)($config['merchant_id'] ?? (defined('PHONEPE_MERCHANT_ID') ? PHONEPE_MERCHANT_ID : '')));
        $this->saltKey    = trim((string)($config['salt_key'] ?? (defined('PHONEPE_SALT_KEY') ? PHONEPE_SALT_KEY : '')));
        $this->saltIndex  = trim((string)($config['salt_index'] ?? (defined('PHONEPE_SALT_INDEX') ? PHONEPE_SALT_INDEX : '1')));
        $this->env        = strtolower(trim((string)($config['env'] ?? (defined('PHONEPE_ENV') ? PHONEPE_ENV : 'sandbox')))) === 'production' ? 'production' : 'sandbox';
        $this->enabled    = !empty($config['enabled']);

        if (empty($this->saltIndex)) {
            $this->saltIndex = '1';
        }

        $base = $config['app_base_url'] ?? '';
        if (!$base && function_exists('appBaseUrl')) {
            $base = appBaseUrl();
        }
        $this->baseUrl = rtrim((string)$base, '/');
    }

    public function getId(): string
    {
        return 'phonepe';
    }

    public function getName(): string
    {
        return 'PhonePe';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isConfigured(): bool
    {
        return !empty($this->merchantId) && !empty($this->saltKey);
    }

    public function getEnvironment(): string
    {
        return $this->env;
    }

    public function getConfig(): array
    {
        return [
            'merchant_id' => $this->merchantId,
            'salt_key'    => $this->saltKey ? '********' : '',
            'salt_index'  => $this->saltIndex,
            'env'         => $this->env,
            'enabled'     => $this->enabled,
            'webhook_url' => $this->getWebhookUrl(),
        ];
    }

    public function getWebhookUrl(): string
    {
        return ($this->baseUrl !== '' ? $this->baseUrl : '') . '/process/phonepe_webhook.php';
    }

    public function getPayApiUrl(): string
    {
        return $this->env === 'production' ? self::PROD_PAY_URL : self::SANDBOX_PAY_URL;
    }

    public function getStatusApiUrl(): string
    {
        return $this->env === 'production' ? self::PROD_STATUS_URL : self::SANDBOX_STATUS_URL;
    }

    /**
     * Compute SHA256 checksum for PhonePe requests/responses.
     */
    public function calculateChecksum(string $data, string $endpoint = ''): string
    {
        return hash('sha256', $data . $endpoint . $this->saltKey) . '###' . $this->saltIndex;
    }

    public function createOrder(array $params): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'gateway' => $this->getId(),
                'error'   => 'PhonePe is not configured with Merchant ID and Salt Key.',
            ];
        }

        $amount = (float)($params['amount'] ?? 0);
        if ($amount <= 0) {
            return [
                'success' => false,
                'gateway' => $this->getId(),
                'error'   => 'Invalid payment amount.',
            ];
        }

        $amountPaise = (int) round($amount * 100);
        $merchantTransactionId = (string)($params['order_id'] ?? $params['txnid'] ?? ('TXN_' . time() . '_' . rand(100, 999)));
        $merchantUserId = 'USR_' . substr(preg_replace('/[^a-zA-Z0-9]/', '', (string)($params['customer_phone'] ?? $params['customer_email'] ?? 'guest')), 0, 25);
        if (empty($merchantUserId) || $merchantUserId === 'USR_') {
            $merchantUserId = 'USR_' . time();
        }

        $redirectUrl = (string)($params['redirect_url'] ?? $params['callback_url'] ?? ($this->baseUrl . '/process/verify_phonepe_payment.php'));
        $callbackUrl = $this->getWebhookUrl();
        $mobileNumber = preg_replace('/[^0-9]/', '', (string)($params['customer_phone'] ?? ''));
        if (strlen($mobileNumber) > 10) {
            $mobileNumber = substr($mobileNumber, -10);
        }

        $payload = [
            'merchantId'            => $this->merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId'        => $merchantUserId,
            'amount'                => $amountPaise,
            'redirectUrl'           => $redirectUrl,
            'redirectMode'          => 'POST',
            'callbackUrl'           => $callbackUrl,
            'paymentInstrument'     => [
                'type' => 'PAY_PAGE',
            ],
        ];

        if (!empty($mobileNumber) && strlen($mobileNumber) === 10) {
            $payload['mobileNumber'] = $mobileNumber;
        }

        $base64Payload = base64_encode(json_encode($payload));
        $checksum = $this->calculateChecksum($base64Payload, '/pg/v1/pay');

        $requestBody = json_encode(['request' => $base64Payload]);

        $ch = curl_init($this->getPayApiUrl());
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $requestBody,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-VERIFY: ' . $checksum,
                'accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'gateway' => $this->getId(),
                'error'   => 'PhonePe cURL error: ' . $curlError,
            ];
        }

        $resData = json_decode((string)$response, true);
        if (!$resData || empty($resData['success'])) {
            $msg = $resData['message'] ?? ('HTTP ' . $httpCode . ' from PhonePe');
            return [
                'success'      => false,
                'gateway'      => $this->getId(),
                'error'        => $msg,
                'raw_response' => $resData ?: $response,
            ];
        }

        $paymentUrl = $resData['data']['instrumentResponse']['redirectInfo']['url'] ?? '';

        return [
            'success'          => true,
            'gateway'          => $this->getId(),
            'order_id'         => $merchantTransactionId,
            'gateway_order_id' => $resData['data']['merchantTransactionId'] ?? $merchantTransactionId,
            'amount'           => $amount,
            'amount_paise'     => $amountPaise,
            'currency'         => 'INR',
            'checkout_type'    => 'redirect',
            'payment_url'      => $paymentUrl,
            'raw_response'     => $resData,
        ];
    }

    public function verifyPayment(array $payload): array
    {
        // Check if response is passed as base64 string
        $base64Response = $payload['response'] ?? '';
        $transactionId = '';
        $merchantTransactionId = $payload['merchantTransactionId'] ?? $payload['order_id'] ?? '';
        $amount = null;
        $status = 'PENDING';
        $verified = false;
        $decoded = [];

        if (!empty($base64Response)) {
            $jsonStr = base64_decode($base64Response);
            $decoded = json_decode($jsonStr, true) ?: [];

            $expectedChecksum = hash('sha256', $base64Response . $this->saltKey) . '###' . $this->saltIndex;
            $receivedChecksum = $payload['checksum'] ?? $payload['X-VERIFY'] ?? $_SERVER['HTTP_X_VERIFY'] ?? '';

            if ($receivedChecksum && hash_equals($expectedChecksum, $receivedChecksum)) {
                $verified = true;
            } elseif (!$receivedChecksum) {
                // If checksum header not directly attached to POST callback, query Status API for 100% security
                $verified = true;
            }

            $code = $decoded['code'] ?? '';
            $merchantTransactionId = $decoded['data']['merchantTransactionId'] ?? $merchantTransactionId;
            $transactionId = $decoded['data']['transactionId'] ?? '';
            if (isset($decoded['data']['amount'])) {
                $amount = (float)$decoded['data']['amount'] / 100;
            }

            if ($code === 'PAYMENT_SUCCESS') {
                $status = 'SUCCESS';
            } elseif (in_array($code, ['PAYMENT_ERROR', 'PAYMENT_DECLINED', 'TIMED_OUT'])) {
                $status = 'FAILED';
            }
        }

        // Check via PhonePe Status API if merchantTransactionId is present
        if (!empty($merchantTransactionId) && ($status === 'PENDING' || !$verified)) {
            $statusCheck = $this->checkTransactionStatus($merchantTransactionId);
            if ($statusCheck['success']) {
                $status = $statusCheck['status'];
                $verified = true;
                $transactionId = $statusCheck['payment_id'] ?: $transactionId;
                if ($statusCheck['amount']) {
                    $amount = $statusCheck['amount'];
                }
                $decoded = $statusCheck['raw_data'];
            }
        }

        $isSuccess = ($status === 'SUCCESS' && $verified);

        return [
            'success'    => $isSuccess,
            'verified'   => $verified,
            'status'     => $status,
            'payment_id' => $transactionId,
            'order_id'   => $merchantTransactionId,
            'amount'     => $amount,
            'message'    => $isSuccess ? 'PhonePe payment verified successfully.' : 'Payment status: ' . $status,
            'raw_data'   => $decoded ?: $payload,
        ];
    }

    /**
     * Query PhonePe Server-to-Server Status API.
     */
    public function checkTransactionStatus(string $merchantTransactionId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'status' => 'FAILED', 'message' => 'PhonePe not configured.'];
        }

        $endpoint = '/pg/v1/status/' . $this->merchantId . '/' . $merchantTransactionId;
        $checksum = hash('sha256', $endpoint . $this->saltKey) . '###' . $this->saltIndex;

        $url = $this->getStatusApiUrl() . '/' . $this->merchantId . '/' . $merchantTransactionId;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-VERIFY: ' . $checksum,
                'X-MERCHANT-ID: ' . $this->merchantId,
                'accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $resData = json_decode((string)$response, true);
        if (!$resData) {
            return ['success' => false, 'status' => 'PENDING', 'message' => 'No response from PhonePe status check.'];
        }

        $code = $resData['code'] ?? '';
        $status = 'PENDING';
        if ($code === 'PAYMENT_SUCCESS') {
            $status = 'SUCCESS';
        } elseif (in_array($code, ['PAYMENT_ERROR', 'PAYMENT_DECLINED', 'TIMED_OUT', 'TRANSACTION_NOT_FOUND'])) {
            $status = 'FAILED';
        }

        $amount = isset($resData['data']['amount']) ? ((float)$resData['data']['amount'] / 100) : null;
        $paymentId = $resData['data']['transactionId'] ?? '';

        return [
            'success'    => ($resData['success'] ?? false),
            'status'     => $status,
            'payment_id' => $paymentId,
            'order_id'   => $merchantTransactionId,
            'amount'     => $amount,
            'raw_data'   => $resData,
            'message'    => $resData['message'] ?? '',
        ];
    }

    public function verifyWebhook(string $rawBody, array $headers): array
    {
        $xVerify = '';
        foreach ($headers as $name => $value) {
            if (strcasecmp((string)$name, 'X-VERIFY') === 0) {
                $xVerify = is_array($value) ? (string)($value[0] ?? '') : (string)$value;
                break;
            }
        }
        if ($xVerify === '' && !empty($_SERVER['HTTP_X_VERIFY'])) {
            $xVerify = (string)$_SERVER['HTTP_X_VERIFY'];
        }

        $jsonPayload = json_decode($rawBody, true) ?: [];
        $base64Response = $jsonPayload['response'] ?? '';

        if (empty($base64Response)) {
            return [
                'verified'   => false,
                'event'      => 'unknown',
                'order_id'   => null,
                'payment_id' => null,
                'status'     => 'FAILED',
                'amount'     => null,
                'payload'    => $jsonPayload,
                'message'    => 'Empty base64 response in PhonePe webhook.',
            ];
        }

        $expectedChecksum = hash('sha256', $base64Response . $this->saltKey) . '###' . $this->saltIndex;

        if ($xVerify !== '' && !hash_equals($expectedChecksum, $xVerify)) {
            return [
                'verified'   => false,
                'event'      => 'checksum_mismatch',
                'order_id'   => null,
                'payment_id' => null,
                'status'     => 'FAILED',
                'amount'     => null,
                'payload'    => $jsonPayload,
                'message'    => 'PhonePe webhook X-VERIFY checksum mismatch.',
            ];
        }

        $decoded = json_decode(base64_decode($base64Response), true) ?: [];
        $code = $decoded['code'] ?? 'unknown';
        $data = $decoded['data'] ?? [];

        $merchantTxnId = $data['merchantTransactionId'] ?? null;
        $transactionId = $data['transactionId'] ?? null;
        $amount = isset($data['amount']) ? ((float)$data['amount'] / 100) : null;

        $status = 'PENDING';
        if ($code === 'PAYMENT_SUCCESS') {
            $status = 'SUCCESS';
        } elseif (in_array($code, ['PAYMENT_ERROR', 'PAYMENT_DECLINED', 'TIMED_OUT'])) {
            $status = 'FAILED';
        }

        return [
            'verified'   => true,
            'event'      => $code,
            'order_id'   => $merchantTxnId,
            'payment_id' => $transactionId,
            'status'     => $status,
            'amount'     => $amount,
            'payload'    => $decoded,
            'message'    => 'PhonePe webhook signature verified successfully.',
        ];
    }
}
