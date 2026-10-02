<?php
/**
 * PaymentGatewayInterface
 * Unified contract for all payment gateway adapters in the application.
 */

namespace App\Payments;

interface PaymentGatewayInterface
{
    /**
     * Unique gateway identifier (e.g., 'razorpay', 'phonepe', 'payu').
     */
    public function getId(): string;

    /**
     * Human-readable gateway display name (e.g., 'Razorpay', 'PhonePe', 'PayU Money').
     */
    public function getName(): string;

    /**
     * Whether this gateway is enabled in admin settings.
     */
    public function isEnabled(): bool;

    /**
     * Whether required credentials/keys are populated.
     */
    public function isConfigured(): bool;

    /**
     * Gateway environment ('sandbox', 'test', or 'production').
     */
    public function getEnvironment(): string;

    /**
     * Return gateway configuration parameters.
     */
    public function getConfig(): array;

    /**
     * Create an order / transaction on the gateway.
     *
     * @param array $params [
     *   'amount'          => (float) In INR, e.g. 500.00
     *   'order_id'        => (string) Internal order / transaction ID
     *   'receipt'         => (string) Optional receipt / reference ID
     *   'customer_name'   => (string)
     *   'customer_email'  => (string)
     *   'customer_phone'  => (string)
     *   'purpose'         => (string) E.g. 'Donation', 'Membership', 'Join Application'
     *   'notes'           => (array) Key-value metadata
     *   'callback_url'    => (string) Redirect / callback URL after payment
     *   'redirect_url'    => (string) SURL / Return URL
     *   'cancel_url'      => (string) FURL / Cancel URL
     * ]
     * @return array [
     *   'success'          => (bool)
     *   'gateway'          => (string) 'razorpay' | 'phonepe' | 'payu'
     *   'order_id'         => (string) Gateway order ID or merchant reference
     *   'gateway_order_id' => (string)
     *   'amount'           => (float)
     *   'currency'         => (string) 'INR'
     *   'checkout_type'    => (string) 'sdk' | 'redirect' | 'form'
     *   'payment_url'      => (string|null) For redirect gateways like PhonePe
     *   'form_data'        => (array|null) For form POST gateways like PayU
     *   'key_id'           => (string|null) For JS SDK gateways like Razorpay
     *   'raw_response'     => (mixed)
     *   'error'            => (string|null)
     * ]
     */
    public function createOrder(array $params): array;

    /**
     * Verify payment status from a client callback / redirect or AJAX payload.
     *
     * @param array $payload Incoming request parameters ($_POST, $_GET, or JSON payload)
     * @return array [
     *   'success'    => (bool) Overall verification outcome
     *   'verified'   => (bool) Cryptographic signature / hash valid
     *   'status'     => (string) 'SUCCESS' | 'FAILED' | 'PENDING'
     *   'payment_id' => (string|null) Gateway payment ID
     *   'order_id'   => (string|null) Merchant or Gateway Order ID
     *   'amount'     => (float|null)
     *   'message'    => (string)
     *   'raw_data'   => (array)
     * ]
     */
    public function verifyPayment(array $payload): array;

    /**
     * Verify and process incoming server-to-server webhook / IPN notification.
     *
     * @param string $rawBody Raw HTTP request body (php://input)
     * @param array $headers HTTP request headers
     * @return array [
     *   'verified'    => (bool) Webhook signature / hash valid
     *   'event'       => (string) Normalized event (e.g., 'payment.captured', 'PAYMENT_SUCCESS')
     *   'order_id'    => (string|null)
     *   'payment_id'  => (string|null)
     *   'status'      => (string) 'SUCCESS' | 'FAILED' | 'PENDING'
     *   'amount'      => (float|null)
     *   'payload'     => (array) Parsed JSON / POST data
     *   'message'     => (string)
     * ]
     */
    public function verifyWebhook(string $rawBody, array $headers): array;

    /**
     * Get the public server-to-server webhook endpoint URL for this gateway.
     */
    public function getWebhookUrl(): string;
}
