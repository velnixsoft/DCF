<?php
/**
 * Razorpay configuration.
 * For production: set keys and webhook secret in dashboard / env — never commit live secrets.
 */
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_SJeMoTDfMwxNSS');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: 'j4Yn72hazBvdmtBvWAPmF25c');
define('RAZORPAY_COMPANY_NAME', getenv('RAZORPAY_COMPANY_NAME') ?: 'Your NGO Name');
define('RAZORPAY_CURRENCY', 'INR');
/** Dashboard → Webhooks → secret (use a distinct value in production) */
define('RAZORPAY_WEBHOOK_SECRET', getenv('RAZORPAY_WEBHOOK_SECRET') ?: RAZORPAY_KEY_SECRET);
