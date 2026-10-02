<?php
/**
 * RazorpayGatewayAdapter
 * Implements PaymentGatewayInterface for Razorpay.
 */

namespace App\Payments;

require_once __DIR__ . '/PaymentGatewayInterface.php';

class RazorpayGatewayAdapter implements PaymentGatewayInterface
{
    protected string $keyId;
    protected string $keySecret;
    protected string $webhookSecret;
    protected string $companyName;
    protected bool $enabled;
    protected string $env;
    protected string $baseUrl;

    public function __construct(array $config = [])
    {
        $this->keyId = trim((string)($config['key_id'] ?? (defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : '')));
        $this->keySecret = trim((string)($config['key_secret'] ?? (defined('RAZORPAY_KEY_SECRET') ? RAZORPAY_KEY_SECRET : '')));
        $this->webhookSecret = trim((string)($config['webhook_secret'] ?? (defined('RAZORPAY_WEBHOOK_SECRET') ? RAZORPAY_WEBHOOK_SECRET : $this->keySecret)));
        $this->companyName = trim((string)($config['company_name'] ?? (defined('RAZORPAY_COMPANY_NAME') ? RAZORPAY_COMPANY_NAME : 'NGO Foundation')));
        $this->enabled = !empty($config['enabled']);
        $this->env = str_starts_with($this->keyId, 'rzp_live_') ? 'production' : 'sandbox';
        
        $base = $config['app_base_url'] ?? '';
        if (!$base && function_exists('appBaseUrl')) {
            $base = appBaseUrl();
        }
        $this->baseUrl = rtrim((string)$base, '/');
    }

    public function getId(): string
    {
        return 'razorpay';
    }

