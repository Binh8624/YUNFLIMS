<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'mock'),

    'refund_cutoff_hours' => (float) env('PAYMENT_REFUND_CUTOFF_HOURS', 0),

    'gateways' => [
        'mock' => [
            'label' => 'Cổng mô phỏng (demo)',
            'enabled' => (bool) env('PAYMENT_MOCK_ENABLED', true),
            'secret' => env('PAYMENT_MOCK_SECRET'),
            'methods' => [
                'mock' => 'Mô phỏng',
            ],
        ],

        'vnpay' => [
            'label' => 'VNPay',
            'enabled' => (bool) env('VNPAY_ENABLED', false),
            'tmn_code' => env('VNPAY_TMN_CODE'),
            'hash_secret' => env('VNPAY_HASH_SECRET'),
            'pay_url' => env('VNPAY_PAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
            'api_url' => env('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),
            'methods' => [
                'vnpay_qr' => 'QR VNPay',
                'vnpay_card' => 'Thẻ / ATM / Ví',
            ],
        ],
    ],
];