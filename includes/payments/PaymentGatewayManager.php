<?php
/**
 * PaymentGatewayManager
 * Central Factory and Registry for Payment Gateways.
 */

namespace App\Payments;

require_once __DIR__ . '/PaymentGatewayInterface.php';
require_once __DIR__ . '/RazorpayGatewayAdapter.php';
require_once __DIR__ . '/PhonePeGatewayAdapter.php';
require_once __DIR__ . '/PayUGatewayAdapter.php';

use PDO;
use Throwable;

class PaymentGatewayManager
{
    protected ?PDO $pdo;
    protected array $settings = [];
    protected array $adapters = [];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
        $this->loadSettings();
    }

    /**
     * Load settings from database or fallback to constants.
     */
    protected function loadSettings(): void
    {
        if ($this->pdo instanceof PDO) {
            try {
                $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'razorpay_%' OR setting_key LIKE 'phonepe_%' OR setting_key LIKE 'payu_%' OR setting_key LIKE 'active_payment_gateway%' OR setting_key = 'site_name' OR setting_key LIKE 'upi_%'");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->settings[$row['setting_key']] = $row['setting_value'];
                }
            } catch (Throwable $e) {
                // Keep default empty settings if DB error occurs
            }
        }
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Return ID of the default active payment gateway.
     * Possible values: 'razorpay', 'phonepe', 'payu', 'manual'.
     */
    public function getActiveGatewayId(string $scope = 'donation'): string
    {
        $active = trim((string)($this->settings['active_payment_gateway'] ?? ''));
        if (empty($active)) {
            $active = 'razorpay';
        }
        return strtolower($active);
    }

    /**
     * Get the currently active payment gateway adapter.
     */
    public function getActiveGateway(string $scope = 'donation'): ?PaymentGatewayInterface
    {
        $activeId = $this->getActiveGatewayId($scope);
        return $this->getGateway($activeId, $scope);
    }

    /**
     * Get a specific gateway adapter by ID.
     */
    public function getGateway(string $id, string $scope = 'donation'): ?PaymentGatewayInterface
    {
        $id = strtolower(trim($id));
        $cacheKey = $id . '_' . $scope;

        if (isset($this->adapters[$cacheKey])) {
            return $this->adapters[$cacheKey];
        }

        $adapter = match ($id) {
            'razorpay' => $this->createRazorpayAdapter($scope),
            'phonepe'  => $this->createPhonePeAdapter($scope),
            'payu'     => $this->createPayUAdapter($scope),
            default    => null,
        };

        if ($adapter) {
            $this->adapters[$cacheKey] = $adapter;
        }

        return $adapter;
    }

    /**
     * Return list of all registered gateway adapters.
     * @return PaymentGatewayInterface[]
     */
    public function getAllGateways(string $scope = 'donation'): array
    {
        return [
            'razorpay' => $this->getGateway('razorpay', $scope),
            'phonepe'  => $this->getGateway('phonepe', $scope),
            'payu'     => $this->getGateway('payu', $scope),
        ];
    }

    /**
     * Return list of only enabled and configured gateway adapters.
     * @return PaymentGatewayInterface[]
     */
    public function getAvailableGateways(string $scope = 'donation'): array
    {
        $available = [];
        foreach ($this->getAllGateways($scope) as $key => $gateway) {
            if ($gateway && $gateway->isEnabled() && $gateway->isConfigured()) {
                $available[$key] = $gateway;
            }
        }
        return $available;
    }

    /**
     * Create Razorpay adapter with configuration.
     */
    protected function createRazorpayAdapter(string $scope = 'donation'): RazorpayGatewayAdapter
    {
        $keyId = trim((string)($this->settings['razorpay_' . $scope . '_key_id'] ?? ''));
        $keySecret = trim((string)($this->settings['razorpay_' . $scope . '_key_secret'] ?? ''));

        if ($keyId === '' || $keySecret === '') {
            $keyId = trim((string)($this->settings['razorpay_key_id'] ?? (defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : '')));
            $keySecret = trim((string)($this->settings['razorpay_key_secret'] ?? (defined('RAZORPAY_KEY_SECRET') ? RAZORPAY_KEY_SECRET : '')));
        }

        $webhookSecret = trim((string)($this->settings['razorpay_webhook_secret'] ?? (defined('RAZORPAY_WEBHOOK_SECRET') ? RAZORPAY_WEBHOOK_SECRET : $keySecret)));
        $companyName = trim((string)($this->settings['site_name'] ?? (defined('RAZORPAY_COMPANY_NAME') ? RAZORPAY_COMPANY_NAME : 'NGO Foundation')));

        $enabledSetting = $this->settings['razorpay_enabled'] ?? null;
        // Default to enabled if not explicitly disabled or if active gateway is razorpay
        $enabled = ($enabledSetting === null || $enabledSetting === '1' || $enabledSetting === 'on' || $this->getActiveGatewayId() === 'razorpay');

        return new RazorpayGatewayAdapter([
            'key_id'         => $keyId,
            'key_secret'     => $keySecret,
            'webhook_secret' => $webhookSecret,
            'company_name'   => $companyName,
            'enabled'        => $enabled,
        ]);
    }

    /**
     * Create PhonePe adapter with configuration.
     */
    protected function createPhonePeAdapter(string $scope = 'donation'): PhonePeGatewayAdapter
    {
        $merchantId = trim((string)($this->settings['phonepe_merchant_id'] ?? (defined('PHONEPE_MERCHANT_ID') ? PHONEPE_MERCHANT_ID : '')));
        $saltKey    = trim((string)($this->settings['phonepe_salt_key'] ?? (defined('PHONEPE_SALT_KEY') ? PHONEPE_SALT_KEY : '')));
        $saltIndex  = trim((string)($this->settings['phonepe_salt_index'] ?? (defined('PHONEPE_SALT_INDEX') ? PHONEPE_SALT_INDEX : '1')));
        $env        = trim((string)($this->settings['phonepe_env'] ?? (defined('PHONEPE_ENV') ? PHONEPE_ENV : 'sandbox')));

        $enabledSetting = $this->settings['phonepe_enabled'] ?? null;
        $enabled = ($enabledSetting === '1' || $enabledSetting === 'on' || $this->getActiveGatewayId() === 'phonepe');

        return new PhonePeGatewayAdapter([
            'merchant_id' => $merchantId,
            'salt_key'    => $saltKey,
            'salt_index'  => $saltIndex,
            'env'         => $env,
            'enabled'     => $enabled,
        ]);
    }

    /**
     * Create PayU adapter with configuration.
     */
    protected function createPayUAdapter(string $scope = 'donation'): PayUGatewayAdapter
    {
        $merchantKey  = trim((string)($this->settings['payu_merchant_key'] ?? (defined('PAYU_MERCHANT_KEY') ? PAYU_MERCHANT_KEY : '')));
        $merchantSalt = trim((string)($this->settings['payu_merchant_salt'] ?? (defined('PAYU_MERCHANT_SALT') ? PAYU_MERCHANT_SALT : '')));
        $env          = trim((string)($this->settings['payu_env'] ?? (defined('PAYU_ENV') ? PAYU_ENV : 'sandbox')));

        $enabledSetting = $this->settings['payu_enabled'] ?? null;
        $enabled = ($enabledSetting === '1' || $enabledSetting === 'on' || $this->getActiveGatewayId() === 'payu');

        return new PayUGatewayAdapter([
            'merchant_key'  => $merchantKey,
            'merchant_salt' => $merchantSalt,
            'env'           => $env,
            'enabled'       => $enabled,
        ]);
    }
}
