<?php
/**
 * PayUGatewayAdapter
 * Implements PaymentGatewayInterface for PayU Money / PayU India.
 */

namespace App\Payments;

require_once __DIR__ . '/PaymentGatewayInterface.php';

class PayUGatewayAdapter implements PaymentGatewayInterface
{
    protected string $merchantKey;
    protected string $merchantSalt;
    protected string $env;
    protected bool $enabled;
    protected string $baseUrl;

    const SANDBOX_PAY_URL = 'https://test.payu.in/_payment';
    const PROD_PAY_URL    = 'https://secure.payu.in/_payment';

    public function __construct(array $config = [])
    {
        $this->merchantKey  = trim((string)($config['merchant_key'] ?? (defined('PAYU_MERCHANT_KEY') ? PAYU_MERCHANT_KEY : '')));
        $this->merchantSalt = trim((string)($config['merchant_salt'] ?? (defined('PAYU_MERCHANT_SALT') ? PAYU_MERCHANT_SALT : '')));
        $this->env          = strtolower(trim((string)($config['env'] ?? (defined('PAYU_ENV') ? PAYU_ENV : 'sandbox')))) === 'production' ? 'production' : 'sandbox';
        $this->enabled      = !empty($config['enabled']);

        $base = $config['app_base_url'] ?? '';
        if (!$base && function_exists('appBaseUrl')) {
            $base = appBaseUrl();
        }
        $this->baseUrl = rtrim((string)$base, '/');
    }

    public function getId(): string
    {
        return 'payu';
    }

    public function getName(): string
    {
        return 'PayU Money';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isConfigured(): bool
    {
        return !empty($this->merchantKey) && !empty($this->merchantSalt);
    }

    public function getEnvironment(): string
    {
        return $this->env;
    }

    public function getConfig(): array
    {
        return [
            'merchant_key'  => $this->merchantKey,
            'merchant_salt' => $this->merchantSalt ? '********' : '',
            'env'           => $this->env,
            'enabled'       => $this->enabled,
            'webhook_url'   => $this->getWebhookUrl(),
        ];
    }

    public function getWebhookUrl(): string
    {
        return ($this->baseUrl !== '' ? $this->baseUrl : '') . '/process/payu_webhook.php';
    }

    public function getPayApiUrl(): string
    {
        return $this->env === 'production' ? self::PROD_PAY_URL : self::SANDBOX_PAY_URL;
    }

    /**
     * Compute outgoing SHA512 hash for PayU checkout form.
     * Official Sequence: key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10|SALT
     */
    public function generateOutgoingHash(
        string $txnid,
        float $amount,
        string $productinfo,
        string $firstname,
        string $email,
        string $udf1 = '',
        string $udf2 = '',
        string $udf3 = '',
        string $udf4 = '',
        string $udf5 = ''
    ): string {
        $amountFormatted = number_format($amount, 2, '.', '');
        $hashSequence = [
            $this->merchantKey,
            $txnid,
            $amountFormatted,
            $productinfo,
            $firstname,
            $email,
            $udf1,
            $udf2,
            $udf3,
            $udf4,
            $udf5,
            '', // udf6
            '', // udf7
            '', // udf8
            '', // udf9
            '', // udf10
            $this->merchantSalt
        ];

        return strtolower(hash('sha512', implode('|', $hashSequence)));
    }

    /**
     * Compute reverse SHA512 hash for PayU response / webhook verification.
     * Official Sequence: [additionalCharges|]SALT|status|udf10|udf9|udf8|udf7|udf6|udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key
     */
    public function generateReverseHash(
        string $status,
        string $txnid,
        float $amount,
        string $productinfo,
        string $firstname,
        string $email,
        string $udf1 = '',
        string $udf2 = '',
        string $udf3 = '',
        string $udf4 = '',
        string $udf5 = '',
        string $additionalCharges = ''
    ): string {
        $amountFormatted = number_format($amount, 2, '.', '');
        
        $reverseSequence = [
            $this->merchantSalt,
            $status,
            '', // udf10
            '', // udf9
            '', // udf8
            '', // udf7
            '', // udf6
            $udf5,
            $udf4,
            $udf3,
            $udf2,
            $udf1,
            $email,
            $firstname,
            $productinfo,
            $amountFormatted,
            $txnid,
            $this->merchantKey
        ];

        $hashString = implode('|', $reverseSequence);

        if (!empty($additionalCharges)) {
            $hashString = $additionalCharges . '|' . $hashString;
        }

        return strtolower(hash('sha512', $hashString));
    }

    public function createOrder(array $params): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'gateway' => $this->getId(),
                'error'   => 'PayU is not configured with Merchant Key and Salt.',
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

        $txnid = (string)($params['order_id'] ?? $params['txnid'] ?? ('PAYU_' . time() . '_' . rand(100, 999)));
        $productinfo = substr(trim((string)($params['purpose'] ?? 'Donation Payment')), 0, 100);
        $firstname = substr(trim((string)($params['customer_name'] ?? 'Donor')), 0, 50);
        $email = substr(trim((string)($params['customer_email'] ?? 'donor@example.com')), 0, 100);
        $phone = substr(preg_replace('/[^0-9]/', '', (string)($params['customer_phone'] ?? '')), -10);

        $udf1 = (string)($params['notes']['donation_id'] ?? $params['notes']['application_id'] ?? $params['notes']['module'] ?? '');
        $udf2 = (string)($params['notes']['purpose'] ?? '');
        $udf3 = (string)($params['notes']['reference'] ?? '');
        $udf4 = (string)($params['notes']['student_id'] ?? '');
        $udf5 = (string)($params['notes']['extra'] ?? '');

        $surl = (string)($params['redirect_url'] ?? $params['callback_url'] ?? ($this->baseUrl . '/process/verify_payu_payment.php'));
        $furl = (string)($params['cancel_url'] ?? ($this->baseUrl . '/process/verify_payu_payment.php'));

        $hash = $this->generateOutgoingHash($txnid, $amount, $productinfo, $firstname, $email, $udf1, $udf2, $udf3, $udf4, $udf5);

        $formData = [
            'key'         => $this->merchantKey,
            'txnid'       => $txnid,
            'amount'      => number_format($amount, 2, '.', ''),
            'productinfo' => $productinfo,
            'firstname'   => $firstname,
            'email'       => $email,
            'phone'       => $phone,
            'surl'        => $surl,
            'furl'        => $furl,
            'hash'        => $hash,
            'udf1'        => $udf1,
            'udf2'        => $udf2,
            'udf3'        => $udf3,
            'udf4'        => $udf4,
            'udf5'        => $udf5,
            'service_provider' => 'payu_paisa',
        ];

        return [
            'success'          => true,
            'gateway'          => $this->getId(),
            'order_id'         => $txnid,
            'gateway_order_id' => $txnid,
            'amount'           => $amount,
            'currency'         => 'INR',
            'checkout_type'    => 'form',
            'payment_url'      => $this->getPayApiUrl(),
            'form_data'        => $formData,
            'raw_response'     => $formData,
        ];
    }