    public function getName(): string
    {
        return 'Razorpay';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isConfigured(): bool
    {
        return !empty($this->keyId) && !empty($this->keySecret);
    }

    public function getEnvironment(): string
    {
        return $this->env;
    }

    public function getConfig(): array
    {
        return [
            'key_id'         => $this->keyId,
            'key_secret'     => $this->keySecret ? '********' : '',
            'webhook_secret' => $this->webhookSecret ? '********' : '',
            'company_name'   => $this->companyName,
            'enabled'        => $this->enabled,
            'env'            => $this->env,
            'webhook_url'    => $this->getWebhookUrl(),
        ];
    }

    public function getWebhookUrl(): string
    {
        return ($this->baseUrl !== '' ? $this->baseUrl : '') . '/process/razorpay_webhook.php';
    }

    public function createOrder(array $params): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'gateway' => $this->getId(),
                'error'   => 'Razorpay is not configured with Key ID and Secret.',
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
        $receipt = substr((string)($params['receipt'] ?? $params['order_id'] ?? ('order_' . time())), 0, 40);
        $currency = strtoupper((string)($params['currency'] ?? 'INR'));

        $notes = is_array($params['notes'] ?? null) ? $params['notes'] : [];
        if (!empty($params['purpose'])) $notes['purpose'] = substr((string)$params['purpose'], 0, 100);
        if (!empty($params['customer_name'])) $notes['customer_name'] = substr((string)$params['customer_name'], 0, 100);
        if (!empty($params['customer_email'])) $notes['customer_email'] = substr((string)$params['customer_email'], 0, 100);
        if (!empty($params['customer_phone'])) $notes['customer_phone'] = substr((string)$params['customer_phone'], 0, 30);

        $orderPayload = [
            'amount'   => $amountPaise,
            'currency' => $currency,
            'receipt'  => $receipt,
            'notes'    => $notes,
            'payment_capture' => 1,
        ];

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($orderPayload),
            CURLOPT_USERPWD        => $this->keyId . ':' . $this->keySecret,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
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
                'error'   => 'cURL error: ' . $curlError,
            ];
        }

        $data = json_decode((string)$response, true);
        if ($httpCode !== 200 || empty($data['id'])) {
            $msg = $data['error']['description'] ?? ('HTTP ' . $httpCode . ' from Razorpay');
            return [
                'success'      => false,
                'gateway'      => $this->getId(),
                'error'        => $msg,
                'raw_response' => $data,
            ];
        }

        return [
            'success'          => true,
            'gateway'          => $this->getId(),
            'order_id'         => $data['id'],
            'gateway_order_id' => $data['id'],
            'amount'           => $amount,
            'amount_paise'     => $amountPaise,
            'currency'         => $currency,
            'checkout_type'    => 'sdk',
            'key_id'           => $this->keyId,
            'company_name'     => $this->companyName,
            'raw_response'     => $data,
        ];
    }

    public function verifyPayment(array $payload): array
    {
        $paymentId = $payload['razorpay_payment_id'] ?? $payload['payment_id'] ?? '';
        $orderId   = $payload['razorpay_order_id']   ?? $payload['order_id']   ?? '';
        $signature = $payload['razorpay_signature']  ?? $payload['signature']  ?? '';

        if (empty($paymentId) || empty($orderId) || empty($signature)) {
            return [
                'success'    => false,
                'verified'   => false,
                'status'     => 'FAILED',
                'payment_id' => $paymentId,
                'order_id'   => $orderId,
                'amount'     => null,
                'message'    => 'Missing payment_id, order_id, or signature.',
                'raw_data'   => $payload,
            ];
        }

        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);

        if (!hash_equals($expectedSignature, $signature)) {
            return [
                'success'    => false,
                'verified'   => false,
                'status'     => 'FAILED',
                'payment_id' => $paymentId,
                'order_id'   => $orderId,
                'amount'     => null,
                'message'    => 'Razorpay signature mismatch.',
                'raw_data'   => $payload,
            ];
        }

        return [
            'success'    => true,
            'verified'   => true,
            'status'     => 'SUCCESS',
            'payment_id' => $paymentId,
            'order_id'   => $orderId,
            'amount'     => isset($payload['amount']) ? (float)$payload['amount'] : null,
            'message'    => 'Payment verified successfully.',
            'raw_data'   => $payload,
        ];
    }

    public function verifyWebhook(string $rawBody, array $headers): array
    {
        $signature = '';
        foreach ($headers as $name => $value) {
            if (strcasecmp((string)$name, 'X-Razorpay-Signature') === 0) {
                $signature = is_array($value) ? (string)($value[0] ?? '') : (string)$value;
                break;
            }
        }

        if ($signature === '' && !empty($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'])) {
            $signature = (string)$_SERVER['HTTP_X_RAZORPAY_SIGNATURE'];
        }

        $secretToUse = !empty($this->webhookSecret) ? $this->webhookSecret : $this->keySecret;
        $expectedSignature = hash_hmac('sha256', $rawBody, $secretToUse);

        if ($signature === '' || !hash_equals($expectedSignature, $signature)) {
            return [
                'verified'   => false,
                'event'      => 'unknown',
                'order_id'   => null,
                'payment_id' => null,
                'status'     => 'FAILED',
                'amount'     => null,
                'payload'    => [],
                'message'    => 'Invalid webhook signature.',
            ];
        }

        $payload = json_decode($rawBody, true) ?: [];
        $event = $payload['event'] ?? 'unknown';

        $payEntity = $payload['payload']['payment']['entity'] ?? null;
        $paymentId = is_array($payEntity) ? ($payEntity['id'] ?? null) : null;
        $orderId   = is_array($payEntity) ? ($payEntity['order_id'] ?? null) : null;
        if (!$orderId && !empty($payload['payload']['order']['entity']['id'])) {
            $orderId = $payload['payload']['order']['entity']['id'];
        }

        $amount = null;
        if (is_array($payEntity) && isset($payEntity['amount'])) {
            $amount = (float)$payEntity['amount'] / 100;
        }

        $status = 'PENDING';
        if (in_array($event, ['payment.captured', 'order.paid', 'subscription.charged'])) {
            $status = 'SUCCESS';
        } elseif (in_array($event, ['payment.failed', 'subscription.halted', 'subscription.cancelled'])) {
            $status = 'FAILED';
        }

        return [
            'verified'   => true,
            'event'      => $event,
            'order_id'   => $orderId,
            'payment_id' => $paymentId,
            'status'     => $status,
            'amount'     => $amount,
            'payload'    => $payload,
            'message'    => 'Razorpay webhook signature verified.',
        ];
    }
}