    public function verifyPayment(array $payload): array
    {
        $status            = strtolower((string)($payload['status'] ?? ''));
        $txnid             = (string)($payload['txnid'] ?? '');
        $amount            = (float)($payload['amount'] ?? 0);
        $productinfo       = (string)($payload['productinfo'] ?? '');
        $firstname         = (string)($payload['firstname'] ?? '');
        $email             = (string)($payload['email'] ?? '');
        $receivedHash      = (string)($payload['hash'] ?? '');
        $mihpayid          = (string)($payload['mihpayid'] ?? $payload['payuMoneyId'] ?? $txnid);
        $additionalCharges = (string)($payload['additionalCharges'] ?? '');

        $udf1 = (string)($payload['udf1'] ?? '');
        $udf2 = (string)($payload['udf2'] ?? '');
        $udf3 = (string)($payload['udf3'] ?? '');
        $udf4 = (string)($payload['udf4'] ?? '');
        $udf5 = (string)($payload['udf5'] ?? '');

        if (empty($receivedHash) || empty($txnid) || empty($status)) {
            return [
                'success'    => false,
                'verified'   => false,
                'status'     => 'FAILED',
                'payment_id' => $mihpayid,
                'order_id'   => $txnid,
                'amount'     => $amount,
                'message'    => 'Missing PayU response parameters or hash.',
                'raw_data'   => $payload,
            ];
        }

        $expectedHash = $this->generateReverseHash(
            $payload['status'] ?? $status,
            $txnid,
            $amount,
            $productinfo,
            $firstname,
            $email,
            $udf1,
            $udf2,
            $udf3,
            $udf4,
            $udf5,
            $additionalCharges
        );

        $verified = hash_equals(strtolower($expectedHash), strtolower($receivedHash));

        if (!$verified) {
            return [
                'success'    => false,
                'verified'   => false,
                'status'     => 'FAILED',
                'payment_id' => $mihpayid,
                'order_id'   => $txnid,
                'amount'     => $amount,
                'message'    => 'PayU hash verification failed (security mismatch).',
                'raw_data'   => $payload,
            ];
        }

        $isSuccess = ($status === 'success' && $verified);
        $normalizedStatus = $isSuccess ? 'SUCCESS' : ($status === 'pending' ? 'PENDING' : 'FAILED');

        return [
            'success'    => $isSuccess,
            'verified'   => $verified,
            'status'     => $normalizedStatus,
            'payment_id' => $mihpayid,
            'order_id'   => $txnid,
            'amount'     => $amount,
            'message'    => $isSuccess ? 'PayU payment verified successfully.' : 'PayU payment status: ' . $status,
            'raw_data'   => $payload,
        ];
    }

    public function verifyWebhook(string $rawBody, array $headers): array
    {
        $payload = [];
        if (str_starts_with(trim($rawBody), '{')) {
            $payload = json_decode($rawBody, true) ?: [];
        } else {
            parse_str($rawBody, $payload);
        }

        if (empty($payload) && !empty($_POST)) {
            $payload = $_POST;
        }

        $verification = $this->verifyPayment($payload);

        return [
            'verified'   => $verification['verified'],
            'event'      => 'payu.payment.' . strtolower($verification['status']),
            'order_id'   => $verification['order_id'],
            'payment_id' => $verification['payment_id'],
            'status'     => $verification['status'],
            'amount'     => $verification['amount'],
            'payload'    => $payload,
            'message'    => $verification['message'],
        ];
    }
}
